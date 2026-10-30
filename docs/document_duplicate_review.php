<?php
require_once __DIR__ . '/../libraries/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['document_csv_duplicate_review']) || empty($_SESSION['document_csv_review_rows'])) {
    header('Location: ../dashboard.php');
    exit;
}

function normalizeDocumentTitle(string $value): string
{
    return trim(preg_replace('/\s+/', ' ', (string) $value) ?? '');
}

function splitCsvList(string $value): array
{
    $clean = trim((string) $value);
    if ($clean === '') {
        return [];
    }

    $parts = preg_split('/\s*(?:;|,|\||\r\n|\n)\s*/', $clean, -1, PREG_SPLIT_NO_EMPTY);
    if ($parts === false || $parts === []) {
        return [$clean];
    }

    $result = [];
    foreach ($parts as $part) {
        $part = trim((string) $part);
        if ($part !== '') {
            $result[] = $part;
        }
    }

    return $result;
}

function getOrCreateLookup(PDO $connection, string $table, string $column, string $name): ?int
{
    $cleanName = trim((string) $name);
    if ($cleanName === '') {
        return null;
    }

    $stmt = $connection->prepare('SELECT id FROM `' . $table . '` WHERE `' . $column . '` = :name LIMIT 1');
    $stmt->execute([':name' => $cleanName]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        return (int) $existing['id'];
    }

    $insert = $connection->prepare('INSERT INTO `' . $table . '` (`' . $column . '`) VALUES (:name)');
    $insert->execute([':name' => $cleanName]);

    return (int) $connection->lastInsertId();
}

function groupRowsByDocumentTitle(array $rows): array
{
    $grouped = [];
    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $title = normalizeDocumentTitle((string) ($row['document_title'] ?? ''));
        $key = strtolower($title);
        if ($title === '' || $key === 'sample documents') {
            continue;
        }

        $grouped[$key][] = $row;
    }

    return $grouped;
}

function rowHasAnyValue(array $row): bool
{
    foreach ($row as $value) {
        if (is_string($value) && trim($value) !== '') {
            return true;
        }
        if (is_numeric($value) && (string) $value !== '') {
            return true;
        }
    }

    return false;
}

function stepSignature(array $step): string
{
    $parts = [];
    foreach (['step_number', 'client_step', 'agency_action', 'fees', 'processing_time', 'person_responsible'] as $field) {
        $parts[] = trim((string) ($step[$field] ?? ''));
    }

    return strtolower(implode('|', $parts));
}

/**
 * Turns every CSV row that belongs to one document into a single structure:
 * document-level fields (first non-empty value wins) plus separate lists of
 * transaction types, requirements, steps and totals. Nothing is concatenated,
 * so no value is lost and the lists stay aligned.
 */
function consolidateDocumentRows(array $rows): array
{
    $doc = [
        'id' => 0,
        'title' => '',
        'office_name' => '',
        'classification' => '',
        'who_may_avail' => '',
        'transaction_types' => [],
        'requirements' => [],
        'steps' => [],
        'totals' => [],
    ];
    $seenRequirements = [];
    $seenSteps = [];
    $seenTotals = [];

    foreach ($rows as $row) {
        $get = static fn (string $field): string => trim((string) ($row[$field] ?? ''));

        if ($doc['title'] === '') {
            $doc['title'] = normalizeDocumentTitle($get('document_title'));
        }
        foreach (['office_name', 'classification', 'who_may_avail'] as $field) {
            if ($doc[$field] === '') {
                $doc[$field] = $get($field);
            }
        }

        foreach (splitCsvList($get('transaction_type')) as $transactionName) {
            $doc['transaction_types'][strtolower($transactionName)] = $transactionName;
        }

        $requirementNames = splitCsvList($get('requirement_name'));
        $whereRaw = $get('where_to_secure');
        $whereParts = splitCsvList($whereRaw);
        foreach ($requirementNames as $index => $requirementName) {
            if (count($requirementNames) === 1) {
                // One requirement: keep the whole "where to secure" text (it may contain commas).
                $where = $whereRaw;
            } else {
                $where = $whereParts[$index] ?? '';
                if ($index === count($requirementNames) - 1 && count($whereParts) > count($requirementNames)) {
                    $where = implode(', ', array_slice($whereParts, $index));
                }
            }

            $requirementKey = strtolower($requirementName . '|' . $where);
            if (!isset($seenRequirements[$requirementKey])) {
                $seenRequirements[$requirementKey] = true;
                $doc['requirements'][] = ['requirement_name' => $requirementName, 'where_to_secure' => $where];
            }
        }

        $step = [
            'step_number' => $get('step_number'),
            'client_step' => $get('client_step'),
            'agency_action' => $get('agency_action'),
            'fees' => $get('fees'),
            'processing_time' => $get('processing_time'),
            'person_responsible' => $get('person_responsible'),
        ];
        if (rowHasAnyValue($step)) {
            $stepNumber = trim((string) ($step['step_number'] ?? ''));
            if ($stepNumber === '') {
                $stepNumber = (string) (count($doc['steps']) + 1);
            }
            $step['step_number'] = $stepNumber;

            $stepKey = stepSignature($step);
            if ($stepKey !== '' && !isset($seenSteps[$stepKey])) {
                $seenSteps[$stepKey] = true;
                $doc['steps'][] = $step;
            }
        }

        $totalFees = $get('total_fees');
        $totalTime = $get('total_time');
        if ($totalFees !== '' || $totalTime !== '') {
            $totalKey = strtolower($totalFees . '|' . $totalTime);
            if (!isset($seenTotals[$totalKey])) {
                $seenTotals[$totalKey] = true;
                $doc['totals'][] = ['fees' => $totalFees, 'time' => $totalTime];
            }
        }
    }

    $doc['transaction_types'] = array_values($doc['transaction_types']);
    foreach ($doc['steps'] as $index => $step) {
        if ($step['step_number'] === '') {
            $doc['steps'][$index]['step_number'] = (string) ($index + 1);
        }
    }

    return $doc;
}

function loadExistingDocument(PDO $connection, string $title): ?array
{
    if ($title === '') {
        return null;
    }

    $docStmt = $connection->prepare('SELECT d.id, d.title, d.who_may_avail, o.name AS office_name, c.name AS classification FROM documents d LEFT JOIN offices o ON o.id = d.office_id LEFT JOIN classifications c ON c.id = d.classification_id WHERE LOWER(TRIM(d.title)) = LOWER(?) LIMIT 1');
    $docStmt->execute([$title]);
    $document = $docStmt->fetch(PDO::FETCH_ASSOC);
    if (!$document) {
        return null;
    }

    $documentId = (int) $document['id'];
    $doc = [
        'id' => $documentId,
        'title' => trim((string) ($document['title'] ?? '')),
        'office_name' => trim((string) ($document['office_name'] ?? '')),
        'classification' => trim((string) ($document['classification'] ?? '')),
        'who_may_avail' => trim((string) ($document['who_may_avail'] ?? '')),
        'transaction_types' => [],
        'requirements' => [],
        'steps' => [],
        'totals' => [],
    ];

    $transactionStmt = $connection->prepare('SELECT tt.name FROM document_transaction_types dtt LEFT JOIN transaction_types tt ON tt.id = dtt.transaction_type_id WHERE dtt.document_id = ? ORDER BY tt.name');
    $transactionStmt->execute([$documentId]);
    foreach ($transactionStmt->fetchAll(PDO::FETCH_COLUMN) as $name) {
        $name = trim((string) $name);
        if ($name !== '') {
            $doc['transaction_types'][] = $name;
        }
    }

    // Older documents may only have the legacy documents.transaction_type_id column.
    if ($doc['transaction_types'] === [] && $connection->query("SHOW COLUMNS FROM documents LIKE 'transaction_type_id'")->fetchColumn() !== false) {
        $legacyStmt = $connection->prepare('SELECT tt.name FROM documents d JOIN transaction_types tt ON tt.id = d.transaction_type_id WHERE d.id = ?');
        $legacyStmt->execute([$documentId]);
        $legacyName = trim((string) $legacyStmt->fetchColumn());
        if ($legacyName !== '') {
            $doc['transaction_types'][] = $legacyName;
        }
    }

    $requirementsStmt = $connection->prepare('SELECT requirement_name, where_to_secure FROM requirements WHERE document_id = ? ORDER BY id');
    $requirementsStmt->execute([$documentId]);
    foreach ($requirementsStmt->fetchAll(PDO::FETCH_ASSOC) as $requirement) {
        $name = trim((string) ($requirement['requirement_name'] ?? ''));
        $where = trim((string) ($requirement['where_to_secure'] ?? ''));
        if ($name !== '' || $where !== '') {
            $doc['requirements'][] = ['requirement_name' => $name, 'where_to_secure' => $where];
        }
    }

    $stepsStmt = $connection->prepare('SELECT step_number, client_step, agency_action, fees, processing_time, person_responsible FROM document_steps WHERE document_id = ? ORDER BY CAST(step_number AS UNSIGNED), id');
    $stepsStmt->execute([$documentId]);
    foreach ($stepsStmt->fetchAll(PDO::FETCH_ASSOC) as $stepRow) {
        $step = [];
        foreach (['step_number', 'client_step', 'agency_action', 'fees', 'processing_time', 'person_responsible'] as $field) {
            $step[$field] = trim((string) ($stepRow[$field] ?? ''));
        }
        if (implode('', $step) !== '') {
            $doc['steps'][] = $step;
        }
    }

    $totalStmt = $connection->prepare('SELECT fees, time FROM `total` WHERE document_id = ? ORDER BY id');
    $totalStmt->execute([$documentId]);
    foreach ($totalStmt->fetchAll(PDO::FETCH_ASSOC) as $totalRow) {
        $fees = trim((string) ($totalRow['fees'] ?? ''));
        $time = trim((string) ($totalRow['time'] ?? ''));
        if ($fees !== '' || $time !== '') {
            $doc['totals'][] = ['fees' => $fees, 'time' => $time];
        }
    }

    return $doc;
}

/**
 * One list of display lines per comparison field. List fields (requirements,
 * steps, totals) keep one entry per record, so the columns stay aligned.
 */
function comparisonFieldLines(array $doc): array
{
    $column = static fn (array $items, string $key): array => array_map(static fn ($item) => trim((string) ($item[$key] ?? '')), $items);
    $scalar = static function (string $value): array {
        $parts = preg_split('/\s*(?:;|\||\r\n|\n)\s*/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
        return $parts === false ? [] : $parts;
    };

    return [
        'title' => $scalar((string) $doc['title']),
        'office_name' => $scalar((string) $doc['office_name']),
        'classification' => $scalar((string) $doc['classification']),
        'who_may_avail' => $scalar((string) $doc['who_may_avail']),
        'transaction_type' => $doc['transaction_types'],
        'requirement_name' => $column($doc['requirements'], 'requirement_name'),
        'where_to_secure' => $column($doc['requirements'], 'where_to_secure'),
        'step_number' => $column($doc['steps'], 'step_number'),
        'client_step' => $column($doc['steps'], 'client_step'),
        'agency_action' => $column($doc['steps'], 'agency_action'),
        'fees' => $column($doc['steps'], 'fees'),
        'processing_time' => $column($doc['steps'], 'processing_time'),
        'person_responsible' => $column($doc['steps'], 'person_responsible'),
        'total_fees' => $column($doc['totals'], 'fees'),
        'total_time' => $column($doc['totals'], 'time'),
    ];
}

function comparisonLinesHaveValue(array $lines): bool
{
    foreach ($lines as $line) {
        if (trim((string) $line) !== '') {
            return true;
        }
    }

    return false;
}

function renderComparisonLines(array $lines, bool $numbered): string
{
    if (!comparisonLinesHaveValue($lines)) {
        return '—';
    }

    $lines = array_values($lines);
    $output = [];
    foreach ($lines as $index => $line) {
        $line = trim((string) $line);
        $text = $line === '' ? '—' : nl2br(htmlspecialchars($line, ENT_QUOTES, 'UTF-8'), false);
        $output[] = ($numbered && count($lines) > 1 ? ($index + 1) . '. ' : '') . $text;
    }

    return implode('<br>', $output);
}

function writeDocumentChildren(PDO $connection, int $documentId, array $doc): void
{
    if ($connection->query("SHOW TABLES LIKE 'document_transaction_types'")->fetchColumn() !== false) {
        foreach ($doc['transaction_types'] as $transactionName) {
            $transactionId = getOrCreateLookup($connection, 'transaction_types', 'name', (string) $transactionName);
            if ($transactionId === null) {
                continue;
            }

            $bridge = $connection->prepare('INSERT INTO document_transaction_types (document_id, transaction_type_id) VALUES (?, ?)');
            $bridge->execute([$documentId, $transactionId]);
        }
    }

    foreach ($doc['requirements'] as $requirement) {
        $where = (string) $requirement['where_to_secure'];
        $requirementStmt = $connection->prepare('INSERT INTO requirements (document_id, requirement_name, where_to_secure) VALUES (?, ?, ?)');
        $requirementStmt->execute([$documentId, $requirement['requirement_name'], $where !== '' ? $where : null]);
    }

    foreach ($doc['steps'] as $step) {
        $stepStmt = $connection->prepare('INSERT INTO document_steps (document_id, step_number, client_step, agency_action, fees, processing_time, person_responsible) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stepStmt->execute([$documentId, $step['step_number'], $step['client_step'], $step['agency_action'], $step['fees'], $step['processing_time'], $step['person_responsible']]);
    }

    foreach ($doc['totals'] as $total) {
        $totalStmt = $connection->prepare('INSERT INTO `total` (document_id, fees, time) VALUES (?, ?, ?)');
        $totalStmt->execute([$documentId, $total['fees'], $total['time']]);
    }
}

function replaceDocumentFromConsolidated(PDO $connection, int $documentId, array $doc): void
{
    if ($doc['title'] === '' || $doc['office_name'] === '' || $doc['classification'] === '') {
        return;
    }

    $officeId = getOrCreateLookup($connection, 'offices', 'name', $doc['office_name']);
    $classificationId = getOrCreateLookup($connection, 'classifications', 'name', $doc['classification']);
    if ($officeId === null || $classificationId === null) {
        return;
    }

    $connection->prepare('UPDATE documents SET title = ?, office_id = ?, classification_id = ?, who_may_avail = ? WHERE id = ?')->execute([
        $doc['title'],
        $officeId,
        $classificationId,
        $doc['who_may_avail'],
        $documentId,
    ]);

    $connection->prepare('DELETE FROM requirements WHERE document_id = ?')->execute([$documentId]);
    $connection->prepare('DELETE FROM document_transaction_types WHERE document_id = ?')->execute([$documentId]);
    $connection->prepare('DELETE FROM document_steps WHERE document_id = ?')->execute([$documentId]);
    $connection->prepare('DELETE FROM `total` WHERE document_id = ?')->execute([$documentId]);

    writeDocumentChildren($connection, $documentId, $doc);
}

function createDocumentFromConsolidated(PDO $connection, array $doc): void
{
    if ($doc['title'] === '' || $doc['office_name'] === '' || $doc['classification'] === '') {
        return;
    }

    $officeId = getOrCreateLookup($connection, 'offices', 'name', $doc['office_name']);
    $classificationId = getOrCreateLookup($connection, 'classifications', 'name', $doc['classification']);
    if ($officeId === null || $classificationId === null) {
        return;
    }

    $documentStmt = $connection->prepare('INSERT INTO documents (title, office_id, classification_id, who_may_avail) VALUES (?, ?, ?, ?)');
    $documentStmt->execute([$doc['title'], $officeId, $classificationId, $doc['who_may_avail']]);

    writeDocumentChildren($connection, (int) $connection->lastInsertId(), $doc);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_document_duplicate_review'])) {
    $selection = $_POST['duplicate_choice'] ?? [];
    $rows = $_SESSION['document_csv_review_rows'] ?? [];
    $duplicates = $_SESSION['document_csv_duplicate_review'] ?? [];

    $db->beginTransaction();
    try {
        foreach (groupRowsByDocumentTitle($rows) as $key => $documentRows) {
            $doc = consolidateDocumentRows($documentRows);

            if (isset($duplicates[$key])) {
                $choice = $selection[$key] ?? 'existing';
                if ($choice === 'new') {
                    $existingId = (int) ($duplicates[$key]['existing']['id'] ?? 0);
                    if ($existingId <= 0) {
                        $found = loadExistingDocument($db, $doc['title']);
                        $existingId = (int) ($found['id'] ?? 0);
                    }

                    if ($existingId > 0) {
                        replaceDocumentFromConsolidated($db, $existingId, $doc);
                    } else {
                        createDocumentFromConsolidated($db, $doc);
                    }
                }
                continue;
            }

            createDocumentFromConsolidated($db, $doc);
        }

        $db->commit();
    } catch (Throwable $exception) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $_SESSION['flash_message'] = 'Unable to finalize the duplicate review: ' . $exception->getMessage();
        $_SESSION['flash_type'] = 'danger';
        header('Location: ../dashboard.php');
        exit;
    }

    unset($_SESSION['document_csv_duplicate_review'], $_SESSION['document_csv_review_rows']);
    $_SESSION['flash_message'] = 'Duplicate document review completed. The selected records were kept and the rejected copies were dropped.';
    $_SESSION['flash_type'] = 'success';
    header('Location: ../dashboard.php');
    exit;
}

$duplicateReview = $_SESSION['document_csv_duplicate_review'];
$groupedCsvRows = groupRowsByDocumentTitle($_SESSION['document_csv_review_rows'] ?? []);
$displayRows = [];
foreach ($duplicateReview as $key => $record) {
    $newRows = $groupedCsvRows[$key] ?? (isset($record['new']) && is_array($record['new']) ? [$record['new']] : []);
    $newDocument = consolidateDocumentRows($newRows);
    $existingDocument = loadExistingDocument($db, $newDocument['title']);
    $emptyDocument = consolidateDocumentRows([]);
    $displayRows[] = [
        'key' => $key,
        'existing' => comparisonFieldLines($existingDocument ?? $emptyDocument),
        'new' => comparisonFieldLines($newDocument),
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Duplicate Document Imports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(180deg, #edf4ff 0%, #f7fafd 100%);
            font-family: Arial, sans-serif;
        }
        .compare-box {
            background: white;
            border: 1px solid #dfe7f3;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(20, 54, 156, 0.08);
        }
        .compare-header {
            background: linear-gradient(135deg, #14369C, #1d4ed8);
            color: white;
        }
        th { white-space: nowrap; }
        td, th { vertical-align: top; }
        .comparison-cell {
            white-space: normal;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="compare-box overflow-hidden">
            <div class="compare-header px-4 py-3">
                <h2 class="mb-0">Duplicate document review</h2>
                <div class="small mt-1 opacity-75">Choose which version to keep. The unselected data will be dropped.</div>
            </div>
            <div class="p-4">
                <form method="POST">
                    <input type="hidden" name="confirm_document_duplicate_review" value="1">
                    <?php foreach ($displayRows as $index => $row): ?>
                        <div class="border rounded-3 p-3 mb-4 bg-light-subtle">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <h5 class="mb-0">Duplicate <?= $index + 1 ?></h5>
                                <div class="btn-group" role="group" aria-label="Keep one version">
                                    <label class="btn btn-outline-primary btn-sm">
                                        <input type="radio" class="me-1" name="duplicate_choice[<?= htmlspecialchars((string) $row['key'], ENT_QUOTES, 'UTF-8'); ?>]" value="existing" checked>
                                        Keep existing
                                    </label>
                                    <label class="btn btn-outline-success btn-sm">
                                        <input type="radio" class="me-1" name="duplicate_choice[<?= htmlspecialchars((string) $row['key'], ENT_QUOTES, 'UTF-8'); ?>]" value="new">
                                        Keep new
                                    </label>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 30%;">Field</th>
                                            <th style="width: 35%;">Existing document</th>
                                            <th style="width: 35%;">New document</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $fields = [
                                            'title' => 'Title',
                                            'office_name' => 'Office',
                                            'classification' => 'Classification',
                                            'who_may_avail' => 'Who may avail',
                                            'transaction_type' => 'Transaction type',
                                            'requirement_name' => 'Requirement',
                                            'where_to_secure' => 'Where to secure',
                                            'step_number' => 'Step number',
                                            'client_step' => 'Client step',
                                            'agency_action' => 'Agency action',
                                            'fees' => 'Fees',
                                            'processing_time' => 'Processing time',
                                            'person_responsible' => 'Person responsible',
                                            'total_fees' => 'Total fees',
                                            'total_time' => 'Total time',
                                        ];
                                        $scalarFields = ['title', 'office_name', 'classification', 'who_may_avail'];
                                        foreach ($fields as $field => $label):
                                            $existingLines = $row['existing'][$field] ?? [];
                                            $newLines = $row['new'][$field] ?? [];
                                            if (!comparisonLinesHaveValue($existingLines) && !comparisonLinesHaveValue($newLines)) {
                                                continue;
                                            }

                                            $numbered = !in_array($field, $scalarFields, true);
                                            $existingDisplay = renderComparisonLines($existingLines, $numbered);
                                            $newDisplay = renderComparisonLines($newLines, $numbered);
                                        ?>
                                            <tr>
                                                <th><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></th>
                                                <td class="comparison-cell"><?= $existingDisplay; ?></td>
                                                <td class="comparison-cell"><?= $newDisplay; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="d-flex justify-content-between flex-wrap gap-2 mt-3">
                        <a href="../dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-success px-4 fw-semibold">Apply selection</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>