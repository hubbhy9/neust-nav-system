<?php
require_once __DIR__ . '/../vendor/autoload.php';

function loadMailEnvironment(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }

    $loaded = true;

    $envFile = __DIR__ . '/../.env';
    if (!is_readable($envFile)) {
        return;
    }

    $mailEnvironment = parse_ini_file($envFile, false, INI_SCANNER_RAW);
    if (!is_array($mailEnvironment)) {
        return;
    }

    foreach ($mailEnvironment as $environmentKey => $environmentValue) {
        if (!is_string($environmentValue)) {
            continue;
        }

        if (getenv($environmentKey) === false || getenv($environmentKey) === '') {
            putenv($environmentKey . '=' . $environmentValue);
            $_ENV[$environmentKey] = $environmentValue;
        }
    }
}

function getMailConfigValue(string $key, string $default = ''): string
{
    loadMailEnvironment();

    $value = getenv($key);
    if ($value !== false && $value !== null) {
        return (string) $value;
    }

    if (isset($_ENV[$key])) {
        return (string) $_ENV[$key];
    }

    return $default;
}

function normalizeMailCredential(string $value): string
{
    $cleaned = trim((string) $value);
    if ($cleaned === '') {
        return '';
    }

    $cleaned = preg_replace('/\s+/', '', $cleaned);
    return $cleaned !== null ? $cleaned : '';
}

function sendSystemEmail(string $to, string $subject, string $body, ?string $fromName = null, ?string $altBody = null): bool
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log('sendSystemEmail: invalid email address: ' . $to);
        return false;
    }

    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        error_log('sendSystemEmail: PHPMailer autoload failed. Composer autoloader is missing or not loaded.');
        return false;
    }

    $host = trim(getMailConfigValue('MAIL_HOST'));
    $username = normalizeMailCredential(getMailConfigValue('MAIL_USERNAME'));
    $password = normalizeMailCredential(getMailConfigValue('MAIL_PASSWORD'));
    $from = normalizeMailCredential(getMailConfigValue('MAIL_FROM', $username));
    $port = (int) getMailConfigValue('MAIL_PORT', '587');

    if ($host === '' || $username === '' || $from === '') {
        error_log('sendSystemEmail: SMTP settings are incomplete.');
        return false;
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $port;
        $mail->Timeout = 8;
        $mail->Timelimit = 15;
        $mail->SMTPOptions = [
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true],
        ];
        $mail->CharSet = 'UTF-8';
        $mail->setFrom($from, $fromName ?: 'NEUST Smart Campus');
        $mail->addAddress($to);
        $mail->Subject = $subject;
        if (preg_match('/<(?:!doctype|html|body|div|p|table|a|ul|ol)\b/i', $body) === 1) {
            $mail->isHTML(true);
        }
        $mail->Body = $body;
        $mail->AltBody = $altBody ?? strip_tags($body);

        return $mail->send();
    } catch (\PHPMailer\PHPMailer\Exception $e) {
        error_log('SMTP send failed: ' . $e->getMessage());
        return false;
    }
}

function sendAdminRoleEmail(string $toEmail, string $fullName): bool
{
    $to = trim($toEmail);
    if ($to === '') {
        return false;
    }

    $safeName = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
    $subject = 'You have been promoted to Admin';
    $htmlMessage = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Promotion</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f6fb; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    <div style="max-width:640px; margin:32px auto; background:#ffffff; border:1px solid #e5e7eb; border-radius:16px; overflow:hidden; box-shadow:0 10px 24px rgba(15, 23, 42, 0.06);">
        <div style="background:linear-gradient(135deg, #14369C, #0B246F); padding:28px 32px; color:#ffffff;">
            <div style="font-size:12px; letter-spacing:1.5px; text-transform:uppercase; opacity:0.9;">NEUST Smart Campus</div>
            <h2 style="margin:10px 0 0; font-size:28px; line-height:1.2;">Administrative Access Granted</h2>
        </div>
        <div style="padding:32px;">
            <p style="margin:0 0 16px; font-size:16px; line-height:1.6; color:#374151;">
                Hello <strong>{$safeName}</strong>,
            </p>
            <p style="margin:0 0 20px; font-size:16px; line-height:1.6; color:#374151;">
                Your account has been promoted to <strong>Admin</strong> in the NEUST Smart Campus system.
            </p>
            <p style="margin:0; font-size:15px; line-height:1.7; color:#374151;">
                You now have access to administrative features and dashboard controls. Please continue to use your account responsibly.
            </p>
        </div>
        <div style="padding:18px 32px 28px; background:#f8fafc; border-top:1px solid #e5e7eb; font-size:12px; color:#64748b; text-align:center;">
            NEUST Smart Campus • Campus Information and Navigation System
        </div>
    </div>
</body>
</html>
HTML;

    return sendSystemEmail($to, $subject, $htmlMessage, 'NEUST Smart Campus');
}

function documentRequestAccessExpiresAt(): string
{
    return (new DateTimeImmutable('now'))->modify('+3 hours')->format('Y-m-d H:i:s');
}

function documentRequestAccessOrigin(string $preferredOrigin = '', bool $preferProvidedOrigin = false): string
{
    $candidate = $preferProvidedOrigin ? trim($preferredOrigin) : '';
    if ($candidate === '') $candidate = trim(getMailConfigValue('APP_PUBLIC_URL'));
    if ($candidate === '') {
        $projectRoot = dirname(__DIR__);
        $urlFiles = [
            $projectRoot . '/ionic-map-app/.public-app-url',
            dirname($projectRoot) . '/checking/ionic-map-app/.public-app-url',
        ];
        foreach ($urlFiles as $urlFile) {
            if (is_readable($urlFile)) {
                $candidate = trim((string) file_get_contents($urlFile));
                if ($candidate !== '') break;
            }
        }
    }
    if ($candidate === '') {
        $candidate = trim($preferredOrigin);
    }
    if ($candidate === '') {
        $candidate = trim((string) ($_SERVER['HTTP_ORIGIN'] ?? ''));
    }
    if ($candidate === '') {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $candidate = $scheme . '://' . (string) ($_SERVER['HTTP_HOST'] ?? '');
    }

    $parts = parse_url($candidate);
    if (!is_array($parts) || !in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true) || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
        return '';
    }
    $host = strtolower((string) $parts['host']);
    if (in_array($host, ['localhost', '127.0.0.1', '::1', '0.0.0.0'], true)) return '';
    $origin = strtolower((string) $parts['scheme']) . '://' . $host;
    if (isset($parts['port'])) $origin .= ':' . (int) $parts['port'];
    return $origin;
}

function sendDocumentRequestEmail(string $to, string $requesterName, int $requestId, string $status, array $documentTitles, string $accessUrl, string $expiresAt): bool
{
        $statusDetails = [
                'pending' => ['subject' => 'Request received', 'heading' => 'Request received', 'message' => 'Your request has been received and is currently under review.'],
                'accepted' => ['subject' => 'Request approved', 'heading' => 'Request approved', 'message' => 'Your document request has been approved. The requested document information is now available through your private access link.'],
                'rejected' => ['subject' => 'Request update', 'heading' => 'Request update', 'message' => 'We are unable to approve your document request at this time. The request status and details are available through your private access link.'],
        ];
        if (!isset($statusDetails[$status])) return false;

        $safeName = htmlspecialchars($requesterName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeUrl = htmlspecialchars($accessUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safeExpiry = htmlspecialchars($expiresAt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $documentItems = array_map(
                static fn ($title): string => '<li style="padding:8px 0;color:#334155;line-height:1.5;">' . htmlspecialchars(trim((string) $title), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</li>',
                $documentTitles
        );
        $documentList = $documentItems !== [] ? implode('', $documentItems) : '<li style="padding:8px 0;color:#64748b;">No document titles are available.</li>';
        $statusMessage = $statusDetails[$status]['message'];
        $heading = $statusDetails[$status]['heading'];
        $htmlBody = <<<HTML
<!doctype html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>{$heading} | NEUST Smart Campus</title></head>
<body style="margin:0;padding:24px 12px;background:#f3f6fb;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:620px;margin:0 auto;background:#ffffff;border:1px solid #dfe5ef;border-radius:10px;">
        <tr><td style="padding:22px 26px;background:#14369c;color:#ffffff;border-radius:9px 9px 0 0;">
            <p style="margin:0 0 8px;font-size:12px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;color:#dbe6ff;">NEUST Smart Campus</p>
            <h1 style="margin:0;font-size:23px;line-height:1.3;">{$heading}</h1>
        </td></tr>
        <tr><td style="padding:25px 26px 10px;">
            <p style="margin:0 0 14px;font-size:15px;line-height:1.6;">Dear <strong>{$safeName}</strong>,</p>
            <p style="margin:0 0 20px;font-size:15px;line-height:1.65;color:#374151;">{$statusMessage}</p>
            <p style="margin:0 0 7px;font-size:12px;font-weight:bold;color:#64748b;text-transform:uppercase;">Request #{$requestId} · Requested documents</p>
            <ul style="margin:0 0 22px;padding:12px 18px 12px 34px;background:#f8fafc;border:1px solid #e5eaf2;border-radius:7px;">{$documentList}</ul>
            <p style="margin:0 0 18px;text-align:center;"><a href="{$safeUrl}" style="display:inline-block;padding:12px 20px;border-radius:6px;background:#14369c;color:#ffffff;font-size:14px;font-weight:bold;text-decoration:none;">View your request</a></p>
            <p style="margin:0 0 22px;font-size:13px;line-height:1.6;color:#64748b;">This private link provides access only to the documents listed in this request and expires on <strong>{$safeExpiry}</strong>.</p>
        </td></tr>
        <tr><td style="padding:15px 26px;background:#f8fafc;border-top:1px solid #e5eaf2;border-radius:0 0 9px 9px;font-size:12px;line-height:1.5;color:#64748b;">This is an automated message from NEUST Smart Campus. Please do not reply directly to this email.</td></tr>
    </table>
</body>
</html>
HTML;
        $plainDocuments = implode("\n", array_map(static fn ($title): string => '- ' . trim((string) $title), $documentTitles));
        $plainBody = "Dear {$requesterName},\n\n"
                . $statusMessage . "\n\n"
                . "Request #{$requestId}\nRequested documents:\n{$plainDocuments}\n\n"
                . "View your request: {$accessUrl}\n"
                . "This private link expires on {$expiresAt} and only provides access to the documents in this request.\n\n"
                . "NEUST Smart Campus\nThis is an automated message. Please do not reply directly.";

        return sendSystemEmail($to, "NEUST Smart Campus | Document request #{$requestId}: {$statusDetails[$status]['subject']}", $htmlBody, 'NEUST Smart Campus', $plainBody);
}

function sendAccountCreatedEmail(string $toEmail, string $firstName, string $password, string $roleLabel): bool
{
    $to = trim($toEmail);
    if ($to === '') {
        return false;
    }

    $safeFirstName = htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8');
    $safeRoleLabel = htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8');
    $safePassword = htmlspecialchars($password, ENT_QUOTES, 'UTF-8');

    $subject = 'Welcome to NEUST Smart Campus';
    $htmlMessage = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Created</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f6fb; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    <div style="max-width:640px; margin:32px auto; background:#ffffff; border:1px solid #e5e7eb; border-radius:16px; overflow:hidden; box-shadow:0 10px 24px rgba(15, 23, 42, 0.06);">
        <div style="background:linear-gradient(135deg, #14369C, #0B246F); padding:28px 32px; color:#ffffff;">
            <div style="font-size:12px; letter-spacing:1.5px; text-transform:uppercase; opacity:0.9;">NEUST Smart Campus</div>
            <h2 style="margin:10px 0 0; font-size:28px; line-height:1.2;">Account Created</h2>
        </div>
        <div style="padding:32px;">
            <p style="margin:0 0 16px; font-size:16px; line-height:1.6; color:#374151;">
                Hello <strong>{$safeFirstName}</strong>,
            </p>
            <p style="margin:0 0 20px; font-size:16px; line-height:1.6; color:#374151;">
                Your account has been created successfully for the <strong>NEUST Smart Campus</strong> system.
            </p>

            <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="width:100%; border-collapse:collapse; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden;">
                <tr>
                    <td style="padding:16px 18px; border-bottom:1px solid #e2e8f0; font-size:14px; color:#475569; width:40%;"><strong>Role</strong></td>
                    <td style="padding:16px 18px; border-bottom:1px solid #e2e8f0; font-size:14px; color:#0f172a;"><span style="display:inline-block; padding:6px 10px; border-radius:999px; background:#dbeafe; color:#1d4ed8; font-weight:bold;">{$safeRoleLabel}</span></td>
                </tr>
                <tr>
                    <td style="padding:16px 18px; font-size:14px; color:#475569;"><strong>Password</strong></td>
                    <td style="padding:16px 18px; font-size:14px; color:#0f172a; font-weight:bold; letter-spacing:0.5px;">{$safePassword}</td>
                </tr>
            </table>

            <p style="margin:24px 0 0; font-size:15px; line-height:1.7; color:#374151;">
                Please log in and change your password after signing in.
            </p>
        </div>
        <div style="padding:18px 32px 28px; background:#f8fafc; border-top:1px solid #e5e7eb; font-size:12px; color:#64748b; text-align:center;">
            NEUST Smart Campus • Campus Information and Navigation System
        </div>
    </div>
</body>
</html>
HTML;

    return sendSystemEmail($to, $subject, $htmlMessage, 'NEUST Smart Campus');
}

function sendPasswordResetOtpEmail(string $toEmail, string $firstName, string $otp, int $expiresInMinutes): bool
{
    $to = trim($toEmail);
    if ($to === '') {
        return false;
    }

    $safeFirstName = htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8');
    $safeOtp = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');
    $safeMinutes = (int) $expiresInMinutes;
    $subject = 'Your Password Reset OTP';
    $htmlMessage = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Password Reset OTP</title></head>
<body style="margin:0;padding:24px 12px;background:#f3f6fb;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:620px;margin:0 auto;background:#fff;border:1px solid #dfe5ef;border-radius:10px;">
        <tr><td style="padding:22px 26px;background:#14369c;color:#fff;border-radius:9px 9px 0 0;"><p style="margin:0 0 8px;font-size:12px;font-weight:bold;text-transform:uppercase;">NEUST Smart Campus</p><h1 style="margin:0;font-size:23px;">Password reset</h1></td></tr>
        <tr><td style="padding:25px 26px;"><p>Hello <strong>{$safeFirstName}</strong>,</p><p>Your one-time password is:</p><p style="margin:20px 0;text-align:center;background:#f8fafc;border:1px solid #dbeafe;border-radius:8px;padding:20px;font-size:32px;letter-spacing:8px;font-weight:bold;">{$safeOtp}</p><p>This code expires in <strong>{$safeMinutes} minutes</strong>. Ignore this message if you did not request a reset.</p></td></tr>
        <tr><td style="padding:15px 26px;background:#f8fafc;border-top:1px solid #e5eaf2;font-size:12px;color:#64748b;">NEUST Smart Campus</td></tr>
    </table>
</body>
</html>
HTML;

    return sendSystemEmail($to, $subject, $htmlMessage, 'NEUST Smart Campus');
}

function sendNewPermanentPasswordEmail(string $toEmail, string $firstName, string $newPassword): bool
{
    $to = trim($toEmail);
    if ($to === '') {
        return false;
    }

    $safeFirstName = htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8');
    $safePassword = htmlspecialchars($newPassword, ENT_QUOTES, 'UTF-8');
    $subject = 'Your New Password';
    $htmlMessage = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>New Password</title></head>
<body style="margin:0;padding:24px 12px;background:#f3f6fb;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:620px;margin:0 auto;background:#fff;border:1px solid #dfe5ef;border-radius:10px;">
        <tr><td style="padding:22px 26px;background:#14369c;color:#fff;border-radius:9px 9px 0 0;"><p style="margin:0 0 8px;font-size:12px;font-weight:bold;text-transform:uppercase;">NEUST Smart Campus</p><h1 style="margin:0;font-size:23px;">Password reset complete</h1></td></tr>
        <tr><td style="padding:25px 26px;"><p>Hello <strong>{$safeFirstName}</strong>,</p><p>Your new password is:</p><p style="margin:20px 0;text-align:center;background:#f8fafc;border:1px solid #dbeafe;border-radius:8px;padding:20px;font-size:22px;letter-spacing:1px;font-weight:bold;">{$safePassword}</p><p>Sign in with this password.</p></td></tr>
        <tr><td style="padding:15px 26px;background:#f8fafc;border-top:1px solid #e5eaf2;font-size:12px;color:#64748b;">NEUST Smart Campus</td></tr>
    </table>
</body>
</html>
HTML;

    return sendSystemEmail($to, $subject, $htmlMessage, 'NEUST Smart Campus');
}
