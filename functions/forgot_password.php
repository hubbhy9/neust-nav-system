<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../libraries/db.php';
require_once __DIR__ . '/mailer.php';

function generateOtp(): string
{
    return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function generateTemporaryPassword(int $length = 16): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%^&*';
    $password = '';
    $max = strlen($chars) - 1;

    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $max)];
    }

    return $password;
}

function getAccountByIdentifier(PDO $db, string $identifier, bool $gmailOnly = false): ?array
{
    $value = trim($identifier);
    if ($value === '') {
        return null;
    }

    if ($gmailOnly && (!filter_var($value, FILTER_VALIDATE_EMAIL) || !preg_match('/@gmail\.com$/i', $value))) {
        return null;
    }

    if ($gmailOnly) {
        $stmt = $db->prepare('SELECT * FROM `user` WHERE LOWER(email) = LOWER(?) LIMIT 1');
        $stmt->execute([$value]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    $stmt = $db->prepare('SELECT * FROM `user` WHERE LOWER(email) = LOWER(?) OR LOWER(student_number) = LOWER(?) LIMIT 1');
    $stmt->execute([$value, $value]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    return $user ?: null;
}

function getDateTimeNow(): DateTimeImmutable
{
    return new DateTimeImmutable('now', new DateTimeZone('Asia/Manila'));
}

function getLockRemainingMinutes(?string $lockedUntil): int
{
    if ($lockedUntil === null || $lockedUntil === '') {
        return 0;
    }

    $lockedAt = new DateTimeImmutable($lockedUntil, new DateTimeZone('Asia/Manila'));
    $now = getDateTimeNow();
    $diff = $lockedAt->getTimestamp() - $now->getTimestamp();

    if ($diff <= 0) {
        return 0;
    }

    return max(1, (int) ceil($diff / 60));
}

function jsonResponse(bool $success, string $message): void
{
    http_response_code($success ? 200 : 400);
    echo json_encode([
        'success' => $success,
        'message' => $message,
    ]);
    exit;
}

$action = trim((string) ($_POST['action'] ?? ''));
$identifier = trim((string) ($_POST['identifier'] ?? ''));
$otp = trim((string) ($_POST['otp'] ?? ''));
$gmailOnly = (string) ($_POST['gmail_only'] ?? '') === '1';

if ($action === '') {
    jsonResponse(false, 'Invalid password reset request.');
}

if ($action !== 'request_otp' && $action !== 'verify_otp') {
    jsonResponse(false, 'Invalid password reset action.');
}

if ($identifier === '') {
    jsonResponse(false, $gmailOnly ? 'Enter a valid Gmail address.' : 'Please enter your email address or student number.');
}

if ($gmailOnly && (!filter_var($identifier, FILTER_VALIDATE_EMAIL) || !preg_match('/@gmail\.com$/i', $identifier))) {
    jsonResponse(false, 'Enter a valid Gmail address ending in @gmail.com.');
}

$user = getAccountByIdentifier($db, $identifier, $gmailOnly);
if (!$user) {
    jsonResponse(false, $gmailOnly ? 'No account was found with this Gmail address. Use the Gmail address registered to your account.' : 'We could not find an account for that email address or student number.');
}

$now = getDateTimeNow();
$lockedUntil = $user['password_reset_locked_until'] ?? null;
if (!empty($lockedUntil) && new DateTimeImmutable($lockedUntil, new DateTimeZone('Asia/Manila')) > $now) {
    $remainingMinutes = getLockRemainingMinutes($lockedUntil);
    jsonResponse(false, 'This account is temporarily locked after multiple failed OTP attempts. Please try again in ' . $remainingMinutes . ' minute(s).');
}

if ($action === 'request_otp') {
    $otpCode = generateOtp();
    $expiresAt = $now->modify('+15 minutes')->format('Y-m-d H:i:s');

    $stmt = $db->prepare('UPDATE `user` SET password_reset_otp = ?, password_reset_expires_at = ?, password_reset_attempts = 0, password_reset_locked_until = NULL, password_reset_requested_at = NOW() WHERE id = ?');
    $stmt->execute([$otpCode, $expiresAt, (int) $user['id']]);

    $firstName = trim((string) ($user['first_name'] ?? ''));
    $email = trim((string) ($user['email'] ?? ''));

    if (!sendPasswordResetOtpEmail($email, $firstName, $otpCode, 15)) {
        jsonResponse(false, 'We could not send the OTP email right now. Please try again later.');
    }

    jsonResponse(true, 'A 6-digit OTP has been sent to your email. It is valid for 15 minutes.');
}

if ($action === 'verify_otp') {
    if ($otp === '') {
        jsonResponse(false, 'Please enter the OTP sent to your email.');
    }

    $storedOtp = (string) ($user['password_reset_otp'] ?? '');
    $expiresAt = $user['password_reset_expires_at'] ?? null;
    $attempts = (int) ($user['password_reset_attempts'] ?? 0);

    if ($storedOtp === '' || $expiresAt === null || $expiresAt === '') {
        jsonResponse(false, 'No active OTP was found for this account. Please request a new one.');
    }

    $expiresDateTime = new DateTimeImmutable($expiresAt, new DateTimeZone('Asia/Manila'));
    if ($expiresDateTime <= $now) {
        $db->prepare('UPDATE `user` SET password_reset_otp = NULL, password_reset_expires_at = NULL, password_reset_attempts = 0, password_reset_locked_until = NULL WHERE id = ?')->execute([(int) $user['id']]);
        jsonResponse(false, 'This OTP has expired. Please request a new one.');
    }

    if (!hash_equals($storedOtp, $otp)) {
        $attempts++;
        if ($attempts >= 3) {
            $lockUntil = $now->modify('+30 minutes')->format('Y-m-d H:i:s');
            $db->prepare('UPDATE `user` SET password_reset_otp = NULL, password_reset_expires_at = NULL, password_reset_attempts = 3, password_reset_locked_until = ? WHERE id = ?')->execute([$lockUntil, (int) $user['id']]);
            jsonResponse(false, 'Too many incorrect OTP attempts. Password reset has been locked for 30 minutes.');
        }

        $db->prepare('UPDATE `user` SET password_reset_attempts = ? WHERE id = ?')->execute([$attempts, (int) $user['id']]);
        jsonResponse(false, 'Incorrect OTP. You have ' . (3 - $attempts) . ' attempt(s) remaining.');
    }

    $newPassword = generateTemporaryPassword(16);
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $db->prepare('UPDATE `user` SET password = ?, password_reset_otp = NULL, password_reset_expires_at = NULL, password_reset_attempts = 0, password_reset_locked_until = NULL WHERE id = ?')->execute([$hash, (int) $user['id']]);

    $firstName = trim((string) ($user['first_name'] ?? ''));
    $email = trim((string) ($user['email'] ?? ''));

    if (!sendNewPermanentPasswordEmail($email, $firstName, $newPassword)) {
        jsonResponse(false, 'OTP is correct, but the new password could not be emailed. Please contact support.');
    }

    jsonResponse(true, 'OTP verified. Your new permanent password has been sent to your email.');
}