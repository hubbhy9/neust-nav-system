<?php
include __DIR__ . '/../libraries/include.php';
require_once __DIR__ . '/audit.php';

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

if (!function_exists('normalizeEmailValue')) {
    function normalizeEmailValue(string $value): string
    {
        return strtolower(trim($value));
    }
}

class add
{
    public string $first_name;
    public string $middle_name;
    public string $last_name;
    public string $suffix;
    public string $email;
    public string $password;
    public string $created_at;

    private $con;

    public function __construct($db)
    {
        $this->con = $db;
    }

    // ✅ CHECK EMAIL EXISTS
    public function emailExists($email): bool
    {
        $stmt = $this->con->prepare("SELECT id FROM user WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return true;
        }

        $archiveStmt = $this->con->prepare("SELECT id FROM deleted_users WHERE email = ? LIMIT 1");
        $archiveStmt->execute([$email]);

        return $archiveStmt->fetch() !== false;
    }

    public function getPost(): void
    {
        $this->first_name = normalizeNameValue($_POST['first_name'] ?? '');
        $this->middle_name = normalizeNameValue($_POST['middle_name'] ?? '');
        $this->last_name = normalizeNameValue($_POST['last_name'] ?? '');
        $this->suffix = normalizeNameValue($_POST['suffix'] ?? '');
        $this->email = normalizeEmailValue($_POST['email'] ?? '');
        $this->password = password_hash($_POST['pass_word'] ?? '', PASSWORD_DEFAULT);
        $this->created_at = date('Y-m-d H:i:s');
    }

    // ✅ INSERT USER
    public function Add(): bool
    {
        $this->getPost();

        $stmt = $this->con->prepare("
            INSERT INTO user 
            (first_name, middle_name, last_name, suffix, email, password, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $this->con->beginTransaction();
        try {
        $result = $stmt->execute([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->suffix,
            $this->email,
            $this->password,
            $this->created_at
        ]);

        if (!$result) {
            $this->con->rollBack();
            return false;
        }
        auditLog($this->con, [
            'action' => 'CREATE', 'module' => 'Users', 'target_type' => 'user',
            'target_id' => (int) $this->con->lastInsertId(), 'target_name' => trim($this->first_name . ' ' . $this->last_name),
            'description' => 'User self-registered an account.',
            'new_values' => ['email' => $this->email, 'role_id' => 0],
            'status' => 'success',
        ]);
        $this->con->commit();

        return $result;
        } catch (Throwable $error) {
            if ($this->con->inTransaction()) $this->con->rollBack();
            throw $error;
        }
    }
}