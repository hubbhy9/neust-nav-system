git<?php
ob_start();
$sessionSavePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'navigation_sessions';
if (!is_dir($sessionSavePath)) {
    @mkdir($sessionSavePath, 0777, true);
}
if (!is_dir($sessionSavePath) || !is_writable($sessionSavePath)) {
    $sessionSavePath = sys_get_temp_dir();
}

if (session_status() === PHP_SESSION_NONE) {
    session_save_path($sessionSavePath);
    session_start();
}

$isGuest = !empty($_SESSION['is_guest']);

$mailEnvironmentFile = __DIR__ . '/.env';
if (is_readable($mailEnvironmentFile)) {
    $mailEnvironment = parse_ini_file($mailEnvironmentFile, false, INI_SCANNER_RAW);
    if (is_array($mailEnvironment)) {
        foreach ($mailEnvironment as $environmentKey => $environmentValue) {
            if (getenv($environmentKey) === false && is_string($environmentValue)) {
                putenv($environmentKey . '=' . $environmentValue);
            }
        }
    }
}

include __DIR__ . '/libraries/include.php';

if (!isset($db) || !$db instanceof PDO) {
    require_once __DIR__ . '/libraries/db.php';
}

require_once __DIR__ . '/functions/audit.php';
require_once __DIR__ . '/docs/viewdocxf.php';
require_once __DIR__ . '/functions/mailer.php';
require_once __DIR__ . '/functions/audit.php';

if (!function_exists('renderDatabaseLinkText')) {
    function renderDatabaseLinkText($value)
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }

        $parts = preg_split('/\s*;\s*/', $raw, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($parts)) {
            return '';
        }

        $html = [];
        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part === '') {
                continue;
            }

            if (preg_match('/^(https?:\/\/|www\.)/i', $part)) {
                $href = preg_match('/^https?:\/\//i', $part) ? $part : 'https://' . $part;
                $html[] = '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($part, ENT_QUOTES, 'UTF-8') . '</a>';
                continue;
            }

            $html[] = htmlspecialchars($part, ENT_QUOTES, 'UTF-8');
        }

        return !empty($html) ? implode('<br>', $html) : '';
    }
}

if (!function_exists('parseFeeAmount')) {
    function parseFeeAmount($value)
    {
        $text = trim((string) $value);
        if ($text === '' || strtolower($text) === 'none') {
            return null;
        }

        if (stripos($text, 'total') !== false) {
            return null;
        }

        preg_match_all('/\d[\d,]*(?:\.\d+)?/', $text, $matches);
        if (empty($matches[0])) {
            return null;
        }

        $amount = end($matches[0]);
        return (float) str_replace(',', '', $amount);
    }
}

if (!function_exists('formatFeeValue')) {
    function formatFeeValue($value)
    {
        $cleanValue = trim((string) $value);

        if ($cleanValue === '' || strtolower($cleanValue) === 'none') {
            return 'None';
        }

        $escaped = htmlspecialchars($cleanValue, ENT_QUOTES, 'UTF-8');

        if (preg_match('/^(.*?)([:：])\s*(P?\s*\d[\d,]*(?:\.\d+)?)(.*)$/i', $escaped, $matches)) {
            $label = trim($matches[1]);
            $amount = trim(str_replace('P', '', $matches[3]));
            $suffix = trim($matches[4]);

            return '<span class="fee-label">' . $label . '</span><span class="fee-separator">:</span> <span class="fee-value"><strong>' . $amount . '</strong></span>' . ($suffix ? ' <span class="fee-suffix">' . $suffix . '</span>' : '');
        }

        if (preg_match('/^\s*\d[\d,]*(?:\.\d+)?\s*$/', $escaped)) {
            return '<strong>₱' . $escaped . '</strong>';
        }

        if (preg_match('/\d+(?:\.\d+)?/', $escaped)) {
            return preg_replace('/(\d+(?:\.\d+)?)/', '<strong>$1</strong>', $escaped);
        }

        return $escaped;
    }
}

if (!function_exists('feeNeedsSmallText')) {
    function feeNeedsSmallText($value)
    {
        $text = trim((string) $value);
        if ($text === '' || strtolower($text) === 'none') {
            return false;
        }

        $withoutCurrencyMarker = preg_replace('/[Pp]/', '', $text);
        return preg_match('/[A-Za-z]/', $withoutCurrencyMarker) === 1;
    }
}

if (!function_exists('formatFeeCell')) {
    function formatFeeCell($value)
    {
        $cleanValue = trim((string) $value);
        if ($cleanValue === '' || strtolower($cleanValue) === 'none') {
            return 'None';
        }

        $lines = preg_split('/\s*(?:\r\n|\n|;\s*|\|\s*)/', $cleanValue);
        $formatted = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $feeClass = feeNeedsSmallText($line) ? 'fee-item fee-item-small' : 'fee-item';
            $formatted[] = '<div class="' . $feeClass . '">' . formatFeeValue($line) . '</div>';
        }

        return implode('', $formatted);
    }
}

if (!function_exists('renderBulletedValues')) {
    function renderBulletedValues($value)
    {
        $raw = trim((string) $value);
        if ($raw === '' || strtolower($raw) === 'none') {
            return '<ul class="info-bullets"><li>None</li></ul>';
        }

        $parts = preg_split('/\s*(?:\r\n|\n|;\s*|\|\s*)/', $raw, -1, PREG_SPLIT_NO_EMPTY);
        if (empty($parts)) {
            return '<ul class="info-bullets"><li>None</li></ul>';
        }

        $items = [];
        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part === '') {
                continue;
            }

            $items[] = '<li>' . htmlspecialchars($part, ENT_QUOTES, 'UTF-8') . '</li>';
        }

        return !empty($items) ? '<ul class="info-bullets">' . implode('', $items) . '</ul>' : '<ul class="info-bullets"><li>None</li></ul>';
    }
}

if (!function_exists('renderRichText')) {
    function renderRichText($value)
    {
        $text = trim((string) $value);
        if ($text === '' || strtolower($text) === 'none') {
            return '';
        }

        $escaped = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $escaped = str_replace(["\r\n", "\r", "\n"], '<br>', $escaped);
        $escaped = preg_replace('/_+/', '<br>', $escaped);

        $escaped = preg_replace_callback(
            '/(https?:\/\/[^\s<]+|www\.[^\s<]+)/i',
            function ($matches) {
                $href = $matches[1];
                if (!preg_match('/^https?:\/\//i', $href)) {
                    $href = 'https://' . $href;
                }

                return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($matches[1], ENT_QUOTES, 'UTF-8') . '</a>';
            },
            $escaped
        );

        return $escaped;
    }
}

if (!function_exists('renderDocumentDetailModalHtml')) {
    function renderDocumentDetailModalHtml(PDO $db, int $documentId): string
    {
        $documentStmt = $db->prepare(
            "SELECT d.id, d.title, d.who_may_avail,
                    o.name AS office_name,
                    c.name AS classification,
                    GROUP_CONCAT(DISTINCT tt.name ORDER BY tt.name SEPARATOR '; ') AS transaction_type
             FROM documents d
             LEFT JOIN offices o ON o.id = d.office_id
             LEFT JOIN classifications c ON c.id = d.classification_id
             LEFT JOIN document_transaction_types dtt ON dtt.document_id = d.id
             LEFT JOIN transaction_types tt ON tt.id = dtt.transaction_type_id
             WHERE d.id = :id
             GROUP BY d.id, d.title, d.who_may_avail, o.name, c.name
             LIMIT 1"
        );
        $documentStmt->execute([':id' => $documentId]);
        $document = $documentStmt->fetch(PDO::FETCH_ASSOC);

        if (!$document) {
            return '<div class="alert alert-warning mb-0">Document not found.</div>';
        }

        $requirementsStmt = $db->prepare("SELECT requirement_name, where_to_secure FROM requirements WHERE document_id = :document_id ORDER BY id");
        $requirementsStmt->execute([':document_id' => $document['id']]);
        $requirements = $requirementsStmt->fetchAll(PDO::FETCH_ASSOC);

        $stepsStmt = $db->prepare("SELECT step_number, client_step, agency_action, fees, processing_time, person_responsible FROM document_steps WHERE document_id = :document_id ORDER BY step_number, id");
        $stepsStmt->execute([':document_id' => $document['id']]);
        $steps = $stepsStmt->fetchAll(PDO::FETCH_ASSOC);

        $totalStmt = $db->prepare("SELECT fees, time FROM `total` WHERE document_id = :document_id ORDER BY id ASC");
        $totalStmt->execute([':document_id' => $document['id']]);
        $documentTotalRows = $totalStmt->fetchAll(PDO::FETCH_ASSOC);

        $totalFees = 0;
        $totalProcessingMinutes = 0;
        foreach ($steps as $step) {
            $feesValue = $step['fees'] ?? '';
            if (is_string($feesValue)) {
                foreach (preg_split('/\s*(?:\r\n|\n|;\s*|\|\s*)/', $feesValue) as $feeLine) {
                    $amount = parseFeeAmount($feeLine);
                    if ($amount !== null) {
                        $totalFees += $amount;
                    }
                }
            }

            $timeValue = trim((string) ($step['processing_time'] ?? ''));
            if ($timeValue !== '' && $timeValue !== 'None') {
                $lower = strtolower($timeValue);
                $minutes = 0;

                if (preg_match('/(\d+)\s*(day|days|d)/', $lower, $dayMatch)) {
                    $minutes += (int) $dayMatch[1] * 24 * 60;
                }
                if (preg_match('/(\d+)\s*(hour|hours|hr|hrs|h)/', $lower, $hourMatch)) {
                    $minutes += (int) $hourMatch[1] * 60;
                }
                if (preg_match('/(\d+)\s*(minute|min|mins|minutes|m)/', $lower, $minuteMatch)) {
                    $minutes += (int) $minuteMatch[1];
                }
                if ($minutes === 0 && preg_match('/\d+/', $lower)) {
                    $minutes = (int) preg_replace('/[^\d]/', '', $lower);
                }

                $totalProcessingMinutes += $minutes;
            }
        }

        $output = '<style>
            .doc-modal-layout { font-family: "Segoe UI", Arial, sans-serif; color: #0f172a; }
            .doc-modal-layout .page-shell { max-width: 1280px; margin: 0 auto; padding: 0 12px 20px; }
            .doc-modal-layout .page-header { position: sticky; top: 0; z-index: 2; background: linear-gradient(135deg, #f3f6fd, #e1ebff); color: #0d2347; border-radius: 22px; padding: 26px 28px; box-shadow: 0 18px 40px rgba(14, 31, 66, 0.14); border: 1px solid rgba(20, 54, 156, 0.08); }
            .doc-modal-layout .eyebrow { display: inline-block; font-size: 11px; letter-spacing: 0.12em; text-transform: uppercase; opacity: 0.8; margin-bottom: 8px; }
            .doc-modal-layout .page-header h2 { margin: 0; font-weight: 800; font-size: clamp(1.4rem, 2vw, 2.2rem); }
            .doc-modal-layout .detail-card { width: 100%; margin-top: 18px; background: rgba(255,255,255,0.9); border: 1px solid rgba(15,23,42,0.06); border-radius: 18px; box-shadow: 0 12px 22px rgba(9,17,44,0.06); padding: 18px 18px 16px; }
            .doc-modal-layout .detail-header { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 18px; flex-wrap: wrap; }
            .doc-modal-layout .detail-header h3 { margin: 0; color: #091a3a; font-weight: 800; }
            .doc-modal-layout .status-badge { display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 999px; font-size: 0.76rem; font-weight: 700; background: #eafaf0; color: #1d9b4d; }
            .doc-modal-layout .info-grid { display: grid; grid-template-columns: repeat(4, minmax(180px, 1fr)); gap: 16px; margin-top: 12px; }
            .doc-modal-layout .info-box { background: #f7f9ff; border: 1px solid rgba(20,54,156,0.18); border-radius: 14px; padding: 14px 12px; }
            .doc-modal-layout .info-label { display: block; font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; margin-bottom: 8px; }
            .doc-modal-layout .info-value { margin: 0; color: #0f172a; font-weight: 600; line-height: 1.5; }
            .doc-modal-layout .info-bullets { margin: 0; padding-left: 1.25rem; color: #0f172a; font-weight: 600; line-height: 1.6; }
            .doc-modal-layout .requirements-box { margin-top: 22px; background: #fffaf0; border: 1px solid rgba(255,206,0,0.28); border-radius: 16px; padding: 18px 18px 10px; }
            .doc-modal-layout .section-title { margin: 0 0 12px; font-size: 0.78rem; letter-spacing: 0.1em; text-transform: uppercase; color: #64748b; font-weight: 800; }
            .doc-modal-layout .requirement-list { list-style: none; padding: 0; margin: 0; display: grid; gap: 10px; }
            .doc-modal-layout .requirement-where-grid { display: grid; gap: 10px; }
            .doc-modal-layout .requirement-where-header, .doc-modal-layout .requirement-where-row { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 12px; align-items: start; }
            .doc-modal-layout .requirement-where-header { font-size: 0.74rem; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b; font-weight: 800; padding: 0 6px; }
            .doc-modal-layout .requirement-where-row > div { background: rgba(255,255,255,0.8); border: 1px solid rgba(15,23,42,0.12); border-radius: 10px; padding: 8px 10px; line-height: 1.5; }
            .doc-modal-layout .requirement-cell { display: flex; align-items: flex-start; gap: 10px; }
            .doc-modal-layout .requirement-cell i { color: #1d9b4d; margin-top: 3px; }
            .doc-modal-layout .where-cell { color: #0f172a; font-weight: 600; }
            .doc-modal-layout .table-wrap { margin-top: 18px; background: white; border: 1px solid rgba(15,23,42,0.06); border-radius: 14px; overflow: hidden; }
            .doc-modal-layout .table thead th { background: linear-gradient(135deg, #14369C, #07184F) !important; color: white !important; text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.08em; }
            .doc-modal-layout .table td, .doc-modal-layout .table th { vertical-align: top; padding: 12px 12px; }
            .doc-modal-layout .fee-highlight strong { font-weight: 800; color: #0d2347; }
            .doc-modal-layout .fee-highlight { white-space: normal; line-height: 1.7; }
            .doc-modal-layout .fee-item { display: flex; flex-wrap: wrap; align-items: baseline; gap: 6px; padding: 5px 0; border-bottom: 1px dashed rgba(20,54,156,0.12); font-size: 1rem; }
            .doc-modal-layout .fee-item-small { font-size: 0.82rem; }
            .doc-modal-layout .fee-item:last-child { border-bottom: none; }
            .doc-modal-layout .fee-label { font-weight: 600; color: #1f2937; }
            .doc-modal-layout .fee-separator { color: #64748b; font-weight: 700; }
            .doc-modal-layout .fee-suffix { color: #475569; }
            .doc-modal-layout .totals-summary { width: 100%; display: flex; justify-content: flex-end; align-items: stretch; gap: 12px; flex-wrap: nowrap; margin-top: 18px; }
            .doc-modal-layout .totals-box { flex: 0 0 260px; min-width: 260px; max-width: 260px; background: #eef4ff; border: 1px solid rgba(20,54,156,0.12); border-radius: 12px; padding: 12px 16px; display: flex; flex-direction: column; justify-content: flex-start; text-align: left; height: 100%; min-height: 120px; }
            .doc-modal-layout .totals-box .label { display: block; font-size: 0.72rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #64748b; margin-bottom: 8px; }
            .doc-modal-layout .totals-item { min-height: 28px; display: flex; align-items: center; }
            .doc-modal-layout .value { font-size: 1.05rem; font-weight: 800; color: #0d2347; margin: 0; line-height: 1.4; }
            @media (max-width: 900px) { .doc-modal-layout .info-grid { grid-template-columns: repeat(2, minmax(180px,1fr)); } }
            @media (max-width: 600px) { .doc-modal-layout .info-grid { grid-template-columns: 1fr; } .doc-modal-layout .totals-summary { flex-direction: column; } }
        </style>
        <div class="doc-modal-layout">
            <div class="page-shell">
                <div class="detail-card">
                    <div class="detail-header">
                        <h3>' . htmlspecialchars($document['title'], ENT_QUOTES, 'UTF-8') . '</h3>
                        
                    </div>

                    <div class="info-grid">
                        <div class="info-box"><span class="info-label">Office division</span><div class="info-value">' . renderBulletedValues($document['office_name'] ?? '') . '</div></div>
                        <div class="info-box"><span class="info-label">Classification</span><div class="info-value">' . renderBulletedValues($document['classification'] ?? '') . '</div></div>
                        <div class="info-box"><span class="info-label">Type of transaction</span><div class="info-value">' . renderBulletedValues($document['transaction_type'] ?? '') . '</div></div>
                        <div class="info-box"><span class="info-label">Who may avail</span><div class="info-value">' . renderBulletedValues($document['who_may_avail'] ?? '') . '</div></div>
                    </div>

                    ' . (!empty($requirements) ? '<div class="requirements-box"><div class="section-title">Requirements &amp; Where to Secure</div><div class="requirement-where-grid"><div class="requirement-where-header"><span>Requirements</span><span>Where to secure</span></div>' . implode('', array_map(function ($requirement) {
                        $whereToSecureValue = trim((string) ($requirement['where_to_secure'] ?? ''));
                        return '<div class="requirement-where-row"><div class="requirement-cell"><i class="fa-solid fa-circle-check"></i><span>' . htmlspecialchars($requirement['requirement_name'] ?? '', ENT_QUOTES, 'UTF-8') . '</span></div>' . ($whereToSecureValue !== '' ? '<div class="where-cell">' . renderDatabaseLinkText($whereToSecureValue) . '</div>' : '<div class="where-cell">—</div>') . '</div>';
                    }, $requirements)) . '</div></div>' : '') . '

                    ' . (!empty($steps) ? '<div class="mt-4"><div class="section-title">Process steps</div><div class="table-wrap"><table class="table table-bordered mb-0"><thead><tr><th style="width:20%;">Client Steps</th><th style="width:25%;">Agency Actions</th><th style="width:20%;">Fees</th><th style="width:15%;">Time</th><th style="width:20%;">Responsible</th></tr></thead><tbody>' . implode('', array_map(function ($step) {
                        return '<tr><td>' . renderRichText($step['client_step'] ?? '') . '</td><td>' . renderRichText($step['agency_action'] ?? '') . '</td><td class="fee-highlight">' . formatFeeCell($step['fees'] ?? 'None') . '</td><td>' . htmlspecialchars($step['processing_time'] ?? '', ENT_QUOTES, 'UTF-8') . '</td><td>' . renderRichText($step['person_responsible'] ?? '') . '</td></tr>';
                    }, $steps)) . '</tbody></table></div><div class="totals-summary" style="justify-content:flex-end;">' . (!empty($documentTotalRows) ? '<div class="totals-box"><span class="label"><i class="fa-solid fa-receipt me-1"></i> Total Fees</span><div class="totals-list">' . implode('', array_map(function ($totalRow) { return '<div class="totals-item"><p class="value">>' . htmlspecialchars(trim((string) ($totalRow['fees'] ?? 'None')) ?: 'None', ENT_QUOTES, 'UTF-8') . '</p></div>'; }, $documentTotalRows)) . '</div></div><div class="totals-box"><span class="label"><i class="fa-regular fa-clock me-1"></i> Total Time</span><div class="totals-list">' . implode('', array_map(function ($totalRow) { return '<div class="totals-item"><p class="value">>' . htmlspecialchars(trim((string) ($totalRow['time'] ?? 'None')) ?: 'None', ENT_QUOTES, 'UTF-8') . '</p></div>'; }, $documentTotalRows)) . '</div></div>' : '<div class="totals-box"><span class="label"><i class="fa-solid fa-receipt me-1"></i> Total Fees</span><p class="value mt-2">None</p></div><div class="totals-box"><span class="label"><i class="fa-regular fa-clock me-1"></i> Total Time</span><p class="value mt-2">None</p></div>') . '</div></div>' : '') . '
                </div>
            </div>
        </div>';

        return $output;
    }
}

if (isset($_GET['doc_modal']) && isset($db) && $db instanceof PDO) {
    if ($isGuest) {
        ob_clean();
        http_response_code(403);
        exit('Please log in to view document steps.');
    }

    $documentModalId = isset($_GET['doc_modal']) ? (int) $_GET['doc_modal'] : 0;
    if ($documentModalId > 0) {
        echo renderDocumentDetailModalHtml($db, $documentModalId);
        exit;
    }
}

if (!function_exists('redirectWithMessage')) {
    function redirectWithMessage(string $message, string $type = 'success', ?string $tab = null): void
    {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;

        if ($tab !== null && $tab !== '') {
            setcookie('dashboard_active_tab', $tab, [
                'expires' => time() + (60 * 60 * 24 * 365),
                'path' => '/',
                'samesite' => 'Lax',
                'secure' => false,
            ]);
        }

        $redirectUrl = 'dashboard.php';
        if ($tab !== null && $tab !== '') {
            $redirectUrl .= '?tab=' . rawurlencode($tab);
            if ($tab === 'tickets') {
                $redirectUrl .= '#pills-tickets';
            }
        }

        header('Location: ' . $redirectUrl);
        exit;
    }
}

if (!function_exists('updateUserRole')) {
    function updateUserRole(PDO $db, int $userId, int $newRoleId): bool
    {
        if ($userId <= 0 || !in_array($newRoleId, [0, 1], true)) {
            return false;
        }

        $stmt = $db->prepare('SELECT id, first_name, last_name, email, role_id FROM `user` WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $target = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$target || (int) $target['role_id'] === $newRoleId) return false;

        $db->beginTransaction();
        try {
            $update = $db->prepare('UPDATE `user` SET role_id = ?, updated_at = NOW() WHERE id = ?');
            $updated = $update->execute([$newRoleId, $userId]);
            if (!$updated) {
                $db->rollBack();
                return false;
            }
            auditLog($db, [
                'action' => 'UPDATE',
                'module' => 'Users',
                'target_type' => 'user',
                'target_id' => $userId,
                'target_name' => trim((string) $target['first_name'] . ' ' . (string) $target['last_name']),
                'description' => 'Changed user role.',
                'old_values' => ['role_id' => (int) $target['role_id']],
                'new_values' => ['role_id' => $newRoleId],
                'status' => 'success',
            ]);
            $db->commit();
        } catch (Throwable $error) {
            if ($db->inTransaction()) $db->rollBack();
            throw $error;
        }

        if ((int) ($_SESSION['id'] ?? 0) === $userId) {
            $_SESSION['role_id'] = $newRoleId;
        }

        return true;
    }
}

if (!function_exists('generateRandomCsvPassword')) {
    function generateRandomCsvPassword(): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $password = '';

        for ($i = 0; $i < 16; $i++) {
            $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        return $password;
    }
}

if (!function_exists('resolveSexValue')) {
    function resolveSexValue($sexValue, $customSexValue): ?string
    {
        $selected = trim((string) $sexValue);
        $custom = trim((string) $customSexValue);

        if ($selected === '' || strtolower($selected) === 'prefer not to say') {
            return null;
        }

        if (strtolower($selected) === 'others') {
            if ($custom === '') {
                return null;
            }
            return trim($custom);
        }

        if (in_array(strtolower($selected), ['male', 'female'], true)) {
            return ucfirst(strtolower($selected));
        }

        return null;
    }
}

if (!function_exists('normalizeNameValue')) {
    function normalizeNameValue(string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\d+/u', '', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        $segments = preg_split('/\s+/', $value, -1, PREG_SPLIT_NO_EMPTY);
        if ($segments === false || count($segments) === 0) {
            return '';
        }

        $normalized = array_map(function ($segment) {
            return mb_convert_case($segment, MB_CASE_TITLE, 'UTF-8');
        }, $segments);

        return implode(' ', $normalized);
    }
}

if (!function_exists('excelCsvTextCell')) {
    function excelCsvTextCell(string $value): string
    {
        $safeValue = str_replace('"', '""', trim((string) $value));
        return '="' . $safeValue . '"';
    }
}

if (!function_exists('normalizeEmailValue')) {
    function normalizeEmailValue(string $value): string
    {
        return strtolower(trim($value));
    }
}

$display = new display($db);
$users = $display->getAll();
$admins = array_values(array_filter($users, function($u){ return ((int)($u['role_id'] ?? 0) === 1); }));
$deletedUsers = [];
$deletedDocuments = [];
if (!$isGuest && (int) ($_SESSION['role_id'] ?? 0) === 1) {
    $deletedUsers = $db->query('SELECT id, registration_number, student_number, last_name, first_name, middle_name, suffix, email, role_id, deleted_at FROM `deleted_users` ORDER BY deleted_at DESC, id DESC')->fetchAll(PDO::FETCH_ASSOC);
    $deletedDocuments = $db->query('SELECT dd.id, dd.title, o.name AS office_name, c.name AS classification, (
            SELECT GROUP_CONCAT(DISTINCT tt.name ORDER BY tt.name SEPARATOR "; ")
            FROM `deleted_document_transaction_types` dtt
            LEFT JOIN `transaction_types` tt ON tt.id = dtt.transaction_type_id
            WHERE dtt.document_id = dd.id
        ) AS transaction_type, dd.who_may_avail, dd.deleted_at
        FROM `deleted_documents` dd
        LEFT JOIN `offices` o ON o.id = dd.office_id
        LEFT JOIN `classifications` c ON c.id = dd.classification_id
        ORDER BY dd.deleted_at DESC, dd.id DESC')->fetchAll(PDO::FETCH_ASSOC);
}

$roles_list = [
    0 => 'User',
    1 => 'Admin',
];

$role_id = $isGuest ? 0 : (int) ($_SESSION['role_id'] ?? 0);
$flashMessage = $_SESSION['flash_message'] ?? '';
$flashType = $_SESSION['flash_type'] ?? 'success';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

if (!isset($_SESSION['id']) && !$isGuest) {
    auditLogSafely($db, [
        'action' => 'ACCESS_DENIED',
        'module' => 'Dashboard',
        'description' => 'Unauthenticated request was redirected from the dashboard.',
        'status' => 'denied',
    ]);
    header("Location: index.php");
    exit;
}

$loggedInUser = null;
if (isset($_SESSION['id']) && !$isGuest) {
    $userDetailsStmt = $db->prepare('SELECT first_name, last_name, email FROM user WHERE id = ? LIMIT 1');
    $userDetailsStmt->execute([(int) $_SESSION['id']]);
    $loggedInUser = $userDetailsStmt->fetch(PDO::FETCH_ASSOC);
}

$loggedInUserName = trim((string) (($loggedInUser['first_name'] ?? $_SESSION['first_name'] ?? '') . ' ' . ($loggedInUser['last_name'] ?? $_SESSION['last_name'] ?? '')));
$loggedInUserName = preg_replace('/\s+/', ' ', $loggedInUserName);
$loggedInUserEmail = trim((string) ($loggedInUser['email'] ?? ''));

if (empty($_SESSION['document_request_csrf'])) {
    $_SESSION['document_request_csrf'] = bin2hex(random_bytes(32));
}
$pendingDocumentRequests = 0;
$latestDocumentRequestId = 0;
try {
    $pendingDocumentRequests = (int) $db->query("SELECT COUNT(*) FROM document_requests WHERE status = 'pending'")->fetchColumn();
    $latestDocumentRequestId = (int) $db->query('SELECT COALESCE(MAX(request_id), 0) FROM document_requests')->fetchColumn();
} catch (Throwable $error) {
    $pendingDocumentRequests = 0;
    $latestDocumentRequestId = 0;
}

if ($isGuest && $_SERVER['REQUEST_METHOD'] === 'POST') {
    http_response_code(403);
    exit('Guest access is read-only.');
}

$homeCardDefaults = [
    'welcome' => [
        'badge' => 'NEUST Smart Campus',
        'title' => 'Welcome to your campus dashboard',
        'description' => 'Access campus services and navigate the campus with a single click. Everything you need for quick and easier campus operations is here.',
        'button' => 'Open Campus Map',
    ],
    'navigation' => [
        'title' => 'Campus Navigation',
        'features' => [
            'Quick route planning across buildings',
            'Office and room highlights for easier location finding',
            'Fast access to campus services and navigation support',
        ],
    ],
    'overview' => [
        'title' => 'Campus at a glance',
        'subtitle' => 'NEUST main campus',
        'buildings' => ['CICT', 'COED', 'CMBT', 'LHS'],
        'description' => 'The campus map covers three floor levels, with room and office locations across the CICT, COED, CMBT, and LHS buildings.',
    ],
    'destinations' => [
        'title' => 'Quick destinations',
        'subtitle' => 'Open a highlighted location on the campus map',
        'items' => [
            ['label' => 'Clinic', 'target' => 'wp-clinic'],
            ['label' => 'Registrar', 'target' => 'wp-registrar'],
        ],
    ],
];
$homeCardFields = [
    'welcome' => [
        'badge' => ['label' => 'Badge', 'type' => 'text', 'maxlength' => 100],
        'title' => ['label' => 'Heading', 'type' => 'text', 'maxlength' => 160],
        'description' => ['label' => 'Description', 'type' => 'textarea', 'maxlength' => 500],
        'button' => ['label' => 'Map button label', 'type' => 'text', 'maxlength' => 80],
    ],
    'navigation' => [
        'title' => ['label' => 'Heading', 'type' => 'text', 'maxlength' => 100],
        'features' => ['label' => 'Items', 'type' => 'list', 'maxlength' => 160],
    ],
    'overview' => [
        'title' => ['label' => 'Heading', 'type' => 'text', 'maxlength' => 100],
        'subtitle' => ['label' => 'Subtitle', 'type' => 'text', 'maxlength' => 100],
        'buildings' => ['label' => 'Building badges', 'type' => 'list', 'maxlength' => 30],
        'description' => ['label' => 'Description', 'type' => 'textarea', 'maxlength' => 500],
    ],
    'destinations' => [
        'title' => ['label' => 'Heading', 'type' => 'text', 'maxlength' => 100],
        'subtitle' => ['label' => 'Subtitle', 'type' => 'text', 'maxlength' => 160],
        'items' => ['label' => 'Destinations', 'type' => 'destination_list', 'maxlength' => 60],
    ],
];
// Quick destinations are built from the campus map (map-editor/map.json), so every room added in
// the map editor can be picked here. The short ids below are only a fallback (map file missing)
// and let cards saved before this change keep pointing at the right room.
$homeLegacyDestinationLabels = [
    'wp-clinic' => ['Clinic'],
    'wp-registrar' => ['Registrar window'],
    'wp-cashier' => ['Cashier Window'],
    'wp-osoad' => ['OSOAD'],
    'wp-bmgs' => ['BMGS'],
    'wp-lhs-principal' => ['LHS Principal'],
    'wp-coed-office' => ['COED Office'],
];

if (!function_exists('homeDestinationCleanLabel')) {
    function homeDestinationCleanLabel($label)
    {
        $parts = preg_split('/\R/u', (string) $label);
        $parts = array_values(array_filter(array_map('trim', $parts), 'strlen'));
        $singleLetters = array_filter($parts, function ($part) {
            return mb_strlen($part, 'UTF-8') === 1;
        });
        if (count($parts) > 1 && count($singleLetters) === count($parts)) {
            return implode('', $parts); // vertical lettering such as C / A / N / T / E / E / N
        }

        return trim((string) preg_replace('/\s+/u', ' ', implode(' ', $parts)));
    }
}

if (!function_exists('homeDestinationNormalize')) {
    function homeDestinationNormalize($value)
    {
        return (string) preg_replace('/\s+/u', '', mb_strtolower((string) $value, 'UTF-8'));
    }
}

if (!function_exists('homeDestinationIconFor')) {
    function homeDestinationIconFor($text)
    {
        $text = mb_strtolower((string) $text, 'UTF-8');
        $icons = [
            'clinic' => 'fa-kit-medical',
            'registrar' => 'fa-file-signature',
            'cashier' => 'fa-cash-register',
            'library' => 'fa-book',
            'canteen' => 'fa-utensils',
            'gym' => 'fa-dumbbell',
            'restroom' => 'fa-restroom',
            'toilet' => 'fa-restroom',
            'comfort' => 'fa-restroom',
            'laborator' => 'fa-computer',
            'comlab' => 'fa-computer',
            'drafting' => 'fa-ruler-combined',
            'faculty' => 'fa-chalkboard-user',
            'principal' => 'fa-user-tie',
            'dean' => 'fa-user-tie',
            'director' => 'fa-user-tie',
            'information' => 'fa-circle-info',
            'admission' => 'fa-clipboard-user',
            'counsel' => 'fa-comments',
            'conference' => 'fa-people-group',
            'stock' => 'fa-box-open',
            'supply' => 'fa-box-open',
            'entrance' => 'fa-door-open',
            'office' => 'fa-building',
            'classroom' => 'fa-chalkboard',
        ];
        foreach ($icons as $keyword => $icon) {
            if (strpos($text, $keyword) !== false) {
                return $icon;
            }
        }

        return 'fa-location-dot';
    }
}

if (!function_exists('renderHomeDestinationOptions')) {
    function renderHomeDestinationOptions(array $options, string $selected): string
    {
        $html = '';
        $known = false;
        $currentGroup = null;
        foreach ($options as $option) {
            if ($currentGroup !== $option['group']) {
                if ($currentGroup !== null) {
                    $html .= '</optgroup>';
                }
                $html .= '<optgroup label="' . htmlspecialchars($option['group'], ENT_QUOTES, 'UTF-8') . '">';
                $currentGroup = $option['group'];
            }
            $isSelected = $option['id'] === $selected;
            $known = $known || $isSelected;
            $html .= '<option value="' . htmlspecialchars($option['id'], ENT_QUOTES, 'UTF-8') . '" data-name="'
                . htmlspecialchars($option['name'], ENT_QUOTES, 'UTF-8') . '"' . ($isSelected ? ' selected' : '') . '>'
                . htmlspecialchars($option['label'], ENT_QUOTES, 'UTF-8') . '</option>';
        }
        if ($currentGroup !== null) {
            $html .= '</optgroup>';
        }
        if (!$known && $selected !== '') {
            $html = '<option value="' . htmlspecialchars($selected, ENT_QUOTES, 'UTF-8') . '" selected>Unavailable location - choose another</option>' . $html;
        }

        return $html;
    }
}

$homeDestinationTargets = [];   // node id => "Name · 3F"
$homeDestinationOptions = [];   // ordered list of id / name / label / group for the pickers
$homeDestinationIcons = [];     // node id => Font Awesome icon
$homeDestinationNodeIndex = []; // node id => normalised name, used to upgrade older saved ids

$homeMapFile = __DIR__ . '/map-editor/map.json';
if (is_readable($homeMapFile)) {
    $homeMapData = json_decode((string) file_get_contents($homeMapFile), true);
    if (is_array($homeMapData) && isset($homeMapData['nodes']) && is_array($homeMapData['nodes'])) {
        $homeBuildingOrder = [];
        foreach (($homeMapData['buildings'] ?? []) as $buildingPosition => $mapBuilding) {
            if (is_array($mapBuilding) && isset($mapBuilding['name'])) {
                $homeBuildingOrder[(string) $mapBuilding['name']] = (int) $buildingPosition;
            }
        }

        $homeDestinationCandidates = [];
        foreach ($homeMapData['nodes'] as $mapNodeId => $mapNode) {
            $mapNodeId = (string) $mapNodeId;
            if (!is_array($mapNode) || ($mapNode['type'] ?? '') !== 'room' || !preg_match('/^[A-Za-z0-9_-]+$/', $mapNodeId)) {
                continue;
            }
            // Rooms the editor marks "Disable destination" or "Remove display" are not shown on the public map.
            if (!empty($mapNode['disableDestination']) || !empty($mapNode['hideDisplay'])) {
                continue;
            }
            $destinationName = homeDestinationCleanLabel($mapNode['label'] ?? '');
            if ($destinationName === '') {
                continue;
            }
            $destinationGroup = trim((string) ($mapNode['buildingName'] ?? ''));
            $destinationFloor = max(1, (int) ($mapNode['floor'] ?? 1));
            $destinationTags = is_array($mapNode['tags'] ?? null) ? implode(' ', array_map('strval', $mapNode['tags'])) : '';
            $homeDestinationCandidates[] = [
                'id' => $mapNodeId,
                'name' => mb_substr($destinationName, 0, 60, 'UTF-8'),
                'group' => $destinationGroup !== '' ? $destinationGroup : 'Other',
                'floor' => $destinationFloor,
                'icon' => homeDestinationIconFor($destinationName . ' ' . $destinationTags),
            ];
        }

        usort($homeDestinationCandidates, function ($a, $b) use ($homeBuildingOrder) {
            $groupOrderA = $homeBuildingOrder[$a['group']] ?? PHP_INT_MAX;
            $groupOrderB = $homeBuildingOrder[$b['group']] ?? PHP_INT_MAX;
            return ($groupOrderA <=> $groupOrderB)
                ?: strcasecmp($a['group'], $b['group'])
                ?: ($a['floor'] <=> $b['floor'])
                ?: strnatcasecmp($a['name'], $b['name']);
        });

        foreach ($homeDestinationCandidates as $candidate) {
            $candidateLabel = $candidate['name'] . ' · ' . $candidate['floor'] . 'F';
            $homeDestinationTargets[$candidate['id']] = $candidateLabel;
            $homeDestinationIcons[$candidate['id']] = $candidate['icon'];
            $homeDestinationNodeIndex[$candidate['id']] = homeDestinationNormalize($candidate['name']);
            $homeDestinationOptions[] = [
                'id' => $candidate['id'],
                'name' => $candidate['name'],
                'label' => $candidateLabel,
                'group' => $candidate['group'],
            ];
        }
    }
}

if (empty($homeDestinationTargets)) {
    $homeFallbackDestinations = [
        'wp-clinic' => ['Clinic', 'fa-kit-medical'],
        'wp-registrar' => ['Registrar', 'fa-file-signature'],
        'wp-cashier' => ['Cashier', 'fa-cash-register'],
        'wp-osoad' => ['OSOAD', 'fa-building'],
        'wp-bmgs' => ['BMGS', 'fa-building-columns'],
        'wp-lhs-principal' => ['LHS Principal', 'fa-user-tie'],
        'wp-coed-office' => ['COED Office', 'fa-building'],
    ];
    foreach ($homeFallbackDestinations as $fallbackId => [$fallbackName, $fallbackIcon]) {
        $homeDestinationTargets[$fallbackId] = $fallbackName;
        $homeDestinationIcons[$fallbackId] = $fallbackIcon;
        $homeDestinationOptions[] = ['id' => $fallbackId, 'name' => $fallbackName, 'label' => $fallbackName, 'group' => 'Campus'];
    }
}
$homeCardContent = $homeCardDefaults;
$db->exec('CREATE TABLE IF NOT EXISTS home_page_cards (card_key VARCHAR(40) NOT NULL PRIMARY KEY, content LONGTEXT NOT NULL, updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
$homeCardStmt = $db->query('SELECT card_key, content FROM home_page_cards');
foreach ($homeCardStmt->fetchAll(PDO::FETCH_ASSOC) as $savedHomeCard) {
    $cardKey = (string) ($savedHomeCard['card_key'] ?? '');
    $savedContent = json_decode((string) ($savedHomeCard['content'] ?? ''), true);
    if (isset($homeCardContent[$cardKey]) && is_array($savedContent)) {
        if ($cardKey === 'navigation' && !isset($savedContent['features'])) {
            $savedContent['features'] = array_values(array_filter([
                $savedContent['feature1'] ?? null,
                $savedContent['feature2'] ?? null,
                $savedContent['feature3'] ?? null,
            ], 'is_string'));
        }
        if ($cardKey === 'overview' && !isset($savedContent['buildings'])) {
            $savedContent['buildings'] = array_values(array_filter([
                $savedContent['building1'] ?? null,
                $savedContent['building2'] ?? null,
                $savedContent['building3'] ?? null,
                $savedContent['building4'] ?? null,
            ], 'is_string'));
        }
        if ($cardKey === 'destinations' && !isset($savedContent['items'])) {
            $savedContent['items'] = [
                [
                    'label' => (string) ($savedContent['clinic_label'] ?? 'Clinic'),
                    'target' => (string) ($savedContent['clinic_target'] ?? 'wp-clinic'),
                ],
                [
                    'label' => (string) ($savedContent['registrar_label'] ?? 'Registrar'),
                    'target' => (string) ($savedContent['registrar_target'] ?? 'wp-registrar'),
                ],
            ];
        }
        $homeCardContent[$cardKey] = array_merge($homeCardContent[$cardKey], array_intersect_key($savedContent, $homeCardDefaults[$cardKey]));
    }
}

// Cards saved earlier stored short ids such as "wp-clinic"; point them at the real map rooms.
if (!empty($homeDestinationNodeIndex) && is_array($homeCardContent['destinations']['items'] ?? null)) {
    foreach ($homeCardContent['destinations']['items'] as $destinationIndex => $savedDestination) {
        $savedTarget = (string) ($savedDestination['target'] ?? '');
        if ($savedTarget === '' || isset($homeDestinationTargets[$savedTarget])) {
            continue;
        }
        $wantedNames = $homeLegacyDestinationLabels[strtolower($savedTarget)]
            ?? [str_replace('-', ' ', (string) preg_replace('/^wp-/i', '', $savedTarget))];
        $wantedNames = array_map('homeDestinationNormalize', $wantedNames);
        foreach ($homeDestinationNodeIndex as $nodeId => $normalizedName) {
            if (in_array($normalizedName, $wantedNames, true)) {
                $homeCardContent['destinations']['items'][$destinationIndex]['target'] = (string) $nodeId;
                break;
            }
        }
    }
}

if (empty($_SESSION['home_card_csrf'])) {
    $_SESSION['home_card_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_home_card') {
    $currentRoleStmt = $db->prepare('SELECT role_id FROM `user` WHERE id = ? LIMIT 1');
    $currentRoleStmt->execute([(int) ($_SESSION['id'] ?? 0)]);
    $currentRoleId = (int) $currentRoleStmt->fetchColumn();
    $cardKey = (string) ($_POST['card_key'] ?? '');
    $postedToken = (string) ($_POST['csrf_token'] ?? '');

    if ($currentRoleId !== 1) {
        http_response_code(403);
        exit('Administrator access is required.');
    }
    if (!hash_equals((string) $_SESSION['home_card_csrf'], $postedToken) || !isset($homeCardFields[$cardKey])) {
        http_response_code(400);
        exit('Invalid card edit request.');
    }

    $updatedContent = [];
    foreach ($homeCardFields[$cardKey] as $fieldName => $fieldOptions) {
        if (in_array(($fieldOptions['type'] ?? ''), ['list', 'destination_list'], true)) {
            $postedItems = $_POST[$fieldName] ?? null;
            if (!is_array($postedItems) || count($postedItems) < 1 || count($postedItems) > 12) {
                http_response_code(400);
                exit('Add between 1 and 12 card items.');
            }

            $items = [];
            foreach ($postedItems as $postedItem) {
                if (($fieldOptions['type'] ?? '') === 'destination_list') {
                    if (!is_array($postedItem)) {
                        http_response_code(400);
                        exit('Each destination must include a label and location.');
                    }
                    $label = trim((string) ($postedItem['label'] ?? ''));
                    $target = trim((string) ($postedItem['target'] ?? ''));
                    if ($label === '' || mb_strlen($label, 'UTF-8') > (int) $fieldOptions['maxlength'] || !array_key_exists($target, $homeDestinationTargets)) {
                        http_response_code(400);
                        exit('Enter a valid destination label and campus location.');
                    }
                    $items[] = ['label' => $label, 'target' => $target];
                    continue;
                }

                if (!is_scalar($postedItem)) {
                    http_response_code(400);
                    exit('Each card item must be text.');
                }
                $item = trim((string) $postedItem);
                if ($item === '' || mb_strlen($item, 'UTF-8') > (int) $fieldOptions['maxlength']) {
                    http_response_code(400);
                    exit('Complete each card item within its maximum length.');
                }
                $items[] = $item;
            }
            $updatedContent[$fieldName] = $items;
            continue;
        }

        $fieldValue = trim((string) ($_POST[$fieldName] ?? ''));
        if (($fieldOptions['type'] ?? '') === 'target') {
            if (!array_key_exists($fieldValue, $homeDestinationTargets)) {
                http_response_code(400);
                exit('Choose a valid campus location.');
            }
        } elseif ($fieldValue === '' || mb_strlen($fieldValue, 'UTF-8') > (int) ($fieldOptions['maxlength'] ?? 200)) {
            http_response_code(400);
            exit('Complete each field within its maximum length.');
        }
        $updatedContent[$fieldName] = $fieldValue;
    }

    $saveHomeCardStmt = $db->prepare('INSERT INTO home_page_cards (card_key, content) VALUES (?, ?) ON DUPLICATE KEY UPDATE content = VALUES(content)');
    $encodedContent = json_encode($updatedContent, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $oldCardStmt = $db->prepare('SELECT content FROM home_page_cards WHERE card_key = ? LIMIT 1');
    $oldCardStmt->execute([$cardKey]);
    $oldCardContent = $oldCardStmt->fetchColumn();
    $db->beginTransaction();
    try {
        $saveHomeCardStmt->execute([$cardKey, $encodedContent]);
        auditLog($db, [
            'action' => 'UPDATE',
            'module' => 'Settings',
            'target_type' => 'home_card',
            'target_id' => $cardKey,
            'target_name' => $cardKey,
            'description' => 'Updated dashboard home card content.',
            'old_values' => ['content' => is_string($oldCardContent) ? json_decode($oldCardContent, true) : null],
            'new_values' => ['content' => $updatedContent],
            'status' => 'success',
        ]);
        $db->commit();
    } catch (Throwable $error) {
        if ($db->inTransaction()) $db->rollBack();
        throw $error;
    }
    redirectWithMessage('Home card updated.', 'success', 'home');
}

$feedbackMessage = '';
$feedbackError = '';

if (empty($_SESSION['ticket_action_csrf'])) {
    $_SESSION['ticket_action_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ticket_action'])) {
    if ((int) $role_id !== 1) {
        http_response_code(403);
        exit('Admin access is required to manage tickets.');
    }

    $postedToken = (string) ($_POST['ticket_action_csrf'] ?? '');
    if (!hash_equals((string) $_SESSION['ticket_action_csrf'], $postedToken)) {
        http_response_code(403);
        exit('Invalid ticket action token. Refresh the page and try again.');
    }

    $ticketRecordId = (int) ($_POST['ticket_id'] ?? 0);
    $ticketAction = (string) $_POST['ticket_action'];
    if ($ticketRecordId <= 0 || !in_array($ticketAction, ['resolve', 'close', 'toggle_priority'], true)) {
        redirectWithMessage('Choose a valid ticket action.', 'danger', 'tickets');
    }

    if ($ticketAction === 'toggle_priority') {
        $oldTicketStmt = $db->prepare('SELECT ticket_code, status, is_priority FROM feedback_tickets WHERE id = ? LIMIT 1');
        $oldTicketStmt->execute([$ticketRecordId]);
        $oldTicket = $oldTicketStmt->fetch(PDO::FETCH_ASSOC);
        $ticketActionStmt = $db->prepare('UPDATE feedback_tickets SET is_priority = 1 - is_priority WHERE id = ?');
        $db->beginTransaction();
        $ticketActionStmt->execute([$ticketRecordId]);
        if ($oldTicket) auditLog($db, [
            'action' => 'UPDATE', 'module' => 'Feedback', 'target_type' => 'feedback_ticket',
            'target_id' => $ticketRecordId, 'target_name' => (string) $oldTicket['ticket_code'],
            'description' => 'Changed feedback ticket priority.',
            'old_values' => ['is_priority' => (int) $oldTicket['is_priority']],
            'new_values' => ['is_priority' => 1 - (int) $oldTicket['is_priority']], 'status' => 'success',
        ]);
        $db->commit();
    } else {
        $oldTicketStmt = $db->prepare('SELECT ticket_code, status FROM feedback_tickets WHERE id = ? LIMIT 1');
        $oldTicketStmt->execute([$ticketRecordId]);
        $oldTicket = $oldTicketStmt->fetch(PDO::FETCH_ASSOC);
        $ticketActionStmt = $db->prepare('UPDATE feedback_tickets SET status = ? WHERE id = ?');
        $ticketStatus = $ticketAction === 'resolve' ? 'resolved' : 'closed';
        $db->beginTransaction();
        $ticketActionStmt->execute([$ticketStatus, $ticketRecordId]);
        if ($oldTicket) auditLog($db, [
            'action' => 'UPDATE', 'module' => 'Feedback', 'target_type' => 'feedback_ticket',
            'target_id' => $ticketRecordId, 'target_name' => (string) $oldTicket['ticket_code'],
            'description' => 'Changed feedback ticket status.',
            'old_values' => ['status' => (string) $oldTicket['status']],
            'new_values' => ['status' => $ticketStatus], 'status' => 'success',
        ]);
        $db->commit();
        redirectWithMessage('Ticket status updated.', 'success', 'tickets');
    }

    redirectWithMessage('Ticket priority updated.', 'success', 'tickets');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_feedback') {
    $feedbackName = trim((string) ($_POST['feedback_name'] ?? $loggedInUserName));
    $feedbackEmail = trim((string) ($_POST['feedback_email'] ?? $loggedInUserEmail));
    $feedbackText = trim((string) ($_POST['feedback_message'] ?? ''));

    if ($feedbackName === '' || $feedbackEmail === '' || $feedbackText === '') {
        $feedbackError = 'Please complete your name, email, and feedback.';
    } elseif (!filter_var($feedbackEmail, FILTER_VALIDATE_EMAIL)) {
        $feedbackError = 'Please enter a valid email address.';
    } elseif (mb_strlen($feedbackText) > 2000) {
        $feedbackError = 'Feedback must be 2,000 characters or fewer.';
    } else {
        $systemRecipientEmail = 'navigationandinformationsic@gmail.com';

        $ticketId = 'TKT-' . date('YmdHis') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $saveTicketStmt = $db->prepare('INSERT INTO feedback_tickets (ticket_code, submitter_name, submitter_email, message) VALUES (?, ?, ?, ?)');
        $db->beginTransaction();
        $saveTicketStmt->execute([$ticketId, $feedbackName, $feedbackEmail, $feedbackText]);
        auditLog($db, [
            'action' => 'CREATE',
            'module' => 'Feedback',
            'target_type' => 'feedback_ticket',
            'target_id' => (int) $db->lastInsertId(),
            'target_name' => $ticketId,
            'description' => 'Submitted feedback. Message content intentionally excluded.',
            'new_values' => ['ticket_code' => $ticketId],
            'status' => 'success',
        ]);
        $db->commit();
        $emailSubject = 'Feedback Ticket ' . $ticketId . ' - NEUST Smart Campus';
        $safeFeedbackName = htmlspecialchars($feedbackName, ENT_QUOTES, 'UTF-8');
        $safeFeedbackEmail = htmlspecialchars($feedbackEmail, ENT_QUOTES, 'UTF-8');
        $safeFeedbackText = nl2br(htmlspecialchars($feedbackText, ENT_QUOTES, 'UTF-8'));
        $emailBody = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Ticket</title>
</head>
<body style="margin:0; padding:0; background-color:#eef4ff; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    <div style="max-width:700px; margin:32px auto; background:#ffffff; border:1px solid #dfe9ff; border-radius:18px; overflow:hidden; box-shadow:0 14px 32px rgba(15, 23, 42, 0.08);">
        <div style="background:linear-gradient(135deg, #14369C 0%, #0B246F 100%); padding:28px 32px; color:#ffffff;">
            <div style="font-size:12px; letter-spacing:1.8px; text-transform:uppercase; opacity:0.9; font-weight:bold;">NEUST Smart Campus</div>
            <h2 style="margin:12px 0 0; font-size:30px; line-height:1.2; font-weight:700;">Feedback Ticket</h2>
        </div>
        <div style="padding:28px 32px 18px;">
            <div style="display:inline-block; background:#fff7d6; border:1px solid #f3d76b; color:#7a5b00; border-radius:999px; padding:8px 14px; font-size:12px; letter-spacing:0.08em; text-transform:uppercase; font-weight:bold; margin-bottom:18px;">
                Ticket ID: {$ticketId}
            </div>

            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="width:100%; border-collapse:collapse; background:#f8fbff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; margin-bottom:22px;">
                <tr>
                    <td style="padding:14px 16px; width:150px; font-size:13px; color:#475569; font-weight:bold; border-bottom:1px solid #e2e8f0;">Sender</td>
                    <td style="padding:14px 16px; font-size:14px; color:#0f172a; border-bottom:1px solid #e2e8f0;">{$safeFeedbackName} &lt;{$safeFeedbackEmail}&gt;</td>
                </tr>
                <tr>
                    <td style="padding:14px 16px; font-size:13px; color:#475569; font-weight:bold; border-bottom:1px solid #e2e8f0;">Subject</td>
                    <td style="padding:14px 16px; font-size:14px; color:#0f172a; border-bottom:1px solid #e2e8f0;">NEUST Smart Campus Feedback</td>
                </tr>
                <tr>
                    <td style="padding:14px 16px; font-size:13px; color:#475569; font-weight:bold;">Status</td>
                    <td style="padding:14px 16px; font-size:14px; color:#0f172a;">New</td>
                </tr>
            </table>

            <div style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:12px; padding:18px 18px 16px;">
                <div style="font-size:12px; letter-spacing:0.12em; text-transform:uppercase; font-weight:bold; color:#6b7280; margin-bottom:10px;">Message</div>
                <div style="font-size:15px; line-height:1.7; color:#1f2937;">{$safeFeedbackText}</div>
            </div>
        </div>
        <div style="padding:18px 32px 26px; background:#f8fafc; border-top:1px solid #e5e7eb; font-size:12px; color:#64748b; text-align:center;">
            This message was sent from the NEUST Smart Campus feedback portal.
        </div>
    </div>
</body>
</html>
HTML;
        $mailUsername = trim((string) getenv('MAIL_USERNAME'));
        $mailPassword = (string) getenv('MAIL_PASSWORD');
        $mailFrom = trim((string) (getenv('MAIL_FROM') ?: $mailUsername));
        $emailSent = false;

        if ($mailUsername !== '' && $mailPassword !== '' && filter_var($systemRecipientEmail, FILTER_VALIDATE_EMAIL)) {
            try {
                $mailer = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mailer->isSMTP();
                $mailer->Host = getenv('MAIL_HOST') ?: 'smtp.gmail.com';
                $mailer->SMTPAuth = true;
                $mailer->Username = $mailUsername;
                $mailer->Password = $mailPassword;
                $mailer->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mailer->Port = (int) (getenv('MAIL_PORT') ?: 587);
                $mailer->CharSet = 'UTF-8';
                $mailer->setFrom($feedbackEmail, $feedbackName);
                $mailer->addReplyTo($feedbackEmail, $feedbackName);
                $mailer->addAddress($systemRecipientEmail, 'NEUST Smart Campus Support');
                $mailer->Subject = $emailSubject;
                $mailer->Body = $emailBody;
                $mailer->AltBody = "Ticket ID: {$ticketId}\n\nFrom: {$feedbackName} <{$feedbackEmail}>\n\nMessage:\n{$feedbackText}";
                $emailSent = $mailer->send();
            } catch (\Throwable $mailError) {
                $emailSent = false;
            }
        }

        header('Location: dashboard.php?feedback=' . ($emailSent ? 'sent' : 'smtp') . '#feedback');
        exit;
    }
}

if (isset($_GET['feedback']) && $_GET['feedback'] === 'sent') {
    $feedbackMessage = 'Thank you. We received your feedback and sent a confirmation email to your submitted email address.';
} elseif (isset($_GET['feedback']) && $_GET['feedback'] === 'smtp') {
    $feedbackError = 'Your feedback was saved, but email delivery is unavailable because no SMTP server is configured. Enable XAMPP Mercury Mail or configure an SMTP relay on port 25.';
}

$feedbackTickets = [];
if ((int) $role_id === 1) {
    $ticketListStmt = $db->query('SELECT id, ticket_code, submitter_name, submitter_email, message, status, is_priority, created_at FROM feedback_tickets ORDER BY is_priority DESC, created_at DESC');
    $feedbackTickets = $ticketListStmt->fetchAll(PDO::FETCH_ASSOC);
}
$ticketCounts = [
    'all' => count($feedbackTickets),
    'unresolved' => 0,
    'resolved' => 0,
    'priority' => 0,
    'closed' => 0,
];
foreach ($feedbackTickets as $ticketCountRow) {
    $ticketCountStatus = (string) ($ticketCountRow['status'] ?? 'open');
    if ($ticketCountStatus === 'resolved') {
        $ticketCounts['resolved']++;
    } elseif ($ticketCountStatus === 'closed') {
        $ticketCounts['closed']++;
    } else {
        $ticketCounts['unresolved']++;
    }
    if (!empty($ticketCountRow['is_priority'])) {
        $ticketCounts['priority']++;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_template'])) {
    $templateHeaders = [
        'registration_number', 'student_number', 'last_name', 'first_name', 'middle_name', 'suffix',
        'barangay', 'city', 'province', 'birthday', 'sex', 'mobile_number', 'email'
    ];
    $sampleRow = [
        '2026-0001', 'S1234567', 'Sample', 'Juan', 'Dela', 'Jr.',
        'San Isidro', 'Quezon City', 'Metro Manila', '2002-05-14', 'Male', '09171234567', 'juan.sample@example.com'
    ];
    $excelTextColumns = [0, 1, 11];
    foreach ($sampleRow as $index => $value) {
        if (in_array($index, $excelTextColumns, true)) {
            $sampleRow[$index] = excelCsvTextCell((string) $value);
        }
    }

    // Drop anything already buffered (page output, stray whitespace) so the file contains only the CSV.
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="users_import_template.csv"');
    header('Cache-Control: no-store');
    $output = fopen('php://output', 'w');
    fputcsv($output, $templateHeaders);
    fputcsv($output, $sampleRow);
    fclose($output);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    auditRequireAdmin($db);
    $registration_number = trim($_POST['registration_number'] ?? '');
    $student_number = trim($_POST['student_number'] ?? '');
    $first_name = normalizeNameValue($_POST['first_name'] ?? '');
    $middle_name = normalizeNameValue($_POST['middle_name'] ?? '');
    $last_name = normalizeNameValue($_POST['last_name'] ?? '');
    $suffix = normalizeNameValue($_POST['suffix'] ?? '');
    $barangay = normalizeNameValue($_POST['barangay'] ?? '');
    $city = normalizeNameValue($_POST['city'] ?? '');
    $province = normalizeNameValue($_POST['province'] ?? '');
    $birthday = trim($_POST['birthday'] ?? '');
    $sex = trim($_POST['sex'] ?? '');
    $custom_sex = trim($_POST['custom_sex'] ?? '');
    $mobile_number = trim($_POST['mobile_number'] ?? '');
    $email = normalizeEmailValue($_POST['email'] ?? '');
    $password = generateRandomCsvPassword();
    $role_id_input = 0;
    $resolvedSex = resolveSexValue($sex, $custom_sex);

    $requiredFields = [
        'registration_number' => $registration_number,
        'student_number' => $student_number,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'barangay' => $barangay,
        'city' => $city,
        'province' => $province,
        'birthday' => $birthday,
        'sex' => $sex,
        'mobile_number' => $mobile_number,
        'email' => $email,
    ];

    $missingRequired = false;
    foreach ($requiredFields as $value) {
        if ($value === '') {
            $missingRequired = true;
            break;
        }
    }

    if ($missingRequired) {
        redirectWithMessage('Please fill in all required fields.', 'danger');
    }

    if (!preg_match('/^[A-Za-z\s\-\."]+$/', $first_name) || ($middle_name !== '' && !preg_match('/^[A-Za-z\s\-\."]+$/', $middle_name)) || !preg_match('/^[A-Za-z\s\-\."]+$/', $last_name)) {
        redirectWithMessage('Names should only contain letters and spaces.', 'danger');
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthday) || !DateTime::createFromFormat('Y-m-d', $birthday)) {
        redirectWithMessage('Please enter a valid birthday in YYYY-MM-DD format.', 'danger');
    }

    if ($sex === 'Others' && $custom_sex === '') {
        redirectWithMessage('Please enter a custom sex value when Others is selected.', 'danger');
    }

    if ($resolvedSex === null && $sex !== 'Prefer not to say') {
        redirectWithMessage('Please choose a valid sex option.', 'danger');
    }

    if (!preg_match('/^[0-9+\-\s()]+$/', $mobile_number)) {
        redirectWithMessage('Please enter a valid mobile number.', 'danger');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectWithMessage('Please enter a valid email address.', 'danger');
    }

    try {
        $duplicateUserValue = static function (string $field, string $value) use ($db): bool {
            if ($value === '') {
                return false;
            }

            $liveStmt = $db->prepare('SELECT id FROM `user` WHERE `' . $field . '` = ? LIMIT 1');
            $liveStmt->execute([$value]);
            if ($liveStmt->fetch()) {
                return true;
            }

            $archiveStmt = $db->prepare('SELECT id FROM `deleted_users` WHERE `' . $field . '` = ? LIMIT 1');
            $archiveStmt->execute([$value]);
            return $archiveStmt->fetch() !== false;
        };

        foreach (['email' => strtolower($email), 'student_number' => $student_number, 'registration_number' => $registration_number] as $field => $value) {
            if ($value !== '' && $duplicateUserValue($field, $value)) {
                $label = str_replace('_', ' ', $field);
                redirectWithMessage('This ' . $label . ' is already registered or archived.', 'danger');
            }
        }

        $stmt = $db->prepare('INSERT INTO user (registration_number, student_number, last_name, first_name, middle_name, suffix, barangay, city, province, birthday, sex, mobile_number, email, password, role_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
        $db->beginTransaction();
        $success = $stmt->execute([
            $registration_number, $student_number, $last_name, $first_name, $middle_name,
            $suffix, $barangay, $city, $province, $birthday, $resolvedSex, $mobile_number,
            strtolower($email), password_hash($password, PASSWORD_DEFAULT), $role_id_input,
        ]);

        if ($success) {
            $createdUserId = (int) $db->lastInsertId();
            auditLog($db, [
                'action' => 'CREATE', 'module' => 'Users', 'target_type' => 'user',
                'target_id' => $createdUserId, 'target_name' => trim($first_name . ' ' . $last_name),
                'description' => 'Administrator created a user account.',
                'new_values' => ['email' => strtolower($email), 'role_id' => $role_id_input],
                'status' => 'success',
            ]);
            $db->commit();
            $emailSent = sendAccountCreatedEmail($email, $first_name, $password, 'User');
            $message = 'User added successfully. A generated password was created and sent to the user email.';
            if ($emailSent) {
                $message .= ' Email sent successfully.';
            } else {
                $message .= ' Email could not be sent from this server, but the account was created.';
            }
            redirectWithMessage($message, 'success');
        }
        if ($db->inTransaction()) $db->rollBack();

        redirectWithMessage('Unable to add user. Please try again.', 'danger');
    } catch (PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
        redirectWithMessage('This email is already registered or the account could not be created.', 'danger');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['promote_admin_id'])) {
    auditRequireAdmin($db);
    $targetId = (int) ($_POST['promote_admin_id'] ?? 0);

    if ($targetId <= 0) {
        redirectWithMessage('Please choose a valid user to promote.', 'danger');
    }

    try {
        $userStmt = $db->prepare('SELECT id, first_name, last_name, email, role_id FROM user WHERE id = ? LIMIT 1');
        $userStmt->execute([$targetId]);
        $existingUser = $userStmt->fetch(PDO::FETCH_ASSOC);

        if (!$existingUser) {
            redirectWithMessage('No existing user account was found for that ID.', 'danger');
        }

        if ((int) ($existingUser['role_id'] ?? 0) === 1) {
            redirectWithMessage('This account is already an admin.', 'warning');
        }

        $promoted = updateUserRole($db, $targetId, 1);

        if ($promoted) {
            $name = trim((string) ($existingUser['first_name'] ?? 'User')) . ' ' . trim((string) ($existingUser['last_name'] ?? ''));
            $emailSent = sendAdminRoleEmail((string) ($existingUser['email'] ?? ''), trim($name));
            $message = 'User promoted to admin successfully: ' . trim($name);
            if ($emailSent) {
                $message .= ' Email notification sent.';
            } else {
                $message .= ' Email notification could not be sent.';
            }
            redirectWithMessage($message, 'warning', 'admin');
        }

        redirectWithMessage('Unable to promote this user to admin. Please try again.', 'danger', 'admin');
    } catch (PDOException $e) {
        redirectWithMessage('The user could not be promoted to admin at this time.', 'danger', 'admin');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['demote_admin_id'])) {
    auditRequireAdmin($db);
    $targetId = (int) ($_POST['demote_admin_id'] ?? 0);

    if ($targetId <= 0) {
        redirectWithMessage('Please choose a valid admin to demote.', 'danger');
    }

    try {
        $userStmt = $db->prepare('SELECT id, first_name, last_name, role_id FROM user WHERE id = ? LIMIT 1');
        $userStmt->execute([$targetId]);
        $existingUser = $userStmt->fetch(PDO::FETCH_ASSOC);

        if (!$existingUser) {
            redirectWithMessage('No admin account was found for that ID.', 'danger');
        }

        if ((int) ($existingUser['role_id'] ?? 0) !== 1) {
            redirectWithMessage('This account is not an admin and cannot be demoted.', 'warning');
        }

        $demoted = updateUserRole($db, $targetId, 0);

        if ($demoted) {
            $name = trim((string) ($existingUser['first_name'] ?? 'Admin')) . ' ' . trim((string) ($existingUser['last_name'] ?? ''));
            redirectWithMessage('Admin demoted to user successfully: ' . trim($name), 'warning', 'admin');
        }

        redirectWithMessage('Unable to demote this admin. Please try again.', 'danger', 'admin');
    } catch (PDOException $e) {
        redirectWithMessage('The admin could not be demoted at this time.', 'danger');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin_new'])) {
    auditRequireAdmin($db);
    $email = normalizeEmailValue($_POST['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectWithMessage('Please enter a valid existing user email to promote to admin.', 'danger');
    }

    try {
        $userStmt = $db->prepare('SELECT id, first_name, last_name, email, role_id FROM user WHERE email = ? LIMIT 1');
        $userStmt->execute([$email]);
        $existingUser = $userStmt->fetch(PDO::FETCH_ASSOC);

        if (!$existingUser) {
            redirectWithMessage('No existing user account was found with that email.', 'danger');
        }

        if ((int) ($existingUser['role_id'] ?? 0) === 1) {
            redirectWithMessage('This account is already an admin.', 'warning');
        }

        $promoted = updateUserRole($db, (int) $existingUser['id'], 1);

        if ($promoted) {
            $name = trim((string) ($existingUser['first_name'] ?? 'User')) . ' ' . trim((string) ($existingUser['last_name'] ?? ''));
            $emailSent = sendAdminRoleEmail((string) ($existingUser['email'] ?? ''), trim($name));
            $message = 'User promoted to admin successfully: ' . trim($name);
            if ($emailSent) {
                $message .= ' Email notification sent.';
            } else {
                $message .= ' Email notification could not be sent.';
            }
            redirectWithMessage($message, 'warning', 'admin');
        }

        redirectWithMessage('Unable to promote this user to admin. Please try again.', 'danger', 'admin');
    } catch (PDOException $e) {
        redirectWithMessage('The user could not be promoted to admin at this time.', 'danger', 'admin');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_csv'])) {
    auditRequireAdmin($db);
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        redirectWithMessage('Please upload a valid CSV file.', 'danger');
    }

    $file = $_FILES['csv_file']['tmp_name'];
    $extension = strtolower(pathinfo($_FILES['csv_file']['name'], PATHINFO_EXTENSION));

    if ($extension !== 'csv') {
        redirectWithMessage('Only CSV files are allowed.', 'danger');
    }

    $importTargetRole = strtolower(trim((string) ($_POST['import_target_role'] ?? 'user')));
    $roleId = in_array($importTargetRole, ['admin', 'administrator'], true) ? 1 : 0;

    $handle = fopen($file, 'r');
    if ($handle === false) {
        redirectWithMessage('Unable to read the uploaded CSV file.', 'danger');
    }

    $header = fgetcsv($handle);
    if ($header === false) {
        redirectWithMessage('The CSV file is empty.', 'danger');
    }

    $canonical = [];
    foreach ($header as $idx => $label) {
        $h = strtolower(trim((string) $label));
        $h_norm = preg_replace('/[^a-z0-9]+/', ' ', $h);

        if (preg_match('/\bregistration\b/', $h_norm) || preg_match('/reg(istration)?[_\s]*no|reg[_\s]*num/', $h_norm)) {
            $canonical[$idx] = 'registration_number';
        } elseif (preg_match('/student|stud(ent)?[_\s]*no|student_number/', $h_norm)) {
            $canonical[$idx] = 'student_number';
        } elseif (preg_match('/\bfirst\b/', $h_norm) || preg_match('/\bfirst name\b/', $h_norm)) {
            $canonical[$idx] = 'first_name';
        } elseif (preg_match('/\blast\b/', $h_norm) || preg_match('/\blast name\b/', $h_norm)) {
            $canonical[$idx] = 'last_name';
        } elseif (preg_match('/\bmiddle\b/', $h_norm) || preg_match('/\bmiddle name\b/', $h_norm)) {
            $canonical[$idx] = 'middle_name';
        } elseif (preg_match('/\bsuffix\b/', $h_norm)) {
            $canonical[$idx] = 'suffix';
        } elseif (preg_match('/\bbarangay\b/', $h_norm)) {
            $canonical[$idx] = 'barangay';
        } elseif (preg_match('/\bcity\b/', $h_norm)) {
            $canonical[$idx] = 'city';
        } elseif (preg_match('/\bprovince\b/', $h_norm)) {
            $canonical[$idx] = 'province';
        } elseif (preg_match('/birthday|birth/', $h_norm)) {
            $canonical[$idx] = 'birthday';
        } elseif (preg_match('/sex|gender/', $h_norm)) {
            $canonical[$idx] = 'sex';
        } elseif (preg_match('/mobile|phone|contact/', $h_norm)) {
            $canonical[$idx] = 'mobile_number';
        } elseif (preg_match('/\bemail\b/', $h_norm)) {
            $canonical[$idx] = 'email';
        } else {
            $canonical[$idx] = null;
        }
    }

    $inserted = 0;
    $skipped = 0;
    $emailsSent = 0;
    $duplicateImportError = null;
    $seenUniqueValues = [
        'registration_number' => [],
        'student_number' => [],
        'email' => [],
    ];

    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) < count($header)) {
            $row = array_pad($row, count($header), '');
        }

        $rowAssoc = [];
        foreach ($canonical as $i => $key) {
            if ($key !== null) {
                $rowAssoc[$key] = trim((string) ($row[$i] ?? ''));
            }
        }

        if (!isset($rowAssoc['first_name'], $rowAssoc['last_name'], $rowAssoc['email'])) {
            $skipped++;
            continue;
        }

        $first_name = normalizeNameValue($rowAssoc['first_name']);
        $middle_name = normalizeNameValue($rowAssoc['middle_name'] ?? '');
        $last_name = normalizeNameValue($rowAssoc['last_name']);
        $suffix = normalizeNameValue($rowAssoc['suffix'] ?? '');
        $email = normalizeEmailValue($rowAssoc['email']);
        $password = generateRandomCsvPassword();
        $registration_number = trim((string) ($rowAssoc['registration_number'] ?? ''));
        $student_number = trim((string) ($rowAssoc['student_number'] ?? ''));

        if (strtolower($last_name) === 'sample') {
            $skipped++;
            continue;
        }

        if ($first_name === '' || $last_name === '' || $email === '') {
            $skipped++;
            continue;
        }

        $duplicateField = null;
        foreach (['registration_number' => $registration_number, 'student_number' => $student_number, 'email' => $email] as $field => $value) {
            if ($value === '') {
                continue;
            }

            $key = strtolower((string) $value);
            if (isset($seenUniqueValues[$field][$key])) {
                $duplicateField = $field;
                break;
            }

            $seenUniqueValues[$field][$key] = true;
        }

        if ($duplicateField !== null) {
            $duplicateImportError = 'CSV import blocked: duplicate value detected for ' . str_replace('_', ' ', $duplicateField) . '. Please remove the duplicate row and try again.';
            break;
        }

        foreach (['registration_number' => $registration_number, 'student_number' => $student_number, 'email' => $email] as $field => $value) {
            if ($value === '') {
                continue;
            }

            $checkStmt = $db->prepare('SELECT id FROM `user` WHERE `' . $field . '` = ? LIMIT 1');
            $checkStmt->execute([$value]);
            if ($checkStmt->fetch()) {
                $duplicateImportError = 'CSV import blocked: a record already exists with this ' . str_replace('_', ' ', $field) . '. Please use a unique value.';
                break 2;
            }

            $archiveCheckStmt = $db->prepare('SELECT id FROM `deleted_users` WHERE `' . $field . '` = ? LIMIT 1');
            $archiveCheckStmt->execute([$value]);
            if ($archiveCheckStmt->fetch()) {
                $duplicateImportError = 'CSV import blocked: this ' . str_replace('_', ' ', $field) . ' already exists in the archive. Please use a unique value.';
                break 2;
            }
        }

        $birthday = null;
        if (!empty($rowAssoc['birthday'])) {
            $dt = date_create($rowAssoc['birthday']);
            if ($dt !== false) {
                $birthday = $dt->format('Y-m-d');
            }
        }

        try {
            $db->beginTransaction();
            $stmt = $db->prepare('INSERT INTO user (registration_number, student_number, last_name, first_name, middle_name, suffix, barangay, city, province, birthday, sex, mobile_number, email, password, role_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())');
            $success = $stmt->execute([
                $registration_number,
                $student_number,
                $last_name,
                $first_name,
                $middle_name,
                $suffix,
                $rowAssoc['barangay'] ?? '',
                $rowAssoc['city'] ?? '',
                $rowAssoc['province'] ?? '',
                $birthday,
                $rowAssoc['sex'] ?? '',
                $rowAssoc['mobile_number'] ?? '',
                strtolower($email),
                password_hash($password, PASSWORD_DEFAULT),
                $roleId,
            ]);

            if ($success) {
                $emailLabel = $roleId === 1 ? 'Admin' : 'User';
                auditLog($db, [
                    'action' => 'CREATE', 'module' => 'Users', 'target_type' => 'user',
                    'target_id' => (int) $db->lastInsertId(), 'target_name' => trim($first_name . ' ' . $last_name),
                    'description' => 'Administrator imported a user account from CSV.',
                    'new_values' => ['email' => strtolower($email), 'role_id' => $roleId, 'source' => 'csv'],
                    'status' => 'success',
                ]);
                $db->commit();
                $inserted++;
                if (sendAccountCreatedEmail($email, $first_name, $password, $emailLabel)) {
                    $emailsSent++;
                }
            } else {
                $db->rollBack();
                $skipped++;
            }
        } catch (PDOException $e) {
            if ($db->inTransaction()) $db->rollBack();
            $skipped++;
        }
    }

    fclose($handle);

    if ($duplicateImportError !== null) {
        redirectWithMessage($duplicateImportError, 'danger');
    }

    redirectWithMessage('CSV import complete. ' . $inserted . ' user(s) imported, ' . $skipped . ' skipped, ' . $emailsSent . ' email(s) sent.', 'success');
}

$documentOfficeMap = [
    'all' => ['label' => 'All document offices', 'color' => '#14369C'],
    'admission' => ['label' => 'Office of Admission and Registration', 'color' => 'red'],
    'guidance' => ['label' => 'Guidance and Counselling Office', 'color' => '#16a34a'],
    'student-affairs' => ['label' => 'Office of Student Affairs / Scholarship and Financial Assistance', 'color' => '#2563eb'],
    'library' => ['label' => 'University Library Services', 'color' => '#0891b2'],
    'discipline' => ['label' => 'Office of Student Discipline and Development', 'color' => 'black'],
    'accounting' => ['label' => 'Accounting Office', 'color' => '#9333ea'],
    'cashier' => ['label' => 'Cashier Office', 'color' => '#f59e0b'],
    'corporate' => ['label' => 'Corporate and Community Affairs Office', 'color' => '#c026d3'],
    'health' => ['label' => 'Health Services', 'color' => '#65a30d'],
    'security' => ['label' => 'Civil Security Unit', 'color' => '#b91c1c'],
    'mis' => ['label' => 'Management Information Systems', 'color' => '#4f46e5'],
    'other' => ['label' => 'Other Services', 'color' => 'gray'],
];

$selectedDocumentOffice = isset($_GET['document_office']) ? trim((string) $_GET['document_office']) : 'all';
if (!isset($documentOfficeMap[$selectedDocumentOffice])) {
    $selectedDocumentOffice = 'all';
}

$documentSearchQuery = trim((string) ($_GET['document_search'] ?? ''));
$documentPage = isset($_GET['document_page']) ? max(1, (int) $_GET['document_page']) : 1;
$documentItemsPerPage = 6;
$documentCardList = [];

foreach ($docs ?? [] as $document) {
    $officeName = trim((string) ($document['office_name'] ?? ''));
    $officeKey = 'other';
    $officeLower = strtolower($officeName);

    if (str_contains($officeLower, 'admission') || str_contains($officeLower, 'registration')) {
        $officeKey = 'admission';
    } elseif (str_contains($officeLower, 'guidance') || str_contains($officeLower, 'counsel')) {
        $officeKey = 'guidance';
    } elseif (str_contains($officeLower, 'student affairs') || str_contains($officeLower, 'scholarship') || str_contains($officeLower, 'financial assistance')) {
        $officeKey = 'student-affairs';
    } elseif (str_contains($officeLower, 'library')) {
        $officeKey = 'library';
    } elseif (str_contains($officeLower, 'discipline') || str_contains($officeLower, 'development')) {
        $officeKey = 'discipline';
    } elseif (str_contains($officeLower, 'accounting')) {
        $officeKey = 'accounting';
    } elseif (str_contains($officeLower, 'cashier')) {
        $officeKey = 'cashier';
    } elseif (str_contains($officeLower, 'corporate') || str_contains($officeLower, 'business affairs') || str_contains($officeLower, 'community')) {
        $officeKey = 'corporate';
    } elseif (str_contains($officeLower, 'health')) {
        $officeKey = 'health';
    } elseif (str_contains($officeLower, 'security')) {
        $officeKey = 'security';
    } elseif (str_contains($officeLower, 'information system') || str_contains($officeLower, 'mis')) {
        $officeKey = 'mis';
    }

    $documentCardList[] = [
        'id' => (int) ($document['id'] ?? 0),
        'title' => (string) ($document['title'] ?? 'Untitled Document'),
        'office_name' => $officeName,
        'office_key' => $officeKey,
        'office_label' => ($documentOfficeMap[$officeKey] ?? $documentOfficeMap['other'])['label'],
        'office_color' => ($documentOfficeMap[$officeKey] ?? $documentOfficeMap['other'])['color'],
        'classification' => trim((string) ($document['classification'] ?? '')),
        'transaction_type' => trim((string) ($document['transaction_type'] ?? '')),
        'who_may_avail' => trim((string) ($document['who_may_avail'] ?? '')),
    ];
}

if ($selectedDocumentOffice !== 'all') {
    $documentCardList = array_values(array_filter($documentCardList, function ($document) use ($selectedDocumentOffice) {
        return $document['office_key'] === $selectedDocumentOffice;
    }));
}

if ($documentSearchQuery !== '') {
    $documentCardList = array_values(array_filter($documentCardList, function ($document) use ($documentSearchQuery) {
        $searchableText = implode(' ', [
            $document['title'],
            $document['office_name'],
            $document['classification'],
            $document['transaction_type'],
            $document['who_may_avail'],
        ]);
        return mb_stripos($searchableText, $documentSearchQuery, 0, 'UTF-8') !== false;
    }));
}

$documentFilterParams = [];
if ($selectedDocumentOffice !== 'all') {
    $documentFilterParams['document_office'] = $selectedDocumentOffice;
}
if ($documentSearchQuery !== '') {
    $documentFilterParams['document_search'] = $documentSearchQuery;
}
$documentFilterQuery = $documentFilterParams !== [] ? '&' . http_build_query($documentFilterParams) : '';

$documentTotalRecords = count($documentCardList);
$documentTotalPages = $documentTotalRecords > 0 ? (int) ceil($documentTotalRecords / $documentItemsPerPage) : 1;
$documentPage = min($documentPage, max(1, $documentTotalPages));
$documentOffset = ($documentPage - 1) * $documentItemsPerPage;
$documentVisibleCards = array_slice($documentCardList, $documentOffset, $documentItemsPerPage);

$allowedDashboardTabs = ['home', 'documents', 'map', 'admin', 'users', 'tickets', 'docs-management', 'document-requests', 'map-configuration', 'audit-logs', 'archive'];
$requestedDashboardTab = trim((string) ($_GET['tab'] ?? ''));
$lastDashboardTab = $requestedDashboardTab !== ''
    ? $requestedDashboardTab
    : (isset($_COOKIE['dashboard_active_tab']) ? trim((string) $_COOKIE['dashboard_active_tab']) : '');
if (!in_array($lastDashboardTab, $allowedDashboardTabs, true)) {
    $lastDashboardTab = 'home';
}

// Fresh login (new session for this user): always open the Home tab.
// Later refreshes in the same session keep using the remembered tab above.
$dashboardTabSessionKey = ($isGuest ? 'guest' : (string) ($_SESSION['id'] ?? '0')) . '|' . session_id();
if (($_SESSION['dashboard_tab_session_key'] ?? '') !== $dashboardTabSessionKey) {
    $_SESSION['dashboard_tab_session_key'] = $dashboardTabSessionKey;
    $lastDashboardTab = 'home';
    if (!headers_sent()) {
        setcookie('dashboard_active_tab', 'home', [
            'expires' => time() + (60 * 60 * 24 * 365),
            'path' => '/',
            'samesite' => 'Lax',
            'secure' => false,
        ]);
    }
}
if ((int) ($role_id ?? 0) !== 1 && in_array($lastDashboardTab, ['admin', 'users', 'tickets', 'docs-management', 'document-requests', 'map-configuration', 'audit-logs', 'archive'], true)) {
    auditLogSafely($db, [
        'action' => 'ACCESS_DENIED',
        'module' => 'Dashboard',
        'description' => 'Non-admin attempted to open an administrator dashboard tab.',
        'status' => 'denied',
    ]);
    $lastDashboardTab = 'home';
}
if ($lastDashboardTab === 'audit-logs' && !canViewAuditLogs($db)) {
    auditLogSafely($db, [
        'action' => 'ACCESS_DENIED',
        'module' => 'Audit Logs',
        'description' => 'Dashboard Audit Logs tab denied; administrator access required.',
        'status' => 'denied',
    ]);
    $lastDashboardTab = 'home';
}
$isDocumentsTabActive = $lastDashboardTab === 'documents';
$dashboardTabPaneMap = [
    'home' => 'pills-landing',
    'map' => 'pills-home',
    'documents' => 'pills-documents',
    'admin' => 'pills-admin',
    'users' => 'pills-users',
    'tickets' => 'pills-tickets',
    'docs-management' => 'pills-docs-management',
    'document-requests' => 'pills-document-requests',
    'map-configuration' => 'pills-map-configuration',
    'audit-logs' => 'pills-audit-logs',
    'archive' => 'pills-archive',
];
$activeDashboardPaneId = $dashboardTabPaneMap[$lastDashboardTab] ?? 'pills-landing';

$documentHeaderTitle = 'Document Processes';
$documentHeaderColor = '#14369C';
$documentHeaderColorDeep = '#07184F';
if ($selectedDocumentOffice !== 'all') {
    foreach ($documentOfficeMap as $key => $legend) {
        if ($key === $selectedDocumentOffice) {
            $documentHeaderTitle = (string) $legend['label'] . ' Documents';
            $documentHeaderColor = (string) $legend['color'];
            $documentHeaderColorDeep = (string) $legend['color'];
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if (isset($office_name)): ?>
        <title>Dashboard - <?php echo $office_name; ?></title>
    <?php else: ?>
        <title>Dashboard</title>
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="img/neust_logo.png">
    <style>
        :root {
            --ns-blue: #14369C;
            --ns-blue-deep: #07184F;
            --ns-navy: #050B24;
            --ns-gold: #FFCE00;
            --ns-gold-dark: #DDAF00;
            --ns-orange: #F89318;
            --ns-white: #FFFFFF;
            --ns-offwhite: #F5F7FB;
            --ns-surface: #F3F6FC;
            --ns-border: rgba(148, 163, 184, 0.2);
            --ns-muted: #64748B;
            --ns-dark: #101522;
            --ns-text: #0f172a;
        }

        table.dataTable thead .sorting,
        table.dataTable thead .sorting_asc,
        table.dataTable thead .sorting_desc,
        table.dataTable thead .sorting_asc_disabled,
        table.dataTable thead .sorting_desc_disabled {
            position: relative;
            padding-right: 2.1rem;
        }

        table.dataTable thead .sorting:before,
        table.dataTable thead .sorting_asc:before,
        table.dataTable thead .sorting_desc:before,
        table.dataTable thead .sorting_asc_disabled:before,
        table.dataTable thead .sorting_desc_disabled:before {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            color: #4b5563;
        }

        table.dataTable thead .sorting:before {
            content: "\f0dc";
            opacity: 0.7;
        }

        table.dataTable thead .sorting_asc:before {
            content: "\f0de";
            color: #14369C;
        }

        table.dataTable thead .sorting_desc:before {
            content: "\f0dd";
            color: #14369C;
        }

        #docManagementTable {
            table-layout: auto;
            width: 100% !important;
            min-width: 0;
            min-height: 520px;
        }

        #docManagementTable th:nth-child(1),
        #docManagementTable td:nth-child(1) { width: 36px !important; }
        #docManagementTable th:nth-child(2),
        #docManagementTable td:nth-child(2) { width: 60px !important; }
        #docManagementTable th:nth-child(3),
        #docManagementTable td:nth-child(3) { width: 22% !important; }
        #docManagementTable th:nth-child(4),
        #docManagementTable td:nth-child(4) { width: 14% !important; }
        #docManagementTable th:nth-child(5),
        #docManagementTable td:nth-child(5) { width: 14% !important; }
        #docManagementTable th:nth-child(6),
        #docManagementTable td:nth-child(6) { width: 15% !important; }
        #docManagementTable th:nth-child(7),
        #docManagementTable td:nth-child(7) { width: 16% !important; }
        #docManagementTable th:nth-child(8),
        #docManagementTable td:nth-child(8) { width: 120px !important; }

        #docManagementTable thead th {
            background: #f3f6fc;
            color: #334155;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            vertical-align: middle;
        }

        #docManagementTable tbody tr:nth-child(even) {
            background: #f8faff;
        }

        #docManagementTable tbody tr:hover {
            background: #eef4ff;
        }

        #docManagementTable tbody td {
            line-height: 1.4;
            overflow-wrap: anywhere;
        }

        #docManagementTable th:last-child,
        #docManagementTable td:last-child {
            position: sticky;
            right: 0;
            background: inherit;
            z-index: 2;
            box-shadow: -1px 0 0 rgba(148, 163, 184, 0.2);
        }

        #docManagementTable_wrapper .table-responsive {
            max-width: 100%;
            overflow-x: auto;
            overscroll-behavior-x: contain;
            scrollbar-color: #9aa9c2 #edf1f7;
            scrollbar-width: thin;
        }

        .doc-management-toolbar {
            padding: 14px;
            background: #f7f9fc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }

        .doc-management-panel > .doc-management-heading {
            padding-bottom: 18px;
            margin-bottom: 18px;
            border-bottom: 1px solid #e2e8f0;
        }

        #docManagementTable thead th,
        #docManagementTable tbody td {
            height: 58px;
            vertical-align: middle;
            white-space: normal;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        #docManagementTable tbody tr {
            height: 58px;
        }

        #docManagementTable_wrapper .table-responsive {
            min-height: 520px;
        }

        * { box-sizing: border-box; }

        body {
            position: relative;
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(20,54,156,0.18), transparent 26%),
                radial-gradient(circle at 90% 12%, rgba(255,206,0,0.18), transparent 18%),
                linear-gradient(180deg, #ebf3ff 0%, #f8faff 38%, #edf3fa 100%);
            color: var(--ns-text);
        }

        body::before {
            content: "";
            position: fixed;
            inset: 8% 10% 8% 10%;
            background-image: url("img/neust_logo.png");
            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;
            opacity: 0.12;
            pointer-events: none;
            z-index: 0;
        }

        h1, h2, h3, h4, h5, h6, .navbar-brand, .offcanvas-title, .brand-title, .section-title, .hero-title {
            font-family: 'Manrope', 'Inter', sans-serif;
        }

        .dashboard-page .bg-custom {
            background: linear-gradient(135deg, var(--ns-blue), var(--ns-blue-deep)) !important;
            border-bottom: 3px solid var(--ns-gold) !important;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1040;
            box-shadow: 0 10px 26px rgba(8, 17, 50, 0.18);
            border-radius: 0 0 18px 18px;
        }

        body {
            padding-top: 72px;
        }

        /* Keep the fixed top nav from shifting when any modal opens/closes
           (Bootstrap hides the page scrollbar and pads .fixed-top to compensate). */
        html {
            overflow-y: scroll;
            scrollbar-gutter: stable;
        }

        .navbar.fixed-top,
        body.modal-open .navbar.fixed-top {
            padding-right: 0 !important;
        }

        .dashboard-page .header-nav .nav-link {
            flex-shrink: 0;
        }


        body.modal-open {
            padding-right: 0 !important;
        }

        .home-card-edit-form {
            display: flex;
            flex: 1 1 auto;
            flex-direction: column;
            min-height: 0;
        }

        .home-card-edit-form .modal-body {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
        }

        .feedback-toast {
            z-index: 1400;
        }

        .dashboard-page .brand-mark {
            width: 28px;
            height: 28px;
            object-fit: contain;
            filter: drop-shadow(0 4px 16px rgba(255,206,0,0.25));
        }

        .dashboard-page .navbar-brand {
            flex: 0 0 auto;
            font-size: 0.82rem;
            line-height: 1.2;
            letter-spacing: 0.02em;
            color: #fff !important;
        }

        .dashboard-page .header-nav {
            display: flex;
            align-items: center;
            gap: clamp(4px, 0.55vw, 8px);
            margin-left: 28px;
            background: rgba(8, 17, 50, 0.1);
            border-radius: 12px;
            padding: 4px 8px;
            flex: 1 1 auto;
            min-width: 0;
            overflow-x: auto;
            overflow-y: hidden;
            white-space: nowrap;
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior-x: contain;
        }

        .dashboard-page .header-nav::-webkit-scrollbar {
            display: none;
        }

        .dashboard-page .header-nav .nav-link {
            display: inline-flex;
            align-items: center;
            gap: clamp(4px, 0.55vw, 7px);
            min-height: 40px;
            padding: clamp(6px, 0.75vw, 12px);
            border: 1px solid transparent;
            border-radius: 10px;
            color: rgba(255,255,255,0.9) !important;
            background: transparent !important;
            font-weight: 700;
            font-size: clamp(0.52rem, 0.82vw, 0.72rem);
            white-space: nowrap;
            transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
            text-decoration: none;
            flex-shrink: 0;
        }

        .dashboard-page .header-nav .nav-link:hover,
        .dashboard-page .header-nav .nav-link.active {
            background: var(--ns-gold) !important;
            border-color: #ffe46b;
            color: #07184f !important;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.25);
        }

        .dashboard-page .header-nav .nav-link.active {
            border: 2px solid #fff1a8;
            box-shadow: 0 0 0 2px rgba(255, 206, 0, 0.45), inset 0 0 0 1px rgba(255,255,255,0.25);
        }

        .dashboard-page .header-nav .nav-link i {
            font-size: 0.82rem;
        }

        .dashboard-page .header-nav .nav-divider {
            width: 1px;
            height: 22px;
            background: rgba(255, 255, 255, 0.35);
            margin: 0 4px;
            align-self: center;
        }

        .dashboard-page .archive-subnav {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px;
            margin-bottom: 1.25rem;
            background: linear-gradient(180deg, rgba(236, 243, 255, 0.9), rgba(246, 249, 255, 0.96));
            border: 1px solid rgba(20, 54, 156, 0.12);
            border-radius: 14px;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
            width: fit-content;
        }

        .dashboard-page .archive-subnav .nav-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 0.7rem 1.1rem;
            border: 1px solid transparent;
            border-radius: 10px;
            background: transparent;
            color: #475467;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            transition: all 0.2s ease;
        }

        .dashboard-page .archive-subnav .nav-link:hover {
            background: rgba(20, 54, 156, 0.05);
            color: #07184f;
            border-color: rgba(20, 54, 156, 0.08);
        }

        .dashboard-page .archive-subnav .nav-link.active {
            background: linear-gradient(135deg, var(--ns-gold), #f6d75a);
            border-color: rgba(255, 255, 255, 0.7);
            color: var(--ns-blue-deep);
            box-shadow: 0 8px 18px rgba(255, 206, 0, 0.25), inset 0 0 0 1px rgba(255,255,255,0.3);
        }

        .dashboard-page .archive-subnav .nav-link i {
            font-size: 0.8rem;
        }

        .document-portal {
            display: block;
            width: 100%;
            background: transparent;
            padding: 0;
        }

        .document-header-card {
            background: linear-gradient(135deg, var(--ns-blue), var(--ns-blue-deep));
            color: white;
            border-radius: 20px;
            min-height: 72px;
            padding: 12px 24px;
            box-shadow: 0 18px 40px rgba(9, 17, 44, 0.12);
            display: flex;
            align-items: center;
            margin-bottom: 14px;
        }

        .document-header-card h2 {
            margin: 0;
            font-weight: 800;
            line-height: 1.25;
            font-size: clamp(1.15rem, 1.6vw, 1.55rem);
        }

        .document-shell {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 320px;
            gap: 24px;
            align-items: start;
        }

        .document-legend {
            grid-column: 2;
            position: sticky;
            top: 84px;
            width: 100%;
            max-height: calc(100vh - 96px); /* safety net on very short screens */
            overflow-y: auto;
            background: rgba(255, 255, 255, 0.68);
            border: 1px solid rgba(20, 54, 156, 0.08);
            border-radius: 18px;
            padding: 12px 12px;
            box-shadow: 0 10px 26px rgba(20, 54, 156, 0.05);
        }

        .document-legend-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
        }

        .document-legend-title {
            margin: 0;
            color: var(--ns-blue-deep);
            font-size: 0.96rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-weight: 800;
        }

        .document-legend-title i {
            color: var(--ns-blue);
            margin-right: 6px;
        }

        .document-legend-items {
            display: grid;
            gap: 5px;
        }

        .document-legend-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            color: var(--ns-text);
            font-size: 0.82rem;
            font-weight: 700;
            line-height: 1.3;
            padding: 6px 10px;
            margin: 0;
            border-radius: 10px;
            border: 1px solid rgba(20, 54, 156, 0.12);
            text-decoration: none;
            transition: background 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
        }

        .document-legend-item:hover,
        .document-legend-item.active {
            background: rgba(20, 54, 156, 0.04);
            color: var(--ns-blue-deep);
            border-color: var(--legend-color, var(--ns-blue));
            transform: translateX(3px);
        }

        .document-legend-item.active {
            box-shadow: inset 4px 0 0 var(--legend-color, var(--ns-blue));
        }

        .document-legend-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            flex: 0 0 13px;
            background: var(--legend-color, var(--ns-blue));
            box-shadow: 0 0 0 3px rgba(20, 54, 156, 0.08);
            margin-top: 2px;
        }

        .document-main {
            grid-column: 1;
            min-width: 0;
        }

        .document-results-status {
            margin: 0 0 12px;
            color: var(--ns-muted);
            font-size: 0.86rem;
        }

        .document-search-form {
            width: 100%;
            max-width: 760px;
            margin: 0 0 14px;
        }

        .document-search-form .input-group {
            position: relative;
            min-height: 46px;
            border-radius: 10px;
            box-shadow: 0 4px 14px rgba(15, 35, 76, 0.06);
        }

        .document-poi-suggestions {
            position: absolute;
            top: calc(100% + 5px);
            left: 48px;
            right: 0;
            z-index: 1100;
            display: none;
            max-height: 320px;
            overflow-y: auto;
            padding: 4px;
            background: #fff;
            border: 1px solid #d6deeb;
            border-radius: 10px;
            box-shadow: 0 12px 28px rgba(15, 35, 76, 0.16);
        }

        .document-poi-suggestion {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            gap: 12px;
            padding: 9px 11px;
            border: 0;
            border-radius: 7px;
            color: #1f2937;
            background: transparent;
            text-align: left;
        }

        .document-poi-suggestion:hover,
        .document-poi-suggestion:focus,
        .document-poi-suggestion.active {
            color: #14369c;
            background: #eef4ff;
            outline: none;
        }

        .document-poi-suggestion-type {
            flex: 0 0 auto;
            color: #64748b;
            font-size: 0.72rem;
        }

        .document-search-form .input-group-text {
            padding-left: 14px;
            color: #64748b;
            background: #fff;
            border-color: #d6deeb;
            border-radius: 10px 0 0 10px;
        }

        .document-search-form .form-control {
            min-width: 0;
            min-height: 46px;
            border-color: #d6deeb;
            box-shadow: none;
        }

        .document-search-form .form-control:focus {
            border-color: #3156ad;
            box-shadow: 0 0 0 3px rgba(49, 86, 173, 0.12);
        }

        .document-search-form .btn {
            padding-inline: 18px;
            font-weight: 700;
        }

        .document-search-form .btn-outline-secondary {
            border-color: #d6deeb;
        }

        .document-pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin: 18px 0 4px;
            flex-wrap: wrap;
        }

        .document-pagination-top {
            justify-content: flex-end;
            margin-top: 0;
            margin-bottom: 16px;
        }

        .campus-overview-panel {
            background: linear-gradient(145deg, rgba(255,255,255,0.98), rgba(244,248,255,0.96));
            border: 1px solid rgba(20, 54, 156, 0.12);
            box-shadow: 0 12px 28px rgba(20, 54, 156, 0.08);
        }

        .campus-building-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0 22px;
            margin: 0 0 16px;
            padding: 0;
            list-style: none;
            border-top: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
        }

        .campus-building-list li {
            padding: 10px 2px;
            color: #24324a;
            font-size: 0.9rem;
            font-weight: 700;
            line-height: 1.35;
        }

        .ticket-queue-card {
            overflow: hidden;
            border: 1px solid #dce4ef;
            border-radius: 12px;
            box-shadow: 0 10px 28px rgba(15, 35, 76, 0.07);
        }

        .ticket-queue-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 20px 22px;
            border-bottom: 1px solid #e5ebf3;
            background: #fff;
        }

        .ticket-queue-title {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .ticket-queue-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            color: #14369c;
            background: #edf3ff;
            border-radius: 10px;
        }

        .ticket-queue-heading {
            margin: 0;
            color: #17233a;
            font-size: 1.12rem;
            font-weight: 800;
            line-height: 1.25;
        }

        .ticket-queue-subtitle {
            margin: 3px 0 0;
            color: #64748b;
            font-size: 0.82rem;
        }

        .ticket-total-count {
            flex: 0 0 auto;
            padding: 7px 10px;
            color: #334155;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .ticket-queue-body {
            padding: 16px 20px 20px;
            background: #fff;
        }

        .ticket-filter-bar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
            margin-bottom: 16px;
            padding: 5px;
            background: #f5f7fb;
            border: 1px solid #e5eaf2;
            border-radius: 10px;
        }

        .ticket-filter-bar .ticket-filter-button {
            min-height: 36px;
            padding: 6px 10px;
            border-color: transparent;
            border-radius: 7px !important;
            font-size: 0.82rem;
            font-weight: 650;
        }

        .ticket-filter-bar .ticket-filter-button.active {
            box-shadow: 0 1px 3px rgba(15, 35, 76, 0.14);
        }

        #feedbackTicketsTable {
            border-collapse: separate;
            border-spacing: 0;
        }

        #feedbackTicketsTable thead th {
            padding: 11px 12px;
            color: #64748b;
            background: #f8fafc;
            border-top: 1px solid #e5eaf2;
            border-bottom: 1px solid #e5eaf2;
            font-size: 0.71rem;
            font-weight: 800;
            letter-spacing: 0.045em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        #feedbackTicketsTable tbody td {
            padding: 14px 12px;
            color: #334155;
            border-bottom: 1px solid #edf1f6;
            vertical-align: middle;
        }

        #feedbackTicketsTable tbody tr:hover td {
            background: #f8faff;
        }

        #feedbackTicketsTable .ticket-message-cell {
            min-width: 240px;
            max-width: 420px;
            color: #475569;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        #feedbackTicketsTable .ticket-code {
            color: #14369c;
            font-weight: 800;
            white-space: nowrap;
        }

        .ticket-action-group form {
            display: inline-flex;
            margin: 0;
        }

        .ticket-action-group .btn {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border-radius: 7px !important;
        }

        @media (max-width: 480px) {
            .campus-building-list {
                grid-template-columns: 1fr;
            }

            .ticket-queue-header {
                align-items: flex-start;
                padding: 16px;
            }

            .ticket-queue-body {
                padding: 12px;
            }

            .ticket-filter-bar {
                gap: 4px;
            }
        }

        .document-page-link {
            min-width: 54px;
            min-height: 52px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            border: 1px solid rgba(20,54,156,0.24);
            background: rgba(255,255,255,0.86);
            color: var(--ns-blue-deep);
            text-decoration: none;
            padding: 8px 14px;
            font-size: 1rem;
            font-weight: 800;
            box-shadow: 0 4px 10px rgba(20,54,156,0.04);
            transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }

        .document-page-link:hover {
            background: #e8efff;
            border-color: var(--ns-blue);
            color: var(--ns-blue-deep);
            transform: translateY(-1px);
        }

        .document-page-link.active {
            background: var(--ns-blue);
            border-color: var(--ns-blue);
            color: white;
            box-shadow: 0 8px 16px rgba(20,54,156,0.2);
        }

        .document-process-list {
            margin-top: 20px;
            display: grid;
            grid-template-columns: repeat(2, minmax(260px, 1fr));
            gap: 22px;
        }

        .document-process-card {
            width: 100%;
            appearance: none;
            -webkit-appearance: none;
            font: inherit;
            background: rgba(255,255,255,0.68);
            border: 2px solid color-mix(in srgb, var(--legend-color) 42%, white);
            border-radius: 18px;
            padding: 12px 14px 12px;
            box-shadow: 0 8px 22px rgba(20, 54, 156, 0.05);
            min-height: 150px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: space-between;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            text-decoration: none;
            color: inherit;
            cursor: pointer;
            text-align: left;
        }

        .document-process-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 24px rgba(20, 54, 156, 0.08);
        }

        .guest-document-card {
            display: grid;
            grid-template-rows: minmax(0, 1fr) auto;
            gap: 9px;
            min-width: 0;
        }

        .guest-document-actions,
        .guest-document-request-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .guest-document-actions {
            padding: 0 2px;
        }

        .guest-document-select {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            color: var(--ns-muted);
            font-size: 0.82rem;
            font-weight: 700;
        }

        .guest-document-select input {
            width: 18px;
            height: 18px;
            accent-color: var(--ns-blue);
        }

        .guest-document-request-toolbar {
            flex-wrap: wrap;
            margin: 14px 0 4px;
            padding: 12px 14px;
            border: 1px solid rgba(20, 54, 156, 0.14);
            border-radius: 10px;
            background: #f7f9ff;
        }

        .guest-document-request-toolbar .btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .guest-document-feedback {
            display: none;
            width: 100%;
            margin: 0;
            font-size: 0.86rem;
        }

        .guest-document-feedback.visible {
            display: block;
        }

        .request-submit-toast {
            position: fixed;
            top: 88px;
            right: 24px;
            z-index: 3000;
            width: min(390px, calc(100vw - 32px));
            padding: 14px 16px;
            border: 1px solid #86efac;
            border-radius: 8px;
            background: #dcfce7;
            color: #166534;
            box-shadow: 0 14px 30px rgba(7, 24, 79, 0.2);
            font-size: 0.9rem;
            font-weight: 700;
            line-height: 1.45;
        }

        .request-submit-toast.error {
            border-color: #fca5a5;
            background: #fee2e2;
            color: #991b1b;
        }

        .admin-request-toast {
            position: fixed;
            right: 18px;
            bottom: 18px;
            z-index: 2200;
            display: none;
            max-width: min(360px, calc(100vw - 36px));
            padding: 13px 16px;
            border-radius: 8px;
            background: #112548;
            color: #fff;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.22);
            font-weight: 700;
        }

        .admin-request-toast.visible {
            display: block;
        }

        @media (max-width: 560px) {
            .guest-document-request-toolbar,
            .guest-document-actions {
                align-items: flex-start;
                flex-direction: column;
            }
        }

        .document-process-card-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            background: color-mix(in srgb, var(--legend-color) 13%, white);
            color: var(--legend-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
            border: 1px solid color-mix(in srgb, var(--legend-color) 28%, white);
            margin-bottom: 10px;
        }

        .document-process-card-content {
            width: 100%;
            min-width: 0;
        }

        .document-process-title {
            color: var(--ns-blue-deep);
            font-size: clamp(0.95rem, 1.2vw, 1.25rem);
            font-weight: 800;
            margin: 0 0 6px;
            line-height: 1.18;
            letter-spacing: 0.02em;
        }

        .document-process-description {
            color: var(--ns-muted);
            font-size: 0.85rem;
            line-height: 1.35;
            margin: 0;
        }

        .document-process-cta {
            margin-top: 10px;
            display: inline-block;
            color: var(--ns-blue);
            text-decoration: none;
            font-weight: 800;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            font-size: 0.78rem;
        }

        .document-process-cta i {
            margin-left: 8px;
        }

        .document-empty-state {
            background: white;
            border: 1px solid rgba(15,23,42,0.08);
            border-radius: 18px;
            padding: 30px 24px;
            color: var(--ns-muted);
            text-align: center;
            margin-top: 24px;
        }

        .document-empty-state h4 {
            font-weight: 800;
            color: var(--ns-blue-deep);
        }

        @media (max-width: 992px) {
            .document-shell {
                grid-template-columns: 1fr;
            }

            .document-main,
            .document-legend {
                grid-column: 1;
            }

            .document-main {
                width: 100%;
            }

            .document-legend {
                position: static;
                width: auto;
                height: auto;
                max-height: none;
                margin-bottom: 20px;
                border-radius: 16px;
                box-shadow: none;
                overflow: visible;
            }
        }

        @media (max-width: 768px) {
            .document-process-list {
                grid-template-columns: 1fr;
            }
        }

        .smart-campus-body {
            background: linear-gradient(180deg, #eef4ff 0%, #f6f9ff 24%, #edf3ff 100%);
        }

        .table .form-check-input.select-all-checkbox,
        .table .form-check-input.user-select-checkbox {
            width: 1.05rem;
            height: 1.05rem;
            border: 2px solid #94a3b8;
            background-color: #ffffff;
            box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.04);
        }

        .table .form-check-input.select-all-checkbox:checked,
        .table .form-check-input.user-select-checkbox:checked {
            border-color: #14369C;
            background-color: #14369C;
            box-shadow: 0 0 0 0.15rem rgba(20, 54, 156, 0.12);
        }

        .offcanvas {
            background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(244,247,255,0.96));
            border-right: 1px solid rgba(20,54,156,0.08);
        }

        .offcanvas .nav-pills .nav-link {
            color: #0f172a;
            background: rgba(20,54,156,0.03);
            border: 1px solid rgba(20,54,156,0.08);
            border-radius: 12px;
            font-weight: 700;
        }

        .offcanvas .nav-pills .nav-link.active {
            background: linear-gradient(90deg, rgba(255,206,0,0.18), rgba(20,54,156,0.08));
            border-color: rgba(255,206,0,0.52);
            color: #0d1a2f;
            box-shadow: inset 0 0 0 1px rgba(255,206,0,0.1);
        }

        .offcanvas .nav-pills .nav-link i {
            color: var(--ns-blue);
        }

        .campus-shell {
            display: flex;
            min-height: calc(100vh - 72px);
            max-width: 100%;
            margin: 0;
            padding: 0 10px 18px 10px;
            gap: 10px;
            align-items: stretch;
        }

        .premium-sidebar {
            width: 300px;
            background:
                linear-gradient(180deg, #14369c 0%, #0b246f 56%, #07184f 100%);
            border: 1px solid rgba(255,255,255,0.18);
            border-radius: 18px 18px 20px 20px;
            padding: 0 10px 12px;
            box-shadow: 0 18px 34px -24px rgba(7,24,79,0.8);
            border-top: 4px solid var(--ns-gold);
            display: flex;
            flex-direction: column;
            height: calc(100vh - 90px);
            margin-top: 8px;
            position: sticky;
            top: 82px;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 2;
        }

        .premium-sidebar .sidebar-group:first-of-type {
            position: sticky;
            top: 0;
            z-index: 3;
        }

        .premium-sidebar::before {
            content: "";
            position: absolute;
            left: 0;
            right: 0;
            top: 0;
            height: 4px;
            background: var(--ns-gold);
            z-index: 1;
        }

        .premium-sidebar::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(255,255,255,0.1), rgba(255,255,255,0));
            pointer-events: none;
            z-index: 0;
        }

        .premium-sidebar .sidebar-group {
            position: relative;
            z-index: 1;
        }

        .premium-sidebar .brand-block {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            min-height: 112px;
            padding: 18px 12px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            margin-bottom: 18px;
        }

        .premium-sidebar .brand-logo {
            width: 62px;
            height: 62px;
            object-fit: contain;
            border-radius: 50%;
            background: rgba(255,255,255,0.08);
            border: 3px solid rgba(255,255,255,0.38);
            box-shadow: inset 0 0 0 4px rgba(255,255,255,0.06);
            padding: 8px;
            flex-shrink: 0;
        }

        .premium-sidebar .brand-copy {
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .premium-sidebar .brand-title {
            font-size: 2.2rem;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            color: var(--ns-white);
            margin: 0;
            font-weight: 800;
            line-height: 1;
        }

        .premium-sidebar .brand-tag {
            display: block;
            margin-top: 2px;
            color: rgba(255,255,255,0.88);
            letter-spacing: 0.06em;
            font-size: 0.72rem;
            text-transform: uppercase;
            line-height: 1.2;
            font-weight: 700;
        }

        .sidebar-nav-stack {
            position: sticky;
            top: 0;
            z-index: 3;
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-top: 10px;
            margin-bottom: 18px;
            background: rgba(7,24,79,0.22);
            backdrop-filter: blur(6px);
            padding-bottom: 4px;
        }

        .sidebar-nav-stack .nav-pills {
            gap: 12px;
        }

        .sidebar-brand-footer {
            position: relative;
            z-index: 1;
            margin-top: 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 18px 12px 10px;
            border-top: 1px solid rgba(255,255,255,0.08);
            min-height: 240px;
        }

        .sidebar-brand-footer .brand-logo {
            width: 110px;
            height: 110px;
            margin-bottom: 12px;
            display: block;
            border-radius: 50%;
            border: 3px solid rgba(255,255,255,0.36);
            background: rgba(255,255,255,0.08);
            padding: 12px;
        }

        .sidebar-brand-footer .brand-title {
            font-size: 3.1rem;
            letter-spacing: 0.02em;
            line-height: 1;
            margin: 0;
            font-weight: 800;
        }

        .sidebar-brand-footer .brand-tag {
            margin-top: 8px;
            font-size: 0.8rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.85);
            font-weight: 700;
        }

        .sidebar-group {
            padding: 8px 0 0;
            flex: 1;
        }

        .sidebar-label {
            display: block;
            margin: 10px 10px 8px;
            color: rgba(255,255,255,0.72);
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .nav-pills {
            background: transparent;
            border-radius: 0;
            padding: 0;
            gap: 12px;
        }

        .premium-sidebar .nav-pills .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            min-height: 58px;
            background: #1d4ed8;
            border: 1px solid #6f9bff;
            border-radius: 12px;
            color: rgba(255,255,255,0.94);
            font-weight: 700;
            padding: 14px 16px;
            transition: all 0.2s ease;
            text-align: left;
            font-size: 1.05rem;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.12), 0 6px 14px rgba(5,11,36,0.14);
            letter-spacing: 0.01em;
            backdrop-filter: blur(4px);
        }

        .premium-sidebar .nav-pills .nav-link i {
            width: 20px;
            text-align: center;
            color: rgba(255,255,255,0.9);
            font-size: 1.15rem;
        }

        .premium-sidebar .nav-pills .nav-link:hover {
            background: #f6c400;
            border-color: #ffe46b;
            color: #07184f;
            box-shadow: 0 8px 18px rgba(5,11,36,0.18);
        }

        .premium-sidebar .nav-pills .nav-link:hover i {
            color: #07184f;
        }

        .premium-sidebar .nav-pills .nav-link.active {
            background: linear-gradient(180deg, rgba(255, 206, 0, 0.95), rgba(255, 193, 0, 0.9));
            border-color: rgba(255,206,0,0.9);
            color: #0d1d4a;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.4), 0 8px 14px rgba(0,0,0,0.16);
            font-weight: 800;
        }

        .premium-sidebar .nav-pills .nav-link.active i {
            color: #0d1d4a;
        }

        .campus-content {
            position: relative;
            z-index: 1;
            flex: 1;
            min-width: 0;
            display: flex;
            min-height: 0;
        }

        .tab-content {
            width: 100%;
            height: 100%;
            display: flex;
            flex: 1 1 auto;
            min-height: 0;
        }

        .tab-content > .tab-pane {
            width: 100%;
            height: 100%;
            min-height: 0;
        }

        .tab-content > .tab-pane > section,
        .tab-content > .tab-pane > .dashboard-grid,
        .tab-content > .tab-pane > .document-portal {
            height: 100%;
        }

        /* Home tab is two stacked sections; let them size to their content
           instead of each stretching to the full tab height. */
        #pills-landing,
        #pills-landing > section {
            height: auto;
        }

        .smart-home {
            padding: 0 2px;
        }

        .hero-panel {
            position: relative;
            overflow: hidden;
            border-radius: 28px;
            padding: 28px 28px 24px;
            background:
                radial-gradient(circle at 15% 10%, rgba(255,206,0,0.18), transparent 28%),
                linear-gradient(135deg, var(--ns-blue-deep) 0%, var(--ns-blue) 54%, var(--ns-navy) 100%);
            color: var(--ns-white);
            border: 1px solid rgba(255,255,255,0.08);
            box-shadow: 0 24px 50px -22px rgba(5,11,36,0.8);
            margin-bottom: 24px;
        }

        .hero-panel::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px);
            background-size: 28px 28px;
            opacity: 0.7;
            pointer-events: none;
        }

        .hero-panel::after {
            content: "";
            position: absolute;
            width: 300px;
            height: 300px;
            right: -80px;
            top: -80px;
            border-radius: 50%;
            border: 1px dashed rgba(255,206,0,0.24);
            pointer-events: none;
        }

        .hero-inner {
            position: relative;
            z-index: 1;
        }

        .hero-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px;
            border-radius: 999px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            color: rgba(255,255,255,0.88);
            font-size: 0.72rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .hero-title {
            margin: 18px 0 10px;
            font-size: clamp(2.1rem, 3vw, 3.2rem);
            line-height: 1.08;
            font-weight: 800;
            max-width: 700px;
        }

        .hero-title .accent {
            color: var(--ns-gold);
        }

        .hero-subcopy {
            margin: 0 0 24px;
            max-width: 660px;
            color: rgba(255,255,255,0.7);
            font-size: 1rem;
        }

        .smart-search {
            display: flex;
            align-items: center;
            gap: 12px;
            max-width: 700px;
            background: rgba(255,255,255,0.96);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 18px;
            padding: 10px 10px 10px 18px;
            box-shadow: 0 14px 38px -22px rgba(0,0,0,0.45);
        }

        .smart-search i {
            color: var(--ns-blue);
            font-size: 1.1rem;
        }

        .smart-search input {
            border: none;
            outline: none;
            flex: 1;
            background: transparent;
            color: var(--ns-text);
            font-size: 1rem;
            min-width: 0;
        }

        .smart-search .btn-search {
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--ns-gold), #ffd84a);
            color: #0d1a2f;
            padding: 12px 18px;
            font-weight: 800;
            letter-spacing: 0.04em;
        }

        .search-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 18px;
        }

        .search-tags .chip {
            padding: 7px 12px;
            border-radius: 999px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            color: rgba(255,255,255,0.9);
            font-size: 0.78rem;
        }

        .section-title {
            margin: 0 0 4px;
            font-size: clamp(1.5rem, 2vw, 2rem);
            font-weight: 800;
            color: #10203e;
        }

        .section-subtitle {
            margin: 0 0 18px;
            color: var(--ns-muted);
            font-size: 0.96rem;
        }

        .action-layout {
            margin: 26px 0 28px;
        }

        .smart-card {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 14px;
            min-height: 170px;
            padding: 20px 18px 16px;
            border-radius: 22px;
            background: linear-gradient(180deg, rgba(255,255,255,0.92), rgba(244,247,252,0.88));
            border: 1px solid rgba(15, 23, 42, 0.06);
            box-shadow: 0 18px 32px -28px rgba(13,26,47,0.6);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            color: var(--ns-text);
            text-decoration: none;
            cursor: pointer;
        }

        .smart-card:hover {
            transform: translateY(-4px);
            border-color: rgba(255,206,0,0.6);
            box-shadow: 0 20px 32px -26px rgba(20,54,156,0.5);
        }

        .search-tags a {
            text-decoration: none;
        }

        .smart-card .icon-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 58px;
            height: 58px;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(20,54,156,0.12), rgba(255,206,0,0.18));
            color: var(--ns-blue);
            font-size: 1.5rem;
            box-shadow: inset 0 0 0 1px rgba(20,54,156,0.04);
        }

        .smart-card h4 {
            margin: 0;
            font-size: 1rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            font-weight: 800;
        }

        .smart-card p {
            margin: 0;
            color: var(--ns-muted);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .smart-card .cta {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--ns-blue);
            padding-top: 2px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1.5fr 0.9fr;
            gap: 22px;
            margin-bottom: 22px;
        }

        .glass-panel {
            background: rgba(255,255,255,0.78);
            border-radius: 26px;
            border: 1px solid rgba(15,23,42,0.06);
            box-shadow: 0 20px 35px -26px rgba(13,26,47,0.55);
            padding: 20px 20px 18px;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            position: relative;
            overflow: hidden;
        }

        .glass-panel::before {
            content: "";
            position: absolute;
            inset: 0 auto auto 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, var(--ns-blue), var(--ns-gold), transparent);
            opacity: 0.9;
        }

        .panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            gap: 12px;
        }

        .panel-head .label {
            color: var(--ns-blue);
            font-size: 0.72rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            font-weight: 800;
        }

        .panel-head .action-link {
            color: var(--ns-blue);
            font-weight: 700;
            text-decoration: none;
            font-size: 0.8rem;
        }

        .map-maximize-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(20,54,156,0.2);
            border-radius: 10px;
            background: rgba(255,255,255,0.86);
            color: var(--ns-blue-deep);
            padding: 8px 11px;
            font-weight: 800;
            cursor: pointer;
            transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }

        .map-maximize-btn:hover {
            background: var(--ns-blue);
            color: white;
            transform: translateY(-1px);
        }

        #pills-home {
            /* 72px fixed navbar + 18px bottom padding of .campus-shell */
            height: calc(100vh - 90px);
            height: calc(100dvh - 90px);
            min-height: 420px;
        }

        #pills-home .dashboard-grid,
        #pills-home .dashboard-grid > .glass-panel {
            height: 100%;
            min-height: 0;
        }

        #map-preview-shell {
            position: relative;
            width: 100%;
            height: 100%;
            min-height: 0;
            overflow: hidden;
            border-radius: 18px;
            background: #0b1633;
        }

        #pills-home .glass-panel {
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        #map-preview-shell.is-maximized {
            position: fixed !important;
            inset: 0 !important;
            z-index: 1800;
            min-height: 100vh !important;
            border-radius: 0 !important;
            background: #0b1633 !important;
        }

        #map-preview-shell:fullscreen {
            width: 100vw !important;
            height: 100vh !important;
            min-height: 100vh !important;
            border-radius: 0 !important;
            background: #0b1633 !important;
        }

        #map-preview-shell.is-maximized iframe,
        #map-preview-shell:fullscreen iframe {
            width: 100% !important;
            height: 100vh !important;
            min-height: 100vh !important;
        }

        #map-preview-shell iframe {
            display: block;
            width: 100%;
            height: 100%;
            min-height: 0;
            border: 0;
            background: #fff;
        }

        .campus-preview {
            position: relative;
            min-height: 260px;
            border-radius: 20px;
            background:
                linear-gradient(180deg, rgba(5,11,36,0.94), rgba(7,24,79,0.9)),
                radial-gradient(circle at center, rgba(255,206,0,0.1), transparent 50%);
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.08);
        }

        .campus-preview::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px);
            background-size: 26px 26px;
            opacity: 0.6;
        }

        .map-node {
            position: absolute;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--ns-gold);
            box-shadow: 0 0 0 4px rgba(255,206,0,0.17);
        }

        .map-node.blue { background: #61a6ff; box-shadow: 0 0 0 4px rgba(97,166,255,0.15); }
        .map-node.orange { background: var(--ns-orange); box-shadow: 0 0 0 4px rgba(248,147,24,0.15); }

        .campus-path {
            position: absolute;
            inset: 0;
        }

        .campus-path svg {
            width: 100%;
            height: 100%;
        }

        .path-line {
            fill: none;
            stroke: var(--ns-gold);
            stroke-width: 3;
            stroke-linecap: round;
            stroke-dasharray: 10 12;
            opacity: 0.9;
        }

        .path-line.alt { stroke: rgba(97,166,255,0.8); stroke-width: 2.5; }

        .campus-preview .destination-badge {
            position: absolute;
            right: 18px;
            top: 18px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255,206,0,0.14);
            border: 1px solid rgba(255,206,0,0.3);
            color: #ffe882;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .campus-preview .destination-badge .pin {
            color: var(--ns-gold);
            margin-right: 6px;
        }

        .next-step-card {
            display: flex;
            flex-direction: column;
            height: 100%;
            background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(245,247,251,0.96));
            border-radius: 24px;
            border: 1px solid rgba(15,23,42,0.05);
            padding: 18px 18px 16px;
            min-height: 260px;
        }

        .mini-label {
            color: var(--ns-blue);
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .next-step-card h3 {
            margin: 14px 0 10px;
            font-size: 1.2rem;
            line-height: 1.35;
            color: #141d33;
        }

        .step-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            align-self: flex-start;
            padding: 7px 10px;
            border-radius: 999px;
            background: rgba(20,54,156,0.08);
            color: var(--ns-blue);
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .progress-bar {
            height: 10px;
            background: rgba(15,23,42,0.06);
            border-radius: 999px;
            overflow: hidden;
            margin: 16px 0 14px;
        }

        .progress-bar span {
            display: block;
            height: 100%;
            width: 45%;
            background: linear-gradient(90deg, var(--ns-gold), var(--ns-orange));
            border-radius: inherit;
        }

        .next-step-card .meta {
            color: var(--ns-muted);
            font-size: 0.88rem;
            line-height: 1.6;
        }

        .primary-btn,
        .secondary-btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: fit-content;
            border-radius: 12px;
            padding: 10px 14px;
            font-weight: 800;
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .primary-btn {
            background: linear-gradient(135deg, var(--ns-gold), #ffd84a);
            color: #0d1a2f;
            box-shadow: 0 10px 18px -14px rgba(255,206,0,0.8);
        }

        .secondary-btn {
            border: 1px solid rgba(20,54,156,0.12);
            color: var(--ns-blue);
            background: rgba(20,54,156,0.03);
        }

        .primary-btn:hover, .secondary-btn:hover {
            transform: translateY(-1px);
        }

        .result-panel {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .results-card {
            background: rgba(255,255,255,0.84);
            border-radius: 22px;
            border: 1px solid rgba(15,23,42,0.06);
            padding: 18px 18px 16px;
        }

        .results-card h4 {
            margin: 0 0 12px;
            color: #101a2d;
            font-size: 0.9rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            font-weight: 800;
        }

        .result-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .result-list li {
            display: grid;
            grid-template-columns: 22px 1fr auto;
            align-items: center;
            gap: 10px;
            padding: 10px 10px;
            border-radius: 12px;
            background: rgba(245,247,251,0.9);
            border: 1px solid rgba(15,23,42,0.04);
        }

        .result-list .type {
            width: 22px;
            height: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 0.8rem;
            background: rgba(20,54,156,0.08);
            color: var(--ns-blue);
        }

        .result-list .detail strong {
            display: block;
            font-size: 0.95rem;
            color: var(--ns-text);
        }

        .result-list .detail span {
            color: var(--ns-muted);
            font-size: 0.76rem;
        }

        .badge-tag {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            padding: 4px 8px;
            border-radius: 999px;
            color: var(--ns-blue);
            background: rgba(20,54,156,0.08);
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.04em;
        }

        table.alternating-row-striping tbody > tr:nth-child(odd),
        table.alternating-row-striping.dataTable tbody > tr.odd,
        table.alternating-row-striping tbody > tr.odd > td {
            background-color: #ffffff !important;
        }

        table.alternating-row-striping tbody > tr:nth-child(even),
        table.alternating-row-striping.dataTable tbody > tr.even,
        table.alternating-row-striping tbody > tr.even > td {
            background-color: #f3f4f6 !important;
        }

        .card-stack {
            display: grid;
            gap: 16px;
        }

        .user-menu {
            position: relative;
            flex-shrink: 0;
        }

        .user-menu .btn-profile {
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.14);
            color: #fff;
            border-radius: 999px;
            padding: clamp(6px, 0.75vw, 10px) clamp(10px, 1vw, 16px);
            display: inline-flex;
            align-items: center;
            gap: clamp(6px, 0.7vw, 10px);
            font-weight: 700;
            font-size: clamp(0.62rem, 0.82vw, 0.82rem);
            white-space: nowrap;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.05);
        }

        .user-menu .btn-profile span {
            display: inline-block;
            max-width: 16ch;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: middle;
        }

        .user-menu .btn-profile:hover,
        .user-menu .btn-profile:focus {
            background: rgba(255,255,255,0.12);
            color: #fff;
        }

        .user-menu .dropdown-menu {
            margin-top: 10px;
            border: none;
            border-radius: 14px;
            box-shadow: 0 18px 38px rgba(14, 22, 43, 0.18);
            padding: 8px;
        }

        .user-menu .dropdown-item {
            border-radius: 10px;
            padding: 10px 12px;
            font-weight: 600;
            color: var(--ns-text);
        }

        .user-menu .dropdown-item:hover {
            background: rgba(20,54,156,0.06);
            color: var(--ns-blue);
        }

        .user-menu .dropdown-divider {
            margin: 6px 0;
        }

        .site-footer {
            position: relative;
            margin-top: 18px;
            background: linear-gradient(180deg, rgba(9, 17, 44, 0.96), rgba(6, 12, 28, 1));
            border-top: 3px solid var(--ns-gold);
            color: rgba(255,255,255,0.85);
            box-shadow: 0 -10px 20px rgba(7, 17, 44, 0.12);
        }

        .site-footer-inner {
            max-width: 1280px;
            margin: 0 auto;
            padding: 16px 20px 8px;
        }

        .site-footer-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 16px;
        }

        .footer-brand {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }

        .footer-brand img {
            width: 32px;
            height: 32px;
            object-fit: contain;
        }

        .footer-brand h3 {
            margin: 0;
            color: #fff;
            font-size: 1rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .footer-copy {
            margin: 0;
            color: rgba(255,255,255,0.72);
            line-height: 1.4;
            max-width: 420px;
            font-size: 0.82rem;
        }

        .footer-heading {
            margin: 0 0 7px;
            color: var(--ns-gold);
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .footer-links,
        .footer-contact {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 5px;
        }

        .footer-links a,
        .footer-contact a,
        .footer-contact span {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 0.82rem;
        }

        .footer-links a:hover,
        .footer-contact a:hover {
            color: var(--ns-gold);
        }

        .feedback-box {
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 16px 18px;
            border-radius: 18px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
        }

        .feedback-box p {
            margin: 0;
            color: rgba(255,255,255,0.72);
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .feedback-box a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: fit-content;
            padding: 10px 14px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--ns-gold), #ffd84a);
            color: #0d1a2f;
            font-weight: 800;
            text-decoration: none;
        }

        .feedback-open-btn {
            display: inline-flex;
            align-items: center;
            border: 0;
            border-radius: 10px;
            background: var(--ns-gold);
            color: #0d1a2f;
            padding: 9px 13px;
            font-weight: 800;
            cursor: pointer;
        }

        .feedback-open-btn:hover {
            background: #ffd84a;
        }

        .feedback-form {
            display: grid;
            gap: 10px;
        }

        .feedback-form input,
        .feedback-form textarea {
            width: 100%;
            border: 1px solid rgba(255,255,255,0.16);
            border-radius: 10px;
            background: rgba(255,255,255,0.08);
            color: white;
            padding: 9px 10px;
            font: inherit;
        }

        .feedback-form input::placeholder,
        .feedback-form textarea::placeholder {
            color: rgba(255,255,255,0.62);
        }

        .feedback-form textarea {
            min-height: 84px;
            resize: vertical;
        }

        .feedback-form button {
            width: fit-content;
            border: 0;
            border-radius: 10px;
            background: var(--ns-gold);
            color: #0d1a2f;
            padding: 9px 13px;
            font-weight: 800;
            cursor: pointer;
        }

        #feedbackModal .feedback-form input,
        #feedbackModal .feedback-form textarea {
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #0f172a;
        }

        #feedbackModal .feedback-form input::placeholder,
        #feedbackModal .feedback-form textarea::placeholder {
            color: #64748b;
        }

        #feedbackModal .feedback-form input:focus,
        #feedbackModal .feedback-form textarea:focus {
            border-color: #2563eb;
            outline: 3px solid rgba(37, 99, 235, 0.14);
        }

        #feedbackModal .modal-body {
            padding: 20px;
        }

        #feedbackModal .feedback-status,
        #feedbackModal .feedback-error {
            margin: 0 0 16px;
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 0.9rem;
            line-height: 1.45;
            font-weight: 600;
        }

        #feedbackModal .feedback-status {
            color: #166534;
            background: #dcfce7;
            border: 1px solid #86efac;
        }

        #feedbackModal .feedback-error {
            color: #991b1b;
            background: #fee2e2;
            border: 1px solid #fca5a5;
        }

        .feedback-status {
            margin: 0 0 10px;
            color: #b8f7ca;
            font-size: 0.86rem;
        }

        .feedback-error {
            margin: 0 0 10px;
            color: #ffd0d0;
            font-size: 0.86rem;
        }
        .guest-login-dialog {
            width: calc(100% - 32px);
            max-width: 540px;
        }

        .guest-login-content {
            overflow: hidden;
            border-radius: 18px;
        }

        .guest-login-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 20px 24px;
            background: linear-gradient(125deg, #14369c 0%, #07184f 100%);
            border: 0;
            border-bottom: 3px solid var(--ns-gold);
        }

        .guest-login-icon {
            display: inline-flex;
            flex: 0 0 44px;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            color: var(--ns-gold);
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            font-size: 1rem;
        }

        .guest-login-eyebrow {
            display: block;
            margin-bottom: 3px;
            color: rgba(255, 255, 255, 0.72);
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .guest-login-header .btn-close {
            margin-left: auto;
        }

        .guest-login-body {
            padding: 26px 28px 22px;
        }

        .guest-login-title {
            margin: 0 0 8px;
            color: #101522;
            font-family: 'Manrope', 'Inter', sans-serif;
            font-size: 1.12rem;
            font-weight: 800;
            line-height: 1.35;
        }

        .guest-login-copy {
            color: #556176;
            font-size: 0.96rem;
            line-height: 1.6;
        }

        .guest-login-footer {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            gap: 10px;
            padding: 16px 24px 20px;
            background: #f7f9fc;
            border-top: 1px solid #e2e8f0;
        }

        .guest-login-footer .btn {
            min-height: 44px;
            padding: 10px 16px;
            font-weight: 700;
        }

        .guest-login-footer .btn-primary {
            background: #14369c;
            border-color: #14369c;
        }
        
        @media (max-width: 575.98px) {
            
            
            
            
            

            .guest-login-header {
                padding: 17px 18px;
            }

            .guest-login-body {
                padding: 22px 20px;
            }

            .guest-login-footer {
                flex-direction: column-reverse;
                padding: 14px 18px 18px;
            }

            .guest-login-footer .btn {
                width: 100%;
            }
        }

        .feedback-toast {
            position: fixed;
            top: 88px;
            right: 24px;
            z-index: 3000;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            width: min(390px, calc(100vw - 32px));
            padding: 14px 16px;
            border-radius: 12px;
            box-shadow: 0 14px 30px rgba(7, 24, 79, 0.2);
            font-size: 0.9rem;
            line-height: 1.45;
            font-weight: 700;
            animation: feedbackToastIn 0.25s ease-out;
        }

        .feedback-toast.success {
            color: #166534;
            background: #dcfce7;
            border: 1px solid #86efac;
        }

        .feedback-toast.info {
            color: #0f3f7a;
            background: #dbeafe;
            border: 1px solid #93c5fd;
        }

        .feedback-toast.warning {
            color: #92400e;
            background: #fef3c7;
            border: 1px solid #fbbf24;
        }

        .feedback-toast.error {
            color: #991b1b;
            background: #fee2e2;
            border: 1px solid #fca5a5;
        }

        .feedback-toast button {
            margin-left: auto;
            border: 0;
            background: transparent;
            color: inherit;
            font-size: 1.1rem;
            line-height: 1;
            cursor: pointer;
        }

        @keyframes feedbackToastIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .footer-bottom {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid rgba(255,255,255,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            color: rgba(255,255,255,0.64);
            font-size: 0.72rem;
        }

        @media (max-width: 991.98px) {
            .dashboard-page {
                padding-top: 128px;
            }

            .dashboard-page .bg-custom > .container-fluid {
                flex-wrap: wrap;
            }

            .dashboard-page .bg-custom {
                min-height: 126px;
            }

            .dashboard-page .bg-custom .navbar-brand {
                margin-right: auto;
            }

            .dashboard-page .bg-custom > .container-fluid > .ms-auto {
                order: 2;
                min-height: 40px;
                margin-left: 12px !important;
            }

            .dashboard-page .header-nav {
                order: 3;
                flex: 0 0 100%;
                width: 100%;
                margin: 8px 0 0;
                padding-bottom: 2px;
            }

            .campus-shell {
                display: block;
                padding: 12px 12px 24px;
            }

            .premium-sidebar {
                display: none;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {

            .dashboard-page {
                padding-top: 204px;
            }

            .dashboard-page .bg-custom {
                min-height: 204px;
                padding-bottom: 6px;
            }

            #pills-home {
                height: calc(100dvh - 222px);
                min-height: 0;
            }

            #map-preview-shell,
            #map-preview-shell iframe {
                height: 100%;
                min-height: 0 !important;
            }

            .hero-panel {
                padding: 20px 18px 18px;
            }

            .smart-search {
                flex-wrap: wrap;
                padding: 12px;
            }

            .smart-search .btn-search {
                width: 100%;
            }

            .glass-panel {
                padding: 18px 14px 14px !important;
            }

            .dashboard-page .navbar-brand {
                font-size: 0.9rem;
            }

            .dashboard-page .header-nav {
                order: 3;
                flex: 0 0 100%;
                width: 100%;
                margin: 5px 0 0;
                padding-bottom: 0;
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 4px;
                overflow: visible;
                white-space: normal;
            }

            .dashboard-page .header-nav .nav-link {
                width: 100%;
                min-width: 0;
                min-height: 48px;
                flex-direction: column;
                justify-content: center;
                gap: 2px;
                padding: 5px 4px;
                font-size: 0.66rem;
                line-height: 1.15;
                text-align: center;
                white-space: normal;
            }

            .dashboard-page .header-nav .nav-link i {
                flex: 0 0 auto;
            }

            .dashboard-page .header-nav .nav-link .badge {
                margin-left: 2px !important;
            }

            @media (min-width: 560px) {
                body {
                    padding-top: 154px;
                }

                .bg-custom {
                    min-height: 154px;
                }

                #pills-home {
                    height: calc(100dvh - 166px);
                }

                .header-nav {
                    grid-template-columns: repeat(5, minmax(0, 1fr));
                    gap: 5px;
                }

                .header-nav .nav-link {
                    min-height: 40px;
                    flex-direction: row;
                    gap: 5px;
                    padding: 5px 6px;
                    font-size: 0.7rem;
                    line-height: 1.1;
                }
            }

            #docManagementTable {
                width: 1490px !important;
                min-width: 1490px !important;
            }

            #docManagementTable_wrapper {
                display: block;
                width: 100%;
                max-width: 100%;
                min-width: 0;
                overflow-x: auto !important;
                overflow-y: hidden;
                overscroll-behavior-x: contain;
                -webkit-overflow-scrolling: touch;
            }

            #docManagementTable_wrapper .table-responsive {
                width: max-content;
                min-width: 100%;
                overflow: visible;
            }

            .doc-management-toolbar {
                padding: 12px;
            }

            .doc-management-toolbar > div,
            .doc-management-toolbar .input-group {
                width: 100% !important;
                min-width: 0 !important;
            }

            .doc-management-toolbar .form-select {
                width: 100% !important;
            }

            .document-search-form .input-group {
                flex-wrap: wrap;
                gap: 8px;
                box-shadow: none;
            }

            .document-search-form .input-group-text {
                border-radius: 10px 0 0 10px;
            }

            .document-search-form .form-control {
                flex: 1 1 calc(100% - 48px);
                border-radius: 0 10px 10px 0 !important;
            }

            .document-search-form .btn {
                flex: 1 1 0;
                border-radius: 9px !important;
            }

            .result-panel {
                grid-template-columns: 1fr;
            }

        }
    </style>
    <style id="responsive-top-nav">
        /* The fixed top bar changes height as its tabs wrap, so everything below it follows its real height. */
        .dashboard-page {
            padding-top: var(--nav-h, 72px) !important;
        }

        .dashboard-page #pills-home {
            height: calc(100vh - var(--nav-h, 72px) - 18px) !important;
            height: calc(100dvh - var(--nav-h, 72px) - 18px) !important;
        }

        /* Brand name stacks as NEUST / Smart / Campus so the tabs get the room. */
        @media (min-width: 992px) {
            .dashboard-page .brand-text {
                display: block;
                width: min-content;
                line-height: 1.12;
            }

            .dashboard-page .navbar-brand {
                align-items: center;
            }

            /* Narrower desktops: wrap rather than hide any tab. */
            .dashboard-page .header-nav {
                flex-wrap: wrap;
                overflow: visible;
                white-space: normal;
                row-gap: 4px;
                margin-left: 16px;
                justify-content: flex-start;
            }

            .dashboard-page .header-nav .nav-link {
                min-height: 36px;
                padding: 6px clamp(8px, 0.8vw, 12px);
                font-size: clamp(0.68rem, 0.8vw, 0.78rem);
            }

            .dashboard-page .campus-shell {
                min-height: calc(100vh - var(--nav-h, 72px));
            }

            .dashboard-page .premium-sidebar {
                top: calc(var(--nav-h, 72px) + 10px);
                height: calc(100vh - var(--nav-h, 72px) - 18px);
            }

            .dashboard-page .document-legend {
                top: calc(var(--nav-h, 72px) + 12px);
                max-height: calc(100vh - var(--nav-h, 72px) - 24px);
            }
        }

        /* Wide enough: every tab on a single line. The script below shrinks the text just enough to fit,
           or falls back to wrapping (nav-wrap) so a tab is never cut off. */
        @media (min-width: 1200px) {
            .dashboard-page .header-nav {
                flex-wrap: nowrap;
                gap: calc(clamp(2px, 0.3vw, 6px) * var(--nav-scale, 1));
                margin-left: clamp(8px, 1vw, 16px);
                padding: 4px clamp(4px, 0.5vw, 8px);
            }

            .dashboard-page .header-nav.nav-wrap {
                flex-wrap: wrap;
            }

            .dashboard-page .header-nav .nav-link {
                gap: calc(clamp(3px, 0.35vw, 6px) * var(--nav-scale, 1));
                padding: 6px calc(clamp(5px, 0.6vw, 10px) * var(--nav-scale, 1));
                font-size: calc(clamp(0.6rem, 0.83vw, 0.78rem) * var(--nav-scale, 1));
            }

            .dashboard-page .header-nav .nav-link i {
                font-size: 0.9em;
            }

            .dashboard-page .header-nav .nav-divider {
                margin: 0 clamp(1px, 0.2vw, 4px);
            }
        }
    </style>
    <link rel="stylesheet" href="ui/mobile/dashboard.css?v=19">
</head>

<body class="smart-campus-body dashboard-page">
    <nav class="navbar navbar-expand-lg navbar-dark bg-custom fixed-top shadow-sm">
        <div class="container-fluid">
            <span class="navbar-brand fw-bold d-flex align-items-center">
                <img src="img/neust_logo.png" alt="NEUST" class="brand-mark me-2">
                <span class="brand-text">NEUST Smart Campus</span>
            </span>

            <button class="mobile-menu-toggle d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileDashboardMenu" aria-controls="mobileDashboardMenu" aria-label="Open navigation menu">
                <i class="fa-solid fa-bars" aria-hidden="true"></i>
            </button>

            <div class="header-nav" id="pills-tab" role="tablist">
                <button class="nav-link <?= $lastDashboardTab === 'home' ? 'active' : ''; ?>" id="pills-landing-tab" data-bs-toggle="pill" data-bs-target="#pills-landing" type="button">
                    <i class="fa-solid fa-house"></i> Home
                </button>
                <button class="nav-link <?= $lastDashboardTab === 'map' ? 'active' : ''; ?>" id="pills-home-tab" data-bs-toggle="pill" data-bs-target="#pills-home" type="button">
                    <i class="fa-solid fa-map"></i> Map
                </button>
                <button class="nav-link <?= $lastDashboardTab === 'documents' ? 'active' : ''; ?>" id="pills-documents-tab" data-bs-toggle="pill" data-bs-target="#pills-documents" type="button">
                    <i class="fa-solid fa-file-lines"></i> Documents
                </button>
                
                <?php if ($role_id == 1): ?>
                    <span class="nav-divider" aria-hidden="true"></span>
                    <button class="nav-link <?= $lastDashboardTab === 'admin' ? 'active' : ''; ?>" id="pills-admin-tab" data-bs-toggle="pill" data-bs-target="#pills-admin" type="button">
                        <i class="fa-solid fa-user-shield"></i> Admins
                    </button>
                    <button class="nav-link <?= $lastDashboardTab === 'users' ? 'active' : ''; ?>" id="pills-users-tab" data-bs-toggle="pill" data-bs-target="#pills-users" type="button">
                        <i class="fa-solid fa-users"></i> All Users
                    </button>
                    <button class="nav-link <?= $lastDashboardTab === 'tickets' ? 'active' : ''; ?>" id="pills-tickets-tab" data-bs-toggle="pill" data-bs-target="#pills-tickets" type="button">
                        <i class="fa-solid fa-ticket"></i> Tickets
                    </button>
                    <button class="nav-link <?= $lastDashboardTab === 'docs-management' ? 'active' : ''; ?>" id="pills-docs-management-tab" data-bs-toggle="pill" data-bs-target="#pills-docs-management" type="button">
                        <i class="fa-solid fa-gear"></i> Docs Management
                    </button>
                    <button class="nav-link document-request-nav <?= $lastDashboardTab === 'document-requests' ? 'active' : ''; ?>" id="pills-document-requests-tab" data-bs-toggle="pill" data-bs-target="#pills-document-requests" type="button" aria-label="Manage document requests<?= $pendingDocumentRequests ? ', ' . $pendingDocumentRequests . ' pending' : ''; ?>">
                        <i class="fa-solid fa-inbox" aria-hidden="true"></i> Requests
                        <span class="badge rounded-pill bg-danger ms-1" id="pendingDocumentRequestBadge" <?= $pendingDocumentRequests > 0 ? '' : 'hidden'; ?>><?= $pendingDocumentRequests > 99 ? '99+' : $pendingDocumentRequests; ?></span>
                    </button>
                    <button class="nav-link <?= $lastDashboardTab === 'map-configuration' ? 'active' : ''; ?>" id="pills-map-configuration-tab" data-bs-toggle="pill" data-bs-target="#pills-map-configuration" type="button">
                        <i class="fa-solid fa-map-location-dot"></i> Map Configuration
                    </button>
                    <?php if (canViewAuditLogs($db)): ?>
                    <button class="nav-link <?= $lastDashboardTab === 'audit-logs' ? 'active' : ''; ?>" id="pills-audit-logs-tab" data-bs-toggle="pill" data-bs-target="#pills-audit-logs" type="button">
                        <i class="fa-solid fa-clipboard-list"></i> Audit Logs
                    </button>
                    <?php endif; ?>
                    <button class="nav-link <?= $lastDashboardTab === 'archive' ? 'active' : ''; ?>" id="pills-archive-tab" data-bs-toggle="pill" data-bs-target="#pills-archive" type="button">
                        <i class="fa-solid fa-archive"></i> Archive
                    </button>
                <?php endif; ?>
            </div>

            <div class="ms-auto d-flex align-items-center">
                <?php $profileUserId = $_SESSION['id'] ?? 0; ?>
                <div class="user-menu dropdown">
                    <button class="btn btn-profile dropdown-toggle d-none d-md-flex" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="User menu">
                        <i class="fa-solid fa-circle-user"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php if (!$isGuest): ?>
                            <li>
                                <a class="dropdown-item" href="crud/read/view.php?id=<?= (int) $profileUserId ?>">
                                    <i class="fa-solid fa-user me-2"></i>Profile
                                </a>
                            </li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item <?= $isGuest ? '' : 'text-danger'; ?>" href="<?= $isGuest ? 'index.php' : 'functions/logout.php'; ?>">
                                <i class="fa-solid <?= $isGuest ? 'fa-right-to-bracket' : 'fa-right-from-bracket'; ?> me-2"></i><?= $isGuest ? 'Login' : 'Logout'; ?>
                            </a>
                        </li>
                    </ul>
                </div>
                <?php if ($isGuest): ?>
                    <span class="d-md-none text-white fw-bold me-3"><i class="fa-solid fa-user-secret me-1"></i>Guest</span>
                <?php else: ?>
                    <a href="crud/read/view.php?id=<?= (int) $profileUserId ?>" class="d-md-none text-white text-decoration-none fw-bold me-3">
                        <i class="fa-solid fa-circle-user me-1"></i><?= htmlspecialchars($_SESSION['first_name'] ?? 'User') ?>
                    </a>
                <?php endif; ?>
                <a href="<?= $isGuest ? 'index.php' : 'functions/logout.php'; ?>" class="d-md-none text-white text-decoration-none fw-bold">
                    <i class="fa-solid <?= $isGuest ? 'fa-right-to-bracket' : 'fa-right-from-bracket'; ?> me-1"></i><?= $isGuest ? 'Login' : 'Logout'; ?>
                </a>
            </div>
        </div>
    </nav>
    <div class="offcanvas offcanvas-end mobile-dashboard-menu" tabindex="-1" id="mobileDashboardMenu" aria-labelledby="mobileDashboardMenuTitle">
        <div class="offcanvas-header">
            <div>
                <span class="mobile-menu-eyebrow">NEUST Smart Campus</span>
                <h2 class="offcanvas-title" id="mobileDashboardMenuTitle">Navigation</h2>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close navigation"></button>
        </div>
        <div class="offcanvas-body">
            <nav class="mobile-menu-links" aria-label="Dashboard sections">
                <button type="button" class="mobile-menu-link" data-dashboard-tab="pills-landing-tab" data-bs-dismiss="offcanvas"><i class="fa-solid fa-house" aria-hidden="true"></i><span>Home</span></button>
                <button type="button" class="mobile-menu-link" data-dashboard-tab="pills-home-tab" data-bs-dismiss="offcanvas"><i class="fa-solid fa-map" aria-hidden="true"></i><span>Campus Map</span></button>
                <button type="button" class="mobile-menu-link" data-dashboard-tab="pills-documents-tab" data-bs-dismiss="offcanvas"><i class="fa-solid fa-file-lines" aria-hidden="true"></i><span>Documents</span></button>
                <?php if ($role_id == 1): ?>
                    <div class="mobile-menu-divider" role="separator"><span>Administration</span></div>
                    <button type="button" class="mobile-menu-link" data-dashboard-tab="pills-admin-tab" data-bs-dismiss="offcanvas"><i class="fa-solid fa-user-shield" aria-hidden="true"></i><span>Admins</span></button>
                    <button type="button" class="mobile-menu-link" data-dashboard-tab="pills-users-tab" data-bs-dismiss="offcanvas"><i class="fa-solid fa-users" aria-hidden="true"></i><span>All Users</span></button>
                    <button type="button" class="mobile-menu-link" data-dashboard-tab="pills-tickets-tab" data-bs-dismiss="offcanvas"><i class="fa-solid fa-ticket" aria-hidden="true"></i><span>Tickets</span></button>
                    <button type="button" class="mobile-menu-link" data-dashboard-tab="pills-docs-management-tab" data-bs-dismiss="offcanvas"><i class="fa-solid fa-gear" aria-hidden="true"></i><span>Docs Management</span></button>
                    <button type="button" class="mobile-menu-link" data-dashboard-tab="pills-document-requests-tab" data-bs-dismiss="offcanvas"><i class="fa-solid fa-inbox" aria-hidden="true"></i><span>Requests<?php if ($pendingDocumentRequests > 0): ?> (<?= (int) $pendingDocumentRequests; ?>)<?php endif; ?></span></button>
                    <button type="button" class="mobile-menu-link" data-dashboard-tab="pills-map-configuration-tab" data-bs-dismiss="offcanvas"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i><span>Map Configuration</span></button>
                    <?php if (canViewAuditLogs($db)): ?>
                        <button type="button" class="mobile-menu-link" data-dashboard-tab="pills-audit-logs-tab" data-bs-dismiss="offcanvas"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i><span>Audit Logs</span></button>
                    <?php endif; ?>
                    <button type="button" class="mobile-menu-link" data-dashboard-tab="pills-archive-tab" data-bs-dismiss="offcanvas"><i class="fa-solid fa-archive" aria-hidden="true"></i><span>Archive</span></button>
                <?php endif; ?>
                <div class="mobile-menu-divider" role="separator"><span>Account</span></div>
                <?php if (!$isGuest): ?>
                    <a class="mobile-menu-link" href="crud/read/view.php?id=<?= (int) $profileUserId ?>"><i class="fa-solid fa-user" aria-hidden="true"></i><span>Profile</span></a>
                <?php endif; ?>
                <a class="mobile-menu-link mobile-menu-account-action" href="<?= $isGuest ? 'index.php' : 'functions/logout.php'; ?>"><i class="fa-solid <?= $isGuest ? 'fa-right-to-bracket' : 'fa-right-from-bracket'; ?>" aria-hidden="true"></i><span><?= $isGuest ? 'Login' : 'Logout'; ?></span></a>
            </nav>
        </div>
    </div>
    <script>
        (function () {
            var mobileLinks = document.querySelectorAll('.mobile-dashboard-menu [data-dashboard-tab]');
            var syncActiveLink = function (tabId) {
                mobileLinks.forEach(function (link) {
                    var isActive = link.dataset.dashboardTab === tabId;
                    link.classList.toggle('active', isActive);
                    link.setAttribute('aria-current', isActive ? 'page' : 'false');
                });
            };

            mobileLinks.forEach(function (link) {
                link.addEventListener('click', function () {
                    var dashboardTab = document.getElementById(link.dataset.dashboardTab);
                    if (dashboardTab) dashboardTab.click();
                });
            });

            document.querySelectorAll('#pills-tab [data-bs-toggle="pill"]').forEach(function (tab) {
                tab.addEventListener('shown.bs.tab', function (event) {
                    syncActiveLink(event.target.id);
                });
            });

            var activeTab = document.querySelector('#pills-tab [data-bs-toggle="pill"].active');
            if (activeTab) syncActiveLink(activeTab.id);
        })();
    </script>
    <script>
        // Keep the fixed dashboard top bar height available to the page without forcing the tabs to keep
        // shrinking or reflowing during document-driven updates.
        (function () {
            var nav = document.querySelector('nav.bg-custom');
            var tabs = document.querySelector('.header-nav');
            if (!nav || !tabs) return;

            var root = document.documentElement;
            var apply = function () {
                root.style.setProperty('--nav-h', Math.ceil(nav.getBoundingClientRect().height) + 'px');
            };

            apply();
            if ('ResizeObserver' in window) {
                new ResizeObserver(apply).observe(nav);
            }
            window.addEventListener('resize', apply);
            window.addEventListener('load', apply);
            if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(apply);
            }
        })();
    </script>

    <div class="campus-shell">
        <main class="campus-content">
            <div class="tab-content" id="pills-tabContent">
                <div class="tab-pane fade <?= $activeDashboardPaneId === 'pills-landing' ? 'show active' : ''; ?>" id="pills-landing">
                    <section class="dashboard-grid" style="grid-template-columns: 1fr; margin-bottom: 0;">
                        <div class="glass-panel" style="padding:28px 24px; background:linear-gradient(135deg, #eef6ff 0%, #f7fbff 100%); border:1px solid rgba(13,110,253,.15);">
                            <?php if ((int) $role_id === 1): ?>
                                <div class="d-flex justify-content-end mb-2">
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editHomeCardWelcome"><i class="fa-solid fa-pen-to-square me-1"></i>Edit card</button>
                                </div>
                            <?php endif; ?>
                            <div class="row g-4 align-items-center">
                                <div class="col-lg-7">
                                    <span class="badge bg-primary-subtle text-primary mb-3 px-3 py-2 rounded-pill fw-semibold">
                                        <i class="fa-solid fa-school me-2"></i><?= htmlspecialchars($homeCardContent['welcome']['badge'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                    <h1 class="fw-bold mb-3 text-dark" style="font-size: clamp(2rem, 4vw, 3.2rem); line-height:1.15;">
                                        <?= htmlspecialchars($homeCardContent['welcome']['title'], ENT_QUOTES, 'UTF-8'); ?>
                                    </h1>
                                    <p class="text-secondary fs-5 mb-4" style="max-width: 700px;">
                                        <?= htmlspecialchars($homeCardContent['welcome']['description'], ENT_QUOTES, 'UTF-8'); ?>
                                    </p>
                                    <div class="d-flex flex-wrap gap-3">
                                        <button type="button" class="btn btn-primary btn-lg px-4" onclick="document.getElementById('pills-home-tab').click();">
                                            <i class="fa-solid fa-map me-2"></i><?= htmlspecialchars($homeCardContent['welcome']['button'], ENT_QUOTES, 'UTF-8'); ?>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-lg-5">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-body p-4">
                                            <div class="d-flex align-items-center mb-3">
                                                <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary text-white" style="width:52px; height:52px;">
                                                    <i class="fa-solid fa-location-dot"></i>
                                                </div>
                                                <div class="ms-3">
                                                    <h5 class="mb-0 fw-bold"><?= htmlspecialchars($homeCardContent['navigation']['title'], ENT_QUOTES, 'UTF-8'); ?></h5>
                                                </div>
                                                <?php if ((int) $role_id === 1): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-primary ms-auto" data-bs-toggle="modal" data-bs-target="#editHomeCardNavigation" aria-label="Edit Campus Navigation card"><i class="fa-solid fa-pen-to-square"></i></button>
                                                <?php endif; ?>
                                            </div>
                                            <ul class="list-group list-group-flush">
                                                <?php foreach ($homeCardContent['navigation']['features'] as $featureText): ?>
                                                    <li class="list-group-item px-0 py-3">
                                                        <i class="fa-solid fa-circle-check text-success me-2"></i><?= htmlspecialchars($featureText, ENT_QUOTES, 'UTF-8'); ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                    <section class="row g-3 mt-1" aria-label="Campus overview and quick links">
                        <div class="col-lg-5">
                            <div class="glass-panel campus-overview-panel h-100 p-4">
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-warning-subtle text-warning-emphasis" style="width:44px;height:44px;">
                                        <i class="fa-solid fa-building"></i>
                                    </span>
                                    <div>
                                        <h2 class="h5 fw-bold mb-0"><?= htmlspecialchars($homeCardContent['overview']['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                                        <span class="small text-secondary"><?= htmlspecialchars($homeCardContent['overview']['subtitle'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                    <?php if ((int) $role_id === 1): ?>
                                        <button type="button" class="btn btn-sm btn-outline-primary ms-auto" data-bs-toggle="modal" data-bs-target="#editHomeCardOverview" aria-label="Edit Campus at a glance card"><i class="fa-solid fa-pen-to-square"></i></button>
                                    <?php endif; ?>
                                </div>
                                <ul class="campus-building-list" aria-label="Campus buildings">
                                    <?php foreach ($homeCardContent['overview']['buildings'] as $buildingLabel): ?>
                                        <li><?= htmlspecialchars($buildingLabel, ENT_QUOTES, 'UTF-8'); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <p class="text-secondary mb-0"><?= htmlspecialchars($homeCardContent['overview']['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                        </div>
                        <div class="col-lg-7">
                            <div class="glass-panel h-100 p-4">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                    <div>
                                        <h2 class="h5 fw-bold mb-1"><?= htmlspecialchars($homeCardContent['destinations']['title'], ENT_QUOTES, 'UTF-8'); ?></h2>
                                        <span class="small text-secondary"><?= htmlspecialchars($homeCardContent['destinations']['subtitle'], ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        <i class="fa-solid fa-location-crosshairs text-primary fs-5" aria-hidden="true"></i>
                                        <?php if ((int) $role_id === 1): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editHomeCardDestinations" aria-label="Edit Quick destinations card"><i class="fa-solid fa-pen-to-square"></i></button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($homeCardContent['destinations']['items'] as $destination): ?>
                                        <?php $destinationTarget = (string) ($destination['target'] ?? 'wp-clinic'); ?>
                                        <button type="button" class="btn btn-outline-primary" onclick="window.openMapTarget(['<?= htmlspecialchars($destinationTarget, ENT_QUOTES, 'UTF-8'); ?>'])">
                                            <i class="fa-solid <?= htmlspecialchars($homeDestinationIcons[$destinationTarget] ?? 'fa-location-dot', ENT_QUOTES, 'UTF-8'); ?> me-2"></i><?= htmlspecialchars((string) ($destination['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="tab-pane fade <?= $activeDashboardPaneId === 'pills-home' ? 'show active' : ''; ?> smart-home" id="pills-home">
                    <section class="dashboard-grid" style="grid-template-columns: 1fr; margin-bottom: 0;">
                        <div class="glass-panel" style="padding:20px 20px 16px; height:100%;">
                            <div id="map-preview-shell" style="overflow:hidden; border-radius:18px; background:#0b1633; width:100%; flex:1 1 0; height:100%; min-height:0;">
                                <button type="button" class="map-landscape-btn" id="mapMaximizeBtn" aria-label="Open campus map in landscape" title="Open map full screen; rotate your device for landscape">
                                    <i class="fa-solid fa-expand" aria-hidden="true"></i>
                                </button>
                                <iframe src="<?= $isGuest ? 'map-editorf/map.php' : 'map-editor/map.php'; ?>?ionic=1<?= $isGuest ? '&amp;guest=1&amp;map_only=1' : ''; ?>&amp;v=20261021-responsive" title="NEUST campus navigation map" style="display:block; width:100%; height:100%; border:0; background:#fff;"></iframe>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="tab-pane fade <?= $activeDashboardPaneId === 'pills-documents' ? 'show active' : ''; ?>" id="pills-documents">
                    <section class="dashboard-grid" style="grid-template-columns: 1fr; margin-bottom: 0;">
                        <div class="document-portal" style="padding: 0;">
                            <div class="document-shell">
                                <div class="document-main">
                                    <div class="document-header-card" style="background: linear-gradient(135deg, <?= htmlspecialchars($documentHeaderColor, ENT_QUOTES, 'UTF-8'); ?>, <?= htmlspecialchars($documentHeaderColorDeep, ENT_QUOTES, 'UTF-8'); ?>);">
                                <div>
                                    <h2><i class="fa-solid fa-file-lines me-2"></i><?= htmlspecialchars($documentHeaderTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
                                </div>
                            </div>
                                            <button type="button" class="mobile-office-filter-button" data-bs-toggle="modal" data-bs-target="#mobileDocumentOfficeModal">
                                                <i class="fa-solid fa-layer-group" aria-hidden="true"></i>
                                                <span>Filter by office</span>
                                                <span class="mobile-office-filter-current"><?= htmlspecialchars($selectedDocumentOffice === 'all' ? 'All offices' : (string) ($documentOfficeMap[$selectedDocumentOffice]['label'] ?? 'Selected office'), ENT_QUOTES, 'UTF-8'); ?></span>
                                                <i class="fa-solid fa-chevron-right mobile-office-filter-chevron" aria-hidden="true"></i>
                                            </button>
                                            <form class="document-search-form" method="get" action="dashboard.php#pills-documents" role="search">
                                                <input type="hidden" name="document_office" value="<?= htmlspecialchars($selectedDocumentOffice, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="document_page" value="1">
                                                <label class="visually-hidden" for="documentSearchInput">Search documents</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></span>
                                                    <input type="search" class="form-control" id="documentSearchInput" name="document_search" value="<?= htmlspecialchars($documentSearchQuery, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Search title, office, classification, or service details..." autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="documentPoiSuggestions">
                                                    <div id="documentPoiSuggestions" class="document-poi-suggestions" role="listbox" aria-label="Campus map locations"></div>
                                                    <button class="btn btn-primary" type="submit">Search</button>
                                                    <?php if ($documentSearchQuery !== ''): ?>
                                                        <a class="btn btn-outline-secondary d-flex align-items-center" href="dashboard.php?document_office=<?= urlencode($selectedDocumentOffice); ?>&document_page=1#pills-documents" aria-label="Clear document search">Clear</a>
                                                    <?php endif; ?>
                                                </div>
                                            </form>
                                    <?php if (!isset($docs) || !is_array($docs) || count($docs) === 0): ?>
                                        <div class="document-empty-state">
                                            <h4 class="mb-2">No documents found</h4>
                                            <p class="mb-0">There are no document records in the database yet.</p>
                                        </div>
                                    <?php elseif (empty($documentVisibleCards)): ?>
                                        <div class="document-empty-state">
                                            <h4 class="mb-2">No matching documents</h4>
                                            <p class="mb-0">Try another search term or choose a different document office.</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="document-results-status">
                                            Showing <?= (int) $documentTotalRecords; ?> matching document<?= $documentTotalRecords === 1 ? '' : 's'; ?><?= $selectedDocumentOffice !== 'all' ? ' for the selected office' : ''; ?><?= $documentSearchQuery !== '' ? ' for “' . htmlspecialchars($documentSearchQuery, ENT_QUOTES, 'UTF-8') . '”' : ''; ?>.
                                        </div>

                                        <?php if ($isGuest): ?>
                                            <?php $allDocumentRequestIds = array_values(array_unique(array_filter(array_map(static fn ($item) => (int) ($item['id'] ?? 0), $docs ?? [])))); ?>
                                            <div class="guest-document-request-toolbar" id="guestDocumentRequestToolbar" data-csrf="<?= htmlspecialchars($_SESSION['document_request_csrf'], ENT_QUOTES, 'UTF-8'); ?>" data-all-document-ids="<?= htmlspecialchars(json_encode($allDocumentRequestIds), ENT_QUOTES, 'UTF-8'); ?>">
                                                <strong id="guestDocumentSelectedCount">0 documents selected</strong>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-primary" id="requestSelectedDocuments" disabled><i class="fa-solid fa-paper-plane me-1" aria-hidden="true"></i>Request selected</button>
                                                    <button type="button" class="btn btn-outline-primary" id="requestAllDocuments"><i class="fa-solid fa-file-circle-check me-1" aria-hidden="true"></i>Request all documents</button>
                                                    <button type="button" class="btn btn-outline-danger" id="deselectGuestDocuments" disabled><i class="fa-solid fa-xmark me-1" aria-hidden="true"></i>Deselect all</button>
                                                </div>
                                                <p class="guest-document-feedback" id="guestDocumentRequestFeedback" role="status"></p>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($documentTotalPages > 1): ?>
                                            <div class="document-pagination document-pagination-top" aria-label="Document page navigation">
                                                <?php if ($documentPage > 1): ?>
                                                    <a class="document-page-link" href="dashboard.php?document_page=<?= (int) ($documentPage - 1); ?><?= $documentFilterQuery; ?>#pills-documents" aria-label="Previous page" title="Previous page"><i class="fa-solid fa-arrow-left"></i></a>
                                                <?php endif; ?>
                                                <?php if ($documentPage < $documentTotalPages): ?>
                                                    <a class="document-page-link" href="dashboard.php?document_page=<?= (int) ($documentPage + 1); ?><?= $documentFilterQuery; ?>#pills-documents" aria-label="Next page" title="Next page"><i class="fa-solid fa-arrow-right"></i></a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="document-process-list">
                                            <?php foreach ($documentVisibleCards as $document): ?>
                                                <?php
                                                $docTitle = trim((string) ($document['title'] ?? 'Untitled Document'));
                                                $docAvailability = trim((string) ($document['who_may_avail'] ?? ''));
                                                $docSummary = $docAvailability !== '' ? 'For ' . $docAvailability : 'Step-by-step guide on how to request and get your school documents.';
                                                ?>
                                                <?php if ($isGuest): ?><article class="guest-document-card"><?php endif; ?>
                                                <button type="button"
                                                        class="document-process-card document-details-btn"
                                                        style="--legend-color: <?= htmlspecialchars((string) $document['office_color'], ENT_QUOTES, 'UTF-8'); ?>;"
                                                        data-document-id="<?= (int) $document['id']; ?>"
                                                        data-bs-toggle="modal"
                                                    data-bs-target="<?= $isGuest ? '#guestLoginRequiredModal' : '#documentDetailsModal'; ?>">
                                                    <div class="document-process-card-icon">
                                                        <i class="fa-solid fa-file-lines"></i>
                                                    </div>
                                                    <div class="document-process-card-content">
                                                        <h3 class="document-process-title"><?= htmlspecialchars(strtoupper(strtolower((string) $docTitle)), ENT_QUOTES, 'UTF-8'); ?></h3>
                                                        <p class="document-process-description"><?= htmlspecialchars($docSummary, ENT_QUOTES, 'UTF-8'); ?></p>
                                                        <span class="document-process-cta">
                                                            View steps <i class="fa-solid fa-arrow-right"></i>
                                                        </span>
                                                    </div>
                                                </button>
                                                <?php if ($isGuest): ?>
                                                    <div class="guest-document-actions">
                                                        <label class="guest-document-select"><input type="checkbox" class="guest-document-checkbox" value="<?= (int) $document['id']; ?>"><span>Select</span></label>
                                                        <button type="button" class="btn btn-sm btn-outline-primary guest-request-single" data-document-id="<?= (int) $document['id']; ?>"><i class="fa-solid fa-paper-plane me-1" aria-hidden="true"></i>Request</button>
                                                    </div>
                                                </article>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>

                                        <?php if ($documentTotalPages > 1): ?>
                                            <div class="document-pagination" aria-label="Document pages">
                                                <?php if ($documentPage > 1): ?>
                                                    <a class="document-page-link" href="dashboard.php?document_page=<?= (int) ($documentPage - 1); ?><?= $documentFilterQuery; ?>#pills-documents">Prev</a>
                                                <?php endif; ?>

                                                <?php for ($page = 1; $page <= $documentTotalPages; $page++): ?>
                                                    <a class="document-page-link <?= $page === $documentPage ? 'active' : ''; ?>" href="dashboard.php?document_page=<?= (int) $page; ?><?= $documentFilterQuery; ?>#pills-documents" aria-current="<?= $page === $documentPage ? 'page' : 'false'; ?>"><?= (int) $page; ?></a>
                                                <?php endfor; ?>

                                                <?php if ($documentPage < $documentTotalPages): ?>
                                                    <a class="document-page-link" href="dashboard.php?document_page=<?= (int) ($documentPage + 1); ?><?= $documentFilterQuery; ?>#pills-documents">Next</a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>

                                <aside class="document-legend" aria-label="Document office legend">
                                    <div class="document-legend-header">
                                        <div class="document-legend-title"><i class="fa-solid fa-layer-group"></i>Document Office Legend</div>
                                    </div>
                                    <div class="document-legend-items">
                                        <a class="document-legend-item document-legend-link <?= $selectedDocumentOffice === 'all' ? 'active' : ''; ?>" href="dashboard.php?document_office=all&document_page=1<?= $documentSearchQuery !== '' ? '&document_search=' . urlencode($documentSearchQuery) : ''; ?>#pills-documents" aria-current="<?= $selectedDocumentOffice === 'all' ? 'page' : 'false'; ?>">
                                            <span class="document-legend-dot" style="--legend-color: #14369C;"></span>
                                            <span>All document offices</span>
                                        </a>
                                        <?php foreach ($documentOfficeMap as $officeKey => $legend): ?>
                                            <?php if ($officeKey === 'all') continue; ?>
                                            <a class="document-legend-item document-legend-link <?= $selectedDocumentOffice === $officeKey ? 'active' : ''; ?>" style="--legend-color: <?= htmlspecialchars((string) $legend['color'], ENT_QUOTES, 'UTF-8'); ?>;" href="dashboard.php?document_office=<?= urlencode($officeKey); ?>&document_page=1<?= $documentSearchQuery !== '' ? '&document_search=' . urlencode($documentSearchQuery) : ''; ?>#pills-documents" aria-current="<?= $selectedDocumentOffice === $officeKey ? 'page' : 'false'; ?>">
                                                <span class="document-legend-dot" style="--legend-color: <?= htmlspecialchars((string) $legend['color'], ENT_QUOTES, 'UTF-8'); ?>;"></span>
                                                <span><?= htmlspecialchars((string) $legend['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </aside>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="modal fade mobile-office-filter-modal" id="mobileDocumentOfficeModal" tabindex="-1" aria-labelledby="mobileDocumentOfficeModalTitle" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <div>
                                    <p class="mobile-office-modal-eyebrow mb-1">Documents</p>
                                    <h2 class="modal-title fs-5" id="mobileDocumentOfficeModalTitle">Choose an office</h2>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <nav class="mobile-office-filter-list" aria-label="Filter documents by office">
                                    <a class="document-legend-item document-legend-link <?= $selectedDocumentOffice === 'all' ? 'active' : ''; ?>" href="dashboard.php?document_office=all&document_page=1<?= $documentSearchQuery !== '' ? '&document_search=' . urlencode($documentSearchQuery) : ''; ?>#pills-documents" aria-current="<?= $selectedDocumentOffice === 'all' ? 'page' : 'false'; ?>">
                                        <span class="document-legend-dot" style="--legend-color: #14369C;"></span>
                                        <span>All document offices</span>
                                    </a>
                                    <?php foreach ($documentOfficeMap as $officeKey => $legend): ?>
                                        <?php if ($officeKey === 'all') continue; ?>
                                        <a class="document-legend-item document-legend-link <?= $selectedDocumentOffice === $officeKey ? 'active' : ''; ?>" style="--legend-color: <?= htmlspecialchars((string) $legend['color'], ENT_QUOTES, 'UTF-8'); ?>;" href="dashboard.php?document_office=<?= urlencode($officeKey); ?>&document_page=1<?= $documentSearchQuery !== '' ? '&document_search=' . urlencode($documentSearchQuery) : ''; ?>#pills-documents" aria-current="<?= $selectedDocumentOffice === $officeKey ? 'page' : 'false'; ?>">
                                            <span class="document-legend-dot" style="--legend-color: <?= htmlspecialchars((string) $legend['color'], ENT_QUOTES, 'UTF-8'); ?>;"></span>
                                            <span><?= htmlspecialchars((string) $legend['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
                <script>
                    document.body.appendChild(document.getElementById('mobileDocumentOfficeModal'));
                </script>

                <div class="tab-pane fade <?= $activeDashboardPaneId === 'pills-docs-management' ? 'show active' : ''; ?>" id="pills-docs-management">
                    <section class="dashboard-grid" style="grid-template-columns: 1fr; margin-bottom: 0;">
                        <div class="glass-panel doc-management-panel" style="padding:28px 24px;">
                            <div class="doc-management-heading d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-3 text-white" style="width:46px;height:46px;background:#14369c;flex:0 0 46px;">
                                        <i class="fa-solid fa-file-lines" aria-hidden="true"></i>
                                    </span>
                                    <div>
                                        <span class="small text-uppercase fw-bold text-primary">Document library</span>
                                        <h2 class="h4 fw-bold mb-1">Docs Management</h2>
                                        <p class="text-secondary mb-0">Add, view, update, and delete document records and process details.</p>
                                    </div>
                                </div>
                                <span class="badge rounded-pill text-bg-light border px-3 py-2 align-self-start align-self-md-center">
                                    <i class="fa-solid fa-layer-group text-primary me-1" aria-hidden="true"></i>
                                    <?= count($docs ?? []); ?> records
                                </span>
                            </div>

                            <div class="doc-management-toolbar d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <a href="docs/docxadd.php" class="btn btn-primary shadow-sm">
                                        <i class="fa-solid fa-file-circle-plus me-2"></i>Add Document
                                    </a>
                                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importDocumentCsvModal">
                                        <i class="fa-solid fa-file-csv me-2"></i>Import CSV
                                    </button>
                                    <button type="button" class="btn btn-outline-danger bulk-doc-delete-btn" data-bs-toggle="modal" data-bs-target="#bulkDeleteDocumentModal" disabled>
                                        <i class="fa-solid fa-trash me-2"></i>Delete Selected
                                    </button>
                                </div>

                                <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                                    <div class="input-group" style="width: min(100%, 420px); min-width: 220px;">
                                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                                        <input type="text" id="docManagementSearchInput" class="form-control border-start-0 shadow-sm" placeholder="Search all documents..." aria-label="Search documents">
                                    </div>
                                    <select class="form-select shadow-sm" id="docManagementSearchField" style="width: 170px;">
                                        <option value="all">All Fields</option>
                                        <option value="id">ID</option>
                                        <option value="title">Title</option>
                                        <option value="office">Office</option>
                                        <option value="classification">Classification</option>
                                        <option value="transaction_type">Transaction Type</option>
                                    </select>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table id="docManagementTable" class="table table-hover align-middle w-100 alternating-row-striping">
                                    <thead>
                                        <tr>
                                            <th style="width: 42px;">
                                                <input type="checkbox" class="form-check-input select-all-checkbox border border-secondary" aria-label="Select all documents">
                                            </th>
                                            <th style="width: 90px;">ID</th>
                                            <th>Title</th>
                                            <th>Office</th>
                                            <th>Classification</th>
                                            <th>Transaction Type</th>
                                            <th>Who May Avail</th>
                                            <th class="text-center" style="width: 180px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($docs)): ?>
                                            <?php foreach ($docs as $document): ?>
                                                <?php $docId = (int) ($document['id'] ?? 0); ?>
                                                <tr>
                                                    <td><input type="checkbox" class="form-check-input document-select-checkbox border border-secondary" value="<?= $docId; ?>" aria-label="Select document"></td>
                                                    <td class="fw-bold text-primary" data-order="<?= $docId; ?>">#<?= $docId; ?></td>
                                                    <td><?= htmlspecialchars((string) ($document['title'] ?? 'Untitled Document'), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td>
                                                        <?= htmlspecialchars((string) ($document['office_name'] ?? 'Not set'), ENT_QUOTES, 'UTF-8'); ?>
                                                    </td>
                                                    <td><?= htmlspecialchars((string) ($document['classification'] ?? 'Not set'), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?= htmlspecialchars((string) ($document['transaction_type'] ?? 'Not set'), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?= htmlspecialchars((string) ($document['who_may_avail'] ?? 'Not set'), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="text-center">
                                                        <div class="btn-group" role="group" aria-label="Document actions">
                                                            <a href="docs/docxv.php?id=<?= $docId; ?>" class="btn btn-sm btn-light text-info" title="View">
                                                                <i class="fa-solid fa-eye"></i>
                                                            </a>
                                                            <a href="docs/docxu.php?id=<?= $docId; ?>" class="btn btn-sm btn-light text-primary" title="Edit">
                                                                <i class="fa-solid fa-pen-to-square"></i>
                                                            </a>
                                                            <button type="button"
                                                                class="btn btn-sm btn-light text-danger delete-document-btn"
                                                                title="Delete"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#deleteDocumentModal"
                                                                data-id="<?= $docId; ?>"
                                                                data-title="<?= htmlspecialchars((string) ($document['title'] ?? 'Untitled Document'), ENT_QUOTES, 'UTF-8'); ?>"
                                                                data-office="<?= htmlspecialchars((string) ($document['office_name'] ?? 'Not set'), ENT_QUOTES, 'UTF-8'); ?>"
                                                                data-classification="<?= htmlspecialchars((string) ($document['classification'] ?? 'Not set'), ENT_QUOTES, 'UTF-8'); ?>"
                                                                data-transaction="<?= htmlspecialchars((string) ($document['transaction_type'] ?? 'Not set'), ENT_QUOTES, 'UTF-8'); ?>"
                                                                data-who="<?= htmlspecialchars((string) ($document['who_may_avail'] ?? 'Not set'), ENT_QUOTES, 'UTF-8'); ?>">
                                                                <i class="fa-solid fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="8" class="text-center py-4 text-muted">
                                                    No documents found. Add a new document to begin.
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="tab-pane fade <?= $activeDashboardPaneId === 'pills-document-requests' ? 'show active' : ''; ?>" id="pills-document-requests">
                    <section class="dashboard-grid" style="grid-template-columns: 1fr; margin-bottom: 0;">
                        <div class="glass-panel" style="padding:8px; min-height: calc(100vh - 150px);">
                            <iframe src="document-requests.php?embed=1&amp;v=20261020-1" title="Document request management" style="width:100%; height:calc(100vh - 190px); min-height:560px; border:0; background:transparent;"></iframe>
                        </div>
                    </section>
                </div>

                <div class="tab-pane fade <?= $activeDashboardPaneId === 'pills-map-configuration' ? 'show active' : ''; ?>" id="pills-map-configuration">
                    <section class="dashboard-grid" style="grid-template-columns: 1fr; margin-bottom: 0;">
                        <div class="glass-panel map-configuration-panel map-configuration-shell" id="mapConfigurationShell" style="padding:16px; min-height: calc(100vh - 170px);">
                            <div class="map-configuration-toolbar">
                                <button type="button" class="map-landscape-btn" id="mapConfigurationLandscapeBtn" aria-label="Open map configuration in landscape" title="Open map editor full screen; rotate your device for landscape">
                                    <i class="fa-solid fa-expand" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div class="map-configuration-frame">
                                <iframe src="map-editor/index.html?v=20261020-map-update-2" title="Map configuration editor" style="width:100%; height:calc(100vh - 190px); min-height:640px; border:0; border-radius:16px; background:#fff;"></iframe>
                            </div>
                        </div>
                    </section>
                </div>

                <?php if (canViewAuditLogs($db)): ?>
                <div class="tab-pane fade <?= $activeDashboardPaneId === 'pills-audit-logs' ? 'show active' : ''; ?>" id="pills-audit-logs">
                    <section class="dashboard-grid" style="grid-template-columns: 1fr; margin-bottom: 0;">
                        <div class="glass-panel" style="padding:8px; min-height: calc(100vh - 150px);">
                            <iframe src="audit-logs.php" title="Administrator audit logs" style="width:100%; height:calc(100vh - 190px); min-height:640px; border:0; background:transparent;"></iframe>
                        </div>
                    </section>
                </div>
                <?php endif; ?>

                <div class="tab-pane fade <?= $activeDashboardPaneId === 'pills-archive' ? 'show active' : ''; ?>" id="pills-archive">
                    <section class="dashboard-grid" style="grid-template-columns: 1fr; margin-bottom: 0;">
                        <div class="card">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                                <h5 class="mb-0 fw-bold">Archive</h5>
                                <span class="badge bg-light text-secondary border archive-count-badge" id="archiveCountBadge">
                                    <?= count($deletedUsers); ?> archived accounts
                                </span>
                            </div>
                            <div class="card-body">
                                <ul class="nav nav-pills archive-subnav" role="tablist" aria-label="Archive navigation">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active archive-subnav-link" id="archive-accounts-tab" data-bs-toggle="pill" data-bs-target="#archive-accounts" type="button" role="tab" aria-controls="archive-accounts" aria-selected="true">
                                            <i class="fa-solid fa-user me-2" aria-hidden="true"></i>Accounts
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link archive-subnav-link" id="archive-documents-tab" data-bs-toggle="pill" data-bs-target="#archive-documents" type="button" role="tab" aria-controls="archive-documents" aria-selected="false">
                                            <i class="fa-solid fa-file-lines me-2" aria-hidden="true"></i>Documents
                                        </button>
                                    </li>
                                </ul>

                                <div class="tab-content">
                                    <div class="tab-pane fade show active" id="archive-accounts" role="tabpanel" aria-labelledby="archive-accounts-tab">
                                        <div class="table-responsive">
                                            <table id="archiveDeletedUsersTable" class="table table-sm table-hover align-middle mb-0 alternating-row-striping">
                                                <thead>
                                                    <tr>
                                                        <th>Full Name</th>
                                                        <th>Registration Number</th>
                                                        <th>Student Number</th>
                                                        <th>Email</th>
                                                        <th>Role</th>
                                                        <th>Deleted</th>
                                                        <th>Auto-delete on</th>
                                                        <th class="text-end">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if ($deletedUsers): ?>
                                                        <?php foreach ($deletedUsers as $deletedUser): ?>
                                                            <?php $deletedUserRole = (string) ($roles_list[(int) ($deletedUser['role_id'] ?? 0)] ?? 'Unknown'); ?>
                                                            <?php
                                                            $archivedUserDeleteBy = '—';
                                                            if (!empty($deletedUser['deleted_at'])) {
                                                                try {
                                                                    $archivedUserDeleteBy = (new DateTimeImmutable((string) $deletedUser['deleted_at'], new DateTimeZone('Asia/Manila')))
                                                                        ->modify('+30 days')
                                                                        ->format('Y-m-d H:i:s');
                                                                } catch (Exception $e) {
                                                                    $archivedUserDeleteBy = (string) $deletedUser['deleted_at'];
                                                                }
                                                            }
                                                            ?>
                                                            <tr>
                                                                <?php
                                                                $archivedUserFullName = trim(implode(' ', array_filter([
                                                                    $deletedUser['last_name'] ?? '',
                                                                    $deletedUser['first_name'] ?? '',
                                                                    $deletedUser['middle_name'] ?? '',
                                                                    $deletedUser['suffix'] ?? ''
                                                                ], function ($value) {
                                                                    return $value !== '' && $value !== null;
                                                                })));
                                                                ?>
                                                                <td><?= htmlspecialchars($archivedUserFullName !== '' ? $archivedUserFullName : '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                                                <td><?= htmlspecialchars((string) ($deletedUser['registration_number'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>
                                                                <td><?= htmlspecialchars((string) ($deletedUser['student_number'] ?? '—'), ENT_QUOTES, 'UTF-8'); ?></td>
                                                                <td><?= htmlspecialchars((string) ($deletedUser['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                                <td><span class="badge bg-secondary"><?= htmlspecialchars($deletedUserRole, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                                                <td><?= htmlspecialchars((string) ($deletedUser['deleted_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                                <td><?= htmlspecialchars($archivedUserDeleteBy, ENT_QUOTES, 'UTF-8'); ?></td>
                                                                <td class="text-end">
                                                                    <div class="d-flex gap-2 justify-content-end">
                                                                        <form method="POST" action="functions/delete.php" class="d-inline" onsubmit="return confirm('Restore this user account?');">
                                                                            <input type="hidden" name="id" value="<?= (int) $deletedUser['id']; ?>">
                                                                            <button type="submit" name="restore_user" value="1" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-rotate-left me-1" aria-hidden="true"></i>Restore</button>
                                                                        </form>
                                                                        <form method="POST" action="functions/delete.php" class="d-inline" onsubmit="return confirm('Permanently delete this archived user? This cannot be undone.');">
                                                                            <input type="hidden" name="id" value="<?= (int) $deletedUser['id']; ?>">
                                                                            <button type="submit" name="delete_archived_user" value="1" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash-can me-1" aria-hidden="true"></i>Delete</button>
                                                                        </form>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="tab-pane fade" id="archive-documents" role="tabpanel" aria-labelledby="archive-documents-tab">
                                        <div class="table-responsive">
                                            <table id="archiveDeletedDocumentsTable" class="table table-sm table-hover align-middle mb-0 alternating-row-striping">
                                                <thead>
                                                    <tr>
                                                        <th>ID</th>
                                                        <th>Title</th>
                                                        <th>Office</th>
                                                        <th>Classification</th>
                                                        <th>Transaction Type</th>
                                                        <th>Who May Avail</th>
                                                        <th>Deleted</th>
                                                        <th>Auto-delete on</th>
                                                        <th class="text-end">Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if ($deletedDocuments): ?>
                                                        <?php foreach ($deletedDocuments as $deletedDocument): ?>
                                                            <?php
                                                            $archivedDocumentDeleteBy = '—';
                                                            if (!empty($deletedDocument['deleted_at'])) {
                                                                try {
                                                                    $archivedDocumentDeleteBy = (new DateTimeImmutable((string) $deletedDocument['deleted_at'], new DateTimeZone('Asia/Manila')))
                                                                        ->modify('+30 days')
                                                                        ->format('Y-m-d H:i:s');
                                                                } catch (Exception $e) {
                                                                    $archivedDocumentDeleteBy = (string) $deletedDocument['deleted_at'];
                                                                }
                                                            }
                                                            ?>
                                                            <tr>
                                                                <td>#<?= (int) $deletedDocument['id']; ?></td>
                                                                <td><?= htmlspecialchars((string) ($deletedDocument['title'] ?? 'Untitled Document'), ENT_QUOTES, 'UTF-8'); ?></td>
                                                                <td><?= htmlspecialchars((string) ($deletedDocument['office_name'] ?? 'Not set'), ENT_QUOTES, 'UTF-8'); ?></td>
                                                                <td><?= htmlspecialchars((string) ($deletedDocument['classification'] ?? 'Not set'), ENT_QUOTES, 'UTF-8'); ?></td>
                                                                <td><?= htmlspecialchars((string) ($deletedDocument['transaction_type'] ?? 'Not set'), ENT_QUOTES, 'UTF-8'); ?></td>
                                                                <td><?= htmlspecialchars((string) ($deletedDocument['who_may_avail'] ?? 'Not set'), ENT_QUOTES, 'UTF-8'); ?></td>
                                                                <td><?= htmlspecialchars((string) ($deletedDocument['deleted_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                                <td><?= htmlspecialchars($archivedDocumentDeleteBy, ENT_QUOTES, 'UTF-8'); ?></td>
                                                                <td class="text-end">
                                                                    <div class="d-flex gap-2 justify-content-end">
                                                                        <form method="POST" action="functions/delete.php" class="d-inline" onsubmit="return confirm('Restore this document?');">
                                                                            <input type="hidden" name="id" value="<?= (int) $deletedDocument['id']; ?>">
                                                                            <button type="submit" name="restore_document" value="1" class="btn btn-sm btn-outline-success"><i class="fa-solid fa-rotate-left me-1" aria-hidden="true"></i>Restore</button>
                                                                        </form>
                                                                        <form method="POST" action="functions/delete.php" class="d-inline" onsubmit="return confirm('Permanently delete this archived document? This cannot be undone.');">
                                                                            <input type="hidden" name="id" value="<?= (int) $deletedDocument['id']; ?>">
                                                                            <button type="submit" name="delete_archived_document" value="1" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash-can me-1" aria-hidden="true"></i>Delete</button>
                                                                        </form>
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Announcements removed -->

                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const archiveCountBadge = document.getElementById('archiveCountBadge');
                        const archiveAccountsTab = document.getElementById('archive-accounts-tab');
                        const archiveDocumentsTab = document.getElementById('archive-documents-tab');

                        function setArchiveSubNav(tabName) {
                            const normalizedTabName = tabName === 'documents' ? 'documents' : 'accounts';
                            const activeButton = document.getElementById('archive-' + normalizedTabName + '-tab');
                            const inactiveButton = document.getElementById('archive-' + (normalizedTabName === 'accounts' ? 'documents' : 'accounts') + '-tab');
                            const activePane = document.getElementById('archive-' + normalizedTabName);
                            const inactivePane = document.getElementById('archive-' + (normalizedTabName === 'accounts' ? 'documents' : 'accounts'));

                            if (activeButton && inactiveButton) {
                                activeButton.classList.add('active');
                                activeButton.setAttribute('aria-selected', 'true');
                                inactiveButton.classList.remove('active');
                                inactiveButton.setAttribute('aria-selected', 'false');
                            }

                            if (activePane && inactivePane) {
                                activePane.classList.add('show', 'active');
                                activePane.classList.remove('fade');
                                inactivePane.classList.remove('show', 'active');
                                inactivePane.classList.add('fade');
                            }

                            // The main tab cookie is handled by the dashboard tab handlers; setting it here
                            // ran on every page load and forced the dashboard to reopen on Archive.

                            try {
                                localStorage.setItem('archive_subnav', normalizedTabName);
                                document.cookie = 'archive_subnav=' + normalizedTabName + '; path=/; max-age=' + (60 * 60 * 24 * 365) + '; SameSite=Lax';
                            } catch (error) {
                                // Ignore storage failures and keep the visible tab state as fallback.
                            }

                            if (archiveCountBadge) {
                                const accountCount = <?= (int) count($deletedUsers); ?>;
                                const documentCount = <?= (int) count($deletedDocuments); ?>;
                                archiveCountBadge.textContent = normalizedTabName === 'documents'
                                    ? documentCount + ' archived documents'
                                    : accountCount + ' archived accounts';
                            }
                        }

                        function restoreArchiveSubNav() {
                            let savedTab = 'accounts';
                            try {
                                savedTab = localStorage.getItem('archive_subnav') || savedTab;
                            } catch (error) {
                                savedTab = 'accounts';
                            }

                            if (!savedTab || !['accounts', 'documents'].includes(savedTab)) {
                                savedTab = 'accounts';
                            }

                            const cookieMatch = document.cookie.split('; ').find(function (part) {
                                return part.startsWith('archive_subnav=');
                            });
                            if (cookieMatch) {
                                const cookieValue = cookieMatch.split('=')[1];
                                if (['accounts', 'documents'].includes(cookieValue)) {
                                    savedTab = cookieValue;
                                }
                            }

                            setArchiveSubNav(savedTab);
                        }

                        if (archiveAccountsTab) {
                            archiveAccountsTab.addEventListener('shown.bs.tab', function () {
                                setArchiveSubNav('accounts');
                            });
                        }

                        if (archiveDocumentsTab) {
                            archiveDocumentsTab.addEventListener('shown.bs.tab', function () {
                                setArchiveSubNav('documents');
                            });
                        }

                        restoreArchiveSubNav();
                    });
                </script>

                <?php if ((int) $role_id === 1): ?>
                <?php
                $sections = [
                                'pills-admin' => ['title' => "System Administrators", 'data' => $admins, 'type' => 'admin'],
                                'pills-users' => ['title' => "All Registered Users", 'data' => $users, 'type' => 'users']
                            ];

                foreach ($sections as $id => $sec):
                    ?>
                    <div class="tab-pane fade <?= $activeDashboardPaneId === $id ? 'show active' : ''; ?>" id="<?= $id ?>">
                        <div class="card">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                                <h5 class="mb-0 fw-bold"><?= $sec['title'] ?></h5>
                            </div>
                            <div class="card-body">
                                <?php if ($id === 'pills-users' || $id === 'pills-admin'): ?>
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                                        <div class="d-flex flex-wrap gap-2">
                                            <?php if ($id === 'pills-users'): ?>
                                                <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                                    <i class="fa-solid fa-user-plus me-2"></i>Add User
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($id === 'pills-admin'): ?>
                                                <button type="button" class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                                                    <i class="fa-solid fa-user-shield me-2"></i>Promote User to Admin
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importCsvModal" data-import-role="<?= $id === 'pills-admin' ? 'admin' : 'user'; ?>">
                                                <i class="fa-solid fa-file-csv me-2"></i>Import CSV
                                            </button>
                                            <button type="button" class="btn btn-outline-danger bulk-delete-btn" disabled>
                                                <i class="fa-solid fa-trash me-2"></i>Delete Selected
                                            </button>
                                        </div>

                                        <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                                            <div class="input-group" style="width: min(100%, 420px); min-width: 240px;">
                                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                                                <input type="text" class="form-control border-start-0 shadow-sm" id="tableSearchInput_<?= $id ?>" placeholder="Search all registered users..." aria-label="Search users">
                                            </div>
                                            <select class="form-select shadow-sm" id="tableSearchField_<?= $id ?>" style="width: 170px;">
                                                <option value="all">All Fields</option>
                                                <option value="registration_number">Registration Number</option>
                                                <option value="student_number">Student Number</option>
                                                <option value="last_name">Last Name</option>
                                                <option value="first_name">First Name</option>
                                                <option value="middle_name">Middle Name</option>
                                                <option value="suffix">Suffix</option>
                                                <option value="email">Email</option>
                                                <option value="role">Role</option>
                                            </select>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="table-responsive">
                                    <table id="table_<?= $id ?>" class="table table-hover w-100 alternating-row-striping">
                                        <thead>
                                            <tr>
                                                <?php if ($id === 'pills-users' || $id === 'pills-admin'): ?>
                                                    <th style="width: 42px;">
                                                        <input type="checkbox" class="form-check-input select-all-checkbox border border-secondary" aria-label="Select all users">
                                                    </th>
                                                <?php endif; ?>
                                                <th>Registration Number</th>
                                                <th>Student Number</th>
                                                <th>Last Name</th>
                                                <th>First Name</th>
                                                <th>Middle Name</th>
                                                <th>Suffix</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($sec['data'] as $row): ?>
                                                <?php $userId = (int) ($row['id'] ?? 0); ?>
                                                <?php $fullName = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? '')); ?>
                                                <?php $roleLabel = (string) ($roles_list[(int) ($row['role_id'] ?? 0)] ?? 'Unknown'); ?>
                                                <?php $registrationNumber = (string) ($row['registration_number'] ?? ''); ?>
                                                <?php $studentNumber = (string) ($row['student_number'] ?? ''); ?>
                                                <?php $lastName = (string) ($row['last_name'] ?? ''); ?>
                                                <?php $firstName = (string) ($row['first_name'] ?? ''); ?>
                                                <?php $middleName = (string) ($row['middle_name'] ?? ''); ?>
                                                <?php $suffix = (string) ($row['suffix'] ?? ''); ?>
                                                <tr>
                                                    <?php if ($id === 'pills-users' || $id === 'pills-admin'): ?>
                                                        <td><input type="checkbox" class="form-check-input user-select-checkbox border border-secondary" value="<?= $userId ?>" aria-label="Select user"></td>
                                                    <?php endif; ?>
                                                    <td><?= htmlspecialchars($registrationNumber !== '' ? $registrationNumber : '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?= htmlspecialchars($studentNumber !== '' ? $studentNumber : '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?= htmlspecialchars($lastName !== '' ? $lastName : '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?= htmlspecialchars($firstName !== '' ? $firstName : '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?= htmlspecialchars($middleName !== '' ? $middleName : '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?= htmlspecialchars($suffix !== '' ? $suffix : '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><?= htmlspecialchars((string) ($row['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?></span></td>
                                                    <td class="text-center">
                                                        <div class="btn-group">
                                                            <a href="crud/read/view.php?id=<?= $userId ?>"
                                                                class="btn btn-sm btn-light text-info"
                                                                title="View" aria-label="View user">
                                                                <i class="fa-solid fa-eye"></i>
                                                            </a>
                                                            <a href="crud/update/edit.php?id=<?= $userId ?>"
                                                                class="btn btn-sm btn-light text-primary"
                                                                title="Edit" aria-label="Edit user">
                                                                <i class="fa-solid fa-pen-to-square"></i>
                                                            </a>
                                                            <?php if ((int) ($row['role_id'] ?? 0) === 1 && $id === 'pills-admin'): ?>
                                                                <button type="button"
                                                                    class="btn btn-sm btn-light text-warning"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#demoteUserModal"
                                                                    data-id="<?= $userId ?>"
                                                                    data-name="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>"
                                                                    data-email="<?= htmlspecialchars((string) ($row['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                    data-role="<?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?>"
                                                                    data-firstname="<?= htmlspecialchars((string) ($row['first_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                    data-lastname="<?= htmlspecialchars((string) ($row['last_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                    data-middlename="<?= htmlspecialchars((string) ($row['middle_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                    data-suffix="<?= htmlspecialchars((string) ($row['suffix'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                    data-roleid="<?= (int) ($row['role_id'] ?? 0) ?>"
                                                                    title="Demote to User">
                                                                    <i class="fa-solid fa-user-minus"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                            <button type="button"
                                                                class="btn btn-sm btn-light text-danger"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#deleteUserModal"
                                                                data-id="<?= $userId ?>"
                                                                data-name="<?= htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?>"
                                                                data-email="<?= htmlspecialchars((string) ($row['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                data-role="<?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?>"
                                                                data-firstname="<?= htmlspecialchars((string) ($row['first_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                data-lastname="<?= htmlspecialchars((string) ($row['last_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                data-middlename="<?= htmlspecialchars((string) ($row['middle_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                data-suffix="<?= htmlspecialchars((string) ($row['suffix'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                                data-roleid="<?= (int) ($row['role_id'] ?? 0) ?>"
                                                                title="Delete">
                                                                <i class="fa-solid fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <div class="tab-pane fade <?= $activeDashboardPaneId === 'pills-tickets' ? 'show active' : ''; ?>" id="pills-tickets">
                    <section class="dashboard-grid" style="grid-template-columns: 1fr; margin-bottom: 0;">
                        <div class="card ticket-queue-card">
                            <div class="ticket-queue-header">
                                <div class="ticket-queue-title">
                                    <span class="ticket-queue-icon" aria-hidden="true"><i class="fa-solid fa-ticket"></i></span>
                                    <div>
                                        <h1 class="ticket-queue-heading">Feedback tickets</h1>
                                        <p class="ticket-queue-subtitle">Review messages and track follow-up status.</p>
                                    </div>
                                </div>
                                <span class="ticket-total-count"><?= $ticketCounts['all']; ?> total</span>
                            </div>
                            <div class="ticket-queue-body">
                                <div class="ticket-filter-bar" role="group" aria-label="Filter feedback tickets">
                                    <button type="button" class="btn btn-sm btn-primary ticket-filter-button active" data-ticket-filter="all" aria-pressed="true">All <span class="badge bg-light text-primary ms-1"><?= $ticketCounts['all']; ?></span></button>
                                    <button type="button" class="btn btn-sm btn-outline-primary ticket-filter-button" data-ticket-filter="unresolved" aria-pressed="false">Unresolved <span class="badge bg-light text-primary ms-1"><?= $ticketCounts['unresolved']; ?></span></button>
                                    <button type="button" class="btn btn-sm btn-outline-primary ticket-filter-button" data-ticket-filter="resolved" aria-pressed="false">Resolved <span class="badge bg-light text-primary ms-1"><?= $ticketCounts['resolved']; ?></span></button>
                                    <button type="button" class="btn btn-sm btn-outline-warning ticket-filter-button" data-ticket-filter="priority" aria-pressed="false"><i class="fa-solid fa-triangle-exclamation me-1" aria-hidden="true"></i>Priority <span class="badge bg-light text-dark ms-1"><?= $ticketCounts['priority']; ?></span></button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary ticket-filter-button" data-ticket-filter="closed" aria-pressed="false">Closed <span class="badge bg-light text-secondary ms-1"><?= $ticketCounts['closed']; ?></span></button>
                                </div>
                                <div class="table-responsive">
                                    <table id="feedbackTicketsTable" class="table table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Ticket</th>
                                                <th>From</th>
                                                <th>Message</th>
                                                <th>Status</th>
                                                <th>Priority</th>
                                                <th>Submitted</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($feedbackTickets as $ticket): ?>
                                                <?php
                                                    $ticketStatus = (string) ($ticket['status'] ?? 'open');
                                                    $ticketStatusClass = $ticketStatus === 'resolved' ? 'success' : ($ticketStatus === 'closed' ? 'secondary' : 'primary');
                                                    $ticketCreatedAt = strtotime((string) ($ticket['created_at'] ?? ''));
                                                ?>
                                                <tr>
                                                    <td class="ticket-code"><?= htmlspecialchars((string) ($ticket['ticket_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="text-nowrap">
                                                        <div class="fw-semibold"><?= htmlspecialchars((string) ($ticket['submitter_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                                        <a href="mailto:<?= htmlspecialchars((string) ($ticket['submitter_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars((string) ($ticket['submitter_email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></a>
                                                    </td>
                                                    <td class="ticket-message-cell"><?= htmlspecialchars((string) ($ticket['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><span class="badge bg-<?= $ticketStatusClass; ?>"><?= htmlspecialchars(ucfirst($ticketStatus), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                                    <td>
                                                        <?php if (!empty($ticket['is_priority'])): ?>
                                                            <span class="badge bg-warning text-dark"><i class="fa-solid fa-triangle-exclamation me-1" aria-hidden="true"></i>Priority</span>
                                                        <?php else: ?>
                                                            <span class="text-secondary">Normal</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-nowrap"><?= $ticketCreatedAt ? date('M j, Y g:i A', $ticketCreatedAt) : '—'; ?></td>
                                                    <td class="text-end text-nowrap">
                                                        <div class="d-inline-flex gap-1 ticket-action-group" role="group" aria-label="Ticket actions">
                                                            <form method="post" action="dashboard.php#pills-tickets">
                                                                <input type="hidden" name="ticket_action_csrf" value="<?= htmlspecialchars($_SESSION['ticket_action_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                                                                <input type="hidden" name="ticket_id" value="<?= (int) ($ticket['id'] ?? 0); ?>">
                                                                <button type="button" class="btn btn-sm btn-outline-success ticket-confirm-trigger" data-bs-toggle="modal" data-bs-target="#ticketActionConfirmModal" data-ticket-action="resolve" data-ticket-code="<?= htmlspecialchars((string) ($ticket['ticket_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" title="Mark resolved" aria-label="Mark <?= htmlspecialchars((string) ($ticket['ticket_code'] ?? 'ticket'), ENT_QUOTES, 'UTF-8'); ?> resolved"><i class="fa-solid fa-check" aria-hidden="true"></i></button>
                                                            </form>
                                                            <form method="post" action="dashboard.php#pills-tickets">
                                                                <input type="hidden" name="ticket_action_csrf" value="<?= htmlspecialchars($_SESSION['ticket_action_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                                                                <input type="hidden" name="ticket_id" value="<?= (int) ($ticket['id'] ?? 0); ?>">
                                                                <button type="button" class="btn btn-sm btn-outline-danger ticket-confirm-trigger" data-bs-toggle="modal" data-bs-target="#ticketActionConfirmModal" data-ticket-action="close" data-ticket-code="<?= htmlspecialchars((string) ($ticket['ticket_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" title="Close ticket" aria-label="Close <?= htmlspecialchars((string) ($ticket['ticket_code'] ?? 'ticket'), ENT_QUOTES, 'UTF-8'); ?>"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                                                            </form>
                                                            <form method="post" action="dashboard.php#pills-tickets">
                                                                <input type="hidden" name="ticket_action_csrf" value="<?= htmlspecialchars($_SESSION['ticket_action_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                                                                <input type="hidden" name="ticket_id" value="<?= (int) ($ticket['id'] ?? 0); ?>">
                                                                <button type="submit" name="ticket_action" value="toggle_priority" class="btn btn-sm <?= !empty($ticket['is_priority']) ? 'btn-warning' : 'btn-outline-warning'; ?>" title="<?= !empty($ticket['is_priority']) ? 'Remove priority' : 'Mark priority'; ?>" aria-label="<?= !empty($ticket['is_priority']) ? 'Remove priority from ' : 'Mark priority for '; ?><?= htmlspecialchars((string) ($ticket['ticket_code'] ?? 'ticket'), ENT_QUOTES, 'UTF-8'); ?>"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php if ((int) $role_id === 1): ?>
        <div class="modal fade" id="ticketActionConfirmModal" tabindex="-1" aria-labelledby="ticketActionConfirmModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header">
                        <h2 class="modal-title fs-5 fw-bold" id="ticketActionConfirmModalLabel">Confirm ticket action</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cancel"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0" id="ticketActionConfirmMessage">Are you sure you want to update this ticket?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="ticketActionConfirmButton">Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ((int) $role_id === 1): ?>
        <?php
        $cardModalTitles = [
            'welcome' => 'Welcome card',
            'navigation' => 'Campus Navigation card',
            'overview' => 'Campus at a glance card',
            'destinations' => 'Quick destinations card',
        ];
        $cardModalIds = [
            'welcome' => 'editHomeCardWelcome',
            'navigation' => 'editHomeCardNavigation',
            'overview' => 'editHomeCardOverview',
            'destinations' => 'editHomeCardDestinations',
        ];
        ?>
        <?php foreach ($homeCardFields as $cardKey => $cardFields): ?>
            <div class="modal fade" id="<?= htmlspecialchars($cardModalIds[$cardKey], ENT_QUOTES, 'UTF-8'); ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <form method="post" action="dashboard.php" class="home-card-edit-form">
                            <div class="modal-header">
                                <h2 class="modal-title fs-5">Edit <?= htmlspecialchars($cardModalTitles[$cardKey], ENT_QUOTES, 'UTF-8'); ?></h2>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="action" value="save_home_card">
                                <input type="hidden" name="card_key" value="<?= htmlspecialchars($cardKey, ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['home_card_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="row g-3">
                                    <?php foreach ($cardFields as $fieldName => $fieldOptions): ?>
                                        <div class="col-12 <?= in_array(($fieldOptions['type'] ?? ''), ['textarea', 'list', 'destination_list'], true) ? '' : 'col-md-6'; ?>">
                                            <label class="form-label" for="<?= htmlspecialchars($cardKey . '_' . $fieldName . (($fieldOptions['type'] ?? '') === 'destination_list' ? '_0_label' : ((($fieldOptions['type'] ?? '') === 'list') ? '_0' : '')), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($fieldOptions['label'], ENT_QUOTES, 'UTF-8'); ?></label>
                                            <?php if (($fieldOptions['type'] ?? '') === 'destination_list'): ?>
                                                <div class="d-grid gap-2 mb-2" id="<?= htmlspecialchars($cardKey . '_' . $fieldName . '_list', ENT_QUOTES, 'UTF-8'); ?>" data-input-name="<?= htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8'); ?>" data-list-type="destination_list" data-maxlength="<?= (int) $fieldOptions['maxlength']; ?>" data-targets="<?= htmlspecialchars(json_encode($homeDestinationOptions, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php foreach ($homeCardContent[$cardKey][$fieldName] as $itemIndex => $destination): ?>
                                                        <div class="d-flex flex-wrap gap-2 home-card-list-item" data-item-index="<?= (int) $itemIndex; ?>">
                                                            <input class="form-control flex-grow-1" type="text" id="<?= htmlspecialchars($cardKey . '_' . $fieldName . '_' . $itemIndex . '_label', ENT_QUOTES, 'UTF-8'); ?>" name="<?= htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8'); ?>[<?= (int) $itemIndex; ?>][label]" value="<?= htmlspecialchars((string) ($destination['label'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" maxlength="<?= (int) $fieldOptions['maxlength']; ?>" aria-label="Destination label <?= (int) ($itemIndex + 1); ?>" required>
                                                            <select class="form-select flex-grow-1" name="<?= htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8'); ?>[<?= (int) $itemIndex; ?>][target]" aria-label="Destination location <?= (int) ($itemIndex + 1); ?>" required>
                                                                <?= renderHomeDestinationOptions($homeDestinationOptions, (string) ($destination['target'] ?? '')); ?>
                                                            </select>
                                                            <button type="button" class="btn btn-outline-danger home-card-remove-item" aria-label="Remove destination"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-primary home-card-add-item" data-list-target="<?= htmlspecialchars($cardKey . '_' . $fieldName . '_list', ENT_QUOTES, 'UTF-8'); ?>"><i class="fa-solid fa-plus me-1"></i>Add destination</button>
                                                <span class="small text-secondary ms-2">Up to 12 destinations</span>
                                            <?php elseif (($fieldOptions['type'] ?? '') === 'list'): ?>
                                                <div class="d-grid gap-2 mb-2" id="<?= htmlspecialchars($cardKey . '_' . $fieldName . '_list', ENT_QUOTES, 'UTF-8'); ?>" data-input-name="<?= htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8'); ?>" data-list-type="list" data-maxlength="<?= (int) $fieldOptions['maxlength']; ?>">
                                                    <?php foreach ($homeCardContent[$cardKey][$fieldName] as $itemIndex => $itemValue): ?>
                                                        <div class="d-flex gap-2 home-card-list-item" data-item-index="<?= (int) $itemIndex; ?>">
                                                            <input class="form-control" type="text" id="<?= htmlspecialchars($cardKey . '_' . $fieldName . '_' . $itemIndex, ENT_QUOTES, 'UTF-8'); ?>" name="<?= htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8'); ?>[]" value="<?= htmlspecialchars($itemValue, ENT_QUOTES, 'UTF-8'); ?>" maxlength="<?= (int) $fieldOptions['maxlength']; ?>" aria-label="<?= htmlspecialchars($fieldOptions['label'] . ' ' . ($itemIndex + 1), ENT_QUOTES, 'UTF-8'); ?>" required>
                                                            <button type="button" class="btn btn-outline-danger home-card-remove-item" aria-label="Remove item"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-primary home-card-add-item" data-list-target="<?= htmlspecialchars($cardKey . '_' . $fieldName . '_list', ENT_QUOTES, 'UTF-8'); ?>"><i class="fa-solid fa-plus me-1"></i>Add item</button>
                                                <span class="small text-secondary ms-2">Up to 12 items</span>
                                            <?php elseif (($fieldOptions['type'] ?? '') === 'textarea'): ?>
                                                <textarea class="form-control" id="<?= htmlspecialchars($cardKey . '_' . $fieldName, ENT_QUOTES, 'UTF-8'); ?>" name="<?= htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8'); ?>" maxlength="<?= (int) $fieldOptions['maxlength']; ?>" rows="3" required><?= htmlspecialchars($homeCardContent[$cardKey][$fieldName], ENT_QUOTES, 'UTF-8'); ?></textarea>
                                            <?php elseif (($fieldOptions['type'] ?? '') === 'target'): ?>
                                                <select class="form-select" id="<?= htmlspecialchars($cardKey . '_' . $fieldName, ENT_QUOTES, 'UTF-8'); ?>" name="<?= htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8'); ?>" required>
                                                    <?= renderHomeDestinationOptions($homeDestinationOptions, (string) $homeCardContent[$cardKey][$fieldName]); ?>
                                                </select>
                                            <?php else: ?>
                                                <input class="form-control" type="text" id="<?= htmlspecialchars($cardKey . '_' . $fieldName, ENT_QUOTES, 'UTF-8'); ?>" name="<?= htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8'); ?>" value="<?= htmlspecialchars($homeCardContent[$cardKey][$fieldName], ENT_QUOTES, 'UTF-8'); ?>" maxlength="<?= (int) $fieldOptions['maxlength']; ?>" required>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-1"></i>Save changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($flashMessage)): ?>
        <?php
            $toastClass = 'success';
            $toastIcon = 'fa-circle-check';

            if (in_array($flashType, ['danger', 'error', 'delete'], true)) {
                $toastClass = 'error';
                $toastIcon = 'fa-circle-exclamation';
            } elseif (in_array($flashType, ['info', 'update', 'primary'], true)) {
                $toastClass = 'info';
                $toastIcon = 'fa-circle-info';
            } elseif (in_array($flashType, ['warning'], true)) {
                $toastClass = 'warning';
                $toastIcon = 'fa-triangle-exclamation';
            }
        ?>
        <div class="feedback-toast <?= $toastClass; ?>" role="status" aria-live="polite">
            <i class="fa-solid <?= $toastIcon; ?> mt-1"></i>
            <span><?= htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8'); ?></span>
            <button type="button" onclick="this.parentElement.remove()" aria-label="Close notification">&times;</button>
        </div>
        <script>
            window.setTimeout(() => document.querySelector('.feedback-toast')?.remove(), 7000);
        </script>
    <?php endif; ?>

    <?php if ($feedbackMessage !== '' || $feedbackError !== ''): ?>
        <div class="feedback-toast <?= $feedbackMessage !== '' ? 'success' : 'error'; ?>" role="status" aria-live="polite">
            <i class="fa-solid <?= $feedbackMessage !== '' ? 'fa-circle-check' : 'fa-circle-exclamation'; ?> mt-1"></i>
            <span><?= htmlspecialchars($feedbackMessage !== '' ? $feedbackMessage : $feedbackError, ENT_QUOTES, 'UTF-8'); ?></span>
            <button type="button" onclick="this.parentElement.remove()" aria-label="Close notification">&times;</button>
        </div>
        <script>
            window.setTimeout(() => document.querySelector('.feedback-toast')?.remove(), 7000);
        </script>
    <?php endif; ?>

    <footer class="site-footer">
        <div class="site-footer-inner">
            <div class="site-footer-grid">
                <div id="feedback">
                    <div class="footer-brand">
                        <img src="img/neust_logo.png" alt="NEUST logo">
                        <h3>NEUST</h3>
                    </div>
                    <p class="footer-copy">Smart Campus helps students, staff, and visitors navigate departments, offices, and services with a faster, clearer campus experience.</p>
                </div>

                <div>
                    <h4 class="footer-heading">Report</h4>
                    <button type="button" class="feedback-open-btn" <?php if ($isGuest): ?>data-bs-toggle="modal" data-bs-target="#guestFeedbackLoginModal"<?php else: ?>data-bs-toggle="modal" data-bs-target="#feedbackModal"<?php endif; ?>>
                        <i class="fa-solid fa-circle-exclamation me-2"></i>Send Report
                    </button>
                </div>
            </div>

            <div class="footer-bottom">
                <span>© 2026 NEUST Smart Campus</span>
            </div>
        </div>
    </footer>

    <div class="modal fade" id="documentDetailsModal" tabindex="-1" aria-labelledby="documentDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 22px; overflow: hidden;">
                <div class="modal-header" style="background: linear-gradient(135deg, #14369C 0%, #07184F 100%); border-bottom: none; color: #fff; padding: 1rem 1.25rem;">
                    <h5 class="modal-title fw-bold" id="documentDetailsModalLabel">Document details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-3" id="documentModalBody" style="background: linear-gradient(180deg, #f6f9ff 0%, #ffffff 100%); min-height: 260px;">
                    <div class="text-center py-5 text-muted">
                        <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
                        <div class="mt-3">Loading document details...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($isGuest): ?>
        <div class="modal fade" id="guestDocumentRequestModal" tabindex="-1" aria-labelledby="guestDocumentRequestTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <form id="guestDocumentRequestForm" data-csrf="<?= htmlspecialchars($_SESSION['document_request_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="modal-header text-white" style="background:linear-gradient(135deg,#14369C,#07184F)">
                            <div><span class="small text-uppercase opacity-75">Document request</span><h2 class="modal-title h5 fw-bold mb-0" id="guestDocumentRequestTitle">Confirm your request</h2></div>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p id="guestDocumentRequestSummary" class="text-secondary"></p>
                            <div class="row g-2 mb-3">
                                <div class="col-6 col-md-3">
                                    <label class="form-label text-nowrap" for="guestDocumentRequesterFirstName">First name</label>
                                    <input class="form-control guest-requester-name" id="guestDocumentRequesterFirstName" name="fname" maxlength="80" autocomplete="given-name" required>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label text-nowrap" for="guestDocumentRequesterMiddleName">Middle name</label>
                                    <input class="form-control guest-requester-name" id="guestDocumentRequesterMiddleName" name="mname" maxlength="80" autocomplete="additional-name">
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label text-nowrap" for="guestDocumentRequesterLastName">Last name</label>
                                    <input class="form-control guest-requester-name" id="guestDocumentRequesterLastName" name="lname" maxlength="80" autocomplete="family-name" required>
                                </div>
                                <div class="col-6 col-md-3">
                                    <label class="form-label text-nowrap" for="guestDocumentRequesterSuffix">Suffix <span class="text-secondary">(optional)</span></label>
                                    <input class="form-control guest-requester-name" id="guestDocumentRequesterSuffix" name="suffix" maxlength="20" autocomplete="honorific-suffix">
                                </div>
                            </div>
                            <label class="form-label" for="guestDocumentRequesterEmail">Email address</label>
                            <input class="form-control mb-3" id="guestDocumentRequesterEmail" name="requester_email" type="email" maxlength="190" required>
                            <p class="small text-danger mt-3 mb-0" id="guestDocumentRequestError" role="alert"></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary" id="guestDocumentRequestSubmit"><i class="fa-solid fa-paper-plane me-1" aria-hidden="true"></i>Send request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal fade" id="guestLoginRequiredModal" tabindex="-1" aria-labelledby="guestLoginRequiredModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered guest-login-dialog">
                <div class="modal-content border-0 shadow-lg guest-login-content">
                    <div class="modal-header text-white guest-login-header">
                        <span class="guest-login-icon" aria-hidden="true"><i class="fa-solid fa-lock"></i></span>
                        <div>
                            <span class="guest-login-eyebrow">Document access</span>
                            <h2 class="modal-title fw-bold mb-0" id="guestLoginRequiredModalLabel">Login required</h2>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body guest-login-body">
                        <h3 class="guest-login-title">View the full document process</h3>
                        <p class="guest-login-copy mb-0">Sign in to access requirements, service steps, fees, and processing times for this document.</p>
                    </div>
                    <div class="modal-footer guest-login-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Continue browsing</button>
                        <a href="index.php" class="btn btn-primary"><i class="fa-solid fa-right-to-bracket me-2" aria-hidden="true"></i>Log in to continue</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="guestFeedbackLoginModal" tabindex="-1" aria-labelledby="guestFeedbackLoginModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered guest-login-dialog">
                <div class="modal-content border-0 shadow-lg guest-login-content">
                    <div class="modal-header text-white guest-login-header">
                        <span class="guest-login-icon" aria-hidden="true"><i class="fa-solid fa-lock"></i></span>
                        <div>
                            <span class="guest-login-eyebrow">Feedback access</span>
                            <h2 class="modal-title fw-bold mb-0" id="guestFeedbackLoginModalLabel">Login required</h2>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body guest-login-body">
                        <h3 class="guest-login-title">Sign in to send report</h3>
                        <p class="guest-login-copy mb-0">Please log in to share suggestions or request assistance.</p>
                    </div>
                    <div class="modal-footer guest-login-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Continue browsing</button>
                        <a href="index.php" class="btn btn-primary"><i class="fa-solid fa-right-to-bracket me-2" aria-hidden="true"></i>Log in to continue</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="feedbackModalLabel">Send Report</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if ($feedbackMessage !== ''): ?>
                        <p class="feedback-status"><?= htmlspecialchars($feedbackMessage, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php elseif ($feedbackError !== ''): ?>
                        <p class="feedback-error"><?= htmlspecialchars($feedbackError, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                    <form method="post" action="dashboard.php#feedback" class="feedback-form">
                        <input type="hidden" name="action" value="submit_feedback">
                        <input type="text" name="feedback_name" placeholder="Your name" value="<?= htmlspecialchars($_POST['feedback_name'] ?? $loggedInUserName, ENT_QUOTES, 'UTF-8'); ?>" readonly required>
                        <input type="email" name="feedback_email" placeholder="Your email" value="<?= htmlspecialchars($_POST['feedback_email'] ?? $loggedInUserEmail, ENT_QUOTES, 'UTF-8'); ?>" readonly required>
                        <textarea name="feedback_message" placeholder="Tell us what we can improve" maxlength="2000" required><?= htmlspecialchars($_POST['feedback_message'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <button type="submit"><i class="fa-solid fa-circle-exclamation me-2"></i>Send Report</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addUserModalLabel"><i class="fa-solid fa-user-plus me-2"></i>Create User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label"><strong>Registration Number <span class="text-danger" aria-hidden="true">*</span></strong></label><input type="text" name="registration_number" class="form-control" required></div>
                            <div class="col-md-6"><label class="form-label"><strong>Student Number <span class="text-danger" aria-hidden="true">*</span></strong></label><input type="text" name="student_number" class="form-control" required></div>
                            <div class="col-md-4"><label class="form-label"><strong>Last Name <span class="text-danger" aria-hidden="true">*</span></strong></label><input type="text" name="last_name" class="form-control name-field" pattern="^[\pL\s'.-]+$" required></div>
                            <div class="col-md-4"><label class="form-label"><strong>First Name <span class="text-danger" aria-hidden="true">*</span></strong></label><input type="text" name="first_name" class="form-control name-field" pattern="^[\pL\s'.-]+$" required></div>
                            <div class="col-md-4"><label class="form-label"><strong>Middle Name</strong></label><input type="text" name="middle_name" class="form-control name-field" pattern="^[\pL\s'.-]+$"></div>
                            <div class="col-md-2"><label class="form-label"><strong>Suffix</strong></label><input type="text" name="suffix" class="form-control"></div>
                            <div class="col-md-3"><label class="form-label"><strong>Barangay <span class="text-danger" aria-hidden="true">*</span></strong></label><input type="text" name="barangay" class="form-control" required></div>
                            <div class="col-md-3"><label class="form-label"><strong>City <span class="text-danger" aria-hidden="true">*</span></strong></label><input type="text" name="city" class="form-control" required></div>
                            <div class="col-md-4"><label class="form-label"><strong>Province <span class="text-danger" aria-hidden="true">*</span></strong></label><input type="text" name="province" class="form-control" required></div>
                            <div class="col-md-4"><label class="form-label"><strong>Birthday <span class="text-danger" aria-hidden="true">*</span></strong></label><input type="date" name="birthday" class="form-control" required></div>
                            <div class="col-md-4"><label class="form-label"><strong>Sex <span class="text-danger" aria-hidden="true">*</span></strong></label><select name="sex" class="form-select sex-select" required>
                                <option value="">Select</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Others">Others</option>
                                <option value="Prefer not to say">Prefer not to say</option>
                            </select></div>
                            <div class="col-md-4"><label class="form-label"><strong>Custom Sex</strong></label><input type="text" name="custom_sex" class="form-control custom-sex-field" placeholder="If Others"></div>
                            <div class="col-md-6"><label class="form-label"><strong>Mobile Number <span class="text-danger" aria-hidden="true">*</span></strong></label><input type="text" name="mobile_number" class="form-control" required></div>
                            <div class="col-md-6"><label class="form-label"><strong>Email <span class="text-danger" aria-hidden="true">*</span></strong></label><input type="email" name="email" class="form-control" required></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_user" value="1" class="btn btn-success">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="addAdminModalLabel"><i class="fa-solid fa-user-shield me-2"></i>Promote Existing User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">Search by</label>
                            <input type="text" id="adminUserSearchInput" class="form-control" placeholder="Search by id, registration number, student number, name, or email">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Field</label>
                            <select id="adminUserSearchField" class="form-select">
                                <option value="all">All fields</option>
                                <option value="id">ID</option>
                                <option value="registration_number">Registration Number</option>
                                <option value="student_number">Student Number</option>
                                <option value="last_name">Last Name</option>
                                <option value="first_name">First Name</option>
                                <option value="middle_name">Middle Name</option>
                                <option value="email">Email</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0 alternating-row-striping" id="adminUserSearchTable">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Registration #</th>
                                    <th>Student #</th>
                                    <th>Last Name</th>
                                    <th>First Name</th>
                                    <th>Middle Name</th>
                                    <th>Email</th>
                                    <th class="text-center" style="width:150px;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="adminUserSearchResults"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="importCsvModal" tabindex="-1" aria-labelledby="importCsvModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 760px;">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 18px; overflow: hidden;">
                <div class="modal-header text-white" style="background: linear-gradient(135deg, #1e7e34 0%, #198754 100%); border: 0; padding: 1rem 1.25rem;">
                    <h5 class="modal-title fw-bold d-flex align-items-center" id="importCsvModalLabel">
                        <i class="fa-solid fa-file-csv me-2"></i>Import Users CSV
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="import_target_role" value="user">
                    <div class="modal-body p-4" style="background: linear-gradient(180deg, #f9fbff 0%, #ffffff 100%);">
                        <div class="border rounded-3 p-3 mb-3 bg-white shadow-sm" style="border-color: rgba(25, 135, 84, 0.18)!important;">
                            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                                <span class="fw-bold text-dark">CSV format</span>
                                <button type="submit" name="download_template" value="1" formnovalidate class="btn btn-sm btn-outline-success fw-semibold">
                                    <i class="fa-solid fa-download me-1"></i>Download Template
                                </button>
                            </div>
                        </div>

                        <div class="rounded-4 p-3 mb-3 border" style="background: linear-gradient(180deg, #f5f9ff 0%, #ffffff 100%); border-color: rgba(20,54,156,0.12)!important;">
                            <div class="fw-bold text-dark mb-2">Required CSV columns</div>
                            <div class="small text-secondary mb-2" style="word-break: break-word; line-height: 1.6;">
                                <span class="text-primary fw-semibold">registration_number</span>, <span class="text-primary fw-semibold">student_number</span>, <span class="text-primary fw-semibold">last_name</span>, <span class="text-primary fw-semibold">first_name</span>, <span class="text-primary fw-semibold">middle_name</span>, <span class="text-primary fw-semibold">suffix</span>, <span class="text-primary fw-semibold">barangay</span>, <span class="text-primary fw-semibold">city</span>, <span class="text-primary fw-semibold">province</span>, <span class="text-primary fw-semibold">birthday</span>, <span class="text-primary fw-semibold">sex</span>, <span class="text-primary fw-semibold">mobile_number</span>, <span class="text-primary fw-semibold">email</span>
                            </div>
                            <div class="small text-dark mb-1"><strong>Notes:</strong> Every field is required except <strong>suffix</strong>. The import target role is determined by the tab you opened this CSV import from.</div>
                        </div>

                        <label for="csv_file" class="form-label fw-semibold text-dark mb-2">Choose file</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fa-solid fa-file-arrow-up text-success"></i></span>
                            <input type="file" id="csv_file" name="csv_file" class="form-control" accept=".csv" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4 pt-0" style="background: #f9fbff;">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="import_csv" value="1" class="btn btn-success px-4 fw-semibold shadow-sm">
                            <i class="fa-solid fa-upload me-2"></i>Upload CSV
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="importDocumentCsvModal" tabindex="-1" aria-labelledby="importDocumentCsvModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 760px;">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 18px; overflow: hidden;">
                <div class="modal-header text-white" style="background: linear-gradient(135deg, #1e7e34 0%, #198754 100%); border: 0; padding: 1rem 1.25rem;">
                    <h5 class="modal-title fw-bold d-flex align-items-center" id="importDocumentCsvModalLabel">
                        <i class="fa-solid fa-file-csv me-2"></i>Import Documents CSV
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="docs/dcrud.php" enctype="multipart/form-data">
                    <div class="modal-body p-4" style="background: linear-gradient(180deg, #f9fbff 0%, #ffffff 100%);">
                        <div class="border rounded-3 p-3 mb-3 bg-white shadow-sm" style="border-color: rgba(25, 135, 84, 0.18)!important;">
                            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                                <span class="fw-bold text-dark">CSV format</span>
                                <a href="docs/document_import_template.php" class="btn btn-sm btn-outline-success fw-semibold" download>
                                    <i class="fa-solid fa-download me-1"></i>Download Template
                                </a>
                            </div>
                        </div>

                        <div class="rounded-4 p-3 mb-3 border" style="background: linear-gradient(180deg, #f5f9ff 0%, #ffffff 100%); border-color: rgba(20,54,156,0.12)!important;">
                            <div class="fw-bold text-dark mb-2">Required CSV columns</div>
                            <div class="small text-secondary mb-2" style="word-break: break-word; line-height: 1.6;">
                                <span class="text-primary fw-semibold">document_title</span>, <span class="text-primary fw-semibold">office_name</span>, <span class="text-primary fw-semibold">classification</span>, <span class="text-primary fw-semibold">transaction_type</span>, <span class="text-primary fw-semibold">who_may_avail</span>, <span class="text-primary fw-semibold">requirement_name</span>, <span class="text-primary fw-semibold">where_to_secure</span>, <span class="text-primary fw-semibold">step_number</span>, <span class="text-primary fw-semibold">client_step</span>, <span class="text-primary fw-semibold">agency_action</span>, <span class="text-primary fw-semibold">fees</span>, <span class="text-primary fw-semibold">processing_time</span>, <span class="text-primary fw-semibold">person_responsible</span>, <span class="text-primary fw-semibold">total_fees</span>, <span class="text-primary fw-semibold">total_time</span>
                            </div>
                            <div class="small text-dark mb-1"><strong>Upload restrictions:</strong> Only <strong>.csv</strong> files are accepted.</div>
                            <div class="small text-dark mb-1">The CSV must include all required columns shown above in the exact format used by the document import reference.</div>
                            <div class="small text-dark mb-1">Duplicate document titles and sample placeholder rows are ignored by the import process.</div>
                            <div class="small text-dark mb-0">This follows the same validation and notification behavior used in the document management reference flow.</div>
                        </div>

                        <label for="document_csv_file" class="form-label fw-semibold text-dark mb-2">Choose file</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fa-solid fa-file-arrow-up text-success"></i></span>
                            <input type="file" id="document_csv_file" name="document_csv_file" class="form-control" accept=".csv" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4 pt-0" style="background: #f9fbff;">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="import_document_csv" value="1" class="btn btn-success px-4 fw-semibold shadow-sm">
                            <i class="fa-solid fa-upload me-2"></i>Upload CSV
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="functions/delete.php">
                    <input type="hidden" name="id" id="deleteUserId">
                    <div class="modal-header border-0 py-3" style="background:#F6F7FA; border-top:4px solid #FFCE00; border-radius: calc(0.5rem - 1px) calc(0.5rem - 1px) 0 0;">
                        <h5 class="modal-title fw-bold" id="deleteUserModalLabel" style="color:#15171F;">
                            <i class="bi bi-trash3 text-danger me-2"></i>Confirm Deletion
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="text-center mb-4">
                            <div class="display-6 text-danger mb-2"><i class="bi bi-exclamation-octagon"></i></div>
                            <h6 class="fw-bold text-dark">Are you sure you want to delete this account?</h6>
                            <p class="text-muted small mb-0">The account will be moved to Deleted Users, where an administrator can restore it.</p>
                        </div>
                        <div class="bg-light p-3 rounded-3 border">
                            <div class="mb-2">
                                <label class="form-label smallest fw-bold text-uppercase text-muted mb-0" style="font-size: 0.7rem;">Target User</label>
                                <div class="fw-bold text-dark" data-user-field="delete-name"></div>
                            </div>
                            <div class="row">
                                <div class="col-4">
                                    <label class="form-label smallest fw-bold text-uppercase text-muted mb-0" style="font-size:0.7rem;">User ID</label>
                                    <div class="small text-dark" data-user-field="delete-id"></div>
                                </div>
                                <div class="col-8">
                                    <label class="form-label smallest fw-bold text-uppercase text-muted mb-0" style="font-size:0.7rem;">Email Address</label>
                                    <div class="small text-dark text-truncate" data-user-field="delete-email"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0 pb-4 px-4">
                        <div class="row g-2 w-100">
                            <div class="col-6">
                                <button type="button" class="btn btn-light border w-100 py-2 fw-bold" data-bs-dismiss="modal">Cancel</button>
                            </div>
                            <div class="col-6">
                                <button type="submit" name="delete" class="btn btn-danger w-100 py-2 fw-bold shadow-sm">Delete Users <i class="bi bi-trash ms-1"></i></button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="demoteUserModal" tabindex="-1" aria-labelledby="demoteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
                <form method="POST" action="dashboard.php">
                    <div class="modal-header border-0 text-dark" style="background: #f1f3f5; padding: 1rem 1.25rem;">
                        <h5 class="modal-title fw-bold" id="demoteUserModalLabel">Demote Admin</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body px-4 py-4" style="background: #fff;">
                        <p class="mb-0 text-dark">Are you sure you want to demote <strong data-user-field="demote-name"></strong> to regular user?</p>
                        <input type="hidden" name="demote_admin_id" id="demoteUserId">
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4 pt-0" style="background: #fff;">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="demote_admin_submit" value="1" class="btn btn-warning fw-semibold">Confirm Demotion</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="bulkDeleteUserModal" tabindex="-1" aria-labelledby="bulkDeleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 18px; overflow: hidden;">
                <div class="modal-header text-white" style="background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%); border: 0; padding: 1rem 1.25rem;">
                    <h5 class="modal-title fw-bold d-flex align-items-center" id="bulkDeleteUserModalLabel">
                        <i class="fa-solid fa-trash me-2"></i>Delete Selected Users
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="bulkDeleteForm" method="POST" action="functions/delete.php">
                    <input type="hidden" name="bulk_delete" value="1">
                    <input type="hidden" name="selected_ids" id="selectedUserIds">
                    <div class="modal-body p-4">
                        <p class="mb-3">Are you sure you want to delete the selected user(s)? This action cannot be undone.</p>
                        <div id="bulkDeleteUserList" class="d-grid gap-2 small text-dark"></div>
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4 pt-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger px-4 fw-semibold">
                            <i class="fa-solid fa-trash me-2"></i>Delete Selected
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteDocumentModal" tabindex="-1" aria-labelledby="deleteDocumentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 18px; overflow: hidden;">
                <div class="modal-header text-white" style="background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%); border: 0; padding: 1rem 1.25rem;">
                    <h5 class="modal-title fw-bold d-flex align-items-center" id="deleteDocumentModalLabel">
                        <i class="fa-solid fa-trash me-2"></i>Delete Document
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="functions/delete.php">
                    <div class="modal-body p-4">
                        <input type="hidden" name="id" id="deleteDocumentId">
                        <input type="hidden" name="target" value="document">
                        <div class="text-center text-danger mb-3">
                            <i class="fa-solid fa-triangle-exclamation fa-2x"></i>
                        </div>
                        <p class="mb-3 text-center">Are you sure you want to delete <strong id="deleteDocumentTitle">this document</strong>?</p>
                        <div class="bg-light border rounded-3 p-3 small text-dark">
                            <div class="mb-2"><strong>Office:</strong> <span data-document-field="delete-office">-</span></div>
                            <div class="mb-2"><strong>Classification:</strong> <span data-document-field="delete-classification">-</span></div>
                            <div class="mb-0"><strong>Transaction Type:</strong> <span data-document-field="delete-transaction">-</span></div>
                        </div>
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4 pt-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete" value="1" class="btn btn-danger px-4 fw-semibold">
                            <i class="fa-solid fa-trash me-2"></i>Delete Document
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="bulkDeleteDocumentModal" tabindex="-1" aria-labelledby="bulkDeleteDocumentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 18px; overflow: hidden;">
                <div class="modal-header text-white" style="background: linear-gradient(135deg, #dc3545 0%, #b02a37 100%); border: 0; padding: 1rem 1.25rem;">
                    <h5 class="modal-title fw-bold d-flex align-items-center" id="bulkDeleteDocumentModalLabel">
                        <i class="fa-solid fa-trash me-2"></i>Delete Selected Documents
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="documentBulkDeleteForm" method="POST" action="functions/delete.php">
                    <input type="hidden" name="bulk_delete" value="1">
                    <input type="hidden" name="target" value="document">
                    <div class="modal-body p-4">
                        <p class="mb-3">Are you sure you want to delete the selected document(s)? This action cannot be undone.</p>
                        <div id="documentBulkDeleteList" class="d-grid gap-2 small text-dark"></div>
                    </div>
                    <div class="modal-footer border-0 px-4 pb-4 pt-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger px-4 fw-semibold">
                            <i class="fa-solid fa-trash me-2"></i>Delete Selected
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>

    <script type="application/json" id="adminUserSearchData">
        <?= json_encode($users, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
    </script>

    <script>
        document.addEventListener('click', async function (event) {
            const trigger = event.target.closest('.document-details-btn');
            if (!trigger) return;

            if (<?= $isGuest ? 'true' : 'false'; ?>) return;

            const modalElement = document.getElementById('documentDetailsModal');
            const modalBody = document.getElementById('documentModalBody');
            if (!modalElement || !modalBody) return;

            const documentId = trigger.dataset.documentId;
            if (!documentId) return;

            modalBody.innerHTML = '<div class="text-center py-5 text-muted"><div class="spinner-border text-primary" role="status" aria-hidden="true"></div><div class="mt-3">Loading document details...</div></div>';

            try {
                const response = await fetch('dashboard.php?doc_modal=' + encodeURIComponent(documentId), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const html = await response.text();
                modalBody.innerHTML = html;
            } catch (error) {
                modalBody.innerHTML = '<div class="alert alert-danger mb-0">Unable to load document details.</div>';
            }
        });

        document.addEventListener('click', function (event) {
            const addButton = event.target.closest('.home-card-add-item');
            if (addButton) {
                const list = document.getElementById(addButton.dataset.listTarget);
                if (!list) return;

                const itemCount = list.querySelectorAll('.home-card-list-item').length;
                if (itemCount >= 12) return;

                const itemRow = document.createElement('div');
                itemRow.className = list.dataset.listType === 'destination_list' ? 'd-flex flex-wrap gap-2 home-card-list-item' : 'd-flex gap-2 home-card-list-item';

                const input = document.createElement('input');
                input.className = 'form-control';
                input.type = 'text';
                input.maxLength = Number(list.dataset.maxlength);
                input.required = true;

                if (list.dataset.listType === 'destination_list') {
                    const existingIndexes = [...list.querySelectorAll('.home-card-list-item')].map(row => Number(row.dataset.itemIndex));
                    const itemIndex = Math.max(-1, ...existingIndexes) + 1;
                    input.className = 'form-control flex-grow-1';
                    itemRow.dataset.itemIndex = String(itemIndex);
                    input.id = list.id + '_' + itemIndex + '_label';
                    input.name = list.dataset.inputName + '[' + itemIndex + '][label]';
                    input.setAttribute('aria-label', 'Destination label ' + (itemCount + 1));

                    const targetSelect = document.createElement('select');
                    targetSelect.className = 'form-select flex-grow-1';
                    targetSelect.name = list.dataset.inputName + '[' + itemIndex + '][target]';
                    targetSelect.setAttribute('aria-label', 'Destination location ' + (itemCount + 1));
                    targetSelect.required = true;
                    let optionGroup = null;
                    let optionGroupName = null;
                    JSON.parse(list.dataset.targets || '[]').forEach((target) => {
                        if (target.group !== optionGroupName) {
                            optionGroup = document.createElement('optgroup');
                            optionGroup.label = target.group;
                            targetSelect.append(optionGroup);
                            optionGroupName = target.group;
                        }
                        const option = document.createElement('option');
                        option.value = target.id;
                        option.textContent = target.label;
                        option.dataset.name = target.name;
                        optionGroup.append(option);
                    });
                    const firstName = (targetSelect.selectedOptions[0]?.dataset.name || '').slice(0, Number(list.dataset.maxlength) || 60);
                    input.value = firstName;
                    input.dataset.autoLabel = firstName;
                    itemRow.append(input, targetSelect);
                } else {
                    input.name = list.dataset.inputName + '[]';
                    input.setAttribute('aria-label', 'Item ' + (itemCount + 1));
                    itemRow.dataset.itemIndex = String(Math.max(-1, ...[...list.querySelectorAll('.home-card-list-item')].map(row => Number(row.dataset.itemIndex))) + 1);
                    itemRow.append(input);
                }

                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'btn btn-outline-danger home-card-remove-item';
                removeButton.setAttribute('aria-label', list.dataset.listType === 'destination_list' ? 'Remove destination' : 'Remove item');
                removeButton.innerHTML = '<i class="fa-solid fa-trash" aria-hidden="true"></i>';

                itemRow.append(removeButton);
                list.append(itemRow);
                input.focus();
                return;
            }

            const removeButton = event.target.closest('.home-card-remove-item');
            if (!removeButton) return;

            const list = removeButton.closest('[data-input-name]');
            const itemRow = removeButton.closest('.home-card-list-item');
            if (list && itemRow && list.querySelectorAll('.home-card-list-item').length > 1) {
                itemRow.remove();
            }
        });

        // Quick destinations: when a different room is chosen, update the label unless it was customised.
        document.querySelectorAll('[data-list-type="destination_list"] .home-card-list-item').forEach(function (row) {
            const labelInput = row.querySelector('input[type="text"]');
            const select = row.querySelector('select');
            const currentName = select?.selectedOptions[0]?.dataset.name || '';
            if (labelInput && currentName && labelInput.value === currentName) {
                labelInput.dataset.autoLabel = currentName;
            }
        });

        document.addEventListener('change', function (event) {
            const select = event.target.closest('[data-list-type="destination_list"] select');
            if (!select) return;

            const list = select.closest('[data-list-type="destination_list"]');
            const labelInput = select.closest('.home-card-list-item')?.querySelector('input[type="text"]');
            if (!list || !labelInput) return;

            const newName = (select.selectedOptions[0]?.dataset.name || '').slice(0, Number(list.dataset.maxlength) || 60);
            if (labelInput.value.trim() === '' || labelInput.value === (labelInput.dataset.autoLabel || '')) {
                labelInput.value = newName;
            }
            labelInput.dataset.autoLabel = newName;
        });

        const mapShell = document.getElementById('map-preview-shell');
        const mapMaximizeBtn = document.getElementById('mapMaximizeBtn');
        const mapConfigurationShell = document.getElementById('mapConfigurationShell');
        const mapConfigurationLandscapeBtn = document.getElementById('mapConfigurationLandscapeBtn');
        let pendingMapTargets = null;

        function renderAdminUserSearchResults(query, field) {
            const rawData = document.getElementById('adminUserSearchData');
            const resultsBody = document.getElementById('adminUserSearchResults');
            if (!rawData || !resultsBody) return;

            try {
                const users = JSON.parse(rawData.textContent || '[]');
                const normalizedQuery = (query || '').trim().toLowerCase();

                const filteredUsers = users.filter(function (user) {
                    const userRoleId = Number(user.role_id ?? 0);
                    if (userRoleId === 1) {
                        return false;
                    }

                    if (!normalizedQuery) return true;

                    const values = {
                        id: String(user.id ?? ''),
                        registration_number: String(user.registration_number ?? ''),
                        student_number: String(user.student_number ?? ''),
                        last_name: String(user.last_name ?? ''),
                        first_name: String(user.first_name ?? ''),
                        middle_name: String(user.middle_name ?? ''),
                        email: String(user.email ?? '')
                    };

                    if (field && field !== 'all') {
                        return (values[field] || '').toLowerCase().includes(normalizedQuery);
                    }

                    return Object.values(values).some(function (value) {
                        return String(value).toLowerCase().includes(normalizedQuery);
                    });
                });

                if (!filteredUsers.length) {
                    resultsBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-3">No matching users found.</td></tr>';
                    return;
                }

                resultsBody.innerHTML = filteredUsers.map(function (user) {
                    return `
                        <tr>
                            <td>${user.id ?? ''}</td>
                            <td>${user.registration_number ?? ''}</td>
                            <td>${user.student_number ?? ''}</td>
                            <td>${user.last_name ?? ''}</td>
                            <td>${user.first_name ?? ''}</td>
                            <td>${user.middle_name ?? ''}</td>
                            <td>${user.email ?? ''}</td>
                            <td class="text-center">
                                <form method="POST" action="dashboard.php#admin" class="d-inline">
                                    <input type="hidden" name="promote_admin_id" value="${user.id ?? ''}">
                                    <button type="submit" name="promote_admin_submit" value="1" class="btn btn-sm btn-warning text-dark">Promote</button>
                                </form>
                            </td>
                        </tr>
                    `;
                }).join('');
            } catch (error) {
                resultsBody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-3">Unable to load user search results.</td></tr>';
            }
        }

        function setDashboardTabCookie(tabName) {
            const allowedTabs = ['home', 'map', 'documents', 'admin', 'users', 'tickets', 'docs-management', 'document-requests', 'map-configuration', 'audit-logs', 'archive'];
            if (!allowedTabs.includes(tabName)) {
                return;
            }

            document.cookie = 'dashboard_active_tab=' + tabName + '; path=/; max-age=' + (60 * 60 * 24 * 365) + '; SameSite=Lax';

            // A ?tab= value in the address bar wins over the cookie on the server, so keep it in step
            // with the tab that is actually open. Otherwise a refresh jumps back to the tab in the URL.
            try {
                const url = new URL(window.location.href);
                if (url.searchParams.has('tab') && url.searchParams.get('tab') !== tabName) {
                    url.searchParams.set('tab', tabName);
                    window.history.replaceState(window.history.state, '', url.toString());
                }
            } catch (error) {
                // Ignore URL update failures; the cookie still stores the tab.
            }
        }

        function getDashboardTabScrollPositions() {
            const cookie = document.cookie.split('; ').find(function (part) {
                return part.startsWith('dashboard_tab_scroll=');
            });
            if (!cookie) return {};

            try {
                return JSON.parse(decodeURIComponent(cookie.substring('dashboard_tab_scroll='.length))) || {};
            } catch (error) {
                return {};
            }
        }

        function saveDashboardTabScroll(tabName, scrollPosition) {
            if (!tabName) return;
            const positions = getDashboardTabScrollPositions();
            positions[tabName] = Math.max(0, Math.round(scrollPosition));
            document.cookie = 'dashboard_tab_scroll=' + encodeURIComponent(JSON.stringify(positions)) + '; path=/; max-age=' + (60 * 60 * 24 * 365) + '; SameSite=Lax';
        }

        function getActiveDashboardTabKey() {
            return getDashboardTabKeyFromButton(document.querySelector('.header-nav .nav-link.active'));
        }

        function restoreDashboardTabScroll(tabName) {
            if (!tabName) return;
            const positions = getDashboardTabScrollPositions();
            window.scrollTo({ top: Number(positions[tabName] || 0), left: 0, behavior: 'auto' });
        }

        function getDashboardTabKeyFromButton(button) {
            if (!button || !button.id) return '';

            const mapping = {
                'pills-landing-tab': 'home',
                'pills-home-tab': 'map',
                'pills-documents-tab': 'documents',
                'pills-admin-tab': 'admin',
                'pills-users-tab': 'users',
                'pills-tickets-tab': 'tickets',
                'pills-docs-management-tab': 'docs-management',
                'pills-document-requests-tab': 'document-requests',
                'pills-map-configuration-tab': 'map-configuration',
                'pills-audit-logs-tab': 'audit-logs',
                'pills-archive-tab': 'archive'
            };

            return mapping[button.id] || '';
        }

        document.addEventListener('DOMContentLoaded', function () {
            const adminSearchInput = document.getElementById('adminUserSearchInput');
            const adminSearchField = document.getElementById('adminUserSearchField');
            const ticketActionConfirmModal = document.getElementById('ticketActionConfirmModal');
            const ticketActionConfirmMessage = document.getElementById('ticketActionConfirmMessage');
            const ticketActionConfirmButton = document.getElementById('ticketActionConfirmButton');
            let pendingTicketActionForm = null;

            ticketActionConfirmModal?.addEventListener('show.bs.modal', function (event) {
                const trigger = event.relatedTarget;
                if (!(trigger instanceof HTMLElement)) return;

                pendingTicketActionForm = trigger.closest('form');
                const action = trigger.dataset.ticketAction;
                const ticketCode = trigger.dataset.ticketCode || 'this ticket';
                ticketActionConfirmMessage.textContent = action === 'resolve'
                    ? `Mark ${ticketCode} as resolved?`
                    : `Close ${ticketCode}? This will mark it as closed.`;
                ticketActionConfirmButton.textContent = action === 'resolve' ? 'Mark resolved' : 'Close ticket';
                ticketActionConfirmButton.classList.toggle('btn-danger', action === 'close');
                ticketActionConfirmButton.classList.toggle('btn-primary', action !== 'close');
            });

            ticketActionConfirmButton?.addEventListener('click', function () {
                if (!pendingTicketActionForm) return;

                const trigger = pendingTicketActionForm.querySelector('.ticket-confirm-trigger');
                if (!trigger) return;

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'ticket_action';
                actionInput.value = trigger.dataset.ticketAction || '';
                pendingTicketActionForm.appendChild(actionInput);
                pendingTicketActionForm.submit();
            });

            const resetLegendScroll = function () {
                document.querySelectorAll('.document-legend-items, .legend-items').forEach(function (container) {
                    container.scrollTop = 0;
                    container.scrollLeft = 0;
                });
            };

            if (adminSearchInput && adminSearchField) {
                renderAdminUserSearchResults('', 'all');
                adminSearchInput.addEventListener('input', function () {
                    renderAdminUserSearchResults(adminSearchInput.value, adminSearchField.value);
                });
                adminSearchField.addEventListener('change', function () {
                    renderAdminUserSearchResults(adminSearchInput.value, adminSearchField.value);
                });
            }

            const documentSearchInput = document.getElementById('documentSearchInput');
            const documentPoiSuggestions = document.getElementById('documentPoiSuggestions');
            const documentSearchForm = documentSearchInput?.closest('form');
            const documentMapPois = [
                { label: 'Admission', keywords: 'admission counselor counseling' },
                { label: 'BMGS', keywords: 'business management' },
                { label: 'BSIE Stockroom', keywords: 'bsie stockroom supplies storage' },
                { label: 'Canteen 1 (North)', keywords: 'canteen food restaurant cafeteria' },
                { label: 'Canteen 2 (South)', keywords: 'canteen food restaurant cafeteria' },
                { label: 'Cashier Office', keywords: 'cashier payment tuition fees receipt' },
                { label: 'CICT Faculty', keywords: 'cict faculty teachers professors' },
                { label: 'Clinic', keywords: 'clinic health medical nurse medicine' },
                { label: 'COED Faculty', keywords: 'coed faculty teachers professors' },
                { label: 'COED Office', keywords: 'coed office education' },
                { label: 'Conference Room', keywords: 'conference meeting seminar' },
                { label: 'Dean Office', keywords: 'dean office administration director' },
                { label: 'Female CR', keywords: 'female women restroom toilet comfort room' },
                { label: 'Guard House / Entrance', keywords: 'guard security entrance gate main entry' },
                { label: 'Gym', keywords: 'gym fitness sports exercise' },
                { label: 'Information Desk', keywords: 'information info desk front' },
                { label: 'Laboratory', keywords: 'laboratory lab computer science' },
                { label: 'Library', keywords: 'library books reading study research' },
                { label: 'Male CR', keywords: 'male men restroom toilet comfort room' },
                { label: 'NSTP', keywords: 'nstp training requirements office' },
                { label: 'Office of Registrar', keywords: 'registrar records transcript clearance documents' },
                { label: 'OSOAD', keywords: 'osoad student affairs services' },
                { label: 'Room 101', keywords: '101 room classroom highschool' },
                { label: 'Room 102', keywords: '102 room classroom highschool' },
                { label: 'Room 103', keywords: '103 room classroom highschool' },
                { label: 'Room 104', keywords: '104 room classroom highschool' },
                { label: 'Room 107', keywords: '107 room classroom' },
                { label: 'Room 108', keywords: '108 room classroom' },
                { label: 'Room 109', keywords: '109 room classroom' },
                { label: 'Room 110', keywords: '110 room classroom' },
                { label: 'Room 113', keywords: '113 room classroom' },
                { label: 'Room 114', keywords: '114 room classroom' },
                { label: 'Room 201', keywords: '201 room computer laboratory comlab' },
                { label: 'Room 202', keywords: '202 room computer laboratory comlab' },
                { label: 'Room 203', keywords: '203 room computer laboratory comlab' },
                { label: 'Room 204', keywords: '204 room computer laboratory comlab' },
                { label: 'Room 206', keywords: '206 room classroom' },
                { label: 'Room 207', keywords: '207 room classroom' },
                { label: 'Room 208', keywords: '208 room classroom' },
                { label: 'Room 209', keywords: '209 room classroom' },
                { label: 'Room 211', keywords: '211 room classroom' },
                { label: 'Room 212', keywords: '212 room classroom' },
                { label: 'Room 213', keywords: '213 room classroom' },
                { label: 'Room 214', keywords: '214 room classroom' },
                { label: 'Room 215', keywords: '215 room classroom' },
                { label: 'Room 218', keywords: '218 room classroom' },
                { label: 'Room 219', keywords: '219 room classroom' },
                { label: 'Room 220', keywords: '220 room classroom' },
                { label: 'Room 221', keywords: '221 room classroom' },
                { label: 'Room 222', keywords: '222 room classroom information system management' },
                { label: 'Room 301', keywords: '301 room classroom' },
                { label: 'Room 302', keywords: '302 room classroom' },
                { label: 'Room 303', keywords: '303 room classroom' },
                { label: 'Stock Room', keywords: 'stockroom supplies storage' }
            ];
            let activeDocumentPoiSuggestion = -1;

            function hideDocumentPoiSuggestions() {
                if (!documentPoiSuggestions || !documentSearchInput) return;
                documentPoiSuggestions.style.display = 'none';
                documentSearchInput.setAttribute('aria-expanded', 'false');
                documentSearchInput.removeAttribute('aria-activedescendant');
                activeDocumentPoiSuggestion = -1;
            }

            function selectDocumentPoiSuggestion(poi) {
                if (!documentSearchInput || !poi) return;
                documentSearchInput.value = poi.label;
                hideDocumentPoiSuggestions();
                documentSearchInput.focus();
            }

            function renderDocumentPoiSuggestions() {
                if (!documentSearchInput || !documentPoiSuggestions) return;

                const query = documentSearchInput.value.trim().toLowerCase();
                documentPoiSuggestions.replaceChildren();
                activeDocumentPoiSuggestion = -1;
                if (!query) {
                    hideDocumentPoiSuggestions();
                    return;
                }

                const matches = documentMapPois
                    .filter(function (poi) {
                        return (poi.label + ' ' + poi.keywords).toLowerCase().includes(query);
                    })
                    .sort(function (a, b) {
                        const aStarts = a.label.toLowerCase().startsWith(query) ? 0 : 1;
                        const bStarts = b.label.toLowerCase().startsWith(query) ? 0 : 1;
                        return aStarts - bStarts || a.label.localeCompare(b.label);
                    })
                    .slice(0, 8);

                if (!matches.length) {
                    hideDocumentPoiSuggestions();
                    return;
                }

                matches.forEach(function (poi, index) {
                    const option = document.createElement('button');
                    option.type = 'button';
                    option.id = 'documentPoiSuggestion_' + index;
                    option.className = 'document-poi-suggestion';
                    option.setAttribute('role', 'option');
                    option.setAttribute('aria-selected', 'false');

                    const label = document.createElement('span');
                    label.textContent = poi.label;
                    const type = document.createElement('span');
                    type.className = 'document-poi-suggestion-type';
                    type.textContent = 'Map location';
                    option.append(label, type);
                    option.addEventListener('mousedown', function (event) {
                        event.preventDefault();
                    });
                    option.addEventListener('click', function () {
                        selectDocumentPoiSuggestion(poi);
                    });
                    documentPoiSuggestions.appendChild(option);
                });

                documentPoiSuggestions.style.display = 'block';
                documentSearchInput.setAttribute('aria-expanded', 'true');
            }

            function setActiveDocumentPoiSuggestion(index) {
                if (!documentPoiSuggestions || !documentSearchInput) return;
                const options = Array.from(documentPoiSuggestions.querySelectorAll('[role="option"]'));
                if (!options.length) return;

                activeDocumentPoiSuggestion = (index + options.length) % options.length;
                options.forEach(function (option, optionIndex) {
                    const isActive = optionIndex === activeDocumentPoiSuggestion;
                    option.classList.toggle('active', isActive);
                    option.setAttribute('aria-selected', String(isActive));
                });
                documentSearchInput.setAttribute('aria-activedescendant', options[activeDocumentPoiSuggestion].id);
            }

            documentSearchInput?.addEventListener('input', renderDocumentPoiSuggestions);
            documentSearchInput?.addEventListener('focus', renderDocumentPoiSuggestions);
            documentSearchInput?.addEventListener('keydown', function (event) {
                const options = documentPoiSuggestions?.querySelectorAll('[role="option"]') || [];
                if (!options.length) return;

                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    setActiveDocumentPoiSuggestion(activeDocumentPoiSuggestion + 1);
                } else if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    setActiveDocumentPoiSuggestion(activeDocumentPoiSuggestion - 1);
                } else if (event.key === 'Enter' && activeDocumentPoiSuggestion >= 0) {
                    event.preventDefault();
                    options[activeDocumentPoiSuggestion].click();
                } else if (event.key === 'Escape') {
                    hideDocumentPoiSuggestions();
                }
            });
            documentSearchInput?.addEventListener('blur', function () {
                window.setTimeout(hideDocumentPoiSuggestions, 120);
            });
            documentSearchForm?.addEventListener('submit', hideDocumentPoiSuggestions);

            let scrollSaveTimer = null;
            setDashboardTabCookie(getActiveDashboardTabKey());
            const tabButtons = document.querySelectorAll('.header-nav .nav-link[data-bs-toggle="pill"]');
            tabButtons.forEach(function (tabButton) {
                tabButton.addEventListener('hide.bs.tab', function () {
                    const currentTabKey = getDashboardTabKeyFromButton(tabButton);
                    if (!currentTabKey) return;
                    window.clearTimeout(scrollSaveTimer);
                    saveDashboardTabScroll(currentTabKey, window.scrollY);
                });
                tabButton.addEventListener('shown.bs.tab', function () {
                    const tabKey = getDashboardTabKeyFromButton(tabButton);
                    if (tabKey) {
                        setDashboardTabCookie(tabKey);
                        window.requestAnimationFrame(function () {
                            restoreDashboardTabScroll(tabKey);
                        });
                    }
                });
            });

            window.addEventListener('scroll', function () {
                window.clearTimeout(scrollSaveTimer);
                scrollSaveTimer = window.setTimeout(function () {
                    saveDashboardTabScroll(getActiveDashboardTabKey(), window.scrollY);
                }, 120);
            }, { passive: true });

            document.querySelectorAll('.document-legend-link, .document-page-link').forEach(function (link) {
                link.addEventListener('click', function () {
                    resetLegendScroll();
                    setDashboardTabCookie('documents');
                });
            });

            window.addEventListener('pageshow', function () {
                resetLegendScroll();
                restoreDashboardTabScroll(getActiveDashboardTabKey());
            });
        });

        window.openMapTarget = function (targets) {
            document.getElementById('pills-home-tab')?.click();
            sendMapTargets(targets || []);
        };

        function sendMapTargets(targets) {
            const mapFrame = mapShell?.querySelector('iframe');
            if (!mapFrame || !targets?.length) return;

            pendingMapTargets = targets;
            if (mapFrame.contentDocument?.readyState === 'complete') {
                if (typeof mapFrame.contentWindow.highlightMapTargets === 'function') {
                    mapFrame.contentWindow.highlightMapTargets(targets);
                } else {
                    mapFrame.contentWindow.postMessage({ type: 'highlight-map-target', targets }, '*');
                }
                pendingMapTargets = null;
            }
        }

        mapShell?.querySelector('iframe')?.addEventListener('load', () => {
            if (!pendingMapTargets) return;

            const mapFrame = mapShell.querySelector('iframe');
            if (typeof mapFrame.contentWindow.highlightMapTargets === 'function') {
                mapFrame.contentWindow.highlightMapTargets(pendingMapTargets);
            } else {
                mapFrame.contentWindow.postMessage({
                    type: 'highlight-map-target',
                    targets: pendingMapTargets
                }, '*');
            }
            pendingMapTargets = null;
        });

        function notifyEmbeddedMapFullscreen(isMaximized) {
            const mapFrame = mapShell?.querySelector('iframe');
            mapFrame?.contentWindow?.postMessage({
                type: 'map-fullscreen-state',
                maximized: isMaximized
            }, '*');
        }

        async function setMapMaximized(shouldMaximize) {
            if (!mapShell) return;

            try {
                if (shouldMaximize && document.fullscreenElement !== mapShell) {
                    if (mapShell.requestFullscreen) {
                        await mapShell.requestFullscreen();
                    } else {
                        mapShell.classList.add('is-maximized');
                    }
                } else if (!shouldMaximize && document.fullscreenElement === mapShell) {
                    await document.exitFullscreen();
                } else if (!shouldMaximize) {
                    mapShell.classList.remove('is-maximized');
                }
            } catch (error) {
                mapShell.classList.toggle('is-maximized', shouldMaximize);
            }

            if (shouldMaximize && screen.orientation?.lock) {
                try { await screen.orientation.lock('landscape'); } catch (error) {}
            } else if (!shouldMaximize) {
                screen.orientation?.unlock?.();
            }

            document.body.style.overflow = shouldMaximize ? 'hidden' : '';
            if (mapMaximizeBtn) {
                mapMaximizeBtn.setAttribute('aria-expanded', String(shouldMaximize));
                mapMaximizeBtn.innerHTML = shouldMaximize
                    ? '<i class="fa-solid fa-compress" aria-hidden="true"></i>'
                    : '<i class="fa-solid fa-expand" aria-hidden="true"></i>';
                mapMaximizeBtn.setAttribute('aria-label', shouldMaximize ? 'Exit full-screen campus map' : 'Open campus map in landscape');
                mapMaximizeBtn.title = shouldMaximize ? 'Exit full-screen campus map' : 'Open campus map in landscape';
            }
            notifyEmbeddedMapFullscreen(shouldMaximize);
        }

        async function setMapConfigurationMaximized(shouldMaximize) {
            if (!mapConfigurationShell) return;

            try {
                if (shouldMaximize && document.fullscreenElement !== mapConfigurationShell) {
                    if (mapConfigurationShell.requestFullscreen) {
                        await mapConfigurationShell.requestFullscreen();
                    } else {
                        mapConfigurationShell.classList.add('is-maximized');
                    }
                } else if (!shouldMaximize && document.fullscreenElement === mapConfigurationShell) {
                    await document.exitFullscreen();
                } else if (!shouldMaximize) {
                    mapConfigurationShell.classList.remove('is-maximized');
                }
            } catch (error) {
                mapConfigurationShell.classList.toggle('is-maximized', shouldMaximize);
            }

            if (shouldMaximize && screen.orientation?.lock) {
                try { await screen.orientation.lock('landscape'); } catch (error) {}
            } else if (!shouldMaximize) {
                screen.orientation?.unlock?.();
            }

            document.body.style.overflow = shouldMaximize ? 'hidden' : '';
            mapConfigurationLandscapeBtn?.setAttribute('aria-expanded', String(shouldMaximize));
            if (mapConfigurationLandscapeBtn) {
                mapConfigurationLandscapeBtn.innerHTML = shouldMaximize
                    ? '<i class="fa-solid fa-compress" aria-hidden="true"></i>'
                    : '<i class="fa-solid fa-expand" aria-hidden="true"></i>';
                mapConfigurationLandscapeBtn.setAttribute('aria-label', shouldMaximize ? 'Exit full-screen map configuration' : 'Open map configuration in landscape');
                mapConfigurationLandscapeBtn.title = shouldMaximize ? 'Exit full-screen map configuration' : 'Open map configuration in landscape';
            }
        }

        mapMaximizeBtn?.addEventListener('click', () => setMapMaximized(!mapShell?.classList.contains('is-maximized') && document.fullscreenElement !== mapShell));
        mapConfigurationLandscapeBtn?.addEventListener('click', () => setMapConfigurationMaximized(!mapConfigurationShell?.classList.contains('is-maximized') && document.fullscreenElement !== mapConfigurationShell));
        window.addEventListener('message', (event) => {
            if (event.data?.type !== 'open-map-target') return;

            document.getElementById('pills-home-tab')?.click();
            sendMapTargets(event.data.targets || [event.data.target].filter(Boolean));
        });
        document.addEventListener('fullscreenchange', () => {
            const isMapFullscreen = document.fullscreenElement === mapShell;
            const isConfigFullscreen = document.fullscreenElement === mapConfigurationShell;
            mapShell?.classList.toggle('is-maximized', isMapFullscreen);
            mapConfigurationShell?.classList.toggle('is-maximized', isConfigFullscreen);
            document.body.style.overflow = isMapFullscreen || isConfigFullscreen ? 'hidden' : '';
            mapMaximizeBtn?.setAttribute('aria-expanded', String(isMapFullscreen));
            mapConfigurationLandscapeBtn?.setAttribute('aria-expanded', String(isConfigFullscreen));
            if (mapMaximizeBtn) {
                mapMaximizeBtn.innerHTML = isMapFullscreen
                    ? '<i class="fa-solid fa-compress" aria-hidden="true"></i>'
                    : '<i class="fa-solid fa-expand" aria-hidden="true"></i>';
                mapMaximizeBtn.setAttribute('aria-label', isMapFullscreen ? 'Exit full-screen campus map' : 'Open campus map in landscape');
                mapMaximizeBtn.title = isMapFullscreen ? 'Exit full-screen campus map' : 'Open campus map in landscape';
            }
            if (mapConfigurationLandscapeBtn) {
                mapConfigurationLandscapeBtn.innerHTML = isConfigFullscreen
                    ? '<i class="fa-solid fa-compress" aria-hidden="true"></i>'
                    : '<i class="fa-solid fa-expand" aria-hidden="true"></i>';
                mapConfigurationLandscapeBtn.setAttribute('aria-label', isConfigFullscreen ? 'Exit full-screen map configuration' : 'Open map configuration in landscape');
                mapConfigurationLandscapeBtn.title = isConfigFullscreen ? 'Exit full-screen map configuration' : 'Open map configuration in landscape';
            }
            notifyEmbeddedMapFullscreen(isMapFullscreen);
            if (!isMapFullscreen && !isConfigFullscreen) screen.orientation?.unlock?.();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                setMapMaximized(false);
                setMapConfigurationMaximized(false);
            }
        });

        // Announcements/pusher integration removed — no department/office broadcasts

        function toggleSexCustomField(selectEl) {
            const parent = selectEl.closest('.modal-body');
            if (!parent) return;
            const customField = parent.querySelector('.custom-sex-field');
            const showCustom = selectEl.value === 'Others';
            if (customField) {
                customField.disabled = !showCustom;
                customField.required = showCustom;
                customField.closest('div')?.classList.toggle('d-none', !showCustom);
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const importCsvModal = document.getElementById('importCsvModal');
            const importTargetRoleInput = importCsvModal?.querySelector('input[name="import_target_role"]');

            document.querySelectorAll('[data-bs-target="#importCsvModal"]').forEach(function (button) {
                button.addEventListener('click', function () {
                    if (!importTargetRoleInput) return;
                    const role = button.dataset.importRole || 'user';
                    importTargetRoleInput.value = role;
                });
            });

            const deleteUserModal = document.getElementById('deleteUserModal');
            const demoteUserModal = document.getElementById('demoteUserModal');

            function populateTextValue(selector, value) {
                const target = selector && document.querySelector(selector);
                if (!target) return;
                target.textContent = value || '';
            }

            function setUserButtonData(modal, event) {
                const trigger = event.relatedTarget;
                if (!trigger || !modal) return;

                const id = trigger.dataset.id || '';
                const name = trigger.dataset.name || '';
                const email = trigger.dataset.email || '';
                const role = trigger.dataset.role || '';
                const firstName = trigger.dataset.firstname || '';
                const lastName = trigger.dataset.lastname || '';
                const middleName = trigger.dataset.middlename || '';
                const suffix = trigger.dataset.suffix || '';
                const roleId = trigger.dataset.roleid || '0';

                if (modal === deleteUserModal) {
                    const deleteUserId = document.getElementById('deleteUserId');
                    if (deleteUserId) deleteUserId.value = id;
                    populateTextValue('#deleteUserModal [data-user-field="delete-name"]', name);
                    populateTextValue('#deleteUserModal [data-user-field="delete-id"]', id ? '#' + id : '');
                    populateTextValue('#deleteUserModal [data-user-field="delete-email"]', email);
                }

                if (modal === demoteUserModal) {
                    const demoteUserId = document.getElementById('demoteUserId');
                    if (demoteUserId) demoteUserId.value = id;
                    populateTextValue('#demoteUserModal [data-user-field="demote-name"]', name);
                }
            }

            deleteUserModal?.addEventListener('show.bs.modal', function (event) {
                setUserButtonData(deleteUserModal, event);
            });

            demoteUserModal?.addEventListener('show.bs.modal', function (event) {
                setUserButtonData(demoteUserModal, event);
            });

            document.querySelectorAll('.sex-select').forEach(function (selectEl) {
                toggleSexCustomField(selectEl);
                selectEl.addEventListener('change', function () {
                    toggleSexCustomField(selectEl);
                });
            });

            const bulkDeleteButtons = document.querySelectorAll('.bulk-delete-btn');
            const bulkDeleteForm = document.getElementById('bulkDeleteForm');
            const selectedUserIdsInput = document.getElementById('selectedUserIds');
            const docBulkDeleteButtons = document.querySelectorAll('.bulk-doc-delete-btn');
            const docBulkDeleteForm = document.getElementById('documentBulkDeleteForm');
            const docBulkDeleteList = document.getElementById('documentBulkDeleteList');
            const deleteDocumentModal = document.getElementById('deleteDocumentModal');
            const bulkDeleteDocumentModal = document.getElementById('bulkDeleteDocumentModal');

            function populateTextValue(selector, value) {
                const target = selector && document.querySelector(selector);
                if (!target) return;
                target.textContent = value || '';
            }

            function setDocumentDeleteData(modal, event) {
                const trigger = event.relatedTarget;
                if (!trigger || !modal) return;

                const id = trigger.dataset.id || '';
                const title = trigger.dataset.title || 'Document';
                const office = trigger.dataset.office || 'Not set';
                const classification = trigger.dataset.classification || 'Not set';
                const transaction = trigger.dataset.transaction || 'Not set';

                if (modal === deleteDocumentModal) {
                    const deleteDocumentId = document.getElementById('deleteDocumentId');
                    if (deleteDocumentId) deleteDocumentId.value = id;
                    const deleteTitle = document.getElementById('deleteDocumentTitle');
                    if (deleteTitle) deleteTitle.textContent = title;
                    populateTextValue('#deleteDocumentModal [data-document-field="delete-office"]', office);
                    populateTextValue('#deleteDocumentModal [data-document-field="delete-classification"]', classification);
                    populateTextValue('#deleteDocumentModal [data-document-field="delete-transaction"]', transaction);
                }
            }

            deleteDocumentModal?.addEventListener('show.bs.modal', function (event) {
                setDocumentDeleteData(deleteDocumentModal, event);
            });

            function updateBulkDeleteState() {
                if (!bulkDeleteForm || !selectedUserIdsInput) return;
                const checked = [...document.querySelectorAll('.user-select-checkbox:checked')].map(input => input.value);
                bulkDeleteButtons.forEach(function (button) {
                    button.disabled = checked.length === 0;
                    button.title = checked.length > 0 ? 'Delete ' + checked.length + ' selected user(s)' : 'Select users to delete';
                });

                const userList = document.getElementById('bulkDeleteUserList');
                if (!userList) return;
                if (checked.length === 0) {
                    userList.innerHTML = '<div class="text-muted">No users selected.</div>';
                    return;
                }

                const selectedEntries = [...document.querySelectorAll('.user-select-checkbox:checked')].map(function (checkbox) {
                    const row = checkbox.closest('tr');
                    const id = checkbox.value;
                    const name = row ? (row.querySelector('td:nth-child(4)')?.textContent || '').trim() : 'Unknown user';
                    return { id, name };
                });

                const visibleEntries = selectedEntries.slice(0, 5);
                const listHtml = visibleEntries.map(function (entry) {
                    return '<div class="fw-semibold">#' + entry.id + ' - ' + entry.name + '</div>';
                }).join('');
                const trailingHtml = selectedEntries.length > 5 ? '<div class="text-muted mt-2">and more...</div>' : '';
                userList.innerHTML = listHtml + trailingHtml;
                selectedUserIdsInput.value = checked.join(',');
            }

            const bulkDeleteUserModal = document.getElementById('bulkDeleteUserModal');
            bulkDeleteUserModal?.addEventListener('show.bs.modal', function () {
                updateBulkDeleteState();
            });

            function updateDocBulkDeleteState() {
                const checked = [...document.querySelectorAll('.document-select-checkbox:checked')].map(input => input.value);
                docBulkDeleteButtons.forEach(function (button) {
                    button.disabled = checked.length === 0;
                    button.title = checked.length > 0 ? 'Delete ' + checked.length + ' selected document(s)' : 'Select documents to delete';
                });

                if (!docBulkDeleteForm) return;
                docBulkDeleteForm.querySelectorAll('input[name="ids[]"]').forEach(function (input) {
                    input.remove();
                });

                checked.forEach(function (id) {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'ids[]';
                    hiddenInput.value = id;
                    docBulkDeleteForm.appendChild(hiddenInput);
                });

                if (!docBulkDeleteList) return;
                if (checked.length === 0) {
                    docBulkDeleteList.innerHTML = '<div class="text-muted">No documents selected.</div>';
                    return;
                }

                const selectedEntries = [...document.querySelectorAll('.document-select-checkbox:checked')].map(function (checkbox) {
                    const row = checkbox.closest('tr');
                    const id = checkbox.value;
                    const title = row ? (row.querySelector('td:nth-child(3)')?.textContent || '').trim() : 'Untitled document';
                    return { id, title };
                });

                const visibleEntries = selectedEntries.slice(0, 5);
                const listHtml = visibleEntries.map(function (entry) {
                    return '<div class="fw-semibold">#' + entry.id + ' - ' + entry.title + '</div>';
                }).join('');
                const trailingHtml = selectedEntries.length > 5 ? '<div class="text-muted mt-2">and more...</div>' : '';
                docBulkDeleteList.innerHTML = listHtml + trailingHtml;
            }

            document.querySelectorAll('.user-select-checkbox, .document-select-checkbox').forEach(function (checkbox) {
                checkbox.addEventListener('change', function () {
                    updateBulkDeleteState();
                    updateDocBulkDeleteState();
                });
            });

            document.querySelectorAll('.select-all-checkbox').forEach(function (headerCheckbox) {
                headerCheckbox.addEventListener('change', function () {
                    const table = headerCheckbox.closest('table');
                    if (!table) return;
                    table.querySelectorAll('.user-select-checkbox, .document-select-checkbox').forEach(function (checkbox) {
                        checkbox.checked = headerCheckbox.checked;
                    });
                    updateBulkDeleteState();
                    updateDocBulkDeleteState();
                });
            });

            bulkDeleteButtons.forEach(function (button) {
                button.addEventListener('click', function () {
                    const checked = [...document.querySelectorAll('.user-select-checkbox:checked')].map(input => input.value);
                    if (checked.length === 0) return;
                    selectedUserIdsInput.value = checked.join(',');
                    const modal = bootstrap.Modal.getOrCreateInstance(bulkDeleteUserModal);
                    modal.show();
                });
            });

            bulkDeleteDocumentModal?.addEventListener('show.bs.modal', function () {
                updateDocBulkDeleteState();
            });

            updateBulkDeleteState();
            updateDocBulkDeleteState();

            const commonOptions = {
                pageLength: 10,
                order: [[1, 'asc']],
                autoWidth: false,
                columnDefs: [
                    { targets: 0, orderable: false },
                    { targets: 1, type: 'num' }
                ],
                searching: true,
                dom: '<"d-flex justify-content-between align-items-center mb-3"l>rt<"d-flex justify-content-between align-items-center mt-3"ip>'
            };

            function applyAlternatingRowStriping(tableElement) {
                if (!tableElement || !tableElement.querySelectorAll) return;

                tableElement.querySelectorAll('tbody tr').forEach(function (row, index) {
                    row.style.backgroundColor = index % 2 === 0 ? '#ffffff' : '#f3f4f6';
                });
            }

            $('table.table').each(function () {
                const tableElement = this;
                const tableId = tableElement.id;
                const documentTableOptions = tableId === 'docManagementTable'
                    ? {
                        ...commonOptions,
                        columnDefs: [
                            ...commonOptions.columnDefs,
                            { targets: 0, width: '44px' },
                            { targets: 1, width: '72px' },
                            { targets: 2, width: '190px' },
                            { targets: 3, width: '205px' },
                            { targets: 4, width: '150px' },
                            { targets: 5, width: '190px' },
                            { targets: 6, width: '180px' },
                            { targets: 7, width: '120px' }
                        ]
                    }
                    : tableId === 'deletedUsersTable'
                        ? {
                            ...commonOptions,
                            language: { emptyTable: 'No deleted users to restore.' }
                        }
                    : tableId === 'archiveDeletedUsersTable'
                        ? {
                            ...commonOptions,
                            order: [[2, 'asc']],
                            language: { emptyTable: 'No archived users found.' }
                        }
                    : tableId === 'archiveDeletedDocumentsTable'
                        ? {
                            ...commonOptions,
                            order: [[1, 'asc']],
                            language: { emptyTable: 'No archived documents found.' }
                        }
                    : tableId === 'feedbackTicketsTable'
                        ? {
                            ...commonOptions,
                            order: [[0, 'desc']],
                            columnDefs: [
                                { targets: 6, orderable: false }
                            ],
                            language: {
                                emptyTable: 'No feedback tickets yet.'
                            }
                        }
                        : commonOptions;
                const table = $(tableElement).DataTable(documentTableOptions);
                applyAlternatingRowStriping(tableElement);
                table.on('draw.dt', function () {
                    applyAlternatingRowStriping(tableElement);
                });

                if (tableId === 'feedbackTicketsTable') {
                    const filterColumns = { status: 3, priority: 4 };
                    document.querySelectorAll('.ticket-filter-button').forEach(function (filterButton) {
                        filterButton.addEventListener('click', function () {
                            const filter = filterButton.dataset.ticketFilter;
                            table.columns([filterColumns.status, filterColumns.priority]).search('');

                            if (filter === 'unresolved') {
                                table.column(filterColumns.status).search('^Open$', true, false);
                            } else if (filter === 'resolved') {
                                table.column(filterColumns.status).search('^Resolved$', true, false);
                            } else if (filter === 'closed') {
                                table.column(filterColumns.status).search('^Closed$', true, false);
                            } else if (filter === 'priority') {
                                table.column(filterColumns.priority).search('^Priority$', true, false);
                            }

                            table.draw();
                            document.querySelectorAll('.ticket-filter-button').forEach(function (button) {
                                const isActive = button === filterButton;
                                const buttonFilter = button.dataset.ticketFilter;
                                button.classList.toggle('active', isActive);
                                button.setAttribute('aria-pressed', String(isActive));
                                button.classList.toggle('btn-primary', isActive && buttonFilter !== 'priority' && buttonFilter !== 'closed');
                                button.classList.toggle('btn-outline-primary', !isActive && buttonFilter !== 'priority' && buttonFilter !== 'closed');
                                button.classList.toggle('btn-warning', isActive && buttonFilter === 'priority');
                                button.classList.toggle('btn-outline-warning', !isActive && buttonFilter === 'priority');
                                button.classList.toggle('btn-secondary', isActive && buttonFilter === 'closed');
                                button.classList.toggle('btn-outline-secondary', !isActive && buttonFilter === 'closed');
                            });
                        });
                    });
                }
                const searchInput = document.getElementById('tableSearchInput_' + tableId.replace('table_', ''));
                const searchField = document.getElementById('tableSearchField_' + tableId.replace('table_', ''));
                const customDocSearchInput = document.getElementById('docManagementSearchInput');
                const customDocSearchField = document.getElementById('docManagementSearchField');

                if (this.id === 'docManagementTable' && customDocSearchInput && customDocSearchField) {
                    const documentColumnMap = {
                        all: null,
                        id: 1,
                        title: 2,
                        office: 3,
                        classification: 4,
                        transaction_type: 5
                    };

                    function applyDocTableSearch() {
                        const query = (customDocSearchInput.value || '').trim();
                        const field = customDocSearchField.value || 'all';

                        table.columns().every(function () {
                            this.search('');
                        });

                        if (query !== '') {
                            const targetColumn = documentColumnMap[field] ?? null;
                            if (targetColumn !== null) {
                                table.column(targetColumn).search(query, false, false);
                            } else {
                                table.search(query, false, false);
                            }
                        }

                        table.draw();
                    }

                    customDocSearchInput.addEventListener('input', applyDocTableSearch);
                    customDocSearchField.addEventListener('change', applyDocTableSearch);
                    return;
                }

                if (!searchInput || !searchField) return;

                const columnMap = {
                    all: null,
                    registration_number: 1,
                    student_number: 2,
                    last_name: 3,
                    first_name: 4,
                    middle_name: 5,
                    suffix: 6,
                    email: 7,
                    role: 8
                };

                function applyTableFieldSearch() {
                    const query = (searchInput.value || '').trim();
                    const field = searchField.value || 'all';

                    table.columns().every(function () {
                        this.search('');
                    });

                    if (query !== '') {
                        const targetColumn = columnMap[field] ?? null;
                        if (targetColumn !== null) {
                            table.column(targetColumn).search(query, false, false);
                        } else {
                            table.search(query, false, false);
                        }
                    }

                    table.draw();
                }

                searchInput.addEventListener('input', applyTableFieldSearch);
                searchField.addEventListener('change', applyTableFieldSearch);
            });
        });
        </script>
        <?php if ($isGuest): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const toolbar = document.getElementById('guestDocumentRequestToolbar');
                const requestModal = document.getElementById('guestDocumentRequestModal');
                const requestForm = document.getElementById('guestDocumentRequestForm');
                if (!toolbar || !requestModal || !requestForm) return;

                const storageKey = 'neust-guest-document-request-selection';
                const selectedIds = new Set();
                try {
                    const storedIds = JSON.parse(localStorage.getItem(storageKey) || '[]');
                    if (Array.isArray(storedIds)) storedIds.map(Number).filter(id => Number.isInteger(id) && id > 0).forEach(id => selectedIds.add(id));
                } catch (_) {}

                let activeRequestIds = [];
                const countLabel = document.getElementById('guestDocumentSelectedCount');
                const selectedButton = document.getElementById('requestSelectedDocuments');
                const allButton = document.getElementById('requestAllDocuments');
                const deselectButton = document.getElementById('deselectGuestDocuments');
                const feedback = document.getElementById('guestDocumentRequestFeedback');
                const summary = document.getElementById('guestDocumentRequestSummary');
                const errorLabel = document.getElementById('guestDocumentRequestError');
                const submitButton = document.getElementById('guestDocumentRequestSubmit');
                const modal = bootstrap.Modal.getOrCreateInstance(requestModal);
                let requestToastTimer = null;

                requestForm.querySelectorAll('.guest-requester-name').forEach(function (input) {
                    input.addEventListener('input', function () {
                        input.value = input.value.replace(/[^\p{L} ]/gu, '');
                    });
                });

                function showRequestToast(message, isSuccess) {
                    document.querySelector('.request-submit-toast')?.remove();
                    window.clearTimeout(requestToastTimer);
                    const toast = document.createElement('div');
                    toast.className = 'request-submit-toast' + (isSuccess ? '' : ' error');
                    toast.setAttribute('role', isSuccess ? 'status' : 'alert');
                    toast.setAttribute('aria-live', isSuccess ? 'polite' : 'assertive');
                    toast.textContent = message;
                    document.body.appendChild(toast);
                    requestToastTimer = window.setTimeout(() => toast.remove(), 6500);
                }

                function saveSelection() {
                    localStorage.setItem(storageKey, JSON.stringify([...selectedIds]));
                    document.querySelectorAll('.guest-document-checkbox').forEach(function (checkbox) {
                        checkbox.checked = selectedIds.has(Number(checkbox.value));
                    });
                    if (countLabel) countLabel.textContent = `${selectedIds.size} document${selectedIds.size === 1 ? '' : 's'} selected`;
                    if (selectedButton) selectedButton.disabled = selectedIds.size === 0;
                    if (deselectButton) deselectButton.disabled = selectedIds.size === 0;
                }

                function openRequest(ids) {
                    activeRequestIds = [...new Set(ids.map(Number).filter(id => Number.isInteger(id) && id > 0))];
                    if (!activeRequestIds.length) return;
                    if (summary) summary.textContent = `You are requesting ${activeRequestIds.length} document${activeRequestIds.length === 1 ? '' : 's'}. Enter contact details so the office can follow up.`;
                    if (errorLabel) errorLabel.textContent = '';
                    modal.show();
                }

                saveSelection();
                document.querySelectorAll('.guest-document-checkbox').forEach(function (checkbox) {
                    checkbox.addEventListener('change', function () {
                        const id = Number(checkbox.value);
                        if (checkbox.checked) selectedIds.add(id);
                        else selectedIds.delete(id);
                        saveSelection();
                    });
                });
                document.querySelectorAll('.guest-request-single').forEach(function (button) {
                    button.addEventListener('click', function () { openRequest([Number(button.dataset.documentId)]); });
                });
                selectedButton?.addEventListener('click', function () { openRequest([...selectedIds]); });
                deselectButton?.addEventListener('click', function () {
                    selectedIds.clear();
                    saveSelection();
                });
                allButton?.addEventListener('click', function () {
                    try { openRequest(JSON.parse(toolbar.dataset.allDocumentIds || '[]')); }
                    catch (_) { if (feedback) { feedback.textContent = 'Could not load the document list. Refresh and try again.'; feedback.classList.add('visible', 'text-danger'); } }
                });

                requestForm.addEventListener('submit', async function (event) {
                    event.preventDefault();
                    if (!activeRequestIds.length || !requestForm.reportValidity()) return;
                    submitButton.disabled = true;
                    if (errorLabel) errorLabel.textContent = '';
                    try {
                        const response = await fetch('functions/submit_document_request.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            credentials: 'same-origin',
                            body: JSON.stringify({
                                csrf_token: requestForm.dataset.csrf,
                                document_ids: activeRequestIds,
                                fname: document.getElementById('guestDocumentRequesterFirstName').value.trim(),
                                mname: document.getElementById('guestDocumentRequesterMiddleName').value.trim(),
                                lname: document.getElementById('guestDocumentRequesterLastName').value.trim(),
                                suffix: document.getElementById('guestDocumentRequesterSuffix').value.trim(),
                                requester_email: document.getElementById('guestDocumentRequesterEmail').value.trim()
                            })
                        });
                        const result = await response.json();
                        if (!response.ok) throw new Error(result.error || 'The request could not be submitted.');
                        selectedIds.clear();
                        localStorage.removeItem(storageKey);
                        saveSelection();
                        if (feedback) {
                            const emailMessage = result.email_sent === true
                                ? ' Check your email for the private document link.'
                                : result.email_pending
                                    ? ' Private link email delivery is processing separately.'
                                    : ' The request was saved, but the access email could not be sent.';
                            feedback.textContent = `Request received for ${result.document_count} document${result.document_count === 1 ? '' : 's'}.${emailMessage}`;
                            feedback.classList.remove('text-danger');
                            feedback.classList.add('visible', 'text-success');
                            showRequestToast(feedback.textContent, true);
                        }
                        requestForm.reset();
                        modal.hide();
                    } catch (error) {
                        const message = error instanceof Error ? error.message : 'The request could not be submitted.';
                        if (errorLabel) errorLabel.textContent = message;
                        showRequestToast(message, false);
                    } finally {
                        submitButton.disabled = false;
                    }
                });
            });
        </script>
        <?php endif; ?>
        <?php if ((int) $role_id === 1): ?>
        <div class="admin-request-toast" id="adminDocumentRequestToast" role="status" aria-live="polite">A new document request has arrived.</div>
        <script>
            (function () {
                let latestRequestId = <?= $latestDocumentRequestId; ?>;
                const badge = document.getElementById('pendingDocumentRequestBadge');
                const toast = document.getElementById('adminDocumentRequestToast');
                async function refreshRequestNotice() {
                    try {
                        const response = await fetch('document-requests.php?ajax=summary', { credentials: 'same-origin', cache: 'no-store' });
                        if (!response.ok) return;
                        const summary = await response.json();
                        const latest = Number(summary.latest_request_id) || 0;
                        const pending = Number(summary.pending_count) || 0;
                        if (badge) {
                            badge.textContent = pending > 99 ? '99+' : String(pending);
                            badge.hidden = pending === 0;
                        }
                        if (latest > latestRequestId && toast) {
                            toast.classList.add('visible');
                            window.setTimeout(() => toast.classList.remove('visible'), 7000);
                        }
                        latestRequestId = Math.max(latestRequestId, latest);
                    } catch (_) {}
                }
                window.setInterval(refreshRequestNotice, 15000);
            })();
        </script>
        <?php endif; ?>
        <script>
            (function () {
                function formatNameValue(value) {
                    return String(value || '')
                        .replace(/\d+/g, '')
                        .replace(/\s+/g, ' ')
                        .trim()
                        .split(' ')
                        .filter(Boolean)
                        .map(function (segment) {
                            if (!segment) return '';
                            return segment.charAt(0).toUpperCase() + segment.slice(1).toLowerCase();
                        })
                        .join(' ');
                }

                document.querySelectorAll('.name-field').forEach(function (input) {
                    input.addEventListener('keydown', function (event) {
                        if (/\d/.test(event.key)) {
                            event.preventDefault();
                        }
                    });

                    input.addEventListener('input', function () {
                        input.value = formatNameValue(input.value);
                    });
                });
            })();
        </script>
</body>
</html>