<?php
require_once __DIR__ . '/../../libraries/db.php';
include __DIR__ . '/../../functions/view.php';
include __DIR__ . '/../../libraries/include.php';

if (!isset($db) || !$db instanceof PDO) {
    die('Database connection is not available.');
}

$Departments = new Departments($db);
$depts = $Departments->getDepartments(); 

$roles = new roles($db);
$roles_list = $roles->getroles(); 

$view = new view($db);
$record = [];
if (isset($_GET['user_id'])) {
    $record = $view->getById((int) $_GET['user_id']);
}

$recordUserId = $record['user_id'] ?? null;
$recordRoleId = $record['role_id'] ?? null;
$recordOfficeId = $record['office_id'] ?? null;
$recordSupervisorId = $record['supervisor_id'] ?? 'None';
$recordEmail = $record['email'] ?? 'N/A';
$recordFirstName = $record['first_name'] ?? '';
$recordMiddleName = $record['middle_name'] ?? '';
$recordLastName = $record['last_name'] ?? '';
$recordSuffix = $record['suffix'] ?? '';
$recordCreatedAt = $record['created_at'] ?? '-';
$recordUpdatedAt = $record['updated_at'] ?? '-';
$recordEmailVerified = (int) ($record['email_verified'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile | NEUST Portal</title>
    <!-- Bootstrap Icons -->
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
        h5, h6 { font-family: 'Manrope', 'Inter', sans-serif; }
        .profile-card { border: none; border-top: 4px solid #FFCE00; border-radius: 12px; }
        .bg-custom { background: linear-gradient(135deg, #14369C, #0B2270); }
        .form-label { font-size: 0.75rem; letter-spacing: 0.5px; color: #6c757d; margin-bottom: 2px; }
        .form-control-plaintext { 
            font-weight: 600; 
            color: #212529; 
            padding: 0.5rem 0.75rem; 
            background-color: #F2F5FD; 
            border-radius: 6px;
            font-size: 0.9rem;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .btn-primary { background-color: #14369C; border-color: #14369C; }
        .btn-primary:hover { background-color: #1B44B8; border-color: #1B44B8; }
    </style>
</head>

<body class="d-flex align-items-center justify-content-center vh-100 overflow-hidden">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-11 col-lg-9 col-xl-8">
                
                <div class="card profile-card shadow-lg">
                    <!-- Header -->
                    <div class="card-header bg-custom text-center py-3">
                        <img src="../../img/neust_logo.png" alt="NEUST" style="width:26px;height:26px;vertical-align:-6px;margin-right:8px;">
                        <span class="fw-bold text-white" style="font-size:1.05rem;">
                            <?= htmlspecialchars(trim(($recordLastName ? $recordLastName . ', ' : '') . $recordFirstName)) ?: 'User Profile' ?>
                        </span>
                    </div>

                    <div class="card-body p-4 p-md-5">
                        <div class="row g-4">
                            <!-- Left Column: Personal -->
                            <div class="col-md-6 border-end">
                                <h6 class="fw-bold mb-3 text-dark text-uppercase small"><i class="bi bi-person-lines-fill me-2"></i>General Information</h6>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold text-uppercase">User ID</label>
                                    <div class="form-control-plaintext">#<?= htmlspecialchars($recordUserId ?? 'N/A') ?></div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold text-uppercase">Full Name</label>
                                    <div class="form-control-plaintext">
                                        <?= htmlspecialchars(trim($recordFirstName . ' ' . $recordMiddleName . ' ' . $recordLastName . ' ' . $recordSuffix)) ?: 'N/A' ?>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold text-uppercase">Account Role</label>
                                    <div class="form-control-plaintext">
                                        <i class="bi bi-shield-lock me-1"></i> <?= htmlspecialchars($recordRoleId !== null && isset($roles_list[$recordRoleId]) ? $roles_list[$recordRoleId] : 'Standard User') ?>
                                    </div>
                                </div>

                                <div class="mb-0">
                                    <label class="form-label fw-bold text-uppercase">Verification Status</label>
                                    <div>
                                        <?php if ($recordEmailVerified === 1): ?>
                                            <span class="status-badge bg-success bg-opacity-10 text-success fw-bold">
                                                <i class="bi bi-patch-check-fill"></i> Verified Account
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge bg-danger bg-opacity-10 text-danger fw-bold">
                                                <i class="bi bi-exclamation-octagon-fill"></i> Pending Verification
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Employment & Logs -->
                            <div class="col-md-6">
                                <h6 class="fw-bold mb-3 text-dark text-uppercase small"><i class="bi bi-building me-2"></i>Organization Details</h6>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold text-uppercase">Email Address</label>
                                    <div class="form-control-plaintext text-lowercase">
                                        <i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($recordEmail) ?>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold text-uppercase">Department / Office</label>
                                    <div class="form-control-plaintext">
                                        <?= htmlspecialchars($recordOfficeId !== null && isset($depts[$recordOfficeId]) ? $depts[$recordOfficeId] : 'Unassigned') ?>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold text-uppercase">Direct Supervisor ID</label>
                                    <div class="form-control-plaintext">
                                        <?= htmlspecialchars($recordSupervisorId) ?>
                                    </div>
                                </div>

                                <div class="row g-2">
                                    <div class="col-6">
                                        <label class="form-label fw-bold text-uppercase">Created At</label>
                                        <div class="small text-muted"><?= htmlspecialchars($recordCreatedAt) ?></div>
                                    </div>
                                    <div class="col-6 text-end">
                                        <label class="form-label fw-bold text-uppercase">Last Update</label>
                                        <div class="small text-muted"><?= htmlspecialchars($recordUpdatedAt) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="row g-2 mt-4 pt-4 border-top">
                            <div class="col-md-4">
                                <a href="../../dashboard.php" class="btn btn-light border w-100 py-2 fw-bold small">
                                    <i class="bi bi-arrow-left me-1"></i> Go Back
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="../update/edit.php?user_id=<?= htmlspecialchars($recordUserId ?? '') ?>" class="btn btn-primary w-100 py-2 fw-bold shadow-sm small">
                                    <i class="bi bi-pencil-square me-1"></i> Edit Profile
                                </a>
                            </div>
                            <div class="col-md-4">
                                <button type="button" class="btn btn-outline-danger w-100 py-2 fw-bold small" data-bs-toggle="modal" data-bs-target="#deleteModal<?= htmlspecialchars($recordUserId ?? '') ?>">
                                    <i class="bi bi-trash3 me-1"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delete Modal -->
                <div class="modal fade" id="deleteModal<?= htmlspecialchars($recordUserId ?? '') ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 shadow">
                            <?php
                            $row['email'] = $recordEmail;
                            $row['user_id'] = $recordUserId ?? '';
                            $row['first_name'] = $recordFirstName;
                            $row['last_name'] = $recordLastName;
                            $row['suffix'] = $recordSuffix;
                            include '../delete/delete.php';
                            ?>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</body>
</html>