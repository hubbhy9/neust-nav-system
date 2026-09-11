<?php
session_start();

include __DIR__ . '/libraries/include.php';
include __DIR__ . '/functions/login_auth.php';

// Redirect if already logged in
if (isset($_SESSION['office_id'])) {
    header("Location: offices/office_" . $_SESSION['office_id'] . ".php");
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
                radial-gradient(circle at top left, rgba(22,73,199,0.10), transparent 22%),
                radial-gradient(circle at 90% 16%, rgba(255,206,0,0.12), transparent 18%),
                linear-gradient(180deg, #edf3ff 0%, #f7f9fc 42%, #eef3fa 100%);
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
            opacity: 0.06;
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
        @media (max-width: 860px) {
            .login-shell { flex-direction: column; }
            .login-brand { padding: 40px 32px; }
            .login-headline { font-size: 26px; margin-top: 30px; }
        }
    </style>
</head>
<body>

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
        <form action="functions/do_login.php" method="POST">
            <div class="login-card">
                <h2>Welcome back</h2>
                <p class="sub">Sign in with your NEUST credentials</p>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Password</label>
                    <input type="password" name="pass_word" class="form-control" required>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" name="login" class="btn btn-primary">
                        Sign In
                    </button>
                </div>

                <a href="crud/create/register.php" class="d-block text-center mt-3 small">
                    Don't have an account? Register here
                </a>
            </div>
        </form>
    </div>
</div>

</body>
</html>
