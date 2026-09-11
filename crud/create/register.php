<?php
include __DIR__ . '/../../libraries/include.php';
include __DIR__ . '/../../functions/addf.php';

if (!isset($db)) {
    die('Database connection is not available.');
}

$error_message = "";

if (isset($_POST['Add'])) {

    $add = new add($db);

    $fn = $_POST['first_name'];
    $ln = $_POST['last_name'];
    $email = $_POST['email'];
    $pass = $_POST['pass_word'];

    // VALIDATION
    if (!preg_match("/^[A-Za-z\s]+$/", $fn) || !preg_match("/^[A-Za-z\s]+$/", $ln)) {
        $error_message = "Names should only contain letters.";

    } elseif (!preg_match("/^[a-zA-Z0-9._%+-]+@gmail\.com$/", $email)) {
        $error_message = "Only @gmail.com email addresses are allowed.";

    } elseif (!preg_match("/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}/", $pass)) {
        $error_message = "Password does not meet security requirements.";

    } elseif ($add->emailExists($email)) {
        $error_message = "Email already registered.";

    } else {

        $data = [
            'first_name' => $fn,
            'middle_name' => $_POST['middle_name'] ?? '',
            'last_name'  => $ln,
            'suffix'     => $_POST['suffix'] ?? '',
            'email'      => $email,
            'pass_word'  => password_hash($pass, PASSWORD_DEFAULT),
            'office_id'  => $_POST['office_id'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];

        $success = $add->Add();

        if ($success) {
           echo "<script>
alert('Account registered successfully!');
window.location.href = '../../login.php';
</script>";
            exit;
        } else {
            $error_message = "Registration failed. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | NEUST Portal</title>
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="../../img/neust_logo.png">
    <style>
        body {
            position: relative;
            background:
                radial-gradient(circle at top left, rgba(20,54,156,0.18), transparent 20%),
                radial-gradient(circle at 85% 18%, rgba(255,206,0,0.15), transparent 18%),
                linear-gradient(135deg, #edf5ff 0%, #f8faff 42%, #eff4fe 100%);
            background-attachment: fixed;
            font-family: 'Inter', sans-serif;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 8% 10% 8% 10%;
            background-image: url("../../img/neust_logo.png");
            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;
            opacity: 0.05;
            pointer-events: none;
            z-index: 0;
        }

        h4, h5 {
            font-family: 'Manrope', 'Inter', sans-serif;
        }

        .container {
            position: relative;
            z-index: 1;
        }

        .reg-card {
            border: 1px solid rgba(17, 24, 39, 0.05);
            border-top: 4px solid #FFCE00;
            border-radius: 18px;
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(8px);
            box-shadow: 0 20px 50px -28px rgba(15, 23, 42, 0.6);
            overflow: hidden;
        }

        .bg-custom {
            background: linear-gradient(135deg, #14369C, #0B2270);
        }

        .form-label {
            font-size: 0.72rem;
            letter-spacing: 0.08em;
            color: #5f6a7d;
            margin-bottom: 6px;
            text-transform: uppercase;
            font-weight: 700;
        }

        .form-control-sm,
        .form-control {
            border-radius: 10px;
            border: 1px solid rgba(15, 23, 42, 0.09);
            background: rgba(248, 250, 252, 0.9);
            padding: 0.72rem 0.8rem;
            transition: all 0.2s ease;
            box-shadow: none;
        }

        .form-control-sm:focus,
        .form-control:focus {
            border-color: rgba(20,54,156,0.55);
            box-shadow: 0 0 0 0.2rem rgba(20,54,156,0.10);
            background: #fff;
        }

        .requirement-box {
            font-size: 0.7rem;
            line-height: 1.4;
            background: linear-gradient(180deg, rgba(20,54,156,0.04), rgba(255,206,0,0.04));
            border: 1px solid rgba(20,54,156,0.08);
            border-radius: 12px;
            padding: 10px 12px;
        }

        .requirement-list {
            list-style: none;
            padding-left: 0;
            margin-bottom: 0;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .requirement-list li {
            display: flex;
            align-items: center;
            white-space: nowrap;
            color: #1f2a3d;
            font-weight: 600;
        }

        .requirement-list i {
            font-size: 0.6rem;
            margin-right: 4px;
            color: #14369C;
        }

        .btn-success {
            background: linear-gradient(135deg, #f6b400, #f89318);
            border: none;
            border-radius: 10px;
            font-weight: 800;
            letter-spacing: 0.04em;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 12px 18px -12px rgba(248,147,24,0.75);
        }

        .btn-success:hover {
            background: linear-gradient(135deg, #f7bb1e, #df7d0c);
            transform: translateY(-1px);
        }

        .signin-link {
            color: #14369C;
            font-weight: 800;
        }

        .alert-danger {
            border-radius: 12px;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }

        @media (max-height: 800px) {
            .reg-card .card-body {
                padding: 1.3rem !important;
            }

            h5 {
                font-size: 1.2rem;
            }
        }
    </style>
</head>

<body class="d-flex align-items-center justify-content-center vh-100 overflow-hidden">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-11 col-lg-10 col-xl-8">

                <!-- Compact Error Alert -->
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm py-2 mb-2 small"
                        role="alert">
                        <i class="bi bi-exclamation-circle-fill me-2"></i> <?= $error_message ?>
                        <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="card reg-card shadow-lg">
                        <div class="card-header bg-custom text-center py-3 d-flex align-items-center justify-content-center gap-2">
                            <img src="../../img/neust_logo.png" alt="NEUST" style="width:28px;height:28px;">
                            <h5 class="mb-0 fw-bold text-white">Create Account</h5>
                        </div>

                        <div class="card-body p-4 p-md-5">
                            <div class="row g-4">
                                <div class="col-md-6 pe-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">First Name <span class="text-danger">*</span></label>
                                        <input type="text" name="first_name" class="form-control form-control-sm"
                                            style="text-transform: capitalize;" placeholder="Juan"
                                            pattern="^[A-Za-z\s]+$" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" name="last_name" class="form-control form-control-sm"
                                            style="text-transform: capitalize;" placeholder="Dela Cruz"
                                            pattern="^[A-Za-z\s]+$" required>
                                    </div>

                                    <div class="row g-2">
                                        <div class="col-8">
                                            <label class="form-label">Middle Name</label>
                                            <input type="text" name="middle_name" class="form-control form-control-sm"
                                                style="text-transform: capitalize;" placeholder="Santos">
                                        </div>
                                        <div class="col-4">
                                            <label class="form-label">Suffix</label>
                                            <input type="text" name="suffix" class="form-control form-control-sm"
                                                style="text-transform: capitalize;" placeholder="Jr.">
                                        </div>
                                    </div>

                                    <div class="mt-3 pt-3 border-top">
                                        <p class="text-muted mb-0" style="font-size: 0.68rem; line-height:1.5;">
                                            <i class="bi bi-info-circle me-1"></i> Names must contain only letters and spaces.
                                        </p>
                                    </div>
                                </div>

                                <div class="col-md-6 ps-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control form-control-sm"
                                            placeholder="user@gmail.com" pattern="^[a-zA-Z0-9._%+-]+@gmail\.com$" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Password <span class="text-danger">*</span></label>
                                        <input type="password" name="pass_word"
                                            class="form-control form-control-sm" placeholder="••••••••"
                                            pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}" required>
                                    </div>

                                    <div class="requirement-box">
                                        <p class="fw-bold text-dark mb-2" style="font-size: 0.7rem; letter-spacing:0.04em; text-transform: uppercase;">Security Requirements</p>
                                        <ul class="requirement-list">
                                            <li><i class="bi bi-check2-square"></i> 8+ chars</li>
                                            <li><i class="bi bi-check2-square"></i> A-z</li>
                                            <li><i class="bi bi-check2-square"></i> 0-9</li>
                                            <li><i class="bi bi-check2-square"></i> !@#$</li>
                                            <li><i class="bi bi-check2-square"></i> @gmail.com</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-4 pt-3 border-top">
                                <button type="submit" name="Add" class="btn btn-success w-100 shadow-sm py-2">
                                    Register Account
                                </button>
                                <div class="mt-3 small text-muted">
                                    Already have an account?
                                    <a href="../../login.php" class="signin-link text-decoration-none ms-1">Sign In</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

</body>

</html>