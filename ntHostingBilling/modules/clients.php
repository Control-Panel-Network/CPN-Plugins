<?php
if (!defined('NTHB_INIT')) {
    exit;
}

function nthb_clients_list(PDO $pdo)
{
    return $pdo->query('SELECT * FROM clients ORDER BY id DESC')->fetchAll();
}

function nthb_client_get(PDO $pdo, $id)
{
    $stmt = $pdo->prepare('SELECT * FROM clients WHERE id = ?');
    $stmt->execute([(int) $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function nthb_client_create(PDO $pdo, array $data)
{
    $email = strtolower(trim((string) ($data['email'] ?? '')));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return [false, 'Valid email is required'];
    }
    $name = trim((string) ($data['display_name'] ?? ''));
    $cpn = trim((string) ($data['cpn_username'] ?? ''));
    $password = (string) ($data['password'] ?? '');
    $hash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : '';
    $now = nthb_now();
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO clients (email, display_name, cpn_username, password_hash, status, invited_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $email,
            $name !== '' ? $name : $email,
            $cpn,
            $hash,
            'active',
            $now,
            $now,
            $now,
        ]);
        $id = (int) $pdo->lastInsertId();
        nthb_audit($pdo, 'admin', 'client.create', 'client', $id, $email);
        return [true, $id];
    } catch (PDOException $e) {
        return [false, 'Could not create client (email may already exist)'];
    }
}

function nthb_client_update_status(PDO $pdo, $id, $status)
{
    $status = nthb_status_allowed($status, ['active', 'suspended', 'cancelled']);
    if ($status === null) {
        return [false, 'Invalid status'];
    }
    $stmt = $pdo->prepare('UPDATE clients SET status = ?, updated_at = ? WHERE id = ?');
    $stmt->execute([$status, nthb_now(), (int) $id]);
    nthb_audit($pdo, 'admin', 'client.status', 'client', (int) $id, $status);
    return [true, 'Updated'];
}

function nthb_client_set_password(PDO $pdo, $id, $password)
{
    $password = (string) $password;
    if (strlen($password) < 8) {
        return [false, 'Password must be at least 8 characters'];
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE clients SET password_hash = ?, updated_at = ? WHERE id = ?');
    $stmt->execute([$hash, nthb_now(), (int) $id]);
    nthb_audit($pdo, 'admin', 'client.password', 'client', (int) $id, '');
    return [true, 'Password updated'];
}
