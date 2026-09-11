<?php
session_start();
include __DIR__ . '/libraries/include.php';

if (!isset($db) || !$db instanceof PDO) {
    require_once __DIR__ . '/libraries/db.php';
}

if (!isset($_SESSION['office_id'])) {
    $_SESSION['office_id'] = null;
}

$display = new display($db);
$record = $display->getAllByDept();
$admins = $display->getAllAdmin();
$staff = $display->getAllStaff();
$users = $display->getAll();

$Departments = new Departments($db);
$depts = $Departments->getDepartments();

$roles_obj = new roles($db);
$roles_list = $roles_obj->getroles();

$office_id = $_SESSION['office_id'];
$role_id = $_SESSION['role_id'] ?? 0;

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$office_name = $depts[$office_id] ?? null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if (isset($office_name)): ?>
        <title>Dashboard - <?php echo $office_name; ?></title>
    <?php else: ?>
        <title>Dashboard</title>
    <?php endif; ?>
    <!-- Include Bootstrap & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="img/neust_logo.png">
    <style>
        :root {
            --ns-blue: #14369C;
            --ns-blue-deep: #07184F;
            --ns-navy: #050B24;
            --ns-gold: #FFCE00;
            --ns-gold-dark: #DDAF00;
            --ns-orange: #F89318;
            --ns-white: #FFFFFF;
            --ns-offwhite: #F5F7FB;
            --ns-surface: #F3F6FC;
            --ns-border: rgba(148, 163, 184, 0.2);
            --ns-muted: #64748B;
            --ns-dark: #101522;
            --ns-text: #0f172a;
        }

        * { box-sizing: border-box; }

        body {
            position: relative;
            font-family: 'Inter', sans-serif;
            background:
                radial-gradient(circle at top left, rgba(20,54,156,0.12), transparent 30%),
                linear-gradient(180deg, #edf3ff 0%, #f7f9fc 60%, #eef3fa 100%);
            color: var(--ns-text);
        }

        body::before {
            content: "";
            position: fixed;
            inset: 8% 10% 8% 10%;
            background-image: url("img/neust_logo.png");
            background-repeat: no-repeat;
            background-position: center;
            background-size: contain;
            opacity: 0.08;
            pointer-events: none;
            z-index: 0;
        }

        h1, h2, h3, h4, h5, h6, .navbar-brand, .offcanvas-title, .brand-title, .section-title, .hero-title {
            font-family: 'Manrope', 'Inter', sans-serif;
        }

        .bg-custom {
            background: linear-gradient(135deg, var(--ns-blue), var(--ns-blue-deep)) !important;
            border-bottom: 3px solid var(--ns-gold);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1040;
            box-shadow: 0 10px 26px rgba(8, 17, 50, 0.18);
            border-radius: 0 0 18px 18px;
        }

        body {
            padding-top: 72px;
        }

        .brand-mark {
            width: 34px;
            height: 34px;
            object-fit: contain;
            filter: drop-shadow(0 4px 16px rgba(255,206,0,0.25));
        }

        .smart-campus-body {
            background: linear-gradient(180deg, #eef4ff 0%, #f6f9ff 24%, #edf3ff 100%);
        }

        .offcanvas {
            background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(244,247,255,0.96));
            border-right: 1px solid rgba(20,54,156,0.08);
        }

        .offcanvas .nav-pills .nav-link {
            color: #0f172a;
            background: rgba(20,54,156,0.03);
            border: 1px solid rgba(20,54,156,0.08);
            border-radius: 12px;
            font-weight: 700;
        }

        .offcanvas .nav-pills .nav-link.active {
            background: linear-gradient(90deg, rgba(255,206,0,0.18), rgba(20,54,156,0.08));
            border-color: rgba(255,206,0,0.52);
            color: #0d1a2f;
            box-shadow: inset 0 0 0 1px rgba(255,206,0,0.1);
        }

        .offcanvas .nav-pills .nav-link i {
            color: var(--ns-blue);
        }

        .burger-button {
            border: 1px solid rgba(255,206,0,0.75);
            border-radius: 12px;
            width: 42px;
            height: 42px;
            padding: 8px 9px;
            background: rgba(255,206,0,0.12);
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 5px;
            box-shadow: none;
        }

        .burger-button span {
            display: block;
            width: 100%;
            height: 2px;
            background: #ffffff;
            border-radius: 2px;
        }

        .burger-button span {
            display: block;
            width: 100%;
            height: 2px;
            background: white;
            border-radius: 2px;
        }

        .campus-shell {
            display: flex;
            min-height: calc(100vh - 72px);
            max-width: 100%;
            margin: 0;
            padding: 0 10px 18px 10px;
            gap: 10px;
            align-items: stretch;
        }

        .premium-sidebar {
            width: 248px;
            background: linear-gradient(180deg, rgba(17, 27, 61, 0.98), rgba(9, 17, 44, 0.96));
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 22px 22px 22px 22px;
            padding: 12px 10px 12px;
            box-shadow:
                inset 0 0 0 1px rgba(255,255,255,0.04),
                inset 8px 8px 18px rgba(255,255,255,0.03),
                inset -10px -10px 18px rgba(0,0,0,0.22),
                0 18px 26px -24px rgba(0, 0, 0, 0.7);
            border-top: 4px solid var(--ns-gold);
            display: flex;
            flex-direction: column;
            height: calc(100vh - 90px);
            margin-top: 8px;
            position: sticky;
            top: 82px;
            overflow: hidden;
            border-bottom-left-radius: 26px;
            border-bottom-right-radius: 26px;
        }

        .premium-sidebar::before {
            content: "";
            position: absolute;
            left: 12px;
            right: 12px;
            top: -16px;
            height: 30px;
            background: linear-gradient(180deg, rgba(17, 27, 61, 0.96), rgba(9, 17, 44, 0.92));
            border-radius: 0 0 18px 18px;
            border: 1px solid rgba(255,255,255,0.06);
            border-top: none;
            box-shadow: inset 0 8px 12px rgba(255,255,255,0.04), 0 8px 18px rgba(0,0,0,0.18);
            z-index: 0;
        }

        .premium-sidebar::after {
            content: "";
            position: absolute;
            left: 18px;
            right: 18px;
            top: 12px;
            height: 2px;
            background: rgba(255,206,0,0.65);
            border-radius: 999px;
            z-index: 1;
        }

        .premium-sidebar .sidebar-group {
            position: relative;
            z-index: 1;
        }

        .premium-sidebar .brand-block {
            padding: 10px 14px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            margin-bottom: 14px;
        }

        .premium-sidebar .brand-block .brand-title {
            font-size: 1.05rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--ns-white);
            margin: 0;
        }

        .premium-sidebar .brand-tag {
            display: block;
            margin-top: 6px;
            color: rgba(255,206,0,0.9);
            letter-spacing: 0.14em;
            font-size: 0.68rem;
            text-transform: uppercase;
        }

        .sidebar-group {
            padding: 8px 0 0;
            flex: 1;
        }

        .sidebar-label {
            display: block;
            margin: 10px 10px 8px;
            color: rgba(255,255,255,0.6);
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
        }

        .nav-pills {
            background: transparent;
            border-radius: 0;
            padding: 0;
            gap: 4px;
        }

        .nav-pills .nav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            min-height: 58px;
            background: rgba(255,255,255,0.01);
            border: 1px solid rgba(255,255,255,0.04);
            border-radius: 12px;
            color: rgba(255,255,255,0.82);
            font-weight: 700;
            padding: 12px 14px;
            transition: all 0.2s ease;
            text-align: left;
            font-size: 1.06rem;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.01);
        }

        .nav-pills .nav-link.active {
            background: linear-gradient(180deg, rgba(255, 206, 0, 0.20), rgba(255, 206, 0, 0.08));
            border-color: rgba(255,206,0,0.75);
            color: #fff;
            box-shadow:
                inset 0 1px 0 rgba(255,255,255,0.2),
                inset 0 -2px 0 rgba(0,0,0,0.08),
                0 8px 16px rgba(0,0,0,0.18);
        }

        .nav-pills .nav-link i {
            width: 18px;
            text-align: center;
            color: rgba(255,255,255,0.8);
        }

        .nav-pills .nav-link:hover {
            background: rgba(255,255,255,0.04);
            border-color: rgba(255,255,255,0.05);
            color: #fff;
        }

        .nav-pills .nav-link.active {
            background: linear-gradient(90deg, rgba(255,206,0,0.16), rgba(20,54,156,0.28));
            border-color: rgba(255,206,0,0.42);
            color: white;
            box-shadow: inset 3px 0 0 var(--ns-gold);
        }

        .nav-pills .nav-link.active i {
            color: var(--ns-gold);
        }

        .campus-content {
            position: relative;
            z-index: 1;
            flex: 1;
            min-width: 0;
        }

        .tab-content {
            height: 100%;
        }

        .smart-home {
            padding: 0 2px;
        }

        .hero-panel {
            position: relative;
            overflow: hidden;
            border-radius: 28px;
            padding: 28px 28px 24px;
            background:
                radial-gradient(circle at 15% 10%, rgba(255,206,0,0.18), transparent 28%),
                linear-gradient(135deg, var(--ns-blue-deep) 0%, var(--ns-blue) 54%, var(--ns-navy) 100%);
            color: var(--ns-white);
            border: 1px solid rgba(255,255,255,0.08);
            box-shadow: 0 24px 50px -22px rgba(5,11,36,0.8);
            margin-bottom: 24px;
        }

        .hero-panel::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px);
            background-size: 28px 28px;
            opacity: 0.7;
            pointer-events: none;
        }

        .hero-panel::after {
            content: "";
            position: absolute;
            width: 300px;
            height: 300px;
            right: -80px;
            top: -80px;
            border-radius: 50%;
            border: 1px dashed rgba(255,206,0,0.24);
            pointer-events: none;
        }

        .hero-inner {
            position: relative;
            z-index: 1;
        }

        .hero-kicker {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 12px;
            border-radius: 999px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            color: rgba(255,255,255,0.88);
            font-size: 0.72rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .hero-title {
            margin: 18px 0 10px;
            font-size: clamp(2.1rem, 3vw, 3.2rem);
            line-height: 1.08;
            font-weight: 800;
            max-width: 700px;
        }

        .hero-title .accent {
            color: var(--ns-gold);
        }

        .hero-subcopy {
            margin: 0 0 24px;
            max-width: 660px;
            color: rgba(255,255,255,0.7);
            font-size: 1rem;
        }

        .smart-search {
            display: flex;
            align-items: center;
            gap: 12px;
            max-width: 700px;
            background: rgba(255,255,255,0.96);
            border: 1px solid rgba(255,255,255,0.12);
            border-radius: 18px;
            padding: 10px 10px 10px 18px;
            box-shadow: 0 14px 38px -22px rgba(0,0,0,0.45);
        }

        .smart-search i {
            color: var(--ns-blue);
            font-size: 1.1rem;
        }

        .smart-search input {
            border: none;
            outline: none;
            flex: 1;
            background: transparent;
            color: var(--ns-text);
            font-size: 1rem;
            min-width: 0;
        }

        .smart-search .btn-search {
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--ns-gold), #ffd84a);
            color: #0d1a2f;
            padding: 12px 18px;
            font-weight: 800;
            letter-spacing: 0.04em;
        }

        .search-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 18px;
        }

        .search-tags .chip {
            padding: 7px 12px;
            border-radius: 999px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
            color: rgba(255,255,255,0.9);
            font-size: 0.78rem;
        }

        .section-title {
            margin: 0 0 4px;
            font-size: clamp(1.5rem, 2vw, 2rem);
            font-weight: 800;
            color: #10203e;
        }

        .section-subtitle {
            margin: 0 0 18px;
            color: var(--ns-muted);
            font-size: 0.96rem;
        }

        .action-layout {
            margin: 26px 0 28px;
        }

        .smart-card {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 14px;
            min-height: 170px;
            padding: 20px 18px 16px;
            border-radius: 22px;
            background: linear-gradient(180deg, rgba(255,255,255,0.92), rgba(244,247,252,0.88));
            border: 1px solid rgba(15, 23, 42, 0.06);
            box-shadow: 0 18px 32px -28px rgba(13,26,47,0.6);
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
            color: var(--ns-text);
            text-decoration: none;
            cursor: pointer;
        }

        .smart-card:hover {
            transform: translateY(-4px);
            border-color: rgba(255,206,0,0.6);
            box-shadow: 0 20px 32px -26px rgba(20,54,156,0.5);
        }

        .search-tags a {
            text-decoration: none;
        }

        .smart-card .icon-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 58px;
            height: 58px;
            border-radius: 18px;
            background: linear-gradient(135deg, rgba(20,54,156,0.12), rgba(255,206,0,0.18));
            color: var(--ns-blue);
            font-size: 1.5rem;
            box-shadow: inset 0 0 0 1px rgba(20,54,156,0.04);
        }

        .smart-card h4 {
            margin: 0;
            font-size: 1rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            font-weight: 800;
        }

        .smart-card p {
            margin: 0;
            color: var(--ns-muted);
            font-size: 0.9rem;
            line-height: 1.5;
        }

        .smart-card .cta {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--ns-blue);
            padding-top: 2px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1.5fr 0.9fr;
            gap: 22px;
            margin-bottom: 22px;
        }

        .glass-panel {
            background: rgba(255,255,255,0.78);
            border-radius: 26px;
            border: 1px solid rgba(15,23,42,0.06);
            box-shadow: 0 20px 35px -26px rgba(13,26,47,0.55);
            padding: 20px 20px 18px;
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            position: relative;
            overflow: hidden;
        }

        .glass-panel::before {
            content: "";
            position: absolute;
            inset: 0 auto auto 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, var(--ns-blue), var(--ns-gold), transparent);
            opacity: 0.9;
        }

        .panel-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
            gap: 12px;
        }

        .panel-head .label {
            color: var(--ns-blue);
            font-size: 0.72rem;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            font-weight: 800;
        }

        .panel-head .action-link {
            color: var(--ns-blue);
            font-weight: 700;
            text-decoration: none;
            font-size: 0.8rem;
        }

        .campus-preview {
            position: relative;
            min-height: 260px;
            border-radius: 20px;
            background:
                linear-gradient(180deg, rgba(5,11,36,0.94), rgba(7,24,79,0.9)),
                radial-gradient(circle at center, rgba(255,206,0,0.1), transparent 50%);
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.08);
        }

        .campus-preview::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,0.05) 1px, transparent 1px);
            background-size: 26px 26px;
            opacity: 0.6;
        }

        .map-node {
            position: absolute;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--ns-gold);
            box-shadow: 0 0 0 4px rgba(255,206,0,0.17);
        }

        .map-node.blue { background: #61a6ff; box-shadow: 0 0 0 4px rgba(97,166,255,0.15); }
        .map-node.orange { background: var(--ns-orange); box-shadow: 0 0 0 4px rgba(248,147,24,0.15); }

        .campus-path {
            position: absolute;
            inset: 0;
        }

        .campus-path svg {
            width: 100%;
            height: 100%;
        }

        .path-line {
            fill: none;
            stroke: var(--ns-gold);
            stroke-width: 3;
            stroke-linecap: round;
            stroke-dasharray: 10 12;
            opacity: 0.9;
        }

        .path-line.alt { stroke: rgba(97,166,255,0.8); stroke-width: 2.5; }

        .campus-preview .destination-badge {
            position: absolute;
            right: 18px;
            top: 18px;
            padding: 8px 12px;
            border-radius: 999px;
            background: rgba(255,206,0,0.14);
            border: 1px solid rgba(255,206,0,0.3);
            color: #ffe882;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .campus-preview .destination-badge .pin {
            color: var(--ns-gold);
            margin-right: 6px;
        }

        .campus-stats {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .campus-stat {
            padding: 16px 12px 14px;
            border-radius: 18px;
            background: linear-gradient(180deg, rgba(245,247,251,0.92), rgba(238,243,250,0.96));
            border: 1px solid rgba(14,30,57,0.04);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.7);
        }

        .campus-stat strong {
            display: block;
            font-size: 1.02rem;
            line-height: 1.1;
            color: var(--ns-blue);
            margin-bottom: 6px;
        }

        .campus-stat span {
            color: var(--ns-muted);
            font-size: 0.62rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-weight: 700;
        }

        .next-step-card {
            display: flex;
            flex-direction: column;
            height: 100%;
            background: linear-gradient(180deg, rgba(255,255,255,0.96), rgba(245,247,251,0.96));
            border-radius: 24px;
            border: 1px solid rgba(15,23,42,0.05);
            padding: 18px 18px 16px;
            min-height: 260px;
        }

        .mini-label {
            color: var(--ns-blue);
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        .next-step-card h3 {
            margin: 14px 0 10px;
            font-size: 1.2rem;
            line-height: 1.35;
            color: #141d33;
        }

        .step-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            align-self: flex-start;
            padding: 7px 10px;
            border-radius: 999px;
            background: rgba(20,54,156,0.08);
            color: var(--ns-blue);
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .progress-bar {
            height: 10px;
            background: rgba(15,23,42,0.06);
            border-radius: 999px;
            overflow: hidden;
            margin: 16px 0 14px;
        }

        .progress-bar span {
            display: block;
            height: 100%;
            width: 45%;
            background: linear-gradient(90deg, var(--ns-gold), var(--ns-orange));
            border-radius: inherit;
        }

        .next-step-card .meta {
            color: var(--ns-muted);
            font-size: 0.88rem;
            line-height: 1.6;
        }

        .primary-btn,
        .secondary-btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: fit-content;
            border-radius: 12px;
            padding: 10px 14px;
            font-weight: 800;
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .primary-btn {
            background: linear-gradient(135deg, var(--ns-gold), #ffd84a);
            color: #0d1a2f;
            box-shadow: 0 10px 18px -14px rgba(255,206,0,0.8);
        }

        .secondary-btn {
            border: 1px solid rgba(20,54,156,0.12);
            color: var(--ns-blue);
            background: rgba(20,54,156,0.03);
        }

        .primary-btn:hover, .secondary-btn:hover {
            transform: translateY(-1px);
        }

        .result-panel {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }

        .results-card {
            background: rgba(255,255,255,0.84);
            border-radius: 22px;
            border: 1px solid rgba(15,23,42,0.06);
            padding: 18px 18px 16px;
        }

        .results-card h4 {
            margin: 0 0 12px;
            color: #101a2d;
            font-size: 0.9rem;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            font-weight: 800;
        }

        .result-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .result-list li {
            display: grid;
            grid-template-columns: 22px 1fr auto;
            align-items: center;
            gap: 10px;
            padding: 10px 10px;
            border-radius: 12px;
            background: rgba(245,247,251,0.9);
            border: 1px solid rgba(15,23,42,0.04);
        }

        .result-list .type {
            width: 22px;
            height: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 0.8rem;
            background: rgba(20,54,156,0.08);
            color: var(--ns-blue);
        }

        .result-list .detail strong {
            display: block;
            font-size: 0.95rem;
            color: var(--ns-text);
        }

        .result-list .detail span {
            color: var(--ns-muted);
            font-size: 0.76rem;
        }

        .badge-tag {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            padding: 4px 8px;
            border-radius: 999px;
            color: var(--ns-blue);
            background: rgba(20,54,156,0.08);
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.04em;
        }

        .card-stack {
            display: grid;
            gap: 16px;
        }

        @media (max-width: 1100px) {
            .campus-shell {
                display: block;
            }

            .premium-sidebar {
                width: 100%;
                margin-bottom: 18px;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .campus-shell {
                padding: 12px 12px 24px;
            }

            .hero-panel {
                padding: 20px 18px 18px;
            }

            .smart-search {
                flex-wrap: wrap;
                padding: 12px;
            }

            .smart-search .btn-search {
                width: 100%;
            }

            #map-preview-shell,
            #map-preview-shell iframe {
                min-height: 470px !important;
                height: 64vh !important;
            }

            .glass-panel {
                padding: 18px 14px 14px !important;
            }

            .navbar-brand {
                font-size: 0.9rem;
            }

            .campus-stat {
                padding: 18px 10px 16px;
            }

            .campus-stat strong {
                font-size: 1.35rem;
            }

            .campus-stat span {
                font-size: 0.58rem;
            }

            .result-panel {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="smart-campus-body">
    <nav class="navbar navbar-expand-lg navbar-dark bg-custom fixed-top shadow-sm">
        <div class="container-fluid">
            <span class="navbar-brand fw-bold d-flex align-items-center">
                <img src="img/neust_logo.png" alt="NEUST" class="brand-mark me-2">
                NEUST Smart Campus
            </span>

            <div class="ms-auto d-flex align-items-center">
                <span class="text-white d-none d-md-inline me-3 small">
                    <i class="fa-solid fa-circle-user me-1"></i> <?= $_SESSION['first_name'] ?>
                </span>
                <a href="functions/logout.php" class="btn btn-danger btn-sm">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
                <button class="navbar-toggler ms-2 burger-button d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMobile" aria-controls="sidebarMobile" aria-label="Toggle menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </nav>

    <div class="campus-shell">
        <aside class="premium-sidebar d-none d-lg-block">
            <div class="brand-block">
                <h1 class="brand-title">NEUST</h1>
                <span class="brand-tag">Campus Navigation</span>
            </div>

            <div class="sidebar-group">
                <div class="nav flex-column nav-pills" id="pills-tab" role="tablist">
                    <button class="nav-link active text-start" id="pills-home-tab" data-bs-toggle="pill" data-bs-target="#pills-home" type="button">
                        <i class="fa-solid fa-house"></i> Home
                    </button>
                    <button class="nav-link text-start" id="pills-services-tab" data-bs-toggle="pill" data-bs-target="#pills-services" type="button">
                        <i class="fa-solid fa-file-lines"></i> Services
                    </button>
                </div>
            </div>

            <?php if ($role_id == 2 || $role_id == 3 || $office_id == 9): ?>
                <div class="sidebar-group">
                    <span class="sidebar-label">Management</span>
                    <div class="nav flex-column nav-pills">
                        <?php if ($office_id == 9): ?>
                            <button class="nav-link text-start" id="pills-notif-tab" data-bs-toggle="pill" data-bs-target="#pills-notif" type="button">
                                <i class="fa-solid fa-bullhorn"></i> Announcements
                            </button>
                        <?php endif; ?>
                        <?php if ($role_id == 2): ?>
                            <button class="nav-link text-start" id="pills-dept-tab" data-bs-toggle="pill" data-bs-target="#pills-dept" type="button">
                                <i class="fa-solid fa-users-gear"></i> My Staff
                            </button>
                        <?php endif; ?>
                        <?php if ($role_id == 3): ?>
                            <button class="nav-link text-start" id="pills-staff-tab" data-bs-toggle="pill" data-bs-target="#pills-staff" type="button">
                                <i class="fa-solid fa-user-tie"></i> Staff List
                            </button>
                            <button class="nav-link text-start" id="pills-admin-tab" data-bs-toggle="pill" data-bs-target="#pills-admin" type="button">
                                <i class="fa-solid fa-user-shield"></i> Admins
                            </button>
                            <button class="nav-link text-start" id="pills-users-tab" data-bs-toggle="pill" data-bs-target="#pills-users" type="button">
                                <i class="fa-solid fa-users"></i> All Users
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </aside>

        <div class="offcanvas offcanvas-start" data-bs-backdrop="true" tabindex="-1" id="sidebarMobile" aria-labelledby="sidebarMobileLabel">
            <div class="offcanvas-header border-bottom">
                <h5 class="offcanvas-title fw-bold" id="sidebarMobileLabel">Navigation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close menu"></button>
            </div>
            <div class="offcanvas-body">
                <div class="nav flex-column nav-pills" id="pills-tab-mobile" role="tablist">
                    <button class="nav-link active mb-2 text-start" id="pills-home-tab-mobile" data-bs-toggle="pill" data-bs-target="#pills-home" type="button">
                        <i class="fa-solid fa-house me-2"></i> Home
                    </button>
                    <button class="nav-link mb-2 text-start" id="pills-services-tab-mobile" data-bs-toggle="pill" data-bs-target="#pills-services" type="button">
                        <i class="fa-solid fa-file-lines me-2"></i> Services
                    </button>
                    <?php if ($office_id == 9): ?>
                        <button class="nav-link mb-2 text-start" id="pills-notif-tab-mobile" data-bs-toggle="pill" data-bs-target="#pills-notif" type="button">
                            <i class="fa-solid fa-bullhorn me-2"></i> Announcements
                        </button>
                    <?php endif; ?>
                    <?php if ($role_id == 2): ?>
                        <button class="nav-link mb-2 text-start" id="pills-dept-tab-mobile" data-bs-toggle="pill" data-bs-target="#pills-dept" type="button">
                            <i class="fa-solid fa-users-gear me-2"></i> My Staff
                        </button>
                    <?php endif; ?>
                    <?php if ($role_id == 3): ?>
                        <button class="nav-link mb-2 text-start" id="pills-staff-tab-mobile" data-bs-toggle="pill" data-bs-target="#pills-staff" type="button">
                            <i class="fa-solid fa-user-tie me-2"></i> Staff List
                        </button>
                        <button class="nav-link mb-2 text-start" id="pills-admin-tab-mobile" data-bs-toggle="pill" data-bs-target="#pills-admin" type="button">
                            <i class="fa-solid fa-user-shield me-2"></i> Admins
                        </button>
                        <button class="nav-link mb-2 text-start" id="pills-users-tab-mobile" data-bs-toggle="pill" data-bs-target="#pills-users" type="button">
                            <i class="fa-solid fa-users me-2"></i> All Users
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <main class="campus-content">
            <div class="tab-content" id="pills-tabContent">
                <div class="tab-pane fade show active smart-home" id="pills-home">
                    <section class="dashboard-grid" style="grid-template-columns: 1fr; margin-bottom: 0;">
                        <div class="glass-panel" style="padding:20px 20px 16px;">
                            <div class="panel-head">
                                <span class="label">Campus navigation</span>
                            </div>

                            <div id="map-preview-shell" style="overflow:hidden; border-radius:18px; background:#0b1633; min-height:680px;">
                                <iframe src="map.php" title="NEUST campus navigation map" style="width:100%; min-height:680px; border:0; background:#fff; display:block;"></iframe>
                            </div>

                            <div class="campus-stats">
                                <div class="campus-stat">
                                    <strong>3</strong>
                                    <span>Buildings</span>
                                </div>
                                <div class="campus-stat">
                                    <strong>42</strong>
                                    <span>Destinations</span>
                                </div>
                                <div class="campus-stat">
                                    <strong>6 min</strong>
                                    <span>Average route</span>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="tab-pane fade" id="pills-services">
                    <div class="container-fluid py-4">
                        <div class="mb-4">
                            <span class="mini-label">Quick access</span>
                            <h3 class="section-title mt-2 mb-0">Campus services</h3>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6 col-xl-4">
                                <a href="crud/read/view.php" class="smart-card">
                                    <div class="icon-wrap"><i class="fa-solid fa-file-circle-check"></i></div>
                                    <div>
                                        <h4>Documents</h4>
                                        <p>Transcript, enrollment, and certificate-related help desks.</p>
                                    </div>
                                    <span class="cta">View <i class="fa-solid fa-arrow-right"></i></span>
                                </a>
                            </div>
                            <div class="col-md-6 col-xl-4">
                                <a href="#" onclick="return false;" class="smart-card">
                                    <div class="icon-wrap"><i class="fa-solid fa-user-doctor"></i></div>
                                    <div>
                                        <h4>Student support</h4>
                                        <p>Medical, guidance, and wellness services across the campus.</p>
                                    </div>
                                    <span class="cta">Assist <i class="fa-solid fa-arrow-right"></i></span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="pills-notif">
                    <div class="card">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 fw-bold text-primary"><i class="fa-solid fa-tower-broadcast me-2"></i>Send Broadcast</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold">Target Audience</label>
                                    <select id="target_office" class="form-select">
                                        <option value="global">All Departments</option>
                                        <?php foreach ($depts as $id => $name): ?>
                                            <option value="<?= $id ?>"><?= $name ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Your Message</label>
                                    <input type="text" id="msg" class="form-control" placeholder="What would you like to announce?">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button class="btn btn-primary w-100" onclick="sendNotif()">
                                        <i class="fa-solid fa-paper-plane me-1"></i> Send
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php
                $sections = [
                    'pills-dept' => ['title' => "Staff Under " . $office_name, 'data' => $record, 'type' => 'dept'],
                    'pills-staff' => ['title' => "Master Staff List", 'data' => $staff, 'type' => 'staff'],
                    'pills-admin' => ['title' => "System Administrators", 'data' => $admins, 'type' => 'admin'],
                    'pills-users' => ['title' => "All Registered Users", 'data' => $users, 'type' => 'users']
                ];

                foreach ($sections as $id => $sec):
                    ?>
                    <div class="tab-pane fade" id="<?= $id ?>">
                        <div class="card">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                                <h5 class="mb-0 fw-bold"><?= $sec['title'] ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="table_<?= $id ?>" class="table table-hover w-100">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Full Name</th>
                                                <th>Email</th>
                                                <?php if ($sec['type'] == 'users') echo "<th>Role</th>"; ?>
                                                <?php if (in_array($sec['type'], ['staff', 'admin'])) echo "<th>Office</th>"; ?>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($sec['data'] as $row): ?>
                                                <tr>
                                                    <td class="fw-bold text-primary">#<?= $row['user_id'] ?></td>
                                                    <td><?= "{$row['first_name']} {$row['last_name']}" ?></td>
                                                    <td><?= $row['email'] ?></td>
                                                    <?php if ($sec['type'] == 'users'): ?>
                                                        <td><span class="badge bg-secondary"><?= $roles_list[$row['role_id']] ?? 'Unknown' ?></span></td>
                                                    <?php endif; ?>
                                                    <?php if (in_array($sec['type'], ['staff', 'admin'])): ?>
                                                        <td class="small text-muted"><?= $depts[$row['office_id']] ?? 'N/A' ?></td>
                                                    <?php endif; ?>
                                                    <td class="text-center">
                                                        <div class="btn-group">
                                                            <a href="crud/read/view.php?user_id=<?= $row['user_id'] ?>" class="btn btn-sm btn-light text-info" title="View"><i class="fa-solid fa-eye"></i></a>
                                                            <a href="crud/update/edit.php?user_id=<?= $row['user_id'] ?>" class="btn btn-sm btn-light text-primary" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                                            <button class="btn btn-sm btn-light text-danger" data-bs-toggle="modal" data-bs-target="#del_<?= $id ?>_<?= $row['user_id'] ?>" title="Delete">
                                                                <i class="fa-solid fa-trash"></i>
                                                            </button>
                                                        </div>
                                                        <div class="modal fade" id="del_<?= $id ?>_<?= $row['user_id'] ?>" tabindex="-1">
                                                            <div class="modal-dialog modal-dialog-centered">
                                                                <div class="modal-content">
                                                                    <?php include 'crud/delete/delete.php'; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>

    <script>
        const notifSound = new Audio('assets/audio/notify.mp3');
        if (window.Notification && Notification.permission !== "granted") { Notification.requestPermission(); }

        const pusher = new Pusher('ee626886154497d0f929', { cluster: 'ap1', forceTLS: true });
        const globalChan = pusher.subscribe('office-global');
        const officeChan = pusher.subscribe('office-<?php echo $office_id; ?>');

        const handleNotif = (data) => {
            notifSound.play().catch(() => { });
            if (Notification.permission === "granted") {
                new Notification(`Message from ${data.from}`, { body: data.message, icon: "assets/img/logo.png" });
            } else {
                alert(`NEWS: ${data.message}`);
            }
        };

        globalChan.bind('new-announcement', handleNotif);
        officeChan.bind('new-announcement', handleNotif);

        function sendNotif() {
            const m = document.getElementById('msg').value;
            const t = document.getElementById('target_office').value;
            if (!m) return alert("Message is empty");

            const params = new URLSearchParams({ message: m, target: t });
            fetch('functions/send_notif.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params
            }).then(() => {
                document.getElementById('msg').value = '';
                alert("Broadcast sent successfully!");
            });
        }

        $(document).ready(function () {
            const commonOptions = {
                pageLength: 10,
                language: { search: "_INPUT_", searchPlaceholder: "Search records..." },
                dom: '<"d-flex justify-content-between align-items-center mb-3"lf>rt<"d-flex justify-content-between align-items-center mt-3"ip>'
            };
            $('table.table').DataTable(commonOptions);
        });
    </script>
</body>
</html>