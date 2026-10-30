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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --ns-blue: #14369C;
            --ns-blue-deep: #07184F;
            --ns-gold: #FFCE00;
            --ns-text: #0f172a;
            --ns-muted: #64748B;
            --ns-surface: #f5f7fb;
            --ns-line: #dfe6f2;
            --ns-white: #ffffff;
        }

        body {
            margin: 0;
            font-family: 'Inter', Arial, sans-serif;
            background:
                radial-gradient(circle at top left, rgba(20,54,156,0.12), transparent 22%),
                radial-gradient(circle at 90% 16%, rgba(255,206,0,0.16), transparent 18%),
                linear-gradient(180deg, #edf5ff 0%, #f7f9fc 42%, #eef3fa 100%);
            color: var(--ns-text);
        }

        .doc-form-shell {
            max-width: 1200px;
            margin: 28px auto 60px;
            padding: 0 18px;
        }

        .doc-form-card {
            border: none;
            border-top: 4px solid var(--ns-gold);
            border-radius: 12px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.82);
            box-shadow: 0 18px 40px rgba(9, 17, 44, 0.08);
        }

        .doc-form-header {
            background: linear-gradient(135deg, var(--ns-blue), var(--ns-blue-deep));
            color: white;
            min-height: 60px;
            padding: 16px 28px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .doc-form-header h5 {
            margin: 0;
            font-size: 0.95rem;
            font-weight: 800;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .doc-form-body {
            background: rgba(255, 255, 255, 0.72);
            padding: 28px 26px 30px;
        }

        .doc-form-body .form-section-title {
            font-size: 0.95rem;
            font-weight: 800;
            letter-spacing: 0.03em;
            color: var(--ns-blue);
            text-transform: uppercase;
            margin-bottom: 1rem;
            border-bottom: 1px solid rgba(20, 54, 156, 0.12);
            padding-bottom: 5px;
        }

        .document-title-block {
            margin-bottom: 20px;
            padding: 18px 20px;
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(20, 54, 156, 0.08), rgba(255, 206, 0, 0.08));
            border: 1px solid rgba(20, 54, 156, 0.12);
        }

        .document-title-label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #64748b;
        }

        .document-title-value {
            margin: 0;
            color: #0f172a;
            font-size: clamp(1.5rem, 2vw, 2.25rem);
            font-weight: 800;
            line-height: 1.3;
            word-break: break-word;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(180px, 1fr));
            gap: 16px;
            margin-top: 8px;
        }

        .detail-box {
            background: #f7f9ff;
            border: 1px solid rgba(20, 54, 156, 0.18);
            border-radius: 14px;
            padding: 14px 12px;
            box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.3);
            min-height: 120px;
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

        .info-bullets {
            margin: 0;
            padding-left: 1.25rem;
            color: #0f172a;
            font-weight: 600;
            line-height: 1.6;
        }

        .info-bullets li + li {
            margin-top: 6px;
        }

        .requirements-box {
            margin-top: 22px;
            background: #fffaf0;
            border: 1px solid rgba(255, 206, 0, 0.28);
            border-radius: 16px;
            padding: 18px 18px 10px;
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

        .requirement-where-row > div {
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

        .table-wrap {
            margin-top: 18px;
            background: white;
            border: 1px solid rgba(15, 23, 42, 0.06);
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid rgba(20, 54, 156, 0.18) !important;
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

        .alert-warning {
            margin-top: 18px;
        }

        @media (max-width: 900px) {
            .detail-grid {
                grid-template-columns: repeat(2, minmax(180px, 1fr));
            }
        }

        @media (max-width: 600px) {
            .detail-grid {
                grid-template-columns: 1fr;
            }

            .requirement-where-header,
            .requirement-where-row {
                grid-template-columns: 1fr;
            }

            .totals-summary {
                flex-direction: column;
                justify-content: stretch;
            }

            .totals-box {
                flex: 1 1 auto;
                min-width: 0;
                max-width: none;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="doc-form-shell">
        <div class="doc-form-card">
            <div class="doc-form-header"><h5>Document Details</h5></div>
            <div class="doc-form-body">
                <?php if ($document): ?>
                    <div class="document-title-block">
                        <span class="document-title-label">Document Title</span>
                        <h2 class="document-title-value"><?php echo htmlspecialchars($document['title'] ?? 'Untitled Document', ENT_QUOTES, 'UTF-8'); ?></h2>
                    </div>

                    <div class="detail-grid">
                        <div class="detail-box">
                            <span class="info-label">Office division</span>
                            <div><?php echo renderBulletedValues($document['office_name'] ?? ''); ?></div>
                        </div>
                        <div class="detail-box">
                            <span class="info-label">Classification</span>
                            <div><?php echo renderBulletedValues($document['classification'] ?? ''); ?></div>
                        </div>
                        <div class="detail-box">
                            <span class="info-label">Type of transaction</span>
                            <div><?php echo renderBulletedValues($document['transaction_type'] ?? ''); ?></div>
                        </div>
                        <div class="detail-box">
                            <span class="info-label">Who may avail</span>
                            <div><?php echo renderBulletedValues($document['who_may_avail'] ?? ''); ?></div>
                        </div>
                    </div>

                    <?php if (!empty($requirements)): ?>
                        <div class="requirements-box">
                            <div class="form-section-title">Requirements &amp; Where to Secure</div>
                            <div class="requirement-where-grid">
                                <div class="requirement-where-header">
                                    <span>Requirements</span>
                                    <span>Where to secure</span>
                                </div>
                                <?php foreach ($requirements as $requirement): ?>
                                    <?php $whereToSecureValue = trim((string) ($requirement['where_to_secure'] ?? '')); ?>
                                    <div class="requirement-where-row">
                                        <div class="requirement-cell">
                                            <i class="fa-solid fa-circle-check"></i>
                                            <span><?php echo htmlspecialchars($requirement['requirement_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                                        </div>
                                        <?php if ($whereToSecureValue !== ''): ?>
                                            <div class="where-cell"><?php echo renderDatabaseLinkText($whereToSecureValue); ?></div>
                                        <?php else: ?>
                                            <div class="where-cell">—</div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($steps)): ?>
                        <div class="mt-4">
                            <div class="form-section-title">Process Steps</div>
                            <div class="table-wrap">
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
                                                <td><?php echo htmlspecialchars($step['processing_time'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?php echo renderRichText($step['person_responsible'] ?? ''); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="totals-summary" style="justify-content: flex-end;">
                                <?php if (!empty($documentTotalRows)): ?>
                                    <div class="totals-box">
                                        <span class="label"><i class="fa-solid fa-receipt me-1"></i> Total Fees</span>
                                        <div class="totals-list">
                                            <?php foreach ($documentTotalRows as $totalRow): ?>
                                                <div class="totals-item">
                                                    <p class="value"><?php echo htmlspecialchars(trim((string) ($totalRow['fees'] ?? 'None')) ?: 'None', ENT_QUOTES, 'UTF-8'); ?></p>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="totals-box">
                                        <span class="label"><i class="fa-regular fa-clock me-1"></i> Total Time</span>
                                        <div class="totals-list">
                                            <?php foreach ($documentTotalRows as $totalRow): ?>
                                                <div class="totals-item">
                                                    <p class="value"><?php echo htmlspecialchars(trim((string) ($totalRow['time'] ?? 'None')) ?: 'None', ENT_QUOTES, 'UTF-8'); ?></p>
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
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-warning mb-0">
                        Document not found in the database: <?php echo htmlspecialchars($targetTitle !== '' ? $targetTitle : 'Unknown document', ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
