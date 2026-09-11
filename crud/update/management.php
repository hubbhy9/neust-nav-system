<?php
require_once __DIR__ . '/../../libraries/db.php';
require_once __DIR__ . '/../../functions/view.php';
include __DIR__ . '/../../libraries/include.php';

if (!isset($db) || !$db instanceof PDO) {
    die('Database connection is not available.');
}

$Departments = new Departments($db);
$depts = $Departments->getDepartments();

$rolesObj = new roles($db);
$roles_list = $rolesObj->getroles();

$view = new view($db);
$record = [];
if (isset($_GET['user_id'])) {
    $record = $view->getById((int) $_GET['user_id']);
}

$recordFirstName = $record['first_name'] ?? '';
$recordLastName = $record['last_name'] ?? '';
$recordMiddleName = $record['middle_name'] ?? '';
$recordSuffix = $record['suffix'] ?? '';
$recordEmail = $record['email'] ?? '';
$recordEmailVerified = (int) ($record['email_verified'] ?? 0);

if (isset($_POST['update'])) {
    include __DIR__ . '/../../functions/edit.php';
    $edit = new edit($db);
    $edit->update((int) $_GET['user_id']);
    header("Location: ../../dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings | NEUST Portal</title>
    <link rel="icon" href="../../img/neust_logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            position: relative;
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(20,54,156,0.12), transparent 22%),
                radial-gradient(circle at 90% 16%, rgba(255,206,0,0.16), transparent 18%),
                linear-gradient(180deg, #edf5ff 0%, #f7f9fc 42%, #eef3fa 100%);
            background-attachment: fixed;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 8% 10% 8% 10%;
            background-image: url("../../img/neust_logo.png");
            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;
            opacity: 0.06;
            pointer-events: none;
            z-index: 0;
        }
        h6 { font-family: 'Manrope', 'Inter', sans-serif; }
    </style>
</head>

<body class="modalManagement d-flex align-items-center justify-content-center vh-100">
    <div class="container d-flex justify-content-center">
        <div class="card update-card shadow-lg">
            <div class="card-header bg-white py-2 border-0 text-center">
                <img src="../../img/neust_logo.png" alt="NEUST" style="width:26px;height:26px;margin-bottom:4px;">
                <h6 class="mb-0 fw-bold text-primary">Account Settings</h6>
                <p class="small text-muted mb-0" style="font-size: 0.75rem;">Modify user information</p>
            </div>

            <div class="card-body px-4 py-3">
                <form method="POST" action="">
                    <div class="row g-2">

                        <!-- Name Row -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-uppercase text-muted">First Name</label>
                            <input type="text" name="first_name" class="form-control form-control-sm"
                                style="text-transform: capitalize;"
                                value="<?= htmlspecialchars($recordFirstName) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-uppercase text-muted">Last Name</label>
                            <input type="text" name="last_name" class="form-control form-control-sm"
                                style="text-transform: capitalize;"
                                value="<?= htmlspecialchars($recordLastName) ?>" required>
                        </div>

                        <!-- Secondary Name Row -->
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-uppercase text-muted">Middle Name</label>
                            <input type="text" name="middle_name" class="form-control form-control-sm"
                                style="text-transform: capitalize;"
                                value="<?= htmlspecialchars($recordMiddleName) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold text-uppercase text-muted">Suffix</label>
                            <input type="text" name="suffix" class="form-control form-control-sm"
                                style="text-transform: capitalize;"
                                value="<?= htmlspecialchars($recordSuffix) ?>">
                        </div>

                        <!-- Email -->
                        <div class="col-12">
                            <label class="form-label fw-bold text-uppercase text-muted">Email Address</label>
                            <input type="email" name="email" class="form-control form-control-sm"
                                style="text-transform: lowercase;"
                                value="<?= htmlspecialchars($recordEmail) ?>" required>
                        </div>

                        <!-- Verification Row (Fits in one row) -->
                        <div class="col-12 mt-3">
                            <label class="form-label fw-bold text-uppercase text-muted">Verification Status</label>
                            <div class="row g-2 align-items-center">
                                <?php if ($recordEmailVerified === 1): ?>
                                    <div class="col-12">
                                        <div
                                            class="status-badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">
                                            <i class="bi bi-patch-check-fill me-2"></i> Verified Account
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="col-7">
                                        <div
                                            class="status-badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">
                                            <i class="bi bi-exclamation-octagon-fill me-2"></i> Pending Verification
                                        </div>
                                    </div>
                                    <div class="col-5">
                                        <button href="" type="submit" name="verify_now"
                                            class="btn btn-outline-danger btn-md w-100 fw-bold">
                                            Verify Now
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="col-12 mt-4 pt-2 border-top">
                            <div class="row g-2">
                                <div class="col-4">
                                    <a href="../../dashboard.php"
                                        class="btn btn-light border btn-sm w-100 py-2 text-muted">
                                        <i class="bi bi-arrow-left me-1"></i> Back
                                    </a>
                                </div>
                                <div class="col-4">
                                    <button type="submit" name="update"
                                        class="btn btn-danger btn-sm border w-100 py-2 shadow-sm fw-bold">
                                        Deactivate<i class="bi bi-check2-circle ms-1"></i>
                                    </button>
                                </div>
                                <div class="col-4">
                                    <button type="submit" name="update"
                                        class="btn btn-primary btn-sm w-100 py-2 shadow-sm fw-bold">
                                        Save Changes <i class="bi bi-check2-circle ms-1"></i>
                                    </button>
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