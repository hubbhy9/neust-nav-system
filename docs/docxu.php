<?php
// Use a writable system temp directory so Windows/XAMPP cannot keep writing into the blocked project session folder.
if (session_status() === PHP_SESSION_NONE) {
    $sharedSessionPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'navigation_sessions';
    if (!is_dir($sharedSessionPath)) {
        @mkdir($sharedSessionPath, 0777, true);
    }
    if (!is_dir($sharedSessionPath) || !is_writable($sharedSessionPath)) {
        $sharedSessionPath = sys_get_temp_dir();
    }
    session_save_path($sharedSessionPath);
    session_start();
}

require_once __DIR__ . '/../libraries/db.php';
require_once __DIR__ . '/../functions/audit.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') auditRequireAdmin($db);

function redirectToDashboardDocs(string $message = '', string $type = 'success'): void
{
    if ($message !== '') {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }

    setcookie('dashboard_active_tab', 'docs-management', [
        'expires' => time() + (60 * 60 * 24 * 365),
        'path' => '/',
        'samesite' => 'Lax',
        'secure' => false,
    ]);

    header('Location: ../dashboard.php');
    exit;
}

$documentId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$feedback = '';
$feedbackType = '';

$offices = $db->query("SELECT id, name FROM offices ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$classifications = $db->query("SELECT id, name FROM classifications ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$transactionTypes = $db->query("SELECT id, name FROM transaction_types ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

$document = null;
if ($documentId > 0) {
    $stmt = $db->prepare(
        "SELECT d.id, d.title, d.who_may_avail, d.office_id, d.classification_id,
                o.name AS office_name,
                c.name AS classification_name
         FROM documents d
         LEFT JOIN offices o ON o.id = d.office_id
         LEFT JOIN classifications c ON c.id = d.classification_id
         WHERE d.id = :id
         LIMIT 1"
    );
    $stmt->execute([':id' => $documentId]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$document) {
    redirectToDashboardDocs('Document not found.', 'warning');
}

$currentSelectedTransactionIds = [];
$bridgeExists = (bool) $db->query("SHOW TABLES LIKE 'document_transaction_types'")->fetchColumn();
if ($bridgeExists) {
    $linkStmt = $db->prepare('SELECT transaction_type_id FROM document_transaction_types WHERE document_id = :document_id ORDER BY transaction_type_id ASC');
    $linkStmt->execute([':document_id' => $documentId]);
    $currentSelectedTransactionIds = array_map('intval', $linkStmt->fetchAll(PDO::FETCH_COLUMN));
}

$requirements = [];
$steps = [];
$documentTotalRows = [];
$reqStmt = $db->prepare('SELECT requirement_name, where_to_secure FROM requirements WHERE document_id = :document_id ORDER BY id ASC');
$reqStmt->execute([':document_id' => $documentId]);
$requirements = $reqStmt->fetchAll(PDO::FETCH_ASSOC);

$stepStmt = $db->prepare('SELECT step_number, client_step, agency_action, fees, processing_time, person_responsible FROM document_steps WHERE document_id = :document_id ORDER BY id ASC');
$stepStmt->execute([':document_id' => $documentId]);
$steps = $stepStmt->fetchAll(PDO::FETCH_ASSOC);

$totalStmt = $db->prepare('SELECT fees, time FROM `total` WHERE document_id = :document_id ORDER BY id ASC');
$totalStmt->execute([':document_id' => $documentId]);
$documentTotalRows = $totalStmt->fetchAll(PDO::FETCH_ASSOC);

$existingSubmittedSteps = array_values(array_map(
    static fn (array $step) => [
        'step_number' => (string) ($step['step_number'] ?? ''),
        'client_step' => (string) ($step['client_step'] ?? ''),
        'agency_action' => (string) ($step['agency_action'] ?? ''),
        'fees' => (string) ($step['fees'] ?? ''),
        'processing_time' => (string) ($step['processing_time'] ?? ''),
        'person_responsible' => (string) ($step['person_responsible'] ?? ''),
    ],
    $steps
));

$existingTotalValues = !empty($documentTotalRows)
    ? $documentTotalRows[0]
    : ['fees' => '₱0.00', 'time' => '0 minutes'];

$documentOfficeName = trim((string) ($document['office_name'] ?? ''));
$documentClassificationName = trim((string) ($document['classification_name'] ?? ''));
$existingRequirementsData = array_values(array_map(
    static fn (array $requirement) => [
        'requirement' => (string) ($requirement['requirement_name'] ?? ''),
        'secure' => (string) ($requirement['where_to_secure'] ?? ''),
    ],
    $requirements
));

$validateStepSequence = static function (array $stepNumbers): void {
    $validSteps = [];

    foreach ($stepNumbers as $stepNumber) {
        $cleanStep = trim((string) $stepNumber);
        if ($cleanStep === '') {
            continue;
        }

        if (!preg_match('/^\d+$/', $cleanStep)) {
            throw new InvalidArgumentException('Step numbers must be whole numbers only.');
        }

        $parsedStep = (int) $cleanStep;
        if ($parsedStep < 1) {
            throw new InvalidArgumentException('Step numbers must be greater than 0.');
        }

        $validSteps[] = $parsedStep;
    }

    if (count($validSteps) <= 1) {
        return;
    }

    $previousStep = $validSteps[0];
    for ($i = 1; $i < count($validSteps); $i++) {
        $currentStep = $validSteps[$i];
        if ($currentStep < $previousStep || $currentStep > ($previousStep + 1)) {
            throw new InvalidArgumentException('Step numbers must stay the same or increase by only 1 each time. Example: 1, 1, 2 is valid, but 1, 1, 3 is not.');
        }

        $previousStep = $currentStep;
    }
};

$validateWhereToSecureCount = static function (array $requirementNames, array $whereToSecureValues): void {
    $validRequirements = array_values(array_filter(array_map(static fn ($value) => trim((string) $value), $requirementNames), static fn ($value) => $value !== ''));
    $validWhereToSecure = array_values(array_filter(array_map(static fn ($value) => trim((string) $value), $whereToSecureValues), static fn ($value) => $value !== ''));

    if (count($validWhereToSecure) > count($validRequirements)) {
        throw new InvalidArgumentException('The number of Where to Secure entries cannot exceed the number of requirements.');
    }
};

$validateRequiredDocumentFields = static function (
    string $title,
    string $office,
    string $classification,
    array $selectedTransactionIds,
    array $stepNumbers
): void {
    if (trim($title) === '') {
        throw new InvalidArgumentException('Document title is required.');
    }

    if (trim($office) === '') {
        throw new InvalidArgumentException('Office is required.');
    }

    if (trim($classification) === '') {
        throw new InvalidArgumentException('Classification is required.');
    }

    if (count($selectedTransactionIds) < 1) {
        throw new InvalidArgumentException('At least one transaction type is required.');
    }

    $filledSteps = array_values(array_filter(array_map(static fn ($value) => trim((string) $value), $stepNumbers), static fn ($value) => $value !== ''));
    if (count($filledSteps) < 1) {
        throw new InvalidArgumentException('At least one step number is required.');
    }
};

$getOrCreateId = static function (PDO $pdo, string $table, string $column, string $name) {
    $cleanName = trim($name);
    if ($cleanName === '') {
        throw new InvalidArgumentException('A required name is missing.');
    }

    $stmt = $pdo->prepare("SELECT id FROM `{$table}` WHERE `{$column}` = :name LIMIT 1");
    $stmt->execute([':name' => $cleanName]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($existing) {
        return (int) $existing['id'];
    }

    $insert = $pdo->prepare("INSERT INTO `{$table}` (`{$column}`) VALUES (:name)");
    $insert->execute([':name' => $cleanName]);
    return (int) $pdo->lastInsertId();
};

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_document'])) {
    try {
        $db->beginTransaction();

        $oldRequirementStmt = $db->prepare('SELECT requirement_name, where_to_secure FROM requirements WHERE document_id = ? ORDER BY id');
        $oldRequirementStmt->execute([$documentId]);
        $oldRequirements = $oldRequirementStmt->fetchAll(PDO::FETCH_ASSOC);
        $oldStepsStmt = $db->prepare('SELECT step_number, client_step, agency_action, fees, processing_time, person_responsible FROM document_steps WHERE document_id = ? ORDER BY step_number, id');
        $oldStepsStmt->execute([$documentId]);
        $oldSteps = $oldStepsStmt->fetchAll(PDO::FETCH_ASSOC);
        $oldTotalsStmt = $db->prepare('SELECT fees, time FROM `total` WHERE document_id = ? ORDER BY id');
        $oldTotalsStmt->execute([$documentId]);
        $oldTotals = $oldTotalsStmt->fetchAll(PDO::FETCH_ASSOC);
        $oldTransactionIds = $currentSelectedTransactionIds;

        $title = trim((string) ($_POST['title'] ?? ''));
        if ($title === '') {
            throw new InvalidArgumentException('Document title is required.');
        }

        $officeInput = trim((string) ($_POST['office'] ?? ''));
        if ($officeInput === 'other') {
            $officeInput = trim((string) ($_POST['office_other'] ?? ''));
        }
        $classificationInput = trim((string) ($_POST['classification'] ?? ''));
        if ($classificationInput === 'other') {
            $classificationInput = trim((string) ($_POST['classification_other'] ?? ''));
        }

        $officeId = $getOrCreateId($db, 'offices', 'name', $officeInput);
        $classificationId = $getOrCreateId($db, 'classifications', 'name', $classificationInput);

        $selectedTransactionIds = [];
        $transactionValues = $_POST['transaction_types'] ?? [];
        if (!is_array($transactionValues)) {
            $transactionValues = [$transactionValues];
        }
        $customTransactionValues = $_POST['transaction_type_other'] ?? [];
        if (!is_array($customTransactionValues)) {
            $customTransactionValues = [$customTransactionValues];
        }

        foreach ($transactionValues as $index => $value) {
            $value = trim((string) $value);
            if ($value === '') {
                continue;
            }

            if ($value === 'other') {
                $customName = trim((string) ($customTransactionValues[$index] ?? ''));
                if ($customName !== '') {
                    $selectedTransactionIds[] = $getOrCreateId($db, 'transaction_types', 'name', $customName);
                }
                continue;
            }

            if (ctype_digit((string) $value)) {
                $selectedTransactionIds[] = (int) $value;
            }
        }

        $stepNumbers = $_POST['step_number'] ?? [];
        if (!is_array($stepNumbers)) {
            $stepNumbers = [$stepNumbers];
        }

        $validateRequiredDocumentFields(
            $title,
            $officeInput,
            $classificationInput,
            $selectedTransactionIds,
            $stepNumbers
        );

        $whoMayAvail = trim((string) ($_POST['who_may_avail'] ?? ''));
        $updateStmt = $db->prepare('UPDATE documents SET title = :title, office_id = :office_id, classification_id = :classification_id, who_may_avail = :who_may_avail WHERE id = :id');
        $updateStmt->execute([
            ':title' => $title,
            ':office_id' => $officeId,
            ':classification_id' => $classificationId,
            ':who_may_avail' => $whoMayAvail !== '' ? $whoMayAvail : null,
            ':id' => $documentId,
        ]);

        if ($bridgeExists) {
            $deleteBridge = $db->prepare('DELETE FROM document_transaction_types WHERE document_id = :document_id');
            $deleteBridge->execute([':document_id' => $documentId]);
        }

        foreach (array_unique($selectedTransactionIds) as $transactionId) {
            $insertBridge = $db->prepare('INSERT INTO document_transaction_types (document_id, transaction_type_id) VALUES (:document_id, :transaction_type_id)');
            $insertBridge->execute([
                ':document_id' => $documentId,
                ':transaction_type_id' => $transactionId,
            ]);
        }

        $deleteRequirements = $db->prepare('DELETE FROM requirements WHERE document_id = :document_id');
        $deleteRequirements->execute([':document_id' => $documentId]);

        $requirementNames = $_POST['requirements'] ?? [];
        if (!is_array($requirementNames)) {
            $requirementNames = [$requirementNames];
        }
        $whereToSecureValues = $_POST['where_to_secure'] ?? [];
        if (!is_array($whereToSecureValues)) {
            $whereToSecureValues = [$whereToSecureValues];
        }

        $validateWhereToSecureCount($requirementNames, $whereToSecureValues);

        foreach ($requirementNames as $index => $requirementName) {
            $requirementText = trim((string) $requirementName);
            if ($requirementText === '') {
                continue;
            }

            $secureText = trim((string) ($whereToSecureValues[$index] ?? ''));
            $reqInsert = $db->prepare('INSERT INTO requirements (document_id, requirement_name, where_to_secure) VALUES (:document_id, :requirement_name, :where_to_secure)');
            $reqInsert->execute([
                ':document_id' => $documentId,
                ':requirement_name' => $requirementText,
                ':where_to_secure' => $secureText !== '' ? $secureText : null,
            ]);
        }

        $deleteSteps = $db->prepare('DELETE FROM document_steps WHERE document_id = :document_id');
        $deleteSteps->execute([':document_id' => $documentId]);

        $stepNumbers = $_POST['step_number'] ?? [];
        if (!is_array($stepNumbers)) {
            $stepNumbers = [$stepNumbers];
        }
        $validateStepSequence($stepNumbers);
        $clientSteps = $_POST['client_step'] ?? [];
        if (!is_array($clientSteps)) {
            $clientSteps = [$clientSteps];
        }
        $agencyActions = $_POST['agency_action'] ?? [];
        if (!is_array($agencyActions)) {
            $agencyActions = [$agencyActions];
        }
        $fees = $_POST['fees'] ?? [];
        if (!is_array($fees)) {
            $fees = [$fees];
        }
        $processingTimes = $_POST['processing_time'] ?? [];
        if (!is_array($processingTimes)) {
            $processingTimes = [$processingTimes];
        }
        $personResponsible = $_POST['person_responsible'] ?? [];
        if (!is_array($personResponsible)) {
            $personResponsible = [$personResponsible];
        }

        foreach ($stepNumbers as $index => $stepNumber) {
            $clientText = trim((string) ($clientSteps[$index] ?? ''));
            $agencyText = trim((string) ($agencyActions[$index] ?? ''));
            $feeText = trim((string) ($fees[$index] ?? ''));
            $timeText = trim((string) ($processingTimes[$index] ?? ''));
            $personText = trim((string) ($personResponsible[$index] ?? ''));

            if ($clientText === '' && $agencyText === '' && $feeText === '' && $timeText === '' && $personText === '') {
                continue;
            }

            $insertStep = $db->prepare('INSERT INTO document_steps (document_id, step_number, client_step, agency_action, fees, processing_time, person_responsible) VALUES (:document_id, :step_number, :client_step, :agency_action, :fees, :processing_time, :person_responsible)');
            $insertStep->execute([
                ':document_id' => $documentId,
                ':step_number' => (int) $stepNumber,
                ':client_step' => $clientText !== '' ? $clientText : null,
                ':agency_action' => $agencyText !== '' ? $agencyText : null,
                ':fees' => $feeText !== '' ? $feeText : 'None',
                ':processing_time' => $timeText !== '' ? $timeText : null,
                ':person_responsible' => $personText !== '' ? $personText : null,
            ]);
        }

        $totalFeesValue = trim((string) ($_POST['total_fees'] ?? ''));
        $totalTimeValue = trim((string) ($_POST['total_time'] ?? ''));
        $deleteTotalRows = $db->prepare('DELETE FROM `total` WHERE document_id = :document_id');
        $deleteTotalRows->execute([':document_id' => $documentId]);

        if ($totalFeesValue !== '' || $totalTimeValue !== '') {
            $insertTotal = $db->prepare('INSERT INTO `total` (document_id, fees, time) VALUES (:document_id, :fees, :time)');
            $insertTotal->execute([
                ':document_id' => $documentId,
                ':fees' => $totalFeesValue !== '' ? $totalFeesValue : 'None',
                ':time' => $totalTimeValue !== '' ? $totalTimeValue : 'None',
            ]);
        }

        $newSteps = [];
        foreach ($stepNumbers as $index => $stepNumber) {
            $clientText = trim((string) ($clientSteps[$index] ?? ''));
            $agencyText = trim((string) ($agencyActions[$index] ?? ''));
            $feeText = trim((string) ($fees[$index] ?? ''));
            $timeText = trim((string) ($processingTimes[$index] ?? ''));
            $personText = trim((string) ($personResponsible[$index] ?? ''));
            if ($clientText === '' && $agencyText === '' && $feeText === '' && $timeText === '' && $personText === '') continue;
            $newSteps[] = [
                'step_number' => (int) $stepNumber,
                'client_step' => $clientText,
                'agency_action' => $agencyText,
                'fees' => $feeText !== '' ? $feeText : 'None',
                'processing_time' => $timeText,
                'person_responsible' => $personText,
            ];
        }
        $newRequirements = [];
        foreach ($requirementNames as $index => $requirementName) {
            $requirementText = trim((string) $requirementName);
            if ($requirementText === '') continue;
            $newRequirements[] = [
                'requirement_name' => $requirementText,
                'where_to_secure' => trim((string) ($whereToSecureValues[$index] ?? '')),
            ];
        }
        $oldValues = [
            'title' => (string) $document['title'],
            'office' => $documentOfficeName,
            'classification' => $documentClassificationName,
            'who_may_avail' => (string) ($document['who_may_avail'] ?? ''),
            'transaction_type_ids' => $oldTransactionIds,
            'requirements' => $oldRequirements,
            'steps' => $oldSteps,
            'totals' => $oldTotals,
        ];
        $newValues = [
            'title' => $title,
            'office' => $officeInput,
            'classification' => $classificationInput,
            'who_may_avail' => $whoMayAvail,
            'transaction_type_ids' => array_values(array_unique($selectedTransactionIds)),
            'requirements' => $newRequirements,
            'steps' => $newSteps,
            'totals' => ($totalFeesValue !== '' || $totalTimeValue !== '') ? [['fees' => $totalFeesValue !== '' ? $totalFeesValue : 'None', 'time' => $totalTimeValue !== '' ? $totalTimeValue : 'None']] : [],
        ];
        auditLog($db, [
            'action' => 'UPDATE',
            'module' => 'Documents',
            'target_type' => 'document',
            'target_id' => $documentId,
            'target_name' => $title,
            'description' => 'Updated document details, requirements, process steps, or totals.',
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'status' => 'success',
        ]);
        $db->commit();
        redirectToDashboardDocs('Document updated successfully.', 'update');
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $feedback = 'Unable to update document: ' . $e->getMessage();
        $feedbackType = 'error';

        $postedRequirements = $_POST['requirements'] ?? [];
        if (!is_array($postedRequirements)) {
            $postedRequirements = [$postedRequirements];
        }
        $postedWhereToSecure = $_POST['where_to_secure'] ?? [];
        if (!is_array($postedWhereToSecure)) {
            $postedWhereToSecure = [$postedWhereToSecure];
        }
        $requirements = array_values(array_map(
            static fn ($requirement, $secure) => [
                'requirement_name' => (string) $requirement,
                'where_to_secure' => (string) $secure,
            ],
            $postedRequirements,
            array_pad($postedWhereToSecure, count($postedRequirements), '')
        ));

        $transactionValues = $_POST['transaction_types'] ?? [];
        if (!is_array($transactionValues)) {
            $transactionValues = [$transactionValues];
        }
        $currentSelectedTransactionIds = [];
        foreach ($transactionValues as $value) {
            $clean = trim((string) $value);
            if ($clean === '') {
                continue;
            }
            if ($clean === 'other') {
                $customName = trim((string) ($_POST['transaction_type_other'][0] ?? ''));
                if ($customName !== '') {
                    $currentSelectedTransactionIds[] = -1;
                }
                continue;
            }
            if (ctype_digit($clean)) {
                $currentSelectedTransactionIds[] = (int) $clean;
            }
        }

        $document['title'] = trim((string) ($_POST['title'] ?? ($document['title'] ?? '')));
        $document['who_may_avail'] = trim((string) ($_POST['who_may_avail'] ?? ($document['who_may_avail'] ?? '')));
        $documentOfficeName = trim((string) ($_POST['office'] ?? $documentOfficeName));
        $documentClassificationName = trim((string) ($_POST['classification'] ?? $documentClassificationName));
    }
}

require_once __DIR__ . '/../libraries/include.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Document | NEUST Portal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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

        html { overflow-y: scroll; }

        body {
            margin: 0;
            font-family: 'Inter', Arial, sans-serif;
            background: radial-gradient(circle at top left, rgba(20,54,156,0.12), transparent 22%), radial-gradient(circle at 90% 16%, rgba(255,206,0,0.16), transparent 18%), linear-gradient(180deg, #edf5ff 0%, #f7f9fc 42%, #eef3fa 100%);
            color: var(--ns-text);
        }

        h6 { font-family: 'Manrope', 'Inter', sans-serif; }

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

        .form-section-title {
            font-size: 0.95rem;
            font-weight: 800;
            letter-spacing: 0.03em;
            color: var(--ns-blue);
            text-transform: uppercase;
            margin-bottom: 1rem;
            border-bottom: 1px solid rgba(20, 54, 156, 0.12);
            padding-bottom: 5px;
        }

        .form-label {
            display: block;
            font-size: 0.68rem;
            letter-spacing: 0.5px;
            color: #6c757d;
            margin-bottom: 2px;
            text-transform: uppercase;
            font-weight: 700;
        }

        .form-control, .form-select, textarea {
            border: 1px solid #ced4da;
            background: #fff;
            border-radius: 6px;
            min-height: 42px;
            padding: 0.6rem 0.8rem;
            font-size: 0.88rem;
            box-shadow: inset 0 0 0 1px rgba(20, 54, 156, 0.02);
        }

        .form-control:focus, .form-select:focus, textarea:focus {
            border-color: rgba(20, 54, 156, 0.9);
            box-shadow: 0 0 0 0.2rem rgba(20, 54, 156, 0.12);
        }

        textarea { resize: vertical; }

        .other-field-pair {
            display: flex;
            gap: 12px;
            align-items: stretch;
            width: 100%;
        }

        .other-field-pair > .col-6,
        .other-field-pair > .transaction-select-wrap,
        .other-field-pair > .transaction-custom-wrap,
        .other-field-pair > .other-input-wrap {
            flex: 1 1 0;
            width: 50%;
            max-width: 50%;
            min-width: 0;
            padding-left: 0;
            padding-right: 0;
            margin: 0;
            box-sizing: border-box;
        }

        .other-input-wrap {
            display: block;
        }

        .transaction-type-entry + .transaction-type-entry {
            margin-top: 12px;
        }

        .transaction-custom-wrap {
            min-width: 0;
        }

        .totals-panel {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(20, 54, 156, 0.08);
        }

        .totals-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            margin-bottom: 1rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #14369C, #0B2270);
            border: none;
            border-radius: 12px;
            padding: 0.72rem 1.3rem;
            font-weight: 700;
        }

        .btn-outline-secondary {
            border-radius: 12px;
            padding: 0.72rem 1.2rem;
            font-weight: 700;
        }

        .btn-outline-danger {
            border-radius: 8px;
        }

        .action-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid rgba(20, 54, 156, 0.08);
        }

        .action-group {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 8px;
            margin-left: auto;
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

        .alert {
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="doc-form-shell">
        <div class="doc-form-card">
            <div class="doc-form-header">
                <h5>Edit Document</h5>
            </div>
            <div class="doc-form-body">
                <form method="POST" action="docxu.php?id=<?= (int) $documentId; ?>">
                    <?php if ($feedback !== ''): ?>
                        <div class="feedback-toast <?= $feedbackType === 'error' ? 'error' : 'success'; ?>" role="status" aria-live="polite">
                            <i class="fa-solid <?= $feedbackType === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check'; ?> mt-1"></i>
                            <span><?= htmlspecialchars($feedback, ENT_QUOTES, 'UTF-8'); ?></span>
                            <button type="button" onclick="this.parentElement.remove()" aria-label="Close notification">&times;</button>
                        </div>
                    <?php endif; ?>

                    <h6 class="form-section-title mb-3">General Info</h6>

                    <div class="mb-3">
                        <label class="form-label">Document Title</label>
                        <input type="text" class="form-control" name="title" value="<?= htmlspecialchars((string) ($document['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <div id="office-other-pair" class="other-field-pair">
                            <div class="col-6">
                                <label class="form-label">Office</label>
                                <select id="office-select" class="form-select" name="office">
                                    <option value="">-- Select Office --</option>
                                    <?php foreach ($offices as $office): ?>
                                        <option value="<?= htmlspecialchars($office['name'], ENT_QUOTES, 'UTF-8'); ?>" <?= trim((string) $documentOfficeName) === trim((string) $office['name']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($office['name'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="other" <?= trim((string) $documentOfficeName) !== '' && !in_array(trim((string) $documentOfficeName), array_map(static fn($office) => trim((string) $office['name']), $offices), true) ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-6 other-input-wrap" id="office-other-wrap">
                                <label class="form-label">Specify Office</label>
                                <input type="text" class="form-control" name="office_other" value="<?= htmlspecialchars($documentOfficeName, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter office name">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div id="classification-other-pair" class="other-field-pair">
                            <div class="col-6">
                                <label class="form-label">Classification</label>
                                <select id="classification-select" class="form-select" name="classification">
                                    <option value="">-- Select Classification --</option>
                                    <?php foreach ($classifications as $classification): ?>
                                        <option value="<?= htmlspecialchars($classification['name'], ENT_QUOTES, 'UTF-8'); ?>" <?= trim((string) $documentClassificationName) === trim((string) $classification['name']) ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($classification['name'], ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="other" <?= trim((string) $documentClassificationName) !== '' && !in_array(trim((string) $documentClassificationName), array_map(static fn($classification) => trim((string) $classification['name']), $classifications), true) ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-6 other-input-wrap" id="classification-other-wrap">
                                <label class="form-label">Specify Classification</label>
                                <input type="text" class="form-control" name="classification_other" value="<?= htmlspecialchars($documentClassificationName, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter classification name">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="transaction-type-count-wrap mb-3">
                            <label class="form-label">Number of Transaction Types</label>
                            <input type="number" id="transaction-type-count" name="transaction_type_count" class="form-control" min="1" max="10" step="1" value="<?= max(1, count($currentSelectedTransactionIds)); ?>" placeholder="1">
                        </div>
                        <div id="transaction-types-container">
                            <?php if (empty($currentSelectedTransactionIds)): ?>
                                <div class="transaction-type-entry">
                                    <div class="other-field-pair">
                                        <div class="transaction-select-wrap">
                                            <label class="form-label">Transaction Type 1</label>
                                            <select class="form-select" name="transaction_types[]">
                                                <option value="">-- Select Transaction Type --</option>
                                                <?php foreach ($transactionTypes as $transactionType): ?>
                                                    <option value="<?= (int) $transactionType['id']; ?>"><?= htmlspecialchars($transactionType['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                                <?php endforeach; ?>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                        <div class="transaction-custom-wrap other-input-wrap">
                                            <label class="form-label">Specify Transaction Type</label>
                                            <input type="text" class="form-control" name="transaction_type_other[]" placeholder="Enter transaction type name" disabled>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($currentSelectedTransactionIds as $index => $transactionTypeId): ?>
                                    <?php
                                        $selectedType = null;
                                        foreach ($transactionTypes as $type) {
                                            if ((int) $type['id'] === (int) $transactionTypeId) {
                                                $selectedType = $type;
                                                break;
                                            }
                                        }
                                    ?>
                                    <div class="transaction-type-entry">
                                        <div class="other-field-pair">
                                            <div class="transaction-select-wrap">
                                                <label class="form-label">Transaction Type <?= $index + 1; ?></label>
                                                <select class="form-select" name="transaction_types[]">
                                                    <option value="">-- Select Transaction Type --</option>
                                                    <?php foreach ($transactionTypes as $transactionType): ?>
                                                        <option value="<?= (int) $transactionType['id']; ?>" <?= (int) $transactionType['id'] === (int) $transactionTypeId ? 'selected' : ''; ?>>
                                                            <?= htmlspecialchars($transactionType['name'], ENT_QUOTES, 'UTF-8'); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                    <option value="other" <?= $selectedType === null ? 'selected' : ''; ?>>Other</option>
                                                </select>
                                            </div>
                                            <div class="transaction-custom-wrap other-input-wrap <?= $selectedType === null ? 'visible' : ''; ?>">
                                                <label class="form-label">Specify Transaction Type</label>
                                                <input type="text" class="form-control" name="transaction_type_other[]" value="" placeholder="Enter transaction type name" <?= $selectedType === null ? '' : 'disabled'; ?>>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Who May Avail</label>
                        <textarea class="form-control" name="who_may_avail" rows="3" placeholder="Who can request this document?"><?= htmlspecialchars((string) ($document['who_may_avail'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>

                    <hr class="my-4">
                    <h6 class="form-section-title mb-3">Requirements</h6>
                    <div class="row g-3 align-items-end">
                        <div class="col-md-6 requirement-count-wrap">
                            <label class="form-label mb-1">Number of Requirements</label>
                            <input type="number" id="requirement-count" class="form-control mt-2" min="1" step="1" value="<?= max(1, count($requirements)); ?>" placeholder="1">
                        </div>
                        
                    </div>
                    <div id="requirements-container" class="mt-3">
                        <?php if (empty($requirements)): ?>
                            <div class="row g-3 mb-3 requirement-row">
                                <div class="col-md-6">
                                    <label class="form-label">Requirement 1</label>
                                    <textarea class="form-control" rows="2" name="requirements[]" placeholder="List required document or checklist item"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Where to Secure 1</label>
                                    <textarea class="form-control" rows="2" name="where_to_secure[]" placeholder="Where the client can secure this requirement"></textarea>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($requirements as $index => $requirement): ?>
                                <div class="row g-3 mb-3 requirement-row">
                                    <div class="col-md-6">
                                        <label class="form-label">Requirement <?= $index + 1; ?></label>
                                        <textarea class="form-control" rows="2" name="requirements[]" placeholder="List required document or checklist item"><?= htmlspecialchars((string) ($requirement['requirement_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Where to Secure <?= $index + 1; ?></label>
                                        <textarea class="form-control" rows="2" name="where_to_secure[]" placeholder="Where the client can secure this requirement"><?= htmlspecialchars((string) ($requirement['where_to_secure'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <hr class="my-4">
                    <h6 class="form-section-title mb-3">Process Steps</h6>
                    <div id="document-steps-container">
                        <?php if (empty($steps)): ?>
                            <div class="row g-3 mt-1 document-step-row align-items-start">
                                <div class="col-md-1">
                                    <label class="form-label">Step No.</label>
                                    <input type="number" class="form-control step-number-input" name="step_number[]" min="1" step="1" inputmode="numeric" pattern="[0-9]*" value="1">
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-outline-danger btn-sm w-100 remove-step-btn" title="Remove step">
                                            <i class="fa-solid fa-minus"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="col">
                                    <label class="form-label">Client Step</label>
                                    <textarea class="form-control" name="client_step[]" rows="3" placeholder="What the client must do"></textarea>
                                </div>
                                <div class="col">
                                    <label class="form-label">Agency Action</label>
                                    <textarea class="form-control" name="agency_action[]" rows="3" placeholder="What the office does"></textarea>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label">Fees</label>
                                    <textarea class="form-control" name="fees[]" rows="3" placeholder="e.g. 180"></textarea>
                                </div>
                                <div class="col-md-1">
                                    <label class="form-label">Time</label>
                                    <textarea class="form-control" name="processing_time[]" rows="3" placeholder="e.g. 5 Minutes"></textarea>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Person Responsible</label>
                                    <textarea class="form-control" name="person_responsible[]" rows="3" placeholder="Office or person assigned"></textarea>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($steps as $index => $step): ?>
                                <div class="row g-3 mt-1 document-step-row align-items-start">
                                    <div class="col-md-1">
                                        <label class="form-label">Step No.</label>
                                        <input type="number" class="form-control step-number-input" name="step_number[]" min="1" step="1" inputmode="numeric" pattern="[0-9]*" value="<?= htmlspecialchars((string) ($step['step_number'] ?? ($index + 1)), ENT_QUOTES, 'UTF-8'); ?>">
                                        <div class="mt-2">
                                            <button type="button" class="btn btn-outline-danger btn-sm w-100 remove-step-btn" title="Remove step">
                                                <i class="fa-solid fa-minus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <label class="form-label">Client Step</label>
                                        <textarea class="form-control" name="client_step[]" rows="3" placeholder="What the client must do"><?= htmlspecialchars((string) ($step['client_step'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    </div>
                                    <div class="col">
                                        <label class="form-label">Agency Action</label>
                                        <textarea class="form-control" name="agency_action[]" rows="3" placeholder="What the office does"><?= htmlspecialchars((string) ($step['agency_action'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label">Fees</label>
                                        <textarea class="form-control" name="fees[]" rows="3" placeholder="Fees to be paid or none"><?= htmlspecialchars((string) ($step['fees'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label">Time</label>
                                        <textarea class="form-control" name="processing_time[]" rows="3" placeholder="e.g. 5 Minutes"><?= htmlspecialchars((string) ($step['processing_time'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Person Responsible</label>
                                        <textarea class="form-control" name="person_responsible[]" rows="3" placeholder="Office or person assigned"><?= htmlspecialchars((string) ($step['person_responsible'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="d-flex justify-content-center mt-3">
                        <button type="button" id="add-document-step" class="btn btn-outline-primary btn-sm px-3">
                            <i class="fa-solid fa-plus me-1"></i>Add Step
                        </button>
                    </div>

                    <div class="totals-panel">
                        <div class="totals-header">
                            <div class="section-title" style="font-size: 0.95rem; font-weight: 800; letter-spacing: 0.03em; color: var(--ns-blue); text-transform: uppercase;">Totals</div>
                            <button type="button" id="toggle-total-manual" class="btn btn-outline-secondary btn-sm manual-toggle-btn">Set Manually</button>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Total Fees</label>
                                <input type="hidden" name="total_fees" id="total-fees-hidden" value="<?= htmlspecialchars(trim((string) ($existingTotalValues['fees'] ?? '₱0.00')) ?: '₱0.00', ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="text" id="total-fees" class="form-control total-input readonly-field" readonly value="<?= htmlspecialchars(trim((string) ($existingTotalValues['fees'] ?? '₱0.00')) ?: '₱0.00', ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Total Time</label>
                                <input type="hidden" name="total_time" id="total-time-hidden" value="<?= htmlspecialchars(trim((string) ($existingTotalValues['time'] ?? '0 minutes')) ?: '0 minutes', ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="text" id="total-time" class="form-control total-input readonly-field" readonly value="<?= htmlspecialchars(trim((string) ($existingTotalValues['time'] ?? '0 minutes')) ?: '0 minutes', ENT_QUOTES, 'UTF-8'); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="action-row">
                        <button type="button" class="btn btn-outline-secondary" onclick="window.history.back();">
                            <i class="fa-solid fa-arrow-left me-2"></i>Back
                        </button>
                        <div class="action-group">
                            <button type="button" class="btn btn-outline-secondary" onclick="window.history.back();">Cancel</button>
                            <button type="submit" name="update_document" class="btn btn-primary">Update Document</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('DOMContentLoaded', function () {
            const toast = document.querySelector('.feedback-toast');
            if (!toast) return;

            window.setTimeout(function () {
                toast.style.transition = 'opacity 0.2s ease, transform 0.2s ease';
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-6px)';
                setTimeout(function () {
                    if (toast && toast.parentNode) {
                        toast.remove();
                    }
                }, 220);
            }, 7000);
        });

        window.documentTransactionTypes = <?php echo json_encode($transactionTypes, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        window.documentTransactionTypeLimit = 0;
        window.documentExistingRequirements = <?php echo json_encode($existingRequirementsData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        window.documentExistingTransactionIds = <?php echo json_encode(array_map('strval', $currentSelectedTransactionIds), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        window.documentSubmittedSteps = <?php echo json_encode($existingSubmittedSteps, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        window.documentSubmittedStepCount = <?php echo json_encode(max(1, count($existingSubmittedSteps))); ?>;
        window.documentExistingTotals = <?php echo json_encode($documentTotalRows, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>
    <script src="docxaddf.php"></script>
</body>
</html>