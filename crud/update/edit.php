<?php
require_once __DIR__ . '/../../libraries/db.php';
include __DIR__ . '/../../functions/view.php';
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

$recordUserId = $record['user_id'] ?? '';
$recordRoleId = $record['role_id'] ?? null;
$recordOfficeId = $record['office_id'] ?? null;
$recordFirstName = $record['first_name'] ?? '';
$recordMiddleName = $record['middle_name'] ?? '';
$recordLastName = $record['last_name'] ?? '';
$recordSuffix = $record['suffix'] ?? '';
$recordEmail = $record['email'] ?? '';
$recordSupervisorId = $record['supervisor_id'] ?? '';
$recordCreatedAt = $record['created_at'] ?? '-';
$recordUpdatedAt = $record['updated_at'] ?? '-';

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
    <title>Edit User | NEUST Portal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="../../img/neust_logo.png">
    <style>
        body {
            position: relative;
            background:
                radial-gradient(circle at top left, rgba(20,54,156,0.12), transparent 22%),
                radial-gradient(circle at 90% 16%, rgba(255,206,0,0.16), transparent 18%),
                linear-gradient(180deg, #edf5ff 0%, #f7f9fc 42%, #eef3fa 100%);
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
            opacity: 0.06;
            pointer-events: none;
            z-index: 0;
        }
        h6 { font-family: 'Manrope', 'Inter', sans-serif; }
        .edit-card { border: none; border-top: 4px solid #FFCE00; border-radius: 12px; }
        .bg-custom { background: linear-gradient(135deg, #14369C, #0B2270); }
        .form-label { font-size: 0.68rem; letter-spacing: 0.5px; color: #6c757d; margin-bottom: 2px; text-transform: uppercase; font-weight: 700; }
        .form-control-sm, .form-select-sm { 
            border-radius: 6px; 
            font-size: 0.85rem;
            background-color: #ffffff;
        }
        .form-control-sm[readonly] { background-color: #F2F5FD; color: #6c757d; cursor: not-allowed; }
        .section-title {
            font-size: 0.75rem;
            font-weight: 800;
            color: #14369C;
            margin-bottom: 1rem;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 5px;
        }
        .btn-success { background-color: #F89318; border-color: #F89318; font-weight: 700; }
        .btn-success:hover { background-color: #DE7E0C; border-color: #DE7E0C; }
        /* Tighten layout for smaller screens */
        @media (max-height: 850px) {
            .card-body { padding: 1.25rem 2rem !important; }
            .mb-3 { margin-bottom: 0.75rem !important; }
            .mt-4 { margin-top: 1rem !important; }
        }
    </style>
</head>

<body class="d-flex align-items-center justify-content-center vh-100 overflow-hidden">
    <div class="container">
        <form method="POST" action="">
            <div class="row justify-content-center">
                <div class="col-md-11 col-lg-10 col-xl-8">
                    
                    <div class="card edit-card shadow-lg">
                        <div class="card-header bg-custom text-center py-2">
                            <img src="../../img/neust_logo.png" alt="NEUST" style="width:20px;height:20px;vertical-align:-4px;margin-right:8px;">
                            <span class="fw-bold text-white" style="font-size:0.95rem;">Edit User Profile</span>
                        </div>

                        <div class="card-body p-4">
                            <div class="row g-4">
                                <!-- Left Column -->
                                <div class="col-md-6 border-end">
                                    <div class="section-title text-uppercase">General Information</div>

                                    <div class="row g-2 mb-2">
                                        <div class="col-4">
                                            <label class="form-label">User ID</label>
                                            <input type="text" value="<?= htmlspecialchars($recordUserId) ?>" class="form-control form-control-sm" readonly>
                                        </div>
                                        <div class="col-8">
                                            <label class="form-label">Role</label>
                                            <select class="form-select form-select-sm" name="role_id">
                                                <option value="<?= htmlspecialchars($recordRoleId ?? '') ?>" selected><?= htmlspecialchars($recordRoleId !== null && isset($roles_list[$recordRoleId]) ? $roles_list[$recordRoleId] : 'Select Role') ?></option>
                                                <?php foreach ($roles_list as $id => $role_name): ?>
                                                    <?php if ($recordRoleId !== null && $id == $recordRoleId) continue; ?>
                                                    <option value="<?= $id ?>"><?= htmlspecialchars($role_name) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label">First Name</label>
                                        <input type="text" name="first_name" style="text-transform: capitalize;" value="<?= htmlspecialchars($recordFirstName) ?>" class="form-control form-control-sm" required>
                                    </div>

                                    <div class="row g-2 mb-2">
                                        <div class="col-8">
                                            <label class="form-label">Last Name</label>
                                            <input type="text" name="last_name" style="text-transform: capitalize;" value="<?= htmlspecialchars($recordLastName) ?>" class="form-control form-control-sm" required>
                                        </div>
                                        <div class="col-4">
                                            <label class="form-label">Suffix</label>
                                            <input type="text" name="suffix" style="text-transform: capitalize;" value="<?= htmlspecialchars($recordSuffix) ?>" class="form-control form-control-sm">
                                        </div>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label">Middle Name</label>
                                        <input type="text" name="middle_name" style="text-transform: capitalize;" value="<?= htmlspecialchars($recordMiddleName) ?>" class="form-control form-control-sm">
                                    </div>

                                </div>

                                <!-- Right Column -->
                                <div class="col-md-6">
                                    <div class="section-title text-uppercase">Account & Office</div>

                                    <div class="mb-2">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" name="email" value="<?= htmlspecialchars($recordEmail) ?>" style="text-transform: lowercase;" class="form-control form-control-sm" required>
                                    </div>

                                    <div class="mb-2">
                                        <label class="form-label">Department</label>
                                        <select class="form-select form-select-sm" name="office_id">
                                            <option value="<?= htmlspecialchars($recordOfficeId ?? '') ?>" selected><?= htmlspecialchars($recordOfficeId !== null && isset($depts[$recordOfficeId]) ? $depts[$recordOfficeId] : 'Select Department') ?></option>
                                            <?php foreach ($depts as $id => $dept_name): ?>
                                                <?php if ($recordOfficeId !== null && $id == $recordOfficeId) continue; ?>
                                                <option value="<?= $id ?>"><?= htmlspecialchars($dept_name) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-0">
                                        <label class="form-label">Supervisor ID</label>
                                        <input type="text" name="supervisor_id" value="<?= htmlspecialchars($recordSupervisorId) ?>" class="form-control form-control-sm" readonly>
                                    </div>

                                    <div class="mb-0 row g-0 mt-2">
                                        <div class="col-6">
                                        <label class="form-label">Registration Date</label>
                                        <div class="small text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($recordCreatedAt) ?></div>
                                        </div>
                                        <div class="col-6">
                                        <label class="form-label">Last update</label>
                                        <div class="small text-muted" style="font-size: 0.75rem;"><?= htmlspecialchars($recordUpdatedAt) ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Footer Actions -->
                            <div class="row g-2 mt-3 pt-3 border-top d-flex align-items-center">
                                <div class="col-6">
                                    <a href="../../dashboard.php" class="btn btn-light border w-100 py-1 fw-bold small text-muted">
                                        Cancel
                                    </a>
                                </div>
                                <div class="col-6">
                                    <button type="submit" name="update" class="btn btn-success w-100 py-1 fw-bold shadow-sm small">
                                        Update Profile
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </form>
    </div>
</body>
</html> 