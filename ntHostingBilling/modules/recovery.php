<?php
if (!defined('NTHB_INIT')) {
    exit;
}

/**
 * Start client password recovery (email token). Does not replace CPN admin auth.
 *
 * @param array<string,mixed> $cfg
 */
function nthb_recovery_request(PDO $pdo, $email, array $cfg, $publicBase)
{
    $email = strtolower(trim((string) $email));
    $stmt = $pdo->prepare('SELECT * FROM clients WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $client = $stmt->fetch();
    // Always return success-shaped message to avoid account enumeration.
    $generic = [true, 'If the account exists, a recovery link was sent'];
    if (!$client) {
        return $generic;
    }

    $token = bin2hex(random_bytes(24));
    $hash = hash('sha256', $token);
    $expires = gmdate('Y-m-d H:i:s', time() + 3600);
    $ins = $pdo->prepare(
        'INSERT INTO recovery_tokens (client_id, token_hash, expires_at, used_at, created_at) VALUES (?, ?, ?, NULL, ?)'
    );
    $ins->execute([(int) $client['id'], $hash, $expires, nthb_now()]);

    $link = rtrim((string) $publicBase, '/') . '/?view=recovery-reset&token=' . urlencode($token);
    $from = (string) ($cfg['mail_from'] ?? 'noreply@localhost');
    $fromName = (string) ($cfg['mail_from_name'] ?? 'News Targeted Hosting');
    $subject = 'Password recovery';
    $body = "Hello,\n\nUse this link within one hour to reset your hosting account password:\n{$link}\n\nIf you did not request this, ignore this email.\n";
    $headers = 'From: ' . $fromName . ' <' . $from . ">\r\n" . 'Content-Type: text/plain; charset=UTF-8';
    @mail($email, $subject, $body, $headers);
    nthb_audit($pdo, 'client', 'recovery.request', 'client', (int) $client['id'], '');
    // Store last link for lab/operator debug (not emailed secrets)
    @file_put_contents(
        nthb_data_dir() . '/last_recovery_link.txt',
        $link . "\n",
        LOCK_EX
    );
    @chmod(nthb_data_dir() . '/last_recovery_link.txt', 0600);
    return $generic;
}

function nthb_recovery_reset(PDO $pdo, $token, $password)
{
    $token = trim((string) $token);
    if ($token === '' || strlen((string) $password) < 8) {
        return [false, 'Valid token and password (8+ chars) required'];
    }
    $hash = hash('sha256', $token);
    $stmt = $pdo->prepare(
        'SELECT * FROM recovery_tokens WHERE token_hash = ? AND used_at IS NULL LIMIT 1'
    );
    $stmt->execute([$hash]);
    $row = $stmt->fetch();
    if (!$row) {
        return [false, 'Invalid or used token'];
    }
    if (strtotime((string) $row['expires_at'] . ' UTC') < time()) {
        return [false, 'Token expired'];
    }
    $set = nthb_client_set_password($pdo, (int) $row['client_id'], $password);
    if (!$set[0]) {
        return $set;
    }
    $upd = $pdo->prepare('UPDATE recovery_tokens SET used_at = ? WHERE id = ?');
    $upd->execute([nthb_now(), (int) $row['id']]);
    nthb_audit($pdo, 'client', 'recovery.reset', 'client', (int) $row['client_id'], '');
    return [true, 'Password updated'];
}
