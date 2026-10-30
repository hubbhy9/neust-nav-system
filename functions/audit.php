<?php
declare(strict_types=1);

function auditActorRole(): string
{
    if (!empty($_SESSION['is_guest'])) return 'guest';
    if (empty($_SESSION['id'])) return 'anonymous';
    return (int) ($_SESSION['role_id'] ?? 0) === 1 ? 'admin' : 'user';
}

function auditActorName(): string
{
    if (!empty($_SESSION['is_guest'])) return 'Guest';
    $name = trim((string) ($_SESSION['first_name'] ?? '') . ' ' . (string) ($_SESSION['last_name'] ?? ''));
    if ($name !== '') return mb_substr($name, 0, 190, 'UTF-8');
    return mb_substr((string) ($_SESSION['email'] ?? 'Unknown'), 0, 190, 'UTF-8');
}

function isCurrentUserAdmin(PDO $db): bool
{
    if (session_status() === PHP_SESSION_NONE) {
        return false;
    }

    if (empty($_SESSION['id']) || !empty($_SESSION['is_guest'])) {
        return false;
    }

    return (int) ($_SESSION['role_id'] ?? 0) === 1;
}

function canViewAuditLogs(?PDO $db = null): bool
{
    if (empty($_SESSION['id']) || !empty($_SESSION['is_guest'])) {
        return false;
    }
    return (int) ($_SESSION['role_id'] ?? 0) === 1;
}

function auditScrub(mixed $value, ?string $key = null): mixed
{
    if ($key !== null && preg_match('/password|pass_word|otp|token|secret|cookie|authorization|csrf|credential|api[_-]?key/i', $key)) {
        return '[REDACTED]';
    }
    if (is_array($value)) {
        $clean = [];
        foreach ($value as $childKey => $childValue) {
            $clean[$childKey] = auditScrub($childValue, is_string($childKey) ? $childKey : null);
        }
        return $clean;
    }
    if (is_string($value)) return mb_substr(str_replace("\0", '', $value), 0, 12000, 'UTF-8');
    if (is_int($value) || is_float($value) || is_bool($value) || $value === null) return $value;
    return mb_substr((string) $value, 0, 12000, 'UTF-8');
}

function auditCleanText(string $value, int $limit): string
{
    $clean = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value) ?? '';
    return mb_substr(trim($clean), 0, $limit, 'UTF-8');
}

function auditDiff(array $oldValues, array $newValues): array
{
    $oldChanged = [];
    $newChanged = [];
    foreach (array_unique(array_merge(array_keys($oldValues), array_keys($newValues))) as $field) {
        $old = $oldValues[$field] ?? null;
        $new = $newValues[$field] ?? null;
        if ($old !== $new) {
            $oldChanged[$field] = $old;
            $newChanged[$field] = $new;
        }
    }
    return [
        'old_values' => auditScrub($oldChanged),
        'new_values' => auditScrub($newChanged),
        'changed_fields' => array_keys($oldChanged),
    ];
}

function auditLog(PDO $db, array $event): void
{
    $oldValues = isset($event['old_values']) && is_array($event['old_values']) ? $event['old_values'] : null;
    $newValues = isset($event['new_values']) && is_array($event['new_values']) ? $event['new_values'] : null;
    if ($oldValues !== null && $newValues !== null) {
        $diff = auditDiff($oldValues, $newValues);
        $oldValues = $diff['old_values'];
        $newValues = $diff['new_values'];
        $changedFields = $diff['changed_fields'];
    } else {
        $oldValues = $oldValues !== null ? auditScrub($oldValues) : null;
        $newValues = $newValues !== null ? auditScrub($newValues) : null;
        $changedFields = isset($event['changed_fields']) && is_array($event['changed_fields']) ? array_values($event['changed_fields']) : null;
    }

    $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    if (filter_var($ip, FILTER_VALIDATE_IP) === false) $ip = '';
    $agent = mb_substr(str_replace(["\r", "\n", "\0"], ' ', (string) ($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 512, 'UTF-8');
    $clientHints = [];
    foreach ([
        'brands' => 'HTTP_SEC_CH_UA',
        'mobile' => 'HTTP_SEC_CH_UA_MOBILE',
        'platform' => 'HTTP_SEC_CH_UA_PLATFORM',
        'platform_version' => 'HTTP_SEC_CH_UA_PLATFORM_VERSION',
        'model' => 'HTTP_SEC_CH_UA_MODEL',
        'bitness' => 'HTTP_SEC_CH_UA_BITNESS',
    ] as $hintName => $serverKey) {
        $hintValue = trim((string) ($_SERVER[$serverKey] ?? ''));
        if ($hintValue !== '') $clientHints[$hintName] = auditCleanText($hintValue, 240);
    }
    $encodedClientHints = $clientHints !== []
        ? json_encode($clientHints, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)
        : null;
    $path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
    $path = mb_substr(str_replace(["\r", "\n", "\0"], '', $path), 0, 512, 'UTF-8');

    $statement = $db->prepare('INSERT INTO audit_logs (user_id, actor_name, actor_role, action, module, target_type, target_id, target_name, description, old_values, new_values, changed_fields, ip_address, user_agent, client_hints, request_method, request_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $statement->execute([
        !empty($_SESSION['id']) && empty($_SESSION['is_guest']) ? (int) $_SESSION['id'] : null,
        auditCleanText((string) ($event['actor_name'] ?? auditActorName()), 190),
        auditCleanText((string) ($event['actor_role'] ?? auditActorRole()), 40),
        auditCleanText(strtoupper((string) ($event['action'] ?? 'UNKNOWN')), 32),
        auditCleanText((string) ($event['module'] ?? 'System'), 80),
        isset($event['target_type']) ? auditCleanText((string) $event['target_type'], 80) : null,
        isset($event['target_id']) ? auditCleanText((string) $event['target_id'], 100) : null,
        isset($event['target_name']) ? auditCleanText((string) $event['target_name'], 255) : null,
        auditCleanText((string) ($event['description'] ?? ''), 1000),
        $oldValues !== null ? json_encode($oldValues, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) : null,
        $newValues !== null ? json_encode($newValues, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) : null,
        $changedFields !== null ? json_encode(array_values($changedFields), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) : null,
        $ip !== '' ? $ip : null,
        $agent !== '' ? $agent : null,
        $encodedClientHints,
        mb_substr(strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'CLI')), 0, 10, 'UTF-8'),
        $path,
        in_array(($event['status'] ?? 'success'), ['success', 'failure', 'denied'], true) ? ($event['status'] ?? 'success') : 'success',
    ]);
}

function auditLogSafely(PDO $db, array $event): void
{
    try {
        auditLog($db, $event);
    } catch (Throwable $error) {
        error_log('Audit log write failed: ' . $error->getMessage());
    }
}

function auditRequireAdmin(PDO $db): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (isCurrentUserAdmin($db)) {
        return;
    }

    auditLogSafely($db, [
        'action' => 'ACCESS_DENIED',
        'module' => 'Authorization',
        'description' => 'Denied access to an administrator-only operation.',
        'status' => 'denied',
    ]);
    http_response_code(403);
    exit('Administrator access is required.');
}
