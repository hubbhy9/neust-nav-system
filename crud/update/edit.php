<?php
require_once __DIR__ . '/../../libraries/db.php';
include __DIR__ . '/../../functions/view.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

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
$recordRegistrationNumber = $record['registration_number'] ?? '';
$recordStudentNumber = $record['student_number'] ?? '';
$recordFirstName = $record['first_name'] ?? '';
$recordMiddleName = $record['middle_name'] ?? '';
$recordLastName = $record['last_name'] ?? '';
$recordSuffix = $record['suffix'] ?? '';
$recordBarangay = $record['barangay'] ?? '';
$recordCity = $record['city'] ?? '';
$recordProvince = $record['province'] ?? '';
$recordBirthday = $record['birthday'] ?? '';
$recordSex = $record['sex'] ?? '';
$recordMobileNumber = $record['mobile_number'] ?? '';
$recordEmail = $record['email'] ?? '';
$recordRoleLabel = $recordRoleId !== null && isset($roles_list[$recordRoleId]) ? $roles_list[$recordRoleId] : 'N/A';


if (isset($_POST['update'])) {
    include __DIR__ . '/../../functions/edit.php';
    $edit = new edit($db);
    $edit->update((int) $_GET['id']);
    header("Location: ../../dashboard.php");
    exit;
}
include __DIR__ . '/../../libraries/include.php';
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

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background:
                radial-gradient(circle at top left, rgba(20,54,156,0.12), transparent 22%),
                radial-gradient(circle at 90% 16%, rgba(255,206,0,0.18), transparent 18%),
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

        .edit-shell {
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

        .edit-header {
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

        .edit-header img {
            width: 24px;
            height: 24px;
            vertical-align: middle;
        }

        .edit-body {
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

        input, select {
            width: 100%;
            min-height: 38px;
            padding: 8px 10px;
            background: var(--field-bg);
            border: 1px solid var(--field-border);
            border-radius: 6px;
            font-size: 0.8rem;
            color: var(--text);
            outline: none;
        }

        input:focus, select:focus {
            box-shadow: 0 0 0 0.18rem rgba(20, 54, 156, 0.12);
        }

        .d-flex { display: flex; }
        .flex-nowrap { flex-wrap: nowrap; }
        .gap-3 { gap: 1rem; }
        .mb-2 { margin-bottom: 0.5rem; }

        .actions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
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

        .btn-cancel {
            background: #f6f8fc;
            border-color: rgba(23, 61, 159, 0.2);
            color: #1c2d4a;
        }

        .btn-submit {
            background: linear-gradient(180deg, #f6b13b, #e89a19);
            color: #fff;
            box-shadow: 0 8px 18px rgba(232, 154, 25, 0.2);
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
            .edit-body {
                padding-inline: 12px;
            }

            .edit-header {
                font-size: 0.8rem;
            }
        }
    </style>
</head>

<body>
    <div class="edit-shell">
        <div class="edit-header">
            <img src="../../img/neust_logo.png" alt="NEUST">
            <span>Edit User Profile</span>
        </div>

        <div class="edit-body">
            <form method="POST" action="">
                <div class="d-flex flex-nowrap gap-3 mb-2">
                    <div style="flex: 1;">
                        <label>ID</label>
                        <input type="text" value="<?= htmlspecialchars($recordId) ?>" readonly>
                    </div>
                    <div style="flex: 3;">
                        <label>Registration Number</label>
                        <input type="text" name="registration_number" value="<?= htmlspecialchars($recordRegistrationNumber) ?>">
                    </div>
                    <div style="flex: 3;">
                        <label>Student Number</label>
                        <input type="text" name="student_number" value="<?= htmlspecialchars($recordStudentNumber) ?>">
                    </div>
                    <div style="flex: 2;">
                        <label>Role</label>
                        <select name="role_id">
                            <option value="<?= htmlspecialchars($recordRoleId ?? '') ?>" selected><?= htmlspecialchars($recordRoleId !== null && isset($roles_list[$recordRoleId]) ? $roles_list[$recordRoleId] : 'Select Role') ?></option>
                            <?php foreach ($roles_list as $id => $role_name): ?>
                                <?php if ($recordRoleId !== null && $id == $recordRoleId) continue; ?>
                                <option value="<?= $id ?>"><?= htmlspecialchars($role_name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="section-header">User Information</div>
                <div class="d-flex flex-nowrap gap-3 mb-2">
                    <div style="flex: 4;">
                        <label>Last Name</label>
                        <input type="text" name="last_name" class="name-field" value="<?= htmlspecialchars($recordLastName) ?>" pattern="^[\pL\s'.-]+$" required>
                    </div>
                    <div style="flex: 4;">
                        <label>First Name</label>
                        <input type="text" name="first_name" class="name-field" value="<?= htmlspecialchars($recordFirstName) ?>" pattern="^[\pL\s'.-]+$" required>
                    </div>
                    <div style="flex: 4;">
                        <label>Middle Name</label>
                        <input type="text" name="middle_name" class="name-field" value="<?= htmlspecialchars($recordMiddleName) ?>" pattern="^[\pL\s'.-]+$">
                    </div>
                    <div style="flex: 1;">
                        <label>Suffix</label>
                        <input type="text" name="suffix" class="name-field" value="<?= htmlspecialchars($recordSuffix) ?>" pattern="^[\pL\s'.-]+$">
                    </div>
                </div>
                <div class="d-flex flex-nowrap gap-3 mb-2">
                    <div style="flex: 4;">
                        <label>Birthday</label>
                        <input type="date" name="birthday" value="<?= htmlspecialchars($recordBirthday) ?>">
                    </div>
                    <div style="flex: 4;">
                        <label>Sex</label>
                        <input type="text" name="sex" value="<?= htmlspecialchars($recordSex) ?>">
                    </div>
                </div>

                <div class="section-header">Address</div>
                <div class="d-flex flex-nowrap gap-3 mb-2">
                    <div style="flex: 4;">
                        <label>Barangay</label>
                        <input type="text" name="barangay" value="<?= htmlspecialchars($recordBarangay) ?>">
                    </div>
                    <div style="flex: 4;">
                        <label>City</label>
                        <input type="text" name="city" value="<?= htmlspecialchars($recordCity) ?>">
                    </div>
                    <div style="flex: 4;">
                        <label>Province</label>
                        <input type="text" name="province" value="<?= htmlspecialchars($recordProvince) ?>">
                    </div>
                </div>

                <div class="section-header">Contact Information</div>
                <div class="d-flex flex-nowrap gap-3 mb-2">
                    <div style="flex: 6;">
                        <label>Email</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($recordEmail) ?>" required>
                    </div>
                    <div style="flex: 6;">
                        <label>Contact Number</label>
                        <input type="text" name="mobile_number" value="<?= htmlspecialchars($recordMobileNumber) ?>" inputmode="numeric" pattern="[0-9]+">
                    </div>
                </div>

                <div class="section-header">Account Details</div>
                <div class="d-flex flex-nowrap gap-3 mb-2">
                    <div style="flex: 1;">
                        <label>ID</label>
                        <input type="text" value="<?= htmlspecialchars($recordId) ?>" readonly>
                    </div>
                    <div style="flex: 2;">
                        <label>Role</label>
                        <input type="text" value="<?= htmlspecialchars($recordRoleLabel) ?>" readonly>
                    </div>
                    <div style="flex: 4;">
                        <label>Registration Number</label>
                        <input type="text" value="<?= htmlspecialchars($recordRegistrationNumber ?: 'N/A') ?>" readonly>
                    </div>
                    <div style="flex: 4;">
                        <label>Student Number</label>
                        <input type="text" value="<?= htmlspecialchars($recordStudentNumber ?: 'N/A') ?>" readonly>
                    </div>
                </div>

                <div class="actions">
                    <a href="../../dashboard.php" class="btn btn-cancel">Cancel</a>
                    <button type="submit" name="update" class="btn btn-submit">Update Profile</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function formatNameValue(value) {
            return String(value || '')
                .replace(/\d+/g, '')
                .replace(/\s+/g, ' ')
                .trim()
                .split(' ')
                .filter(Boolean)
                .map(function (segment) {
                    if (!segment) return '';
                    return segment.charAt(0).toUpperCase() + segment.slice(1).toLowerCase();
                })
                .join(' ');
        }

        document.querySelectorAll('.name-field').forEach(function (input) {
            input.addEventListener('keydown', function (event) {
                if (/\d/.test(event.key)) {
                    event.preventDefault();
                }
            });

            input.addEventListener('input', function () {
                input.value = formatNameValue(input.value);
            });
        });

        document.querySelectorAll('input[name="mobile_number"]').forEach(function (field) {
            field.setAttribute('inputmode', 'numeric');
            field.setAttribute('pattern', '[0-9]+');

            field.addEventListener('keydown', function (event) {
                if (event.ctrlKey || event.altKey || event.metaKey) {
                    return;
                }

                const allowedKeys = ['Backspace', 'Delete', 'Tab', 'ArrowLeft', 'ArrowRight', 'Home', 'End', 'Enter'];
                if (allowedKeys.includes(event.key) || /^[0-9]$/.test(event.key)) {
                    return;
                }

                event.preventDefault();
            });

            field.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '');
            });

            field.addEventListener('paste', function (event) {
                const pasted = (event.clipboardData || window.clipboardData)?.getData('text') || '';
                if (!pasted) {
                    return;
                }

                const digits = pasted.replace(/\D/g, '');
                if (digits === '') {
                    event.preventDefault();
                    return;
                }

                event.preventDefault();
                const start = this.selectionStart ?? this.value.length;
                const end = this.selectionEnd ?? this.value.length;
                this.value = this.value.slice(0, start) + digits + this.value.slice(end);
                this.setSelectionRange(this.value.length, this.value.length);
            });
        });
    </script>
</body>
</html> 