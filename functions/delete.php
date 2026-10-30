<?php
require_once __DIR__ . '/../libraries/include.php';
require_once __DIR__ . '/audit.php';

class delete
{
    private $con;

    public function __construct($con)
    {
        $this->con = $con;
    }

    public function getById(int $id, string $table = 'user'): array
    {
        $safeTable = in_array($table, ['user', 'documents'], true) ? $table : 'user';
        $stmt = $this->con->prepare("SELECT * FROM `$safeTable` WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: [];
    }

    private function getUserColumnList(): string
    {
        $columns = $this->con->query('SHOW COLUMNS FROM `user`')->fetchAll(PDO::FETCH_COLUMN, 0);
        return implode(', ', array_map(static fn (string $column): string => '`' . str_replace('`', '``', $column) . '`', $columns));
    }

    private function getTableColumnList(string $table): string
    {
        $columns = $this->con->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_COLUMN, 0);
        return implode(', ', array_map(static fn (string $column): string => '`' . str_replace('`', '``', $column) . '`', $columns));
    }

    private function archiveDocumentRelations(int $documentId): void
    {
        $relatedTables = [
            'document_transaction_types' => 'deleted_document_transaction_types',
            'requirements' => 'deleted_requirements',
            'document_steps' => 'deleted_document_steps',
            'total' => 'deleted_total',
        ];

        foreach ($relatedTables as $sourceTable => $archiveTable) {
            $tableExists = $this->con->query("SHOW TABLES LIKE '$sourceTable'")->fetchColumn();
            if ($tableExists === false) {
                continue;
            }

            $columns = $this->getTableColumnList($sourceTable);
            $archive = $this->con->prepare("INSERT INTO `$archiveTable` ($columns, `deleted_at`, `deleted_by`) SELECT $columns, NOW(), ? FROM `$sourceTable` WHERE document_id = ?");
            $archive->execute([!empty($_SESSION['id']) ? (int) $_SESSION['id'] : null, $documentId]);

            $deleteRows = $this->con->prepare("DELETE FROM `$sourceTable` WHERE document_id = ?");
            $deleteRows->execute([$documentId]);
        }
    }

    private function restoreDocumentRelations(int $documentId): void
    {
        $relatedTables = [
            'document_transaction_types' => 'deleted_document_transaction_types',
            'requirements' => 'deleted_requirements',
            'document_steps' => 'deleted_document_steps',
            'total' => 'deleted_total',
        ];

        foreach ($relatedTables as $targetTable => $archiveTable) {
            $tableExists = $this->con->query("SHOW TABLES LIKE '$targetTable'")->fetchColumn();
            if ($tableExists === false) {
                continue;
            }

            $columns = $this->getTableColumnList($targetTable);
            $restore = $this->con->prepare("INSERT INTO `$targetTable` ($columns) SELECT $columns FROM `$archiveTable` WHERE document_id = ?");
            $restore->execute([$documentId]);

            $clearArchive = $this->con->prepare("DELETE FROM `$archiveTable` WHERE document_id = ?");
            $clearArchive->execute([$documentId]);
        }
    }

    public function deleteUser(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $sessionUserId = (int) ($_SESSION['id'] ?? 0);
        $isAdmin = !empty($_SESSION['id']) && (int) ($_SESSION['role_id'] ?? 0) === 1 && empty($_SESSION['is_guest']);
        $isSelfDelete = $sessionUserId > 0 && $sessionUserId === $id;

        if (!$isAdmin && !$isSelfDelete) {
            auditLogSafely($this->con, ['action' => 'ACCESS_DENIED', 'module' => 'Users', 'target_type' => 'user', 'target_id' => $id, 'description' => 'Denied attempt to delete another user account.', 'status' => 'denied']);
            http_response_code(403);
            exit('You may only delete your own account.');
        }
        $target = $this->getById($id, 'user');
        if (!$target) return false;
        $this->con->beginTransaction();
        try {
            $columns = $this->getUserColumnList();
            $archive = $this->con->prepare("INSERT INTO `deleted_users` ($columns, `deleted_at`, `deleted_by`) SELECT $columns, NOW(), ? FROM `user` WHERE id = ?");
            $archive->execute([!empty($_SESSION['id']) ? (int) $_SESSION['id'] : null, $id]);
            if ($archive->rowCount() !== 1) throw new RuntimeException('Account could not be archived.');

            $stmt = $this->con->prepare('DELETE FROM `user` WHERE id = ?');
            $stmt->execute([$id]);
            if ($stmt->rowCount() !== 1) throw new RuntimeException('Account deletion failed.');
            auditLog($this->con, [
                'action' => 'DELETE', 'module' => 'Users', 'target_type' => 'user', 'target_id' => $id,
                'target_name' => trim((string) ($target['first_name'] ?? '') . ' ' . (string) ($target['last_name'] ?? '')),
                'description' => 'Moved a user account to the deleted users archive.',
                'old_values' => ['email' => (string) ($target['email'] ?? ''), 'role_id' => (int) ($target['role_id'] ?? 0)],
                'status' => 'success',
            ]);
            $this->con->commit();
            return true;
        } catch (Throwable $error) {
            if ($this->con->inTransaction()) $this->con->rollBack();
            return false;
        }
    }

    public function restoreUser(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        auditRequireAdmin($this->con);
        $this->con->beginTransaction();
        try {
            $targetStmt = $this->con->prepare('SELECT * FROM `deleted_users` WHERE id = ? LIMIT 1 FOR UPDATE');
            $targetStmt->execute([$id]);
            $target = $targetStmt->fetch(PDO::FETCH_ASSOC);
            if (!$target) throw new RuntimeException('Archived user not found.');

            $columns = $this->getUserColumnList();
            $restore = $this->con->prepare("INSERT INTO `user` ($columns) SELECT $columns FROM `deleted_users` WHERE id = ?");
            $restore->execute([$id]);
            if ($restore->rowCount() !== 1) throw new RuntimeException('User could not be restored.');

            $removeArchive = $this->con->prepare('DELETE FROM `deleted_users` WHERE id = ?');
            $removeArchive->execute([$id]);
            if ($removeArchive->rowCount() !== 1) throw new RuntimeException('Archived user record could not be removed.');

            auditLog($this->con, [
                'action' => 'RESTORE', 'module' => 'Users', 'target_type' => 'user', 'target_id' => $id,
                'target_name' => trim((string) ($target['first_name'] ?? '') . ' ' . (string) ($target['last_name'] ?? '')),
                'description' => 'Restored a user account from the deleted users archive.',
                'old_values' => ['email' => (string) ($target['email'] ?? ''), 'role_id' => (int) ($target['role_id'] ?? 0)],
                'status' => 'success',
            ]);
            $this->con->commit();
            return true;
        } catch (Throwable $error) {
            if ($this->con->inTransaction()) $this->con->rollBack();
            return false;
        }
    }

    public function deleteArchivedUser(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        auditRequireAdmin($this->con);
        $targetStmt = $this->con->prepare('SELECT * FROM `deleted_users` WHERE id = ? LIMIT 1 FOR UPDATE');
        $targetStmt->execute([$id]);
        $target = $targetStmt->fetch(PDO::FETCH_ASSOC);
        if (!$target) {
            return false;
        }

        $this->con->beginTransaction();
        try {
            $deleteArchive = $this->con->prepare('DELETE FROM `deleted_users` WHERE id = ?');
            $deleteArchive->execute([$id]);
            if ($deleteArchive->rowCount() !== 1) {
                throw new RuntimeException('Archived user record could not be removed.');
            }

            auditLog($this->con, [
                'action' => 'DELETE', 'module' => 'Users', 'target_type' => 'user', 'target_id' => $id,
                'target_name' => trim((string) ($target['first_name'] ?? '') . ' ' . (string) ($target['last_name'] ?? '')),
                'description' => 'Permanently deleted an archived user account.',
                'old_values' => ['email' => (string) ($target['email'] ?? ''), 'role_id' => (int) ($target['role_id'] ?? 0)],
                'status' => 'success',
            ]);
            $this->con->commit();
            return true;
        } catch (Throwable $error) {
            if ($this->con->inTransaction()) {
                $this->con->rollBack();
            }
            return false;
        }
    }

    public function deleteDocument(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        auditRequireAdmin($this->con);
        try {
            $this->con->beginTransaction();
            $targetStmt = $this->con->prepare('SELECT * FROM documents WHERE id = ? LIMIT 1');
            $targetStmt->execute([$id]);
            $target = $targetStmt->fetch(PDO::FETCH_ASSOC);
            if (!$target) throw new RuntimeException('Document not found.');

            $documentColumns = $this->getTableColumnList('documents');
            $archive = $this->con->prepare("INSERT INTO `deleted_documents` ($documentColumns, `deleted_at`, `deleted_by`) SELECT $documentColumns, NOW(), ? FROM `documents` WHERE id = ?");
            $archive->execute([!empty($_SESSION['id']) ? (int) $_SESSION['id'] : null, $id]);
            if ($archive->rowCount() !== 1) {
                throw new RuntimeException('Document could not be archived.');
            }

            $this->archiveDocumentRelations($id);

            $legacyColumnExists = (bool) $this->con->query("SHOW COLUMNS FROM documents LIKE 'transaction_type_id'")->fetchColumn();
            if ($legacyColumnExists) {
                $legacyUpdate = $this->con->prepare('UPDATE documents SET transaction_type_id = NULL WHERE id = ?');
                $legacyUpdate->execute([$id]);
            }

            $deleteDocument = $this->con->prepare('DELETE FROM documents WHERE id = ?');
            $result = $deleteDocument->execute([$id]);
            if ($result !== true) {
                throw new RuntimeException('Document deletion failed.');
            }

            auditLog($this->con, [
                'action' => 'DELETE', 'module' => 'Documents', 'target_type' => 'document', 'target_id' => $id,
                'target_name' => (string) ($target['title'] ?? ''), 'description' => 'Archived document and moved it to the deleted documents archive.',
                'old_values' => ['title' => (string) ($target['title'] ?? ''), 'who_may_avail' => (string) ($target['who_may_avail'] ?? '')],
                'status' => 'success',
            ]);

            $this->con->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->con->inTransaction()) {
                $this->con->rollBack();
            }
            error_log('deleteDocument(' . $id . ') failed: ' . $e->getMessage());
            return false;
        }
    }

    public function restoreDocument(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        auditRequireAdmin($this->con);
        try {
            $this->con->beginTransaction();
            $targetStmt = $this->con->prepare('SELECT * FROM `deleted_documents` WHERE id = ? LIMIT 1 FOR UPDATE');
            $targetStmt->execute([$id]);
            $target = $targetStmt->fetch(PDO::FETCH_ASSOC);
            if (!$target) throw new RuntimeException('Archived document not found.');

            $documentColumns = $this->getTableColumnList('documents');
            $restore = $this->con->prepare("INSERT INTO `documents` ($documentColumns) SELECT $documentColumns FROM `deleted_documents` WHERE id = ?");
            $restore->execute([$id]);
            if ($restore->rowCount() !== 1) {
                throw new RuntimeException('Document could not be restored.');
            }

            $this->restoreDocumentRelations($id);

            $removeArchive = $this->con->prepare('DELETE FROM `deleted_documents` WHERE id = ?');
            $removeArchive->execute([$id]);
            if ($removeArchive->rowCount() !== 1) {
                throw new RuntimeException('Archived document record could not be removed.');
            }

            auditLog($this->con, [
                'action' => 'RESTORE', 'module' => 'Documents', 'target_type' => 'document', 'target_id' => $id,
                'target_name' => (string) ($target['title'] ?? ''), 'description' => 'Restored a document from the deleted documents archive.',
                'old_values' => ['title' => (string) ($target['title'] ?? ''), 'who_may_avail' => (string) ($target['who_may_avail'] ?? '')],
                'status' => 'success',
            ]);

            $this->con->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->con->inTransaction()) {
                $this->con->rollBack();
            }
            error_log('restoreDocument(' . $id . ') failed: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteArchivedDocument(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        auditRequireAdmin($this->con);
        $targetStmt = $this->con->prepare('SELECT * FROM `deleted_documents` WHERE id = ? LIMIT 1 FOR UPDATE');
        $targetStmt->execute([$id]);
        $target = $targetStmt->fetch(PDO::FETCH_ASSOC);
        if (!$target) {
            return false;
        }

        try {
            $this->con->beginTransaction();

            $relatedTables = [
                'deleted_document_transaction_types',
                'deleted_requirements',
                'deleted_document_steps',
                'deleted_total',
            ];

            foreach ($relatedTables as $archiveTable) {
                $tableExists = $this->con->query("SHOW TABLES LIKE '$archiveTable'")->fetchColumn();
                if ($tableExists !== false) {
                    $deleteRelations = $this->con->prepare("DELETE FROM `$archiveTable` WHERE document_id = ?");
                    $deleteRelations->execute([$id]);
                }
            }

            $removeArchive = $this->con->prepare('DELETE FROM `deleted_documents` WHERE id = ?');
            $removeArchive->execute([$id]);
            if ($removeArchive->rowCount() !== 1) {
                throw new RuntimeException('Archived document record could not be removed.');
            }

            auditLog($this->con, [
                'action' => 'DELETE', 'module' => 'Documents', 'target_type' => 'document', 'target_id' => $id,
                'target_name' => (string) ($target['title'] ?? ''), 'description' => 'Permanently deleted an archived document.',
                'old_values' => ['title' => (string) ($target['title'] ?? ''), 'who_may_avail' => (string) ($target['who_may_avail'] ?? '')],
                'status' => 'success',
            ]);

            $this->con->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->con->inTransaction()) {
                $this->con->rollBack();
            }
            error_log('deleteArchivedDocument(' . $id . ') failed: ' . $e->getMessage());
            return false;
        }
    }
}

$delete = new delete($db);

if (isset($_POST['restore_document'])) {
    $id = (int) ($_POST['id'] ?? 0);
    $restored = $delete->restoreDocument($id);
    $_SESSION['flash_message'] = $restored
        ? 'Document restored successfully.'
        : 'Document could not be restored.';
    $_SESSION['flash_type'] = $restored ? 'success' : 'danger';
    header('Location: ../dashboard.php?tab=archive#pills-archive');
    exit;
} elseif (isset($_POST['restore_user'])) {
    $id = (int) ($_POST['id'] ?? 0);
    $restored = $delete->restoreUser($id);
    $_SESSION['flash_message'] = $restored
        ? 'User restored successfully.'
        : 'User could not be restored. Check that the email is not already assigned to another account.';
    $_SESSION['flash_type'] = $restored ? 'success' : 'danger';
    setcookie('dashboard_active_tab', 'archive', [
        'expires' => time() + (60 * 60 * 24 * 365),
        'path' => '/',
        'samesite' => 'Lax',
        'secure' => false,
    ]);
    header('Location: ../dashboard.php?tab=archive#pills-archive');
    exit;
} elseif (isset($_POST['delete_own_account'])) {
    $id = (int) ($_SESSION['id'] ?? 0);
    if ($id <= 0 || !empty($_SESSION['is_guest'])) {
        http_response_code(401);
        exit('Sign in is required to delete your account.');
    }

    $archived = $delete->deleteUser($id);
    if ($archived) {
        session_unset();
        session_destroy();
        header('Location: ../index.php?account_deleted=1');
        exit;
    }

    $_SESSION['flash_message'] = 'Your account could not be deleted.';
    $_SESSION['flash_type'] = 'danger';
    header('Location: ../dashboard.php');
    exit;
} elseif (isset($_POST['delete_archived_user'])) {
    $id = (int) ($_POST['id'] ?? 0);
    $deleted = $delete->deleteArchivedUser($id);
    $_SESSION['flash_message'] = $deleted ? 'Archived user permanently deleted.' : 'Archived user could not be permanently deleted.';
    $_SESSION['flash_type'] = $deleted ? 'success' : 'danger';
    setcookie('dashboard_active_tab', 'archive', [
        'expires' => time() + (60 * 60 * 24 * 365),
        'path' => '/',
        'samesite' => 'Lax',
        'secure' => false,
    ]);
    header('Location: ../dashboard.php?tab=archive#pills-archive');
    exit;
} elseif (isset($_POST['delete_archived_document'])) {
    $id = (int) ($_POST['id'] ?? 0);
    $deleted = $delete->deleteArchivedDocument($id);
    $_SESSION['flash_message'] = $deleted ? 'Archived document permanently deleted.' : 'Archived document could not be permanently deleted.';
    $_SESSION['flash_type'] = $deleted ? 'success' : 'danger';
    setcookie('dashboard_active_tab', 'archive', [
        'expires' => time() + (60 * 60 * 24 * 365),
        'path' => '/',
        'samesite' => 'Lax',
        'secure' => false,
    ]);
    header('Location: ../dashboard.php?tab=archive#pills-archive');
    exit;
} elseif (isset($_POST['delete']) || (strtolower((string) ($_POST['target'] ?? '')) === 'document' && isset($_POST['id']) && !isset($_POST['bulk_delete']))) {
    $id = (int) ($_POST['id'] ?? 0);
    $target = strtolower((string) ($_POST['target'] ?? 'user'));

    if ($id > 0) {
        if ($target === 'document') {
            $archivedDoc = $delete->deleteDocument($id);
            $_SESSION['flash_message'] = $archivedDoc
                ? 'Document moved to the Archive.'
                : 'Document could not be archived. Nothing was deleted.';
            $_SESSION['flash_type'] = $archivedDoc ? 'success' : 'danger';

            $returnTab = (string) ($_POST['return_tab'] ?? 'docs-management');
            $allowedTabs = ['home', 'documents', 'map', 'admin', 'users', 'tickets', 'docs-management', 'document-requests', 'map-configuration', 'audit-logs', 'archive'];
            if (!in_array($returnTab, $allowedTabs, true)) {
                $returnTab = 'docs-management';
            }
            setcookie('dashboard_active_tab', $returnTab, [
                'expires' => time() + (60 * 60 * 24 * 365),
                'path' => '/',
                'samesite' => 'Lax',
                'secure' => false,
            ]);
            header('Location: ../dashboard.php?tab=' . rawurlencode($returnTab) . '#' . ($returnTab === 'archive' ? 'pills-archive' : 'pills-' . $returnTab));
            exit;
        }

        if (empty($_SESSION['id']) && empty($_SESSION['is_guest'])) {
            http_response_code(401);
            exit('Sign in is required.');
        }
        $archived = $delete->deleteUser($id);
        if ($archived && (int) ($_SESSION['id'] ?? 0) === $id) {
            session_unset();
            session_destroy();
            header('Location: ../index.php?account_deleted=1');
            exit;
        }
        $_SESSION['flash_message'] = $archived ? 'User moved to Deleted Users.' : 'User could not be deleted.';
        $_SESSION['flash_type'] = $archived ? 'success' : 'danger';
        header('Location: ../dashboard.php');
        exit;
    }
} elseif (isset($_POST['bulk_delete'])) {
    $ids = [];
    if (!empty($_POST['ids']) && is_array($_POST['ids'])) {
        $ids = array_map('intval', $_POST['ids']);
    } else {
        $selected = $_POST['selected_ids'] ?? '';
        $ids = array_map('intval', preg_split('/\s*,\s*/', trim((string) $selected), -1, PREG_SPLIT_NO_EMPTY));
    }
    $ids = array_values(array_unique(array_filter($ids, static fn (int $v): bool => $v > 0)));
    $target = strtolower((string) ($_POST['target'] ?? 'user'));

    $ok = 0;
    $failed = 0;
    if (!empty($ids)) {
        if ($target === 'user' && (int) ($_SESSION['role_id'] ?? 0) !== 1) {
            http_response_code(403);
            exit('Administrator access is required for bulk user deletion.');
        }
        foreach ($ids as $id) {
            $done = ($target === 'document') ? $delete->deleteDocument($id) : $delete->deleteUser($id);
            $done ? $ok++ : $failed++;
        }
    }

    if ($ok === 0 && $failed === 0) {
        $_SESSION['flash_message'] = 'No records were selected.';
        $_SESSION['flash_type'] = 'danger';
    } else {
        $label = ($target === 'document') ? 'document(s) moved to the Archive' : 'user(s) moved to Deleted Users';
        $_SESSION['flash_message'] = $ok . ' ' . $label . ($failed ? ', ' . $failed . ' failed.' : '.');
        $_SESSION['flash_type'] = $failed ? 'danger' : 'success';
    }
    header('Location: ../dashboard.php');
    exit;
} else {
    header('Location: ../dashboard.php');
    exit;
}