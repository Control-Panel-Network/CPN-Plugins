<?php
if (!defined('NTHB_INIT')) {
    exit;
}

function nthb_ownership_list(PDO $pdo)
{
    $sql = 'SELECT o.*, c.email AS client_email, c.cpn_username
            FROM site_ownership o
            JOIN clients c ON c.id = o.client_id
            ORDER BY o.id DESC';
    return $pdo->query($sql)->fetchAll();
}

function nthb_ownership_for_client(PDO $pdo, $clientId)
{
    $stmt = $pdo->prepare('SELECT * FROM site_ownership WHERE client_id = ? ORDER BY domain ASC');
    $stmt->execute([(int) $clientId]);
    return $stmt->fetchAll();
}

function nthb_ownership_assign(PDO $pdo, $clientId, $domain, $role = 'owner')
{
    $domain = strtolower(trim((string) $domain));
    if ($domain === '' || !preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $domain)) {
        return [false, 'Valid domain is required'];
    }
    if (!nthb_client_get($pdo, $clientId)) {
        return [false, 'Client not found'];
    }
    $role = nthb_status_allowed($role, ['owner', 'admin', 'billing', 'viewer']) ?: 'owner';
    $now = nthb_now();
    $canManage = in_array($role, ['owner', 'admin'], true) ? 1 : 0;
    $canBilling = in_array($role, ['owner', 'admin', 'billing'], true) ? 1 : 0;
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO site_ownership (client_id, domain, role, can_manage, can_billing, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON CONFLICT(client_id, domain) DO UPDATE SET
               role = excluded.role,
               can_manage = excluded.can_manage,
               can_billing = excluded.can_billing,
               updated_at = excluded.updated_at'
        );
        $stmt->execute([(int) $clientId, $domain, $role, $canManage, $canBilling, $now, $now]);
        nthb_audit($pdo, 'admin', 'ownership.assign', 'site_ownership', (int) $clientId, $domain . ':' . $role);
        return [true, 'Assigned'];
    } catch (PDOException $e) {
        return [false, 'Could not assign ownership'];
    }
}

function nthb_ownership_remove(PDO $pdo, $id)
{
    $stmt = $pdo->prepare('DELETE FROM site_ownership WHERE id = ?');
    $stmt->execute([(int) $id]);
    nthb_audit($pdo, 'admin', 'ownership.remove', 'site_ownership', (int) $id, '');
    return [true, 'Removed'];
}
