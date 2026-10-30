<?php
require_once __DIR__ . '/../libraries/db.php';
require_once __DIR__ . '/../functions/audit.php';

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

auditRequireAdmin($db);

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

$officeStmt = $db->query("SELECT name FROM offices ORDER BY name ASC");
$offices = $officeStmt->fetchAll(PDO::FETCH_ASSOC);

$classificationStmt = $db->query("SELECT name FROM classifications ORDER BY name ASC");
$classifications = $classificationStmt->fetchAll(PDO::FETCH_ASSOC);

$transactionTypeStmt = $db->query("SELECT id, name FROM transaction_types ORDER BY name ASC");
$transactionTypes = $transactionTypeStmt->fetchAll(PDO::FETCH_ASSOC);

$saveMessage = '';
$saveError = '';

$submittedRequirements = [];
$submittedTransactionOptions = [];
$submittedSteps = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedRequirements = $_POST['requirements'] ?? [];
    if (!is_array($postedRequirements)) {
        $postedRequirements = [$postedRequirements];
    }
    $postedWhereToSecure = $_POST['where_to_secure'] ?? [];
    if (!is_array($postedWhereToSecure)) {
        $postedWhereToSecure = [$postedWhereToSecure];
    }

    $submittedRequirements = array_values(array_map(
        static fn ($requirement, $secure) => [
            'requirement' => (string) $requirement,
            'secure' => (string) $secure,
        ],
        $postedRequirements,
        array_pad($postedWhereToSecure, count($postedRequirements), '')
    ));

    $postedTransactionValues = $_POST['transaction_types'] ?? [];
    if (!is_array($postedTransactionValues)) {
        $postedTransactionValues = [$postedTransactionValues];
    }
    $postedTransactionOthers = $_POST['transaction_type_other'] ?? [];
    if (!is_array($postedTransactionOthers)) {
        $postedTransactionOthers = [$postedTransactionOthers];
    }

    foreach ($postedTransactionValues as $index => $value) {
        $selectedValue = trim((string) $value);
        $customValue = trim((string) ($postedTransactionOthers[$index] ?? ''));
        $submittedTransactionOptions[] = [
            'selected' => $selectedValue,
            'custom' => $customValue,
        ];
    }

    $postedStepNumbers = $_POST['step_number'] ?? [];
    if (!is_array($postedStepNumbers)) {
        $postedStepNumbers = [$postedStepNumbers];
    }
    $postedClientSteps = $_POST['client_step'] ?? [];
    if (!is_array($postedClientSteps)) {
        $postedClientSteps = [$postedClientSteps];
    }
    $postedAgencyActions = $_POST['agency_action'] ?? [];
    if (!is_array($postedAgencyActions)) {
        $postedAgencyActions = [$postedAgencyActions];
    }
    $postedFees = $_POST['fees'] ?? [];
    if (!is_array($postedFees)) {
        $postedFees = [$postedFees];
    }
    $postedTimes = $_POST['processing_time'] ?? [];
    if (!is_array($postedTimes)) {
        $postedTimes = [$postedTimes];
    }
    $postedPersons = $_POST['person_responsible'] ?? [];
    if (!is_array($postedPersons)) {
        $postedPersons = [$postedPersons];
    }

    foreach ($postedStepNumbers as $index => $stepNumber) {
        $submittedSteps[] = [
            'step_number' => (string) $stepNumber,
            'client_step' => (string) ($postedClientSteps[$index] ?? ''),
            'agency_action' => (string) ($postedAgencyActions[$index] ?? ''),
            'fees' => (string) ($postedFees[$index] ?? ''),
            'processing_time' => (string) ($postedTimes[$index] ?? ''),
            'person_responsible' => (string) ($postedPersons[$index] ?? ''),
        ];
    }
}

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
    string $whoMayAvail,
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

    if (trim($whoMayAvail) === '') {
        throw new InvalidArgumentException('Who may avail is required.');
    }

    $filledSteps = array_values(array_filter(array_map(static fn ($value) => trim((string) $value), $stepNumbers), static fn ($value) => $value !== ''));
    if (count($filledSteps) < 1) {
        throw new InvalidArgumentException('At least one step number is required.');
    }
};

$documentAlreadyExists = static function (string $title, int $officeId, int $classificationId) use ($db): bool {
    $liveStmt = $db->prepare('SELECT id FROM documents WHERE LOWER(title) = LOWER(?) AND office_id = ? AND classification_id = ? LIMIT 1');
    $liveStmt->execute([$title, $officeId, $classificationId]);
    if ($liveStmt->fetch()) {
        return true;
    }

    $archiveStmt = $db->prepare('SELECT id FROM deleted_documents WHERE LOWER(title) = LOWER(?) AND office_id = ? AND classification_id = ? LIMIT 1');
    $archiveStmt->execute([$title, $officeId, $classificationId]);
    return $archiveStmt->fetch() !== false;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();

        $transactionBridgeExists = (bool) $db->query("SHOW TABLES LIKE 'document_transaction_types'")->fetch(PDO::FETCH_ASSOC);
        $documentColumns = $db->query("SHOW COLUMNS FROM documents")->fetchAll(PDO::FETCH_ASSOC);
        $hasLegacyTransactionTypeColumn = false;
        foreach ($documentColumns as $column) {
            if ($column['Field'] === 'transaction_type_id') {
                $hasLegacyTransactionTypeColumn = true;
                break;
            }
        }

        $getOrCreateId = function (string $table, string $column, string $name) use ($db) {
            $cleanName = trim($name);
            if ($cleanName === '') {
                throw new InvalidArgumentException('A required field is empty.');
            }

            $stmt = $db->prepare("SELECT id FROM `$table` WHERE `$column` = :name LIMIT 1");
            $stmt->execute([':name' => $cleanName]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                return (int) $existing['id'];
            }

            $insert = $db->prepare("INSERT INTO `$table` (`$column`) VALUES (:name)");
            $insert->execute([':name' => $cleanName]);
            return (int) $db->lastInsertId();
        };

        $officeInput = trim((string) ($_POST['office'] ?? ''));
        if ($officeInput === 'other') {
            $officeInput = trim((string) ($_POST['office_other'] ?? ''));
        }
        $classificationInput = trim((string) ($_POST['classification'] ?? ''));
        if ($classificationInput === 'other') {
            $classificationInput = trim((string) ($_POST['classification_other'] ?? ''));
        }

        $officeId = $getOrCreateId('offices', 'name', $officeInput);
        $classificationId = $getOrCreateId('classifications', 'name', $classificationInput);

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
                    $selectedTransactionIds[] = $getOrCreateId('transaction_types', 'name', $customName);
                }
                continue;
            }

            if (ctype_digit((string) $value)) {
                $selectedTransactionIds[] = (int) $value;
            }
        }

        $title = trim((string) ($_POST['title'] ?? ''));
        $whoMayAvail = trim((string) ($_POST['who_may_avail'] ?? ''));

        $stepNumbers = $_POST['step_number'] ?? [];
        if (!is_array($stepNumbers)) {
            $stepNumbers = [$stepNumbers];
        }

        $validateRequiredDocumentFields(
            $title,
            $officeInput,
            $classificationInput,
            $whoMayAvail,
            $selectedTransactionIds,
            $stepNumbers
        );

        if ($documentAlreadyExists($title, $officeId, $classificationId)) {
            throw new InvalidArgumentException('A document with this title, office, and classification already exists in the active or archived records.');
        }

        $documentSql = "INSERT INTO documents (title, office_id, classification_id, who_may_avail";
        $documentParams = [$title, $officeId, $classificationId, $whoMayAvail];
        if ($hasLegacyTransactionTypeColumn) {
            $documentSql .= ', transaction_type_id';
            $documentParams[] = $selectedTransactionIds[0] ?? 0;
        }
        $documentSql .= ') VALUES (?, ?, ?, ?';
        if ($hasLegacyTransactionTypeColumn) {
            $documentSql .= ', ?';
        }
        $documentSql .= ')';

        $documentStmt = $db->prepare($documentSql);
        $documentStmt->execute($documentParams);
        $documentId = (int) $db->lastInsertId();

        if ($transactionBridgeExists) {
            foreach (array_unique($selectedTransactionIds) as $transactionId) {
                $bridgeStmt = $db->prepare('INSERT INTO document_transaction_types (document_id, transaction_type_id) VALUES (:document_id, :transaction_type_id)');
                $bridgeStmt->execute([
                    ':document_id' => $documentId,
                    ':transaction_type_id' => $transactionId,
                ]);
            }
        }

        $requirementNames = $_POST['requirements'] ?? [];
        if (!is_array($requirementNames)) {
            $requirementNames = [$requirementNames];
        }
        $whereToSecure = $_POST['where_to_secure'] ?? [];
        if (!is_array($whereToSecure)) {
            $whereToSecure = [$whereToSecure];
        }

        $validateWhereToSecureCount($requirementNames, $whereToSecure);

        foreach ($requirementNames as $index => $requirementName) {
            $requirementText = trim((string) $requirementName);
            if ($requirementText === '') {
                continue;
            }

            $secureText = trim((string) ($whereToSecure[$index] ?? ''));
            $reqStmt = $db->prepare('INSERT INTO requirements (document_id, requirement_name, where_to_secure) VALUES (:document_id, :requirement_name, :where_to_secure)');
            $reqStmt->execute([
                ':document_id' => $documentId,
                ':requirement_name' => $requirementText,
                ':where_to_secure' => $secureText !== '' ? $secureText : null,
            ]);
        }

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
        $responsibles = $_POST['person_responsible'] ?? [];
        if (!is_array($responsibles)) {
            $responsibles = [$responsibles];
        }

        foreach ($stepNumbers as $index => $stepNumber) {
            $clientText = trim((string) ($clientSteps[$index] ?? ''));
            $agencyText = trim((string) ($agencyActions[$index] ?? ''));
            $feeText = trim((string) ($fees[$index] ?? ''));
            $timeText = trim((string) ($processingTimes[$index] ?? ''));
            $personText = trim((string) ($responsibles[$index] ?? ''));

            if ($clientText === '' && $agencyText === '' && $feeText === '' && $timeText === '' && $personText === '') {
                continue;
            }

            $stepStmt = $db->prepare('INSERT INTO document_steps (document_id, step_number, client_step, agency_action, fees, processing_time, person_responsible) VALUES (:document_id, :step_number, :client_step, :agency_action, :fees, :processing_time, :person_responsible)');
            $stepStmt->execute([
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

        auditLog($db, [
            'action' => 'CREATE',
            'module' => 'Documents',
            'target_type' => 'document',
            'target_id' => $documentId,
            'target_name' => $title,
            'description' => 'Created document and its requirements, steps, and totals.',
            'new_values' => [
                'title' => $title,
                'office' => $officeInput,
                'classification' => $classificationInput,
                'who_may_avail' => $whoMayAvail,
                'transaction_type_ids' => array_values(array_unique($selectedTransactionIds)),
                'requirements' => array_values(array_filter(array_map('trim', $requirementNames))),
            ],
            'status' => 'success',
        ]);
        $db->commit();
        redirectToDashboardDocs('Document added successfully.', 'success');
    } catch (Throwable $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $saveError = 'Unable to save document: ' . $e->getMessage();
    }
}

require_once __DIR__ . '/../libraries/include.php';
?>
<title>Add Document</title>
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

    html {
        overflow-y: scroll;
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

    h6 {
        font-family: 'Manrope', 'Inter', sans-serif;
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

    .doc-form-body .form-label {
        font-size: 0.68rem;
        letter-spacing: 0.5px;
        color: #6c757d;
        margin-bottom: 2px;
        text-transform: uppercase;
        font-weight: 700;
    }

    .doc-form-body .required-label::after {
        content: ' *';
        color: #dc2626;
        font-weight: 800;
    }

    .doc-form-body .form-control,
    .doc-form-body .form-select,
    .doc-form-body textarea {
        border: 1px solid #ced4da;
        background: #fff;
        border-radius: 6px;
        min-height: 42px;
        padding: 0.6rem 0.8rem;
        font-size: 0.88rem;
        box-shadow: inset 0 0 0 1px rgba(20, 54, 156, 0.02);
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
    }

    .doc-form-body .form-control:focus,
    .doc-form-body .form-select:focus,
    .doc-form-body textarea:focus {
        border-color: rgba(20, 54, 156, 0.9);
        box-shadow: 0 0 0 0.2rem rgba(20, 54, 156, 0.12);
    }

    .doc-form-body textarea {
        resize: vertical;
    }

    .doc-form-body .form-section {
        margin-top: 18px;
        padding-top: 4px;
    }

    .doc-form-body hr {
        border-top: 2px solid rgba(20, 54, 156, 0.18);
        margin: 1.75rem 0 1.5rem;
        opacity: 1;
    }

    .requirement-row,
    .document-step-row {
        margin-left: 0;
        margin-right: 0;
    }

    .requirement-row>[class*="col-"] {
        margin-bottom: 0;
    }

    .requirement-row .form-label,
    .document-step-row .form-label {
        white-space: nowrap;
    }

    .requirement-note {
        color: #dc2626;
        font-size: 0.72rem;
        font-style: italic;
        font-weight: 600;
        line-height: 1.4;
        margin: 0;
        align-self: flex-end;
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

    .requirement-header-wrap {
        display: flex;
        align-items: end;
        gap: 10px;
        flex-wrap: wrap;
    }

    .requirement-header-wrap .form-label {
        margin-bottom: 0;
    }

    .step-row-actions {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
    }

    .remove-step-btn {
        border: 1px solid #e11d48;
        color: #e11d48;
        background: white;
        border-radius: 10px;
        min-height: 44px;
        font-weight: 700;
    }

    .remove-step-btn:hover {
        background: #fff1f5;
        color: #be123c;
    }

    .totals-panel {
        margin-top: 24px;
        padding: 18px 18px 8px;
        background: rgba(238, 244, 255, 0.9);
        border: 1px solid rgba(20, 54, 156, 0.12);
        border-radius: 16px;
    }

    .totals-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 6px;
    }

    .totals-header .section-title {
        margin: 0;
        font-size: 0.78rem;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: #64748b;
        font-weight: 800;
    }

    .manual-toggle-btn {
        min-width: 130px;
        font-weight: 700;
    }

    .total-input.readonly-field {
        background-color: #f8fafc;
        color: #334155;
        cursor: not-allowed;
    }

    .doc-form-actions {
        border-top: 1px solid rgba(20, 54, 156, 0.08);
        margin-top: 24px;
        padding-top: 20px;
    }

    .action-row {
        width: 100%;
        gap: 16px;
    }

    .action-back-btn,
    .action-group .btn {
        min-height: 44px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }

    .action-group {
        margin-left: auto;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--ns-blue), var(--ns-blue-deep));
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

    .btn-outline-primary {
        border-radius: 12px;
        font-weight: 700;
    }

    #transaction-types-container {
        display: flex;
        flex-direction: column;
        gap: 12px;
        width: 100%;
    }

    .transaction-type-entry {
        width: 100%;
        margin: 0;
    }

    .transaction-type-entry-label {
        display: block;
        margin-bottom: 8px;
        font-size: 0.92rem;
        font-weight: 700;
    }

    .transaction-type-entry .other-field-pair {
        display: flex;
        gap: 16px;
        align-items: flex-start;
        width: 100%;
        margin: 0;
    }

    .transaction-type-entry .transaction-select-wrap,
    .transaction-type-entry .transaction-custom-wrap {
        flex: 0 0 100%;
        max-width: 100%;
        width: 100%;
        padding: 0;
    }

    .transaction-type-entry .other-field-pair.has-other > .transaction-select-wrap,
    .transaction-type-entry .other-field-pair.has-other > .transaction-custom-wrap {
        flex: 0 0 50%;
        max-width: 50%;
        width: 50%;
    }

    .transaction-type-entry .other-input-wrap {
        display: none;
    }

    .transaction-type-entry .other-input-wrap.visible {
        display: block;
    }

    .transaction-type-entry .other-input-wrap .form-control {
        min-height: 48px;
        height: 48px;
        line-height: 1.2;
    }

    .transaction-type-count-wrap {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        width: 100%;
    }

    .transaction-type-count-wrap .form-label {
        margin-bottom: 8px;
    }

    .transaction-type-count-wrap .form-control {
        max-width: 180px;
    }

    .other-input-wrap {
        display: none;
    }

    .other-input-wrap.visible {
        display: block;
    }

    .other-input-wrap .form-control {
        min-height: 48px;
        height: 48px;
        line-height: 1.2;
    }

    .other-field-pair {
        display: flex;
        gap: 16px;
        align-items: stretch;
        width: 100%;
    }

    .other-field-pair > .col-6,
    .other-field-pair > .transaction-select-wrap,
    .other-field-pair > .transaction-custom-wrap,
    .other-field-pair > .other-input-wrap {
        flex: 0 0 50%;
        width: 50%;
        max-width: 50%;
        min-width: 0;
        padding-left: 0;
        padding-right: 0;
        margin: 0;
        box-sizing: border-box;
    }

    .other-field-pair .form-select,
    .other-field-pair .form-control {
        height: 48px !important;
        min-height: 48px !important;
        line-height: 1.2;
    }

    .other-field-pair > .other-input-wrap,
    .other-field-pair.has-other > .other-input-wrap {
        display: block;
    }

    .requirement-count-wrap {
        width: 100%;
        max-width: 50%;
    }

    .requirement-count-wrap .form-control {
        width: 100%;
    }

    #requirements-container .requirement-row {
        display: flex;
        gap: 16px;
        align-items: flex-start;
        width: 100%;
    }

    #requirements-container .requirement-row > .col-md-6 {
        flex: 0 0 calc(50% - 8px);
        max-width: calc(50% - 8px);
        width: calc(50% - 8px);
        min-width: 0;
    }
</style>
<div class="doc-form-shell">
    <div class="doc-form-card">
        <div class="doc-form-header">
            <h5>Add Document</h5>
        </div>
        <div class="doc-form-body">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php if ($saveError !== ''): ?>
                    <div class="feedback-toast error" role="status" aria-live="polite">
                        <i class="fa-solid fa-circle-exclamation mt-1"></i>
                        <span><?php echo htmlspecialchars($saveError, ENT_QUOTES, 'UTF-8'); ?></span>
                        <button type="button" onclick="this.parentElement.remove()" aria-label="Close notification">&times;</button>
                    </div>
                <?php elseif ($saveMessage !== ''): ?>
                    <div class="feedback-toast success" role="status" aria-live="polite">
                        <i class="fa-solid fa-circle-check mt-1"></i>
                        <span><?php echo htmlspecialchars($saveMessage, ENT_QUOTES, 'UTF-8'); ?></span>
                        <button type="button" onclick="this.parentElement.remove()" aria-label="Close notification">&times;</button>
                    </div>
                <?php endif; ?>
                <h6 class="form-section-title mb-1">
                    General Info</h6>
                <p class="requirement-note mb-3">fields with an asterisk (*) are required.</p>
                <div class="mb-3">
                    <label class="form-label required-label">Document Title</label>
                    <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars((string) ($_POST['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter document title" required>
                </div>
                <div class="mb-3">
                    <div id="office-other-pair" class="other-field-pair">
                        <div class="col-6">
                            <label class="form-label required-label">Office</label>
                            <select id="office-select" class="form-select" name="office">
                                <option value="">-- Select Office --</option>
                                <?php foreach ($offices as $office): ?>
                                    <option value="<?php echo htmlspecialchars($office['name'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo ((string) ($_POST['office'] ?? '') === (string) $office['name']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($office['name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="other" <?php echo ((string) ($_POST['office'] ?? '') === 'other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-6 other-input-wrap" id="office-other-wrap">
                            <label class="form-label">Specify Office</label>
                            <input type="text" class="form-control" name="office_other" value="<?php echo htmlspecialchars((string) ($_POST['office_other'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter office name">
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <div id="classification-other-pair" class="other-field-pair">
                        <div class="col-6">
                            <label class="form-label required-label">Classification</label>
                            <select id="classification-select" class="form-select" name="classification">
                                <option value="">-- Select Classification --</option>
                                <?php foreach ($classifications as $classification): ?>
                                    <option value="<?php echo htmlspecialchars($classification['name'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo ((string) ($_POST['classification'] ?? '') === (string) $classification['name']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($classification['name'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                                <option value="other" <?php echo ((string) ($_POST['classification'] ?? '') === 'other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        <div class="col-6 other-input-wrap" id="classification-other-wrap">
                            <label class="form-label">Specify Classification</label>
                            <input type="text" class="form-control" name="classification_other" value="<?php echo htmlspecialchars((string) ($_POST['classification_other'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="Enter classification name">
                        </div>
                    </div>
                    
                </div>
                <div class="mb-3">
                    <div class="transaction-type-count-wrap mb-3">
                        <label class="form-label required-label">Number of Transaction Types</label>
                        <input type="number" id="transaction-type-count" name="transaction_type_count" class="form-control" min="1" max="10" step="1" value="<?php echo htmlspecialchars((string) ($_POST['transaction_type_count'] ?? '1'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="1">
                    </div>
                    <div id="transaction-types-container"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label required-label">Who May Avail</label>
                    <textarea class="form-control" name="who_may_avail" rows="1" placeholder="Who can request this document?" required><?php echo htmlspecialchars((string) ($_POST['who_may_avail'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <hr class="my-4">
                <h6 class="form-section-title mb-3"
                    style="font-size: 0.95rem; font-weight: 800; letter-spacing: 0.03em; color: var(--ns-blue); text-transform: uppercase;">
                    Requirements</h6>
                <div class="row g-3 align-items-end">
                    <div class="col-md-6 requirement-count-wrap">
                        <div class="requirement-header-wrap">
                            <label class="form-label mb-1">Number of Requirements</label>
                        </div>
                        <input type="number" id="requirement-count" name="requirement_count" class="form-control mt-2" min="1" step="1" value="<?php echo htmlspecialchars((string) ($_POST['requirement_count'] ?? '1'), ENT_QUOTES, 'UTF-8'); ?>" placeholder="1">
                        <p class="requirement-note mb-0">
                            *The 'Requirements' section can have more entries than 'Where to Secure,' but never the
                            other way around.
                        </p>
                    </div>
                </div>

                <div id="requirements-container" class="mt-3"></div>
                <hr class="my-4">
                <h6 class="form-section-title mb-3"
                    style="font-size: 0.95rem; font-weight: 800; letter-spacing: 0.03em; color: var(--ns-blue); text-transform: uppercase;">
                    Process Steps</h6>
                    <p class="requirement-note mb-0">
                            *Fields can be blank; it will default to an empty value.
                            <br>*Step number can be duplicated.
                        </p>
                <div id="document-steps-container">
                    <div class="row g-3 mt-1 document-step-row align-items-start">
                        <div class="col-md-1">
                            <label class="form-label required-label">Step No.</label>
                            
                            <input type="number" class="form-control step-number-input" name="step_number[]" min="1" step="1"
                                inputmode="numeric" pattern="[0-9]*" placeholder="1">
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
                </div>

                <div class="d-flex justify-content-center mt-3">
                    <button type="button" id="add-document-step" class="btn btn-outline-primary btn-sm px-3">
                        <i class="fa-solid fa-plus me-1"></i>Add Step
                    </button>
                </div>

                <div class="totals-panel">
                    <div class="totals-header">
                        <div class="section-title"  style="font-size: 0.95rem; font-weight: 800; letter-spacing: 0.03em; color: var(--ns-blue); text-transform: uppercase;">Totals</div>
                        <button type="button" id="toggle-total-manual" class="btn btn-outline-secondary btn-sm manual-toggle-btn">
                            Set Manually
                        </button>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Total Fees</label>
                            <input type="hidden" name="total_fees" id="total-fees-hidden" value="₱0.00">
                            <input type="text" id="total-fees" class="form-control total-input readonly-field" readonly value="₱0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Total Time</label>
                            <input type="hidden" name="total_time" id="total-time-hidden" value="0 minutes">
                            <input type="text" id="total-time" class="form-control total-input readonly-field" readonly value="0 minutes">
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3 mt-4 pt-3 border-top action-row">
                    <button type="button" class="btn btn-outline-secondary action-back-btn" onclick="window.history.back();">
                        <i class="fa-solid fa-arrow-left me-2"></i>Back
                    </button>
                    <div class="d-flex justify-content-end align-items-center gap-2 action-group">
                        <button type="button" id="clear-document-form" class="btn btn-outline-secondary">Clear</button>
                        <button type="submit" class="btn btn-primary">Save Document</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
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
    window.documentExistingRequirements = <?php echo json_encode($submittedRequirements, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    window.documentExistingTransactionIds = <?php echo json_encode(array_map(static fn ($option) => trim((string) ($option['selected'] ?? '')), $submittedTransactionOptions), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    window.documentSubmittedTransactionOptions = <?php echo json_encode($submittedTransactionOptions, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    window.documentSubmittedSteps = <?php echo json_encode($submittedSteps, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    window.documentSubmittedRequirementCount = <?php echo json_encode(max(1, (int) ($_POST['requirement_count'] ?? count($submittedRequirements) ?: 1))); ?>;
    window.documentSubmittedTransactionTypeCount = <?php echo json_encode(max(1, (int) ($_POST['transaction_type_count'] ?? count($submittedTransactionOptions) ?: 1))); ?>;
    window.documentSubmittedStepCount = <?php echo json_encode(max(1, count($submittedSteps))); ?>;
</script>
<script src="docxaddf.php"></script>