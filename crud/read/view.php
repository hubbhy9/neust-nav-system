<?php
session_start();

require_once __DIR__ . '/../../libraries/db.php';
include __DIR__ . '/../../functions/view.php';
include __DIR__ . '/../../libraries/include.php';

if (!isset($db) || !$db instanceof PDO) {
    die('Database connection is not available.');
}

$roles_list = [
    0 => 'User',
    1 => 'Admin',
];

$view = new view($db);
$record = [];
if (isset($_GET['id'])) {
    $record = $view->getById((int) $_GET['id']);
}
$recordId = $record['id'] ?? 0;
$recordRoleId = $record['role_id'] ?? null;
$currentUserRoleId = (int) ($_SESSION['role_id'] ?? 0);
$recordRegistrationNumber = $record['registration_number'] ?? 'N/A';
$recordStudentNumber = $record['student_number'] ?? 'N/A';
$recordFirstName = $record['first_name'] ?? '';
$recordMiddleName = $record['middle_name'] ?? '';
$recordLastName = $record['last_name'] ?? '';
$recordSuffix = $record['suffix'] ?? '';
$recordBarangay = $record['barangay'] ?? 'N/A';
$recordCity = $record['city'] ?? 'N/A';
$recordProvince = $record['province'] ?? 'N/A';
$recordBirthday = $record['birthday'] ?? 'N/A';
$recordSex = $record['sex'] ?? 'N/A';
$recordMobileNumber = $record['mobile_number'] ?? 'N/A';
$recordEmail = $record['email'] ?? 'N/A';
$recordRoleLabel = $recordRoleId !== null && isset($roles_list[$recordRoleId]) ? $roles_list[$recordRoleId] : 'User';
$recordCreatedAt = $record['created_at'] ?? '-';
$recordUpdatedAt = $record['updated_at'] ?? '-';
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
        :root {
            --navy: #173d9f;
            --navy-dark: #0b2f7a;
            --gold: #ffce00;
            --panel: rgba(255, 255, 255, 0.7);
            --field-bg: rgba(255, 255, 255, 0.85);
            --field-border: rgba(27, 64, 154, 0.9);
            --text: #1d2a39;
            --muted: #5f6b7e;
            --border-soft: #dfe7f4;
            --danger: #e75459;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                radial-gradient(circle at top left, rgba(20, 54, 156, 0.12), transparent 22%),
                radial-gradient(circle at 90% 16%, rgba(255, 206, 0, 0.18), transparent 18%),
                linear-gradient(180deg, #edf5ff 0%, #f7f9fc 42%, #edf3fb 100%);
            font-family: 'Inter', sans-serif;
            color: var(--text);
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

        .profile-shell {
            position: relative;
            z-index: 1;
            width: min(100%, 860px);
            margin: 18px auto;
            background: rgba(255, 255, 255, 0.62);
            border: 1px solid rgba(23, 61, 159, 0.08);
            border-radius: 9px;
            box-shadow: 0 16px 28px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .profile-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 42px;
            padding: 8px 16px;
            background: linear-gradient(90deg, var(--navy), var(--navy-dark));
            color: #fff;
            font-size: 0.82rem;
            font-weight: 800;
            letter-spacing: 0.02em;
            text-align: center;
        }

        .profile-header .brand {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .profile-header img {
            width: 24px;
            height: 24px;
            vertical-align: middle;
        }

        .profile-body {
            padding: 8px 14px 16px;
        }

        .section-header {
            margin: 4px 4px 0;
            padding: 6px 4px 5px;
            border-bottom: 1px solid var(--border-soft);
            font-size: 0.62rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: blue;
        }

        label {
            font-size: 0.62rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--muted);
            margin: 0;
        }

        .cell {
            min-height: 38px;
            display: flex;
            align-items: center;
            padding: 8px 10px;
            background: var(--field-bg);
            border: 1px solid var(--field-border);
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
        }

        .d-flex { display: flex; }
        .flex-nowrap { flex-wrap: nowrap; }
        .gap-3 { gap: 1rem; }
        .mb-2 { margin-bottom: 0.5rem; }

        .actions {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 10px;
            padding: 14px 4px 6px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 36px;
            border-radius: 7px;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.02em;
            text-decoration: none;
            transition: 0.2s ease;
            border: 1px solid transparent;
            cursor: pointer;
        }

        .btn-back {
            background: #f6f8fc;
            border-color: rgba(23, 61, 159, 0.2);
            color: #1c2d4a;
        }

        .btn-edit {
            background: linear-gradient(180deg, #1a4ed6, #14369c);
            color: #fff;
            box-shadow: 0 8px 18px rgba(20, 54, 156, 0.18);
        }

        .btn-delete {
            background: #fff;
            border-color: rgba(231, 84, 89, 0.7);
            color: #d73a42;
        }

        .btn:hover {
            filter: brightness(0.98);
        }

        @media (max-width: 820px) {
            .actions {
                grid-template-columns: 1fr;
            }

            .d-flex {
                flex-wrap: wrap;
            }
        }

        @media (max-width: 520px) {
            .profile-body {
                padding-inline: 12px;
            }

            .profile-header {
                font-size: 0.8rem;
            }
        }
    </style>
</head>

<body>
    <div class="profile-shell">
        <div class="profile-header">
            <span class="brand">
                <img src="../../img/neust_logo.png" alt="NEUST">
                <span>#<?= htmlspecialchars($recordId ?? 0) ?> -</span>
                <?= htmlspecialchars(trim((($recordLastName ?: 'last name') . ', ' . ($recordFirstName ?: 'first name') . ', ' . ($recordMiddleName ? substr($recordMiddleName, 0, 1) : 'middle initial')))) ?>
            </span>
        </div>

        <div class="profile-body">
            <div class="section-header">User Information</div>
            <div class="d-flex flex-nowrap gap-3 mb-2">
                <div style="flex: 4;">
                    <label>Last Name</label>
                    <div class="cell"><?= htmlspecialchars($recordLastName ?: 'N/A') ?></div>
                </div>
                <div style="flex: 4;">
                    <label>First Name</label>
                    <div class="cell"><?= htmlspecialchars($recordFirstName ?: 'N/A') ?></div>
                </div>
                <div style="flex: 4;">
                    <label>Middle Name</label>
                    <div class="cell"><?= htmlspecialchars($recordMiddleName ?: 'N/A') ?></div>
                </div>
                <div style="flex: 1;">
                    <label>Suffix</label>
                    <div class="cell"><?= htmlspecialchars($recordSuffix ?: 'N/A') ?></div>
                </div>
            </div>
            <div class="d-flex flex-nowrap gap-3 mb-2">
                <div style="flex: 4;">
                    <label>Birthday</label>
                    <div class="cell"><?= htmlspecialchars($recordBirthday ?: 'N/A') ?></div>
                </div>
                <div style="flex: 4;">
                    <label>Sex</label>
                    <div class="cell"><?= htmlspecialchars($recordSex ?: 'N/A') ?></div>
                </div>
            </div>

            <div class="section-header">Address</div>
            <div class="d-flex flex-nowrap gap-3 mb-2">
                <div style="flex: 4;">
                    <label>Barangay</label>
                    <div class="cell"><?= htmlspecialchars($recordBarangay ?: 'N/A') ?></div>
                </div>
                <div style="flex: 4;">
                    <label>City</label>
                    <div class="cell"><?= htmlspecialchars($recordCity ?: 'N/A') ?></div>
                </div>
                <div style="flex: 4;">
                    <label>Province</label>
                    <div class="cell"><?= htmlspecialchars($recordProvince ?: 'N/A') ?></div>
                </div>
            </div>

            <div class="section-header">Contact Information</div>
            <div class="d-flex flex-nowrap gap-3 mb-2">
                <div style="flex: 6;">
                    <label>Email</label>
                    <div class="cell"><?= htmlspecialchars($recordEmail ?: 'N/A') ?></div>
                </div>
                <div style="flex: 6;">
                    <label>Contact Number</label>
                    <div class="cell"><?= htmlspecialchars($recordMobileNumber ?: 'N/A') ?></div>
                </div>
            </div>

            <div class="section-header">Account Details</div>
            <div class="d-flex flex-nowrap gap-3 mb-2">
                <div style="flex: 1;">
                    <label>ID</label>
                    <div class="cell"><?= htmlspecialchars($recordId ?? 0) ?></div>
                </div>
                <div style="flex: 2;">
                    <label>Role</label>
                    <div class="cell"><?= htmlspecialchars($recordRoleLabel) ?></div>
                </div>
                <div style="flex: 4;">
                    <label>Registration Number</label>
                    <div class="cell"><?= htmlspecialchars($recordRegistrationNumber ?: 'N/A') ?></div>
                </div>
                <div style="flex: 4;">
                    <label>Student Number</label>
                    <div class="cell"><?= htmlspecialchars($recordStudentNumber ?: 'N/A') ?></div>
                </div>
            </div>

            <div class="actions">
                <a href="../../dashboard.php" class="btn btn-back"><i class="bi bi-arrow-left me-1"></i> Go Back</a>
                <?php if ($currentUserRoleId === 1): ?>
                    <a href="../update/edit.php?id=<?= htmlspecialchars($record['id'] ?? '') ?>" class="btn btn-edit"><i class="bi bi-pencil-square me-1"></i> Edit Profile</a>
                <?php else: ?>
                    <span class="btn btn-edit" style="visibility:hidden;">Edit Profile</span>
                <?php endif; ?>
                <button type="button" class="btn btn-delete" data-bs-toggle="modal" data-bs-target="#deleteModal<?= htmlspecialchars($recordId ?? '') ?>">
                    <i class="bi bi-trash3 me-1"></i> Delete
                </button>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteModal<?= htmlspecialchars($recordId ?? '') ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <?php
                $row['email'] = $recordEmail;
                $row['id'] = $record['id'] ?? '';
                $row['first_name'] = $recordFirstName;
                $row['last_name'] = $recordLastName;
                $row['suffix'] = $recordSuffix;
                include '../delete/delete.php';
                ?>
            </div>
        </div>
    </div>
</body>
</html>