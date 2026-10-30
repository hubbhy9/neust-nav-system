<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once __DIR__ . '/../libraries/db.php';
require_once __DIR__ . '/audit.php';

class edit
{

    public string $registration_number = '';
    public string $student_number = '';
    public string $first_name = '';
    public string $middle_name = '';
    public string $last_name = '';
    public string $suffix = '';
    public string $barangay = '';
    public string $city = '';
    public string $province = '';
    public string $birthday = '';
    public string $sex = '';
    public string $mobile_number = '';
    public string $email = '';
    public string $role_id = '';
    private $con;

    public function __construct($db)
    {
        $this->con = $db;
    }

    private function normalizeNameValue(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/\d+/u', '', $value);
        $value = preg_replace('/\s+/', ' ', $value);
        $segments = preg_split('/\s+/', $value, -1, PREG_SPLIT_NO_EMPTY);
        if ($segments === false || count($segments) === 0) {
            return '';
        }

        return implode(' ', array_map(function ($segment) {
            return mb_convert_case($segment, MB_CASE_TITLE, 'UTF-8');
        }, $segments));
    }

    public function getPost(): void
    {
        if (!empty($_POST)) {
            $this->registration_number = $_POST['registration_number'] ?? '';
            $this->student_number      = $_POST['student_number'] ?? '';
            $this->first_name          = $this->normalizeNameValue($_POST['first_name'] ?? '');
            $this->middle_name         = $this->normalizeNameValue($_POST['middle_name'] ?? '');
            $this->last_name           = $this->normalizeNameValue($_POST['last_name'] ?? '');
            $this->suffix              = $this->normalizeNameValue($_POST['suffix'] ?? '');
            $this->barangay            = $this->normalizeNameValue($_POST['barangay'] ?? '');
            $this->city                = $this->normalizeNameValue($_POST['city'] ?? '');
            $this->province            = $this->normalizeNameValue($_POST['province'] ?? '');
            $this->birthday            = $_POST['birthday'] ?? '';
            $this->sex                 = $_POST['sex'] ?? '';
            $this->mobile_number       = $_POST['mobile_number'] ?? '';
            $this->email               = $_POST['email'] ?? '';
            $this->role_id             = $_POST['role_id'] ?? '';
        }
    }

    public function update($id) {

        $this->getPost();

        $id = (int) $id;
        $isAdmin = !empty($_SESSION['id']) && (int) ($_SESSION['role_id'] ?? 0) === 1 && empty($_SESSION['is_guest']);
        if (!$isAdmin && (int) ($_SESSION['id'] ?? 0) !== $id) {
            auditLogSafely($this->con, [
                'action' => 'ACCESS_DENIED', 'module' => 'Users', 'target_type' => 'user', 'target_id' => $id,
                'description' => 'Denied attempt to edit another user account.', 'status' => 'denied',
            ]);
            http_response_code(403);
            exit('You may only edit your own account.');
        }

        $oldStmt = $this->con->prepare('SELECT id, registration_number, student_number, first_name, middle_name, last_name, suffix, barangay, city, province, birthday, sex, mobile_number, email, role_id FROM user WHERE id = ? LIMIT 1');
        $oldStmt->execute([$id]);
        $oldUser = $oldStmt->fetch(PDO::FETCH_ASSOC);
        if (!$oldUser) return false;
        if (!$isAdmin) $this->role_id = (string) $oldUser['role_id'];

        $newUser = [
            'registration_number' => $this->registration_number,
            'student_number' => $this->student_number,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'suffix' => $this->suffix,
            'barangay' => $this->barangay,
            'city' => $this->city,
            'province' => $this->province,
            'birthday' => $this->birthday,
            'sex' => $this->sex,
            'mobile_number' => $this->mobile_number,
            'email' => $this->email,
            'role_id' => (int) $this->role_id,
        ];

        $this->con->beginTransaction();
        try {
        
        $sql = 'UPDATE user SET 
            registration_number = ?,
            student_number = ?,
            first_name = ?, 
            middle_name = ?, 
            last_name = ?, 
            suffix = ?,
            barangay = ?,
            city = ?,
            province = ?,
            birthday = ?,
            sex = ?,
            mobile_number = ?,
            email = ?, 
            role_id = ? 
            WHERE id = ?';

        $stmt = $this->con->prepare($sql);
        $stmt->execute([
            $this->registration_number,
            $this->student_number,
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->suffix,
            $this->barangay,
            $this->city,
            $this->province,
            $this->birthday,
            $this->sex,
            $this->mobile_number,
            $this->email,
            $this->role_id,
            $id
        ]);
        auditLog($this->con, [
            'action' => 'UPDATE', 'module' => 'Users', 'target_type' => 'user', 'target_id' => $id,
            'target_name' => trim($this->first_name . ' ' . $this->last_name),
            'description' => 'Updated user profile fields.',
            'old_values' => $oldUser,
            'new_values' => $newUser,
            'status' => 'success',
        ]);
        $this->con->commit();
        } catch (Throwable $error) {
            if ($this->con->inTransaction()) $this->con->rollBack();
            throw $error;
        }
    }
}

if (isset($_POST['update'])) {
    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

    if ($id > 0) {
        $editor = new edit($db);
        $editor->update($id);
        $_SESSION['flash_message'] = 'User updated successfully.';
        $_SESSION['flash_type'] = 'success';
        header('Location: ../../dashboard.php');
        exit;
    }
} else {
    header("Location: ../../dashboard.php");
    exit;
}
?>