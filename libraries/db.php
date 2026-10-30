<?php
class Database
{
    private const ARCHIVE_RETENTION_DAYS = 30;

    private $host = 'localhost';
    private $db = 'capstone';
    private $user = 'root';
    private $pass = '';
    private $charset = 'utf8mb4';
    private $port = '3306';

    private $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    private $pdo;
    private $dsn;

    private function ensureUserColumnExists(string $column, string $alterSql): void
    {
        $stmt = $this->pdo->query("SHOW COLUMNS FROM `user` LIKE '{$column}'");
        if ($stmt && $stmt->fetch() !== false) {
            return;
        }

        $this->pdo->exec($alterSql);
    }

    private function ensurePasswordResetColumns(): void
    {
        $this->ensureUserColumnExists('password_reset_otp', "ALTER TABLE `user` ADD COLUMN `password_reset_otp` VARCHAR(255) NULL AFTER `password`");
        $this->ensureUserColumnExists('password_reset_expires_at', "ALTER TABLE `user` ADD COLUMN `password_reset_expires_at` DATETIME NULL AFTER `password_reset_otp`");
        $this->ensureUserColumnExists('password_reset_attempts', "ALTER TABLE `user` ADD COLUMN `password_reset_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `password_reset_expires_at`");
        $this->ensureUserColumnExists('password_reset_locked_until', "ALTER TABLE `user` ADD COLUMN `password_reset_locked_until` DATETIME NULL AFTER `password_reset_attempts`");
        $this->ensureUserColumnExists('password_reset_requested_at', "ALTER TABLE `user` ADD COLUMN `password_reset_requested_at` DATETIME NULL AFTER `password_reset_locked_until`");

        $otpColumn = $this->pdo->query("SHOW COLUMNS FROM `user` LIKE 'password_reset_otp'")->fetch(PDO::FETCH_ASSOC);
        if ($otpColumn && preg_match('/^varchar\((\d+)\)$/i', (string) ($otpColumn['Type'] ?? ''), $matches) && (int) $matches[1] < 255) {
            $this->pdo->exec("ALTER TABLE `user` MODIFY COLUMN `password_reset_otp` VARCHAR(255) NULL");
        }
    }

    private function ensureDocumentRequestSuffixColumn(): void
    {
        $table = $this->pdo->query("SHOW TABLES LIKE 'document_requests'")->fetchColumn();
        if ($table === false) {
            return;
        }

        $column = $this->pdo->query("SHOW COLUMNS FROM `document_requests` LIKE 'suffix'")->fetchColumn();
        if ($column === false) {
            $this->pdo->exec('ALTER TABLE `document_requests` ADD COLUMN `suffix` VARCHAR(20) NULL AFTER `lname`');
        }
    }

    private function ensureDeletedUsersTable(): void
    {
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS `deleted_users` LIKE `user`');

        foreach ([
            'deleted_at' => 'ALTER TABLE `deleted_users` ADD COLUMN `deleted_at` DATETIME NULL',
            'deleted_by' => 'ALTER TABLE `deleted_users` ADD COLUMN `deleted_by` INT NULL',
        ] as $column => $alterSql) {
            $columnExists = $this->pdo->query("SHOW COLUMNS FROM `deleted_users` LIKE '{$column}'")->fetchColumn();
            if ($columnExists === false) {
                $this->pdo->exec($alterSql);
            }
        }

        $uniqueIndexes = [];
        foreach ($this->pdo->query('SHOW INDEX FROM `deleted_users`')->fetchAll(PDO::FETCH_ASSOC) as $index) {
            $indexName = (string) ($index['Key_name'] ?? '');
            if ($indexName !== '' && $indexName !== 'PRIMARY' && (int) ($index['Non_unique'] ?? 1) === 0) {
                $uniqueIndexes[$indexName] = true;
            }
        }
        foreach (array_keys($uniqueIndexes) as $indexName) {
            $quotedIndex = str_replace('`', '``', $indexName);
            $this->pdo->exec("ALTER TABLE `deleted_users` DROP INDEX `$quotedIndex`");
        }
    }

    private function ensureDeletedDocumentsTable(): void
    {
        $archiveTables = [
            'deleted_documents' => 'documents',
            'deleted_document_steps' => 'document_steps',
            'deleted_requirements' => 'requirements',
            'deleted_document_transaction_types' => 'document_transaction_types',
            'deleted_total' => 'total',
        ];

        foreach ($archiveTables as $archiveTable => $sourceTable) {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS `$archiveTable` LIKE `$sourceTable`");

            foreach ([
                'deleted_at' => "ALTER TABLE `$archiveTable` ADD COLUMN `deleted_at` DATETIME NULL",
                'deleted_by' => "ALTER TABLE `$archiveTable` ADD COLUMN `deleted_by` INT NULL",
            ] as $column => $alterSql) {
                $columnExists = $this->pdo->query("SHOW COLUMNS FROM `$archiveTable` LIKE '{$column}'")->fetchColumn();
                if ($columnExists === false) {
                    $this->pdo->exec($alterSql);
                }
            }
        }
    }

    private function purgeExpiredArchiveRecords(): void
    {
        $archiveTables = [
            'deleted_users',
            'deleted_documents',
            'deleted_document_steps',
            'deleted_requirements',
            'deleted_document_transaction_types',
            'deleted_total',
        ];

        $cutoff = self::ARCHIVE_RETENTION_DAYS;

        foreach ($archiveTables as $table) {
            $this->pdo->exec(
                "DELETE FROM `$table` WHERE `deleted_at` IS NOT NULL AND `deleted_at` <= DATE_SUB(NOW(), INTERVAL $cutoff DAY)"
            );
        }
    }

    public function initConnection()
    {
        $this->dsn = "mysql:host=$this->host;dbname=$this->db;charset=$this->charset;port=$this->port";

        try {
            $this->pdo = new PDO($this->dsn, $this->user, $this->pass, $this->options);

            // ✅ Set PHP timezone (server-side)
            date_default_timezone_set('Asia/Manila');

            // ✅ Set MySQL timezone (database-side)
            $this->pdo->exec("SET time_zone = '+08:00'");

            // ✅ Ensure the referenced roles table exists for user.role_id foreign keys.
            $this->pdo->exec(
                "CREATE TABLE IF NOT EXISTS roles (
                    role_id INT NOT NULL,
                    role_name VARCHAR(50) NOT NULL,
                    PRIMARY KEY (role_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );

            $this->pdo->exec(
                "INSERT INTO roles (role_id, role_name) VALUES (0, 'User'), (1, 'Admin')
                 ON DUPLICATE KEY UPDATE role_name = VALUES(role_name)"
            );

            $this->ensurePasswordResetColumns();
            $this->ensureDeletedUsersTable();
            $this->ensureDeletedDocumentsTable();
            $this->purgeExpiredArchiveRecords();
            $this->ensureDocumentRequestSuffixColumn();

        } catch (\PDOException $e) {
            throw new \PDOException($e->getMessage(), (int) $e->getCode());
        }

        return $this->pdo;
    }
}

$connect = new Database();
$db = $connect->initConnection();