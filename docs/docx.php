<?php
require_once __DIR__ . '/../libraries/db.php';

$documentId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$targetTitle = trim((string) ($_GET['title'] ?? ''));

$allDocumentIds = $db->query("SELECT id FROM documents ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);

if ($documentId <= 0 && !empty($allDocumentIds)) {
    $documentId = (int) $allDocumentIds[0];
}

$document = null;

if ($documentId > 0) {
    $stmt = $db->prepare(
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
    $stmt->execute([':id' => $documentId]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($document && $targetTitle === '') {
        $targetTitle = trim((string) ($document['title'] ?? ''));
    }
}

$previousId = null;
$nextId = null;

if (!empty($allDocumentIds)) {
    $currentIndex = array_search($documentId, $allDocumentIds, true);
    if ($currentIndex !== false) {
        if ($currentIndex > 0) {
            $previousId = (int) $allDocumentIds[$currentIndex - 1];
        }
        if ($currentIndex < count($allDocumentIds) - 1) {
            $nextId = (int) $allDocumentIds[$currentIndex + 1];
        }
    }
}

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

function feeNeedsSmallText($value)
{
    $text = trim((string) $value);
    if ($text === '' || strtolower($text) === 'none') {
        return false;
    }

    $withoutCurrencyMarker = preg_replace('/[Pp]/', '', $text);
    return preg_match('/[A-Za-z]/', $withoutCurrencyMarker) === 1;
}

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
        $part = trim($part);
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

$requirements = [];
$steps = [];
$totalFees = 0;
$totalProcessingMinutes = 0;
$documentWhereToSecure = '';
$documentTotalRows = [];

if ($document) {
    $reqStmt = $db->prepare(
        "SELECT requirement_name, where_to_secure
         FROM requirements
         WHERE document_id = :document_id
         ORDER BY id"
    );
    $reqStmt->execute([':document_id' => $document['id']]);
    $requirements = $reqStmt->fetchAll(PDO::FETCH_ASSOC);

    $whereToSecureValues = [];
    foreach ($requirements as $requirement) {
        $value = trim((string) ($requirement['where_to_secure'] ?? ''));
        if ($value !== '' && !in_array($value, $whereToSecureValues, true)) {
            $whereToSecureValues[] = $value;
        }
    }
    $documentWhereToSecure = implode('; ', $whereToSecureValues);

    $totalStmt = $db->prepare(
        "SELECT fees, time
         FROM `total`
         WHERE document_id = :document_id
         ORDER BY id ASC"
    );
    $totalStmt->execute([':document_id' => $document['id']]);
    $documentTotalRows = $totalStmt->fetchAll(PDO::FETCH_ASSOC);

    $stepStmt = $db->prepare(
        "SELECT step_number, client_step, agency_action, fees, processing_time, person_responsible
         FROM document_steps
         WHERE document_id = :document_id
         ORDER BY step_number, id"
    );
    $stepStmt->execute([':document_id' => $document['id']]);
    $steps = $stepStmt->fetchAll(PDO::FETCH_ASSOC);

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
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <style>
        body {
            background: linear-gradient(180deg, #eef4ff 0%, #f5f7fb 100%);
            font-family: "Segoe UI", Arial, sans-serif;
            color: #0f172a;
            margin: 0;
            padding: 12px;
        }

        .page-shell {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 12px 20px;
        }

        .page-header {
            position: sticky;
            top: 0;
            z-index: 1030;
            background: linear-gradient(135deg, #f3f6fd, #e1ebff);
            color: #0d2347;
            border-radius: 22px;
            padding: 26px 28px;
            box-shadow: 0 18px 40px rgba(14, 31, 66, 0.14);
            border: 1px solid rgba(20, 54, 156, 0.08);
        }

        .eyebrow {
            display: inline-block;
            font-size: 11px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            opacity: 0.8;
            margin-bottom: 8px;
        }

        .page-header h2 {
            margin: 0;
            font-weight: 800;
            font-size: clamp(1.4rem, 2vw, 2.2rem);
        }

        .detail-card {
            width: 100%;
            margin-top: 18px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid rgba(15, 23, 42, 0.06);
            border-radius: 18px;
            box-shadow: 0 12px 22px rgba(9, 17, 44, 0.06);
            padding: 18px 18px 16px;
        }

        .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .detail-header h3 {
            margin: 0;
            color: #091a3a;
            font-weight: 800;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 999px;
            font-size: 0.76rem;
            font-weight: 700;
            background: #eafaf0;
            color: #1d9b4d;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(180px, 1fr));
            gap: 16px;
            margin-top: 12px;
        }

        .info-box {
            background: #f7f9ff;
            border: 1px solid rgba(20, 54, 156, 0.18);
            border-radius: 14px;
            padding: 14px 12px;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.3);
        }

        .info-label {
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            margin-bottom: 8px;
        }

        .info-value {
            margin: 0;
            color: #0f172a;
            font-weight: 600;
            line-height: 1.5;
        }

        .info-bullets {
            margin: 0;
            padding-left: 1.25rem;
            color: #0f172a;
            font-weight: 600;
            line-height: 1.6;
        }

        .info-bullets li+li {
            margin-top: 6px;
        }

        .requirements-box {
            margin-top: 22px;
            background: #fffaf0;
            border: 1px solid rgba(255, 206, 0, 0.28);
            border-radius: 16px;
            padding: 18px 18px 10px;
        }

        .requirements-box h5,
        .section-title {
            margin: 0 0 12px;
            font-size: 0.78rem;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 800;
        }

        .requirement-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 10px;
        }

        .requirement-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(15, 23, 42, 0.12);
            border-radius: 10px;
            padding: 10px 12px;
            line-height: 1.5;
        }

        .requirement-list i {
            color: #1d9b4d;
            margin-top: 3px;
        }

        .requirement-where-grid {
            display: grid;
            gap: 10px;
        }

        .requirement-where-header,
        .requirement-where-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            gap: 12px;
            align-items: start;
        }

        .requirement-where-header {
            font-size: 0.74rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 800;
            padding: 0 6px;
        }

        .requirement-where-row>div {
            background: rgba(255, 255, 255, 0.8);
            border: 1px solid rgba(15, 23, 42, 0.12);
            border-radius: 10px;
            padding: 8px 10px;
            line-height: 1.5;
        }

        .requirement-cell {
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .requirement-cell i {
            color: #1d9b4d;
            margin-top: 3px;
        }

        .where-cell {
            color: #0f172a;
            font-weight: 600;
        }

        .modal-header {
            background: linear-gradient(135deg, #14369C, #07184F);
            color: white;
            border-bottom: none;
        }

        .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        .table-wrap {
            margin-top: 18px;
            background: white;
            border: 1px solid rgba(15, 23, 42, 0.06);
            border-radius: 14px;
            overflow: hidden;
        }

        .table thead th {
            background: linear-gradient(135deg, #14369C, #07184F) !important;
            color: white !important;
            text-transform: uppercase;
            font-size: 0.72rem;
            letter-spacing: 0.08em;
        }

        .table td,
        .table th {
            vertical-align: top;
            padding: 12px 12px;
        }

        .table tbody tr:nth-child(even) td {
            background-color: #f3f6fd;
        }

        .table tbody tr:nth-child(odd) td {
            background-color: #ffffff;
        }

        .fee-highlight strong {
            font-weight: 800;
            color: #0d2347;
        }

        .fee-highlight {
            white-space: normal;
            line-height: 1.7;
        }

        .fee-item {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            gap: 6px;
            padding: 5px 0;
            border-bottom: 1px dashed rgba(20, 54, 156, 0.12);
            font-size: 1rem;
        }

        .fee-item-small {
            font-size: 0.82rem;
        }

        .fee-item:last-child {
            border-bottom: none;
        }

        .fee-label {
            font-weight: 600;
            color: #1f2937;
        }

        .fee-separator {
            color: #64748b;
            font-weight: 700;
        }

        .fee-suffix {
            color: #475569;
        }

        .totals-summary {
            width: 100%;
            display: flex;
            justify-content: flex-end;
            align-items: stretch;
            gap: 12px;
            flex-wrap: nowrap;
            margin-top: 18px;
        }

        .totals-box {
            flex: 0 0 260px;
            min-width: 260px;
            max-width: 260px;
            background: #eef4ff;
            border: 1px solid rgba(20, 54, 156, 0.12);
            border-radius: 12px;
            padding: 12px 16px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            text-align: left;
            height: 100%;
            min-height: 120px;
        }

        .totals-box .label {
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 8px;
        }

        .totals-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1;
            justify-content: flex-start;
        }

        .totals-item {
            min-height: 28px;
            display: flex;
            align-items: center;
        }

        .totals-box .value {
            font-size: 1.05rem;
            font-weight: 800;
            color: #0d2347;
            margin: 0;
            line-height: 1.4;
        }

        .primary-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #14369C;
            color: white;
            border: none;
            border-radius: 10px;
            padding: 0.7rem 1.1rem;
            font-weight: 700;
            box-shadow: 0 10px 20px rgba(20, 54, 156, 0.18);
        }

        .primary-btn:hover {
            background: #0d2347;
            color: white;
        }

        .secondary-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border: 1px solid rgba(20, 54, 156, 0.18);
            color: #14369C;
            background: #eef4ff;
            border-radius: 10px;
            padding: 0.7rem 1.1rem;
            font-weight: 700;
        }

        .nav-btn-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        @media (max-width: 900px) {
            .info-grid {
                grid-template-columns: repeat(2, minmax(180px, 1fr));
            }
        }

        @media (max-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="page-shell">

        <?php if ($document): ?>
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <div class="info-box">
                        <span class="info-label">Office division</span>
                        <div class="info-value"><?php echo renderBulletedValues($document['office_name'] ?? ''); ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-box">
                        <span class="info-label">Classification</span>
                        <div class="info-value"><?php echo renderBulletedValues($document['classification'] ?? ''); ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-box">
                        <span class="info-label">Type of transaction</span>
                        <div class="info-value"><?php echo renderBulletedValues($document['transaction_type'] ?? ''); ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-box">
                        <span class="info-label">Who may avail</span>
                        <div class="info-value"><?php echo renderBulletedValues($document['who_may_avail'] ?? ''); ?></div>
                    </div>
                </div>
            </div>

            <?php if (!empty($requirements)): ?>
                <div class="mb-4 p-3 border rounded-3 bg-light-subtle"
                    style="border-color: rgba(20, 54, 156, 0.18) !important;">
                    <div class="section-title">Requirements &amp; Where to Secure</div>
                    <div class="requirement-where-grid">
                        <div class="requirement-where-header">
                            <span>Requirements</span>
                            <span>Where to secure</span>
                        </div>
                        <?php
                        foreach ($requirements as $requirement):
                            $whereToSecureValue = trim((string) ($requirement['where_to_secure'] ?? ''));
                            ?>
                            <div class="requirement-where-row">
                                <div class="requirement-cell">
                                    <i class="fa-solid fa-circle-check"></i>
                                    <span><?php echo htmlspecialchars($requirement['requirement_name'] ?? ''); ?></span>
                                </div>
                                <?php if ($whereToSecureValue !== ''): ?>
                                    <div class="where-cell">
                                        <?php echo renderDatabaseLinkText($whereToSecureValue); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($steps)): ?>
                <div class="mb-2 section-title">Process steps</div>
                <div class="table-wrap border rounded-3 overflow-hidden"
                    style="border-color: rgba(20, 54, 156, 0.18) !important;">
                    <table class="table table-bordered mb-0">
                        <thead>
                            <tr>
                                <th style="width: 20%;">Client Steps</th>
                                <th style="width: 25%;">Agency Actions</th>
                                <th style="width: 20%;">Fees</th>
                                <th style="width: 15%;">Time</th>
                                <th style="width: 20%;">Responsible</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($steps as $step): ?>
                                <tr>
                                    <td><?php echo renderRichText($step['client_step'] ?? ''); ?></td>
                                    <td><?php echo renderRichText($step['agency_action'] ?? ''); ?></td>
                                    <td class="fee-highlight"><?php echo formatFeeCell($step['fees'] ?? 'None'); ?></td>
                                    <td><?php echo htmlspecialchars($step['processing_time'] ?? ''); ?></td>
                                    <td><?php echo renderRichText($step['person_responsible'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="totals-summary" style="justify-content: flex-end;">
                    <?php if (!empty($documentTotalRows)): ?>
                        <!-- Combined Total Fees Box -->
                        <div class="totals-box">
                            <span class="label"><i class="fa-solid fa-receipt me-1"></i> Total Fees</span>
                            <div class="totals-list">
                                <?php foreach ($documentTotalRows as $totalRow): ?>
                                    <div class="totals-item">
                                        <p class="value">
                                            ><?php echo htmlspecialchars(trim((string) ($totalRow['fees'] ?? 'None')) ?: 'None'); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Combined Total Time Box -->
                        <div class="totals-box">
                            <span class="label"><i class="fa-regular fa-clock me-1"></i> Total Time</span>
                            <div class="totals-list">
                                <?php foreach ($documentTotalRows as $totalRow): ?>
                                    <div class="totals-item">
                                        <p class="value">
                                            ><?php echo htmlspecialchars(trim((string) ($totalRow['time'] ?? 'None')) ?: 'None'); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="totals-box">
                            <span class="label"><i class="fa-solid fa-receipt me-1"></i> Total Fees</span>
                            <p class="value mt-2">None</p>
                        </div>
                        <div class="totals-box">
                            <span class="label"><i class="fa-regular fa-clock me-1"></i> Total Time</span>
                            <p class="value mt-2">None</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-warning mt-3 mb-0">
                Document not found in the database: <?php echo htmlspecialchars($targetTitle !== '' ? $targetTitle : 'Unknown document'); ?>
            </div>
        <?php endif; ?>
    </div>

</body>

</html>