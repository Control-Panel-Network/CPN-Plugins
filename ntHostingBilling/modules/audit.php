<?php
if (!defined('NTHB_INIT')) {
    exit;
}

function nthb_audit(PDO $pdo, $actor, $action, $entityType = '', $entityId = null, $detail = '')
{
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO audit_log (actor, action, entity_type, entity_id, detail, created_at) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            (string) $actor,
            (string) $action,
            (string) $entityType,
            $entityId !== null ? (int) $entityId : null,
            (string) $detail,
            nthb_now(),
        ]);
    } catch (Throwable $e) {
        // Never break primary flows on audit failure.
    }
}
