<?php
include __DIR__ . '/../libraries/include.php';

class add
{
    public string $first_name;
    public string $middle_name;
    public string $last_name;
    public string $suffix;
    public string $email;
    public string $pass_word;
    public ?string $office_id = null;
    public string $created_at;

    private $con;

    public function __construct($db)
    {
        $this->con = $db;
    }

    // ✅ CHECK EMAIL EXISTS
    public function emailExists($email): bool
    {
        $stmt = $this->con->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        return $stmt->rowCount() > 0;
    }

    public function getPost(): void
    {
        $this->first_name = $_POST['first_name'] ?? '';
        $this->middle_name = $_POST['middle_name'] ?? '';
        $this->last_name = $_POST['last_name'] ?? '';
        $this->suffix = $_POST['suffix'] ?? '';
        $this->email = $_POST['email'] ?? '';
        $this->pass_word = password_hash($_POST['pass_word'], PASSWORD_DEFAULT);
        $this->office_id = $_POST['office_id'] ?? null;
        $this->created_at = date('Y-m-d H:i:s');
    }

    // ✅ INSERT USER
    public function Add(): bool
    {
        $this->getPost();

        $stmt = $this->con->prepare("
            INSERT INTO users 
            (first_name, middle_name, last_name, suffix, email, pass_word, office_id, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $result = $stmt->execute([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->suffix,
            $this->email,
            $this->pass_word,
            $this->office_id,
            $this->created_at
        ]);

        return $result;
    }
}