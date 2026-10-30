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

    header('Location: ../dashboard.php?tab=docs-management#pills-docs-management');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectToDashboardDocs('This action requires a form submission.', 'warning');
}

$ids = [];
if (isset($_POST['ids']) && is_array($_POST['ids'])) {
    foreach ($_POST['ids'] as $id) {
        $cleanId = (int) $id;
        if ($cleanId > 0) {
            $ids[] = $cleanId;
        }
    }
} else {
    $singleId = (int) ($_POST['id'] ?? 0);
    if ($singleId > 0) {
        $ids[] = $singleId;
    }
}

if (empty($ids)) {
    redirectToDashboardDocs('No document was selected for deletion.', 'warning');
}

try {
    $db->beginTransaction();

    foreach (array_unique($ids) as $id) {
        $targetStmt = $db->prepare('SELECT * FROM documents WHERE id = ? LIMIT 1');
        $targetStmt->execute([$id]);
        $target = $targetStmt->fetch(PDO::FETCH_ASSOC);
        if (!$target) continue;

        $documentColumns = $db->query("SHOW COLUMNS FROM `documents`")->fetchAll(PDO::FETCH_COLUMN, 0);
        $documentColumnsSql = implode(', ', array_map(static fn (string $column): string => '`' . str_replace('`', '``', $column) . '`', $documentColumns));
        $archive = $db->prepare("INSERT INTO `deleted_documents` ($documentColumnsSql, `deleted_at`, `deleted_by`) SELECT $documentColumnsSql, NOW(), ? FROM `documents` WHERE id = ?");
        $archive->execute([!empty($_SESSION['id']) ? (int) $_SESSION['id'] : null, $id]);
        if ($archive->rowCount() !== 1) {
            throw new RuntimeException('Document could not be archived.');
        }

        $relatedTables = [
            'document_transaction_types' => 'deleted_document_transaction_types',
            'requirements' => 'deleted_requirements',
            'document_steps' => 'deleted_document_steps',
            'total' => 'deleted_total',
        ];

        foreach ($relatedTables as $sourceTable => $archiveTable) {
            $tableExists = $db->query("SHOW TABLES LIKE '$sourceTable'")->fetchColumn();
            if ($tableExists === false) {
                continue;
            }

            $sourceColumns = $db->query("SHOW COLUMNS FROM `$sourceTable`")->fetchAll(PDO::FETCH_COLUMN, 0);
            $sourceColumnsSql = implode(', ', array_map(static fn (string $column): string => '`' . str_replace('`', '``', $column) . '`', $sourceColumns));
            $archiveChildren = $db->prepare("INSERT INTO `$archiveTable` ($sourceColumnsSql, `deleted_at`, `deleted_by`) SELECT $sourceColumnsSql, NOW(), ? FROM `$sourceTable` WHERE document_id = ?");
            $archiveChildren->execute([!empty($_SESSION['id']) ? (int) $_SESSION['id'] : null, $id]);

            $deleteChildren = $db->prepare("DELETE FROM `$sourceTable` WHERE document_id = ?");
            $deleteChildren->execute([$id]);
        }

        $deleteDocument = $db->prepare('DELETE FROM documents WHERE id = ?');
        $deleteDocument->execute([$id]);

        auditLog($db, [
            'action' => 'DELETE',
            'module' => 'Documents',
            'target_type' => 'document',
            'target_id' => $id,
            'target_name' => (string) ($target['title'] ?? ''),
            'description' => 'Moved a document to the deleted documents archive.',
            'old_values' => ['title' => (string) ($target['title'] ?? ''), 'who_may_avail' => (string) ($target['who_may_avail'] ?? '')],
            'status' => 'success',
        ]);
    }

    $db->commit();
    redirectToDashboardDocs('Document(s) moved to archive successfully.', 'success');
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    redirectToDashboardDocs('Unable to move the selected document(s) to archive.', 'danger');
}
