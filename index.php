<?php
$sessionSavePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'navigation_sessions';
if (!is_dir($sessionSavePath)) {
    @mkdir($sessionSavePath, 0777, true);
}
if (!is_dir($sessionSavePath) || !is_writable($sessionSavePath)) {
    $sessionSavePath = sys_get_temp_dir();
}

if (session_status() === PHP_SESSION_NONE) {
    session_save_path($sessionSavePath);
    session_start();
}

if (empty($_SESSION['guest_login_csrf'])) {
    $_SESSION['guest_login_csrf'] = bin2hex(random_bytes(32));
}

include __DIR__ . '/libraries/include.php';
include __DIR__ . '/functions/login_auth.php';

$privacyConsentAccepted = isset($_SESSION['privacy_accepted']) && $_SESSION['privacy_accepted'] == 1;
$guestLoginError = (string) ($_SESSION['guest_login_error'] ?? '');
$accountDeleted = isset($_GET['account_deleted']) && $_GET['account_deleted'] === '1';
unset($_SESSION['guest_login_error']);
if (isset($_COOKIE['neustPrivacyConsent']) && $_COOKIE['neustPrivacyConsent'] === '1') {
    $_SESSION['privacy_accepted'] = 1;
    $privacyConsentAccepted = true;
}

// Redirect if already logged in
if (isset($_SESSION['id'])) {
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | NEUST Student Navigation &amp; Wayfinding System</title>
    <link rel="icon" href="img/neust_logo.png">
    <style>
        :root {
            --blue-900:#0A235C; --blue-800:#123D9A; --blue-700:#1A52D7; --blue-600:#2567F5;
            --gold-500:#FFCE00; --gold-300:#FFE271;
            --panel-light:#F5F7FB;
            --card-shadow: 0 30px 80px -30px rgba(11,42,107,0.48);
        }
        body {
            position: relative;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            margin: 0;
            background:
                radial-gradient(circle at top left, rgba(22,73,199,0.18), transparent 24%),
                radial-gradient(circle at 90% 16%, rgba(255,206,0,0.22), transparent 16%),
                linear-gradient(180deg, #eaf2ff 0%, #f5f9ff 38%, #edf3fa 100%);
            background-attachment: fixed;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 8% 10% 8% 10%;
            background-image: url("img/neust_logo.png");
            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;
            opacity: 0.12;
            pointer-events: none;
            z-index: 0;
        }
        .login-shell {
            display: flex;
            min-height: 100vh;
        }
        .login-brand {
            flex: 0.62;
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(ellipse 620px 420px at 18% 10%, rgba(255,206,0,.08), transparent 56%),
                linear-gradient(150deg, #1849C2 0%, #153D9B 42%, #0E2D74 100%);
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 52px 28px 44px;
            box-shadow: inset -8px 0 20px rgba(4,13,35,0.08);
        }
        .login-brand .gear-ring {
            position: absolute;
            width: 460px; height: 460px;
            right: -150px; bottom: -150px;
            border-radius: 50%;
            border: 2px dashed rgba(255,206,0,.30);
        }
        .login-brand .gear-ring.r2 {
            width: 340px; height: 340px; right: -90px; bottom: -90px;
            border-color: rgba(248,147,24,.25);
        }
        .brand-top { display: flex; align-items: center; gap: 16px; position: relative; z-index: 1; }
        .brand-top img { width: 60px; height: 60px; }
        .brand-top .name { font-weight: 800; font-size: 15px; line-height: 1.4; }
        .brand-top .name small {
            display: block; font-weight: 500; font-size: 11px; color: var(--gold-300);
            letter-spacing: .12em; margin-top: 4px;
        }
        .login-headline {
            font-weight: 800; font-size: 32px; line-height: 1.25; margin-top: 50px;
            max-width: 420px; position: relative; z-index: 1;
        }
        .login-headline span { color: var(--gold-500); }
        .login-copy {
            margin-top: 16px; color: #B9C2E6; font-size: 14px; line-height: 1.65;
            max-width: 380px; position: relative; z-index: 1;
        }
        .login-tags { display: flex; gap: 10px; margin-top: 32px; position: relative; z-index: 1; flex-wrap: wrap; }
        .login-tags span {
            border: 1px solid rgba(255,206,0,.4); color: var(--gold-300); font-size: 11.5px;
            padding: 6px 13px; border-radius: 999px; font-weight: 600;
        }
        .login-form-side {
            flex: 1.38; display: flex; align-items: center; justify-content: center;
            background: linear-gradient(180deg, #f1f4fb 0%, #f7f9fc 100%); padding: 42px 28px;
        }
        .login-form-side form {
            width: min(740px, 100%);
        }
        .login-card {
            width: 100%; min-height: 300px; background: linear-gradient(180deg, #ffffff 0%, #fdfdff 100%);
            border-radius: 20px; padding: 30px 34px; border: 1px solid rgba(17, 51, 124, 0.15);
            border-top: 6px solid var(--gold-500); box-shadow: var(--card-shadow);
            display: flex; flex-direction: column; justify-content: center; transform: translateY(-4px);
        }
        .login-card h2 { font-size: 21px; margin-bottom: 4px; font-weight: 800; color: #0d1631; }
        .login-card p.sub { color: #5b647d; font-size: 13px; margin-bottom: 24px; }
        .login-card .form-control {
            height: 52px; border-radius: 10px; border: 1px solid #D2DCEF; font-size: 15px;
            padding: 12px 14px; margin-top: 8px; background: #fff; transition: all 0.2s ease;
            box-shadow: inset 0 1px 2px rgba(15, 35, 76, 0.02);
        }
        .login-card .form-control:focus {
            border-color: rgba(25, 82, 219, 0.9);
            box-shadow: 0 0 0 3px rgba(25, 82, 219, 0.11);
        }
        .login-card .form-label {
            margin-bottom: 0; font-size: 13px; color: #2F3748;
        }
        .login-card .btn {
            height: 52px; border-radius: 10px; font-weight: 700; font-size: 16px;
            background: linear-gradient(180deg, var(--blue-700), var(--blue-600));
            border: none; box-shadow: 0 16px 24px -18px rgba(17, 57, 170, 1);
        }
        .login-card .btn:hover {
            background: linear-gradient(180deg, var(--blue-600), var(--blue-700));
            box-shadow: 0 18px 26px -18px rgba(17, 57, 170, 1);
        }
        .login-card .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .login-card .btn-guest {
            color: #183b87;
            background: #fff;
            border: 1px solid #b8c7e4;
            box-shadow: none;
        }
        .login-card .btn-guest:hover:not(:disabled) {
            color: #102a67;
            background: #f3f6fc;
            border-color: #7893c5;
            box-shadow: none;
        }
        .guest-divider {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 15px 0 12px;
            color: #7a8499;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .guest-divider::before,
        .guest-divider::after {
            content: "";
            height: 1px;
            flex: 1;
            background: #e1e7f0;
        }

        .privacy-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(9, 18, 38, 0.68);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 2000;
        }
        .privacy-modal {
            width: min(700px, 100%);
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 30px 60px rgba(10, 22, 52, 0.24);
            border: 1px solid rgba(16, 41, 93, 0.08);
            overflow: hidden;
        }
        .privacy-modal-header {
            background: linear-gradient(135deg, #0f3d9b 0%, #123d9a 100%);
            color: #fff;
            padding: 22px 26px;
        }
        .privacy-modal-header h3 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
        }
        .privacy-modal-body {
            padding: 22px 24px 10px;
            color: #2d3a52;
        }
        .privacy-modal-body h4 {
            margin: 0 0 12px;
            font-size: 18px;
            color: #102a5b;
        }
        .privacy-modal-body p, .privacy-modal-body li {
            font-size: 14px;
            line-height: 1.7;
            color: #46536f;
        }
        .privacy-modal-body ul {
            margin: 10px 0 16px 18px;
            padding: 0;
        }
        .privacy-save-error {
            margin: 0;
            color: #991b1b;
            font-size: 13px;
            line-height: 1.5;
        }
        .privacy-modal-footer {
            display: flex;
            flex-direction: column;
            gap: 14px;
            padding: 0 24px 24px;
        }
        .privacy-check {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: #1c2d4d;
        }
        .privacy-check input {
            width: 16px; height: 16px;
        }
        .privacy-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }
        .privacy-btn {
            border: none;
            border-radius: 10px;
            padding: 12px 18px;
            font-weight: 700;
            cursor: pointer;
        }
        .privacy-btn.primary {
            background: linear-gradient(180deg, var(--blue-700), var(--blue-600));
            color: #fff;
        }
        .privacy-btn.secondary {
            background: #edf2ff;
            color: #142e61;
        }
        .forgot-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(9, 18, 38, 0.62);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 18px;
            z-index: 2000;
            backdrop-filter: blur(4px);
        }
        .forgot-modal {
            width: min(460px, 100%);
            max-height: min(88vh, 640px);
            background: linear-gradient(180deg, #fff 0%, #f8fbff 100%);
            border-radius: 30px;
            box-shadow: 0 30px 80px rgba(10, 22, 52, 0.24);
            border: 1px solid rgba(16, 41, 93, 0.08);
            overflow: hidden;
            transform: translateY(calc(var(--keyboard-offset, 0px) * -1));
            transition: transform 180ms ease;
        }
        .forgot-modal-header {
            background: linear-gradient(145deg, #f5f8ff 0%, #fff 100%);
            color: #112548;
            border-bottom: 1px solid #edf1f7;
            padding: 20px 24px;
        }
        .forgot-modal-header h3 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
        }
        .forgot-modal-body {
            padding: 22px 24px 10px;
        }
        .forgot-modal-body p {
            margin: 0 0 14px;
            color: #4a5772;
            font-size: 14px;
            line-height: 1.7;
        }
        .forgot-modal-body .form-control {
            height: 52px;
            border-radius: 14px;
            border: 1px solid #d4dff3;
            font-size: 15px;
            padding: 12px 14px;
            background: #fff;
            box-shadow: inset 0 1px 2px rgba(17, 37, 72, 0.04);
        }
        .forgot-modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 18px 24px 24px;
            border-top: 1px solid #edf1f7;
            background: linear-gradient(145deg, #f5f8ff 0%, #fff 100%);
            border-radius: 0 0 30px 30px;
        }
        .forgot-btn {
            border: none;
            border-radius: 12px;
            padding: 12px 18px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(17, 37, 72, 0.08);
        }
        .forgot-btn.secondary {
            background: #edf2ff;
            color: #142e61;
        }
        .forgot-btn.primary {
            background: linear-gradient(180deg, var(--blue-700), var(--blue-600));
            color: #fff;
        }
        @media (max-width: 540px) {
            .forgot-modal-backdrop {
                align-items: flex-end;
                justify-content: stretch;
                padding: 0;
            }
            .forgot-modal {
                width: 100%;
                max-height: calc(100vh - 12px);
                border-radius: 32px 32px 0 0;
            }
            .forgot-modal-header {
                border-radius: 32px 32px 0 0;
            }
            .forgot-modal-footer {
                border-radius: 0 0 32px 32px;
            }
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
        .feedback-toast.info {
            color: #0f3f7a;
            background: #dbeafe;
            border: 1px solid #93c5fd;
        }
        .feedback-toast.warning {
            color: #92400e;
            background: #fef3c7;
            border: 1px solid #fbbf24;
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
        @media (max-width: 860px) {
            .login-shell { flex-direction: column; }
            .login-brand { padding: 40px 32px; }
            .login-headline { font-size: 26px; margin-top: 30px; }
            .privacy-actions { justify-content: stretch; }
            .privacy-btn { flex: 1; }
        }
    </style>
</head>
<body>

<div id="privacyConsentModal" class="privacy-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="privacyTitle">
    <div class="privacy-modal">
        <div class="privacy-modal-header">
            <h3 id="privacyTitle">Privacy Policy &amp; System Agreement</h3>
        </div>
        <div class="privacy-modal-body">
            <h4>Before you sign in</h4>
            <p>By using this NEUST Student Navigation and Wayfinding System, you agree that your account information, office access, and navigation-related activity may be used to provide services, support your requests, and improve campus guidance within the system.</p>
            <ul>
                <li>Your email address, name, role, and office assignment are used for system authentication and access control.</li>
                <li>Access is limited to authorized users only and is monitored for system security and proper use.</li>
                <li>You are responsible for keeping your login credentials confidential and secure.</li>
            </ul>
            <p>We respect your privacy and process your data only for authorized academic and operational purposes related to this system. By continuing, you acknowledge that you have read and agree to these terms.</p>
        </div>
        <div class="privacy-modal-footer">
            <p id="privacySaveError" class="privacy-save-error" role="alert" hidden></p>
            <label class="privacy-check">
                <input id="dontShowAgain" type="checkbox">
                <span>Don't show this again</span>
            </label>
            <div class="privacy-actions">
                <!-- <button type="button" class="privacy-btn secondary" id="privacyDeclineBtn">Cancel</button> -->
                <button type="button" class="privacy-btn primary" id="privacyAgreeBtn">I Agree</button>
            </div>
        </div>
    </div>
</div>

<div id="forgotPasswordModal" class="forgot-modal-backdrop" role="dialog" aria-modal="true" aria-labelledby="forgotPasswordTitle">
    <div class="forgot-modal">
        <div class="forgot-modal-header">
            <h3 id="forgotPasswordTitle">Forgot Password</h3>
        </div>
        <div class="forgot-modal-body">
            <p id="forgotPasswordText">Enter your email address or student number to receive a 6-digit OTP.</p>
            <input id="forgotPasswordIdentifier" type="text" class="form-control" placeholder="Email address or student number" aria-label="Email address or student number">
            <input id="forgotPasswordOtp" type="text" class="form-control mt-3" inputmode="numeric" maxlength="6" placeholder="Enter 6-digit OTP" aria-label="OTP" style="display:none;">
            <div id="forgotPasswordMessage" class="small mt-2" style="display:none;"></div>
        </div>
        <div class="forgot-modal-footer">
            <button type="button" class="forgot-btn secondary" id="forgotPasswordCancelBtn">Cancel</button>
            <button type="button" class="forgot-btn primary" id="forgotPasswordSendBtn">Send OTP</button>
            <button type="button" class="forgot-btn primary" id="forgotPasswordVerifyBtn" style="display:none;">Verify OTP</button>
        </div>
    </div>
</div>

<div class="login-shell">
    <div class="login-brand">
        <div class="gear-ring"></div>
        <div class="gear-ring r2"></div>
        <div class="brand-top">
            <img src="img/neust_logo.png" alt="NEUST Logo">
            <div class="name">
                Nueva Ecija University of<br>Science and Technology
                <small>STUDENT NAVIGATION &amp; WAYFINDING SYSTEM</small>
            </div>
        </div>
        <div class="login-headline">Find your way. <span>Know what to do.</span></div>
        <p class="login-copy">Search an office, a room, or a service — get the location, the requirements, and the steps, all in one place.</p>
    </div>

    <div class="login-form-side">
        <form id="loginForm" action="functions/do_login.php" method="POST">
            <div class="login-card">
                <h2>Welcome back</h2>
                <p class="sub">Sign in with your NEUST credentials</p>
                <?php if ($accountDeleted): ?>
                    <div class="alert alert-success py-2 small" role="alert">Your account has been archived and you have been logged out.</div>
                <?php endif; ?>
                <?php if ($guestLoginError !== ''): ?>
                    <div class="alert alert-warning py-2 small" role="alert"><?= htmlspecialchars($guestLoginError, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <input type="hidden" name="guest_csrf" value="<?= htmlspecialchars($_SESSION['guest_login_csrf'], ENT_QUOTES, 'UTF-8'); ?>">

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Email</label>
                    <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Password</label>
                    <input type="password" name="pass_word" class="form-control" placeholder="****************" required>
                    <div class="mt-2 text-end">
                        <a href="#" id="forgotPasswordLink" class="small text-primary text-decoration-none fw-semibold">Forgot Password?</a>
                    </div>
                </div>
                
                <div class="d-grid mt-4">
                    <button type="submit" id="loginSubmitBtn" name="login" class="btn btn-primary" disabled>
                        Sign In
                    </button>
                </div>

                <div class="guest-divider">or</div>
                <button type="submit" id="guestLoginBtn" class="btn btn-guest" name="guest_login" value="1" formnovalidate disabled>
                    <i class="fa-solid fa-user-secret me-2" aria-hidden="true"></i>Continue as Guest
                </button>
                <p class="text-center small text-secondary mt-2 mb-0">Guest access is read-only.</p>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('privacyConsentModal');
        const agreeBtn = document.getElementById('privacyAgreeBtn');
        const declineBtn = document.getElementById('privacyDeclineBtn');
        const dontShowAgain = document.getElementById('dontShowAgain');
        const privacySaveError = document.getElementById('privacySaveError');
        const loginForm = document.getElementById('loginForm');
        const loginSubmitBtn = document.getElementById('loginSubmitBtn');
        const guestLoginBtn = document.getElementById('guestLoginBtn');
        const forgotPasswordLink = document.getElementById('forgotPasswordLink');
        const forgotPasswordModal = document.getElementById('forgotPasswordModal');
        const forgotPasswordCancelBtn = document.getElementById('forgotPasswordCancelBtn');
        const forgotPasswordSendBtn = document.getElementById('forgotPasswordSendBtn');
        const forgotPasswordVerifyBtn = document.getElementById('forgotPasswordVerifyBtn');
        const forgotPasswordIdentifier = document.getElementById('forgotPasswordIdentifier');
        const forgotPasswordOtp = document.getElementById('forgotPasswordOtp');
        const forgotPasswordMessage = document.getElementById('forgotPasswordMessage');
        const forgotPasswordText = document.getElementById('forgotPasswordText');
        let consentGranted = <?php echo $privacyConsentAccepted ? 'true' : 'false'; ?>;

        function enableLogin() {
            if (loginSubmitBtn) loginSubmitBtn.disabled = false;
            if (guestLoginBtn) guestLoginBtn.disabled = false;
        }

        function disableLogin() {
            if (loginSubmitBtn) loginSubmitBtn.disabled = true;
            if (guestLoginBtn) guestLoginBtn.disabled = true;
        }

        function hideModal() {
            if (modal) modal.style.display = 'none';
        }

        function showModal() {
            if (modal) modal.style.display = 'flex';
        }

        if (consentGranted) {
            hideModal();
            enableLogin();
        } else {
            disableLogin();
            showModal();
        }

        if (agreeBtn) {
            agreeBtn.addEventListener('click', function () {
                agreeBtn.disabled = true;
                if (privacySaveError) {
                    privacySaveError.hidden = true;
                    privacySaveError.textContent = '';
                }

                fetch('functions/accept_privacy.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'accepted=1&dont_show_again=' + (dontShowAgain.checked ? '1' : '0')
                })
                .then(function (response) {
                    if (!response.ok) throw new Error('Consent could not be saved.');
                    return response.json();
                })
                .then(function (result) {
                    if (!result.success) throw new Error('Consent could not be saved.');
                    consentGranted = true;
                    hideModal();
                    enableLogin();
                })
                .catch(function () {
                    showModal();
                    disableLogin();
                    if (privacySaveError) {
                        privacySaveError.textContent = 'We could not save your agreement. Please try again.';
                        privacySaveError.hidden = false;
                    }
                })
                .finally(function () {
                    agreeBtn.disabled = false;
                });
            });
        }

        if (declineBtn) {
            declineBtn.addEventListener('click', function () {
                consentGranted = false;
                fetch('functions/accept_privacy.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'accepted=0'
                }).finally(function () {
                    disableLogin();
                    showModal();
                });
            });
        }

        function syncForgotPasswordViewportOffset() {
            const viewport = window.visualViewport;
            const offset = viewport ? Math.max(0, window.innerHeight - viewport.height - viewport.offsetTop) : 0;
            document.documentElement.style.setProperty('--keyboard-offset', offset + 'px');
        }

        function attachForgotPasswordKeyboardSync() {
            const viewport = window.visualViewport;
            if (!viewport) return;
            viewport.addEventListener('resize', syncForgotPasswordViewportOffset);
            viewport.addEventListener('scroll', syncForgotPasswordViewportOffset);
            syncForgotPasswordViewportOffset();
        }

        function detachForgotPasswordKeyboardSync() {
            const viewport = window.visualViewport;
            if (!viewport) return;
            viewport.removeEventListener('resize', syncForgotPasswordViewportOffset);
            viewport.removeEventListener('scroll', syncForgotPasswordViewportOffset);
            document.documentElement.style.setProperty('--keyboard-offset', '0px');
        }

        function setButtonLoading(button, isLoading, label) {
            if (!button) return;
            if (isLoading) {
                button.disabled = true;
                button.dataset.originalText = button.textContent;
                button.textContent = label;
            } else {
                button.disabled = false;
                button.textContent = button.dataset.originalText || label;
            }
        }

        function resetForgotPasswordState() {
            if (forgotPasswordOtp) forgotPasswordOtp.value = '';
            if (forgotPasswordOtp) forgotPasswordOtp.style.display = 'none';
            if (forgotPasswordVerifyBtn) forgotPasswordVerifyBtn.style.display = 'none';
            if (forgotPasswordSendBtn) forgotPasswordSendBtn.style.display = 'inline-block';
            if (forgotPasswordText) forgotPasswordText.textContent = 'Enter your email address or student number to receive a 6-digit OTP.';
            if (forgotPasswordMessage) {
                forgotPasswordMessage.textContent = '';
                forgotPasswordMessage.style.display = 'none';
                forgotPasswordMessage.className = 'small mt-2';
            }
            if (forgotPasswordSendBtn) {
                forgotPasswordSendBtn.textContent = 'Send OTP';
                forgotPasswordSendBtn.disabled = false;
            }
            if (forgotPasswordVerifyBtn) {
                forgotPasswordVerifyBtn.textContent = 'Verify OTP';
                forgotPasswordVerifyBtn.disabled = false;
            }
        }

        function setForgotPasswordMessage(message, type) {
            if (!forgotPasswordMessage) return;
            forgotPasswordMessage.textContent = message;
            forgotPasswordMessage.style.display = 'block';
            forgotPasswordMessage.className = 'small mt-2 ' + (type === 'error' ? 'text-danger' : 'text-success');
        }

        function confirmForgotPasswordCancel() {
            const confirmed = window.confirm('Are you sure you want to cancel password recovery? Your current OTP request and progress will be lost.');
            if (!confirmed) {
                return;
            }

            if (forgotPasswordModal) forgotPasswordModal.style.display = 'none';
            if (forgotPasswordIdentifier) forgotPasswordIdentifier.value = '';
            detachForgotPasswordKeyboardSync();
            resetForgotPasswordState();
        }

        function showFeedbackToast(message, type) {
            var toast = document.createElement('div');
            toast.className = 'feedback-toast ' + (type === 'error' ? 'error' : type === 'warning' ? 'warning' : type === 'info' ? 'info' : 'success');
            toast.setAttribute('role', 'status');
            toast.setAttribute('aria-live', 'polite');

            var icon = document.createElement('i');
            icon.className = 'fa-solid ' + (type === 'error' ? 'fa-circle-exclamation' : type === 'warning' ? 'fa-triangle-exclamation' : type === 'info' ? 'fa-circle-info' : 'fa-circle-check');
            icon.style.marginTop = '2px';

            var text = document.createElement('span');
            text.textContent = message;

            var closeButton = document.createElement('button');
            closeButton.type = 'button';
            closeButton.setAttribute('aria-label', 'Close notification');
            closeButton.innerHTML = '&times;';
            closeButton.addEventListener('click', function () {
                if (toast && toast.parentNode) {
                    toast.remove();
                }
            });

            toast.appendChild(icon);
            toast.appendChild(text);
            toast.appendChild(closeButton);
            document.body.appendChild(toast);

            window.setTimeout(function () {
                if (toast && toast.parentNode) {
                    toast.remove();
                }
            }, 7000);
        }

        if (forgotPasswordLink) {
            forgotPasswordLink.addEventListener('click', function (event) {
                event.preventDefault();
                if (forgotPasswordModal) {
                    resetForgotPasswordState();
                    if (forgotPasswordIdentifier) {
                        forgotPasswordIdentifier.value = '';
                    }
                    forgotPasswordModal.style.display = 'flex';
                    attachForgotPasswordKeyboardSync();
                    if (forgotPasswordIdentifier) {
                        setTimeout(function () {
                            forgotPasswordIdentifier.focus();
                        }, 50);
                    }
                }
            });
        }

        if (forgotPasswordCancelBtn) {
            forgotPasswordCancelBtn.addEventListener('click', function () {
                confirmForgotPasswordCancel();
            });
        }

        if (forgotPasswordModal) {
            forgotPasswordModal.addEventListener('click', function (event) {
                if (event.target === forgotPasswordModal) {
                    confirmForgotPasswordCancel();
                }
            });
        }

        if (forgotPasswordSendBtn) {
            forgotPasswordSendBtn.addEventListener('click', function () {
                if (!forgotPasswordIdentifier || !forgotPasswordIdentifier.value.trim()) {
                    forgotPasswordIdentifier.focus();
                    setForgotPasswordMessage('Please enter your email address or student number.', 'error');
                    return;
                }

                setButtonLoading(forgotPasswordSendBtn, true, 'Sending OTP...');
                fetch('functions/forgot_password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: 'action=request_otp&identifier=' + encodeURIComponent(forgotPasswordIdentifier.value.trim())
                })
                .then(function (response) {
                    return response.json();
                })
                .then(function (result) {
                    if (!result.success) {
                        throw new Error(result.message || 'Unable to send OTP.');
                    }

                    if (forgotPasswordOtp) {
                        forgotPasswordOtp.style.display = 'block';
                        forgotPasswordOtp.focus();
                    }
                    if (forgotPasswordVerifyBtn) {
                        forgotPasswordVerifyBtn.style.display = 'inline-block';
                    }
                    if (forgotPasswordSendBtn) {
                        forgotPasswordSendBtn.style.display = 'none';
                    }
                    setForgotPasswordMessage(result.message, 'success');
                })
                .catch(function (error) {
                    setForgotPasswordMessage(error.message || 'Unable to send OTP right now.', 'error');
                })
                .finally(function () {
                    setButtonLoading(forgotPasswordSendBtn, false, 'Send OTP');
                });
            });
        }

        if (forgotPasswordVerifyBtn) {
            forgotPasswordVerifyBtn.addEventListener('click', function () {
                if (!forgotPasswordIdentifier || !forgotPasswordIdentifier.value.trim()) {
                    forgotPasswordIdentifier.focus();
                    setForgotPasswordMessage('Please enter your email address or student number.', 'error');
                    return;
                }

                if (!forgotPasswordOtp || !forgotPasswordOtp.value.trim()) {
                    forgotPasswordOtp.focus();
                    setForgotPasswordMessage('Please enter the 6-digit OTP sent to your email.', 'error');
                    return;
                }

                setButtonLoading(forgotPasswordVerifyBtn, true, 'Verifying OTP...');
                fetch('functions/forgot_password.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: 'action=verify_otp&identifier=' + encodeURIComponent(forgotPasswordIdentifier.value.trim()) + '&otp=' + encodeURIComponent(forgotPasswordOtp.value.trim())
                })
                .then(function (response) {
                    return response.json();
                })
                .then(function (result) {
                    if (!result.success) {
                        throw new Error(result.message || 'OTP verification failed.');
                    }

                    setForgotPasswordMessage(result.message, 'success');
                    showFeedbackToast(result.message, 'success');
                    if (forgotPasswordModal) {
                        setTimeout(function () {
                            forgotPasswordModal.style.display = 'none';
                            detachForgotPasswordKeyboardSync();
                            resetForgotPasswordState();
                            if (forgotPasswordIdentifier) forgotPasswordIdentifier.value = '';
                            if (forgotPasswordOtp) forgotPasswordOtp.value = '';
                        }, 1800);
                    }
                })
                .catch(function (error) {
                    setForgotPasswordMessage(error.message || 'Verification failed.', 'error');
                })
                .finally(function () {
                    setButtonLoading(forgotPasswordVerifyBtn, false, 'Verify OTP');
                });
            });
        }

        loginForm.addEventListener('submit', function (event) {
            if (!consentGranted) {
                event.preventDefault();
                disableLogin();
                showModal();
            }
        });
    })();
</script>

</body>
</html>
