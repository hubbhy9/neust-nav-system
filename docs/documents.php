<?php
require_once __DIR__ . '/viewdocxf.php';

$selectedOffice = isset($_GET['office']) ? trim((string) $_GET['office']) : '';
$currentPage = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$itemsPerPage = 6;

$officeMap = [
    'admission' => ['label' => 'Office of Admission and Registration', 'color' => 'red'],
    'guidance' => ['label' => 'Guidance and Counselling Office', 'color' => '#16a34a'],
    'student-affairs' => ['label' => 'Office of Student Affairs / Scholarship and Financial Assistance', 'color' => '#2563eb'],
    'library' => ['label' => 'University Library Services', 'color' => '#0891b2'],
    'discipline' => ['label' => 'Office of Student Discipline and Development', 'color' => 'black'],
    'accounting' => ['label' => 'Accounting Office', 'color' => '#9333ea'],
    'cashier' => ['label' => 'Cashier Office', 'color' => '#f59e0b'],
    'corporate' => ['label' => 'Corporate and Community Affairs Office', 'color' => '#c026d3'],
    'health' => ['label' => 'Health Services', 'color' => '#65a30d'],
    'security' => ['label' => 'Civil Security Unit', 'color' => '#b91c1c'],
    'mis' => ['label' => 'Management Information Systems', 'color' => '#4f46e5'],
    'other' => ['label' => 'Other Services', 'color' => 'gray'],
];

$processes = [];
foreach ($docs as $doc) {
    $officeName = trim((string) ($doc['office_name'] ?? ''));
    $officeKey = 'other';
    $officeLower = strtolower($officeName);

    if (str_contains($officeLower, 'admission') || str_contains($officeLower, 'registration')) {
        $officeKey = 'admission';
    } elseif (str_contains($officeLower, 'guidance') || str_contains($officeLower, 'counsel')) {
        $officeKey = 'guidance';
    } elseif (str_contains($officeLower, 'student affairs') || str_contains($officeLower, 'scholarship') || str_contains($officeLower, 'financial assistance')) {
        $officeKey = 'student-affairs';
    } elseif (str_contains($officeLower, 'library')) {
        $officeKey = 'library';
    } elseif (str_contains($officeLower, 'discipline') || str_contains($officeLower, 'development')) {
        $officeKey = 'discipline';
    } elseif (str_contains($officeLower, 'accounting')) {
        $officeKey = 'accounting';
    } elseif (str_contains($officeLower, 'cashier')) {
        $officeKey = 'cashier';
    } elseif (str_contains($officeLower, 'corporate') || str_contains($officeLower, 'business affairs') || str_contains($officeLower, 'community')) {
        $officeKey = 'corporate';
    } elseif (str_contains($officeLower, 'health')) {
        $officeKey = 'health';
    } elseif (str_contains($officeLower, 'security')) {
        $officeKey = 'security';
    } elseif (str_contains($officeLower, 'information system') || str_contains($officeLower, 'mis')) {
        $officeKey = 'mis';
    }

    $legend = $officeMap[$officeKey] ?? $officeMap['other'];
    $requirements = [];
    foreach (($reqByDoc[$doc['id']] ?? []) as $req) {
        $name = trim((string) ($req['requirement_name'] ?? ''));
        $secure = trim((string) ($req['where_to_secure'] ?? ''));
        $text = $name;
        if ($secure !== '') {
            $text .= ' — ' . $secure;
        }
        if ($text !== '') {
            $requirements[] = $text;
        }
    }

    $stepRows = $stepsByDoc[$doc['id']] ?? [];
    $steps = [];
    foreach ($stepRows as $step) {
        $stepText = trim((string) ($step['agency_action'] ?? ''));
        if ($stepText === '') {
            $stepText = trim((string) ($step['client_step'] ?? ''));
        }
        if ($stepText !== '') {
            $steps[] = $stepText;
        }
    }

    $persons = [];
    foreach ($stepRows as $step) {
        $person = trim((string) ($step['person_responsible'] ?? ''));
        if ($person !== '' && !in_array($person, $persons, true)) {
            $persons[] = $person;
        }
    }

    $processes[] = [
        'process_id' => (int) ($doc['id'] ?? 0),
        'title' => (string) ($doc['title'] ?? 'Untitled document'),
        'who_may_avail' => (string) ($doc['who_may_avail'] ?? ''),
        'total_fee' => 0,
        'total_time' => '',
        'display_office' => $officeName !== '' ? $officeName : $legend['label'],
        'legend' => [
            'key' => $officeKey,
            'label' => $legend['label'],
            'color' => $legend['color'],
        ],
        'requirements' => $requirements,
        'steps' => $steps,
        'persons' => $persons,
    ];
}

if ($selectedOffice !== '' && isset($officeMap[$selectedOffice])) {
    $processes = array_values(array_filter($processes, function ($process) use ($selectedOffice) {
        return ($process['legend']['key'] ?? 'other') === $selectedOffice;
    }));
}

$totalProcesses = count($processes);
$totalPages = $totalProcesses > 0 ? (int) ceil($totalProcesses / $itemsPerPage) : 1;
$currentPage = min($currentPage, max(1, $totalPages));
$offset = ($currentPage - 1) * $itemsPerPage;
$paginatedProcesses = array_slice($processes, $offset, $itemsPerPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Processes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .document-process-page {
            --ns-blue: #14369C;
            --ns-blue-deep: #07184F;
            --ns-gold: #FFCE00;
            --ns-text: #0f172a;
            --ns-muted: #64748B;
            --ns-surface: #f5f7fb;
            --ns-light: #eef4ff;
            --ns-success: #1d9b4d;
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(180deg, #edf3ff 0%, #f7f9fc 60%, #eef3fa 100%);
            color: var(--ns-text);
        }

        .document-process-page .service-topbar {
            position: sticky;
            top: 0;
            z-index: 1100;
            background: linear-gradient(135deg, var(--ns-blue-deep), var(--ns-blue));
            box-shadow: 0 10px 30px rgba(7, 24, 79, 0.18);
        }

        .document-process-page .service-topbar-inner {
            max-width: 1380px;
            margin: 0 auto;
            padding: 0.85rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .document-process-page .service-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: #fff;
            font-weight: 800;
            letter-spacing: 0.02em;
        }

        .document-process-page .service-brand-mark {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.12);
            color: var(--ns-gold);
        }

        .document-process-page .tracking-wrap {
            max-width: 1120px;
            margin: 0 420px 50px auto;
            padding: 30px 20px 60px;
        }

        .document-process-page .header-card {
            background: linear-gradient(135deg, var(--ns-blue), var(--ns-blue-deep));
            color: white;
            border-radius: 20px;
            min-height: 110px;
            padding: 18px 28px;
            box-shadow: 0 18px 40px rgba(9, 17, 44, 0.12);
            transition: background 0.2s ease;
            display: flex;
            align-items: center;
        }

        .document-process-page .header-card h2 {
            margin: 0;
            font-weight: 800;
            line-height: 1.35;
            max-width: 100%;
        }

        .document-process-page .header-card p {
            margin: 10px 0 0;
            color: rgba(255,255,255,0.82);
            font-size: 1rem;
        }

        .document-process-page .process-list {
            margin-top: 20px;
            display: grid;
            grid-template-columns: repeat(2, minmax(260px, 1fr));
            gap: 22px;
        }

        .document-process-page .legend-sidebar {
            position: fixed;
            top: 0;
            right: 0;
            z-index: 1055;
            width: 380px;
            height: 100vh;
            padding: 20px 18px;
            overflow: hidden;
            background: transparent;
            box-shadow: none;
        }

        .document-process-page .legend-sidebar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }

        .document-process-page .legend-title {
            margin: 0;
            color: var(--ns-blue-deep);
            font-size: 0.96rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            font-weight: 800;
            white-space: nowrap;
        }

        .document-process-page .legend-title i {
            color: var(--ns-blue);
            margin-right: 6px;
        }

        .document-process-page .legend-subtitle {
            margin: -8px 0 16px;
            color: var(--ns-muted);
            font-size: 0.78rem;
            line-height: 1.4;
        }

        .document-process-page .legend-items {
            display: grid;
            gap: 7px;
        }

        .document-process-page .legend-item,
        .document-process-page .office-heading {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--ns-text);
            font-size: 0.88rem;
            font-weight: 700;
        }

        .document-process-page .legend-item {
            align-items: flex-start;
            line-height: 1.4;
        }

        .document-process-page .legend-item-link {
            position: relative;
            justify-content: flex-start;
            padding: 7px 10px;
            margin: 0;
            border-radius: 10px;
            border: 1px solid color-mix(in srgb, var(--legend-color) 48%, transparent);
            text-decoration: none;
            transition: background 0.2s ease, border-color 0.2s ease, transform 0.2s ease;
        }

        .document-process-page .legend-item-link:hover,
        .document-process-page .legend-item-link.active {
            background: var(--ns-light);
            color: var(--ns-blue-deep);
            border-color: var(--legend-color);
            transform: translateX(3px);
        }

        .document-process-page .legend-item-link.active {
            box-shadow: inset 4px 0 0 var(--legend-color);
        }

        .document-process-page .legend-all-link {
            background: var(--ns-blue);
            border-color: var(--ns-blue-deep);
            color: white;
            box-shadow: 0 5px 14px rgba(20,54,156,0.2);
        }

        .document-process-page .legend-all-link:hover,
        .document-process-page .legend-all-link.active {
            background: var(--ns-blue-deep);
            border-color: var(--ns-blue-deep);
            color: white;
        }

        .document-process-page .legend-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            flex: 0 0 13px;
            background: var(--legend-color);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--legend-color) 14%, transparent);
        }

        .document-process-page .office-heading {
            grid-column: 1 / -1;
            margin-top: 6px;
            padding: 10px 4px 0;
            color: var(--ns-blue-deep);
            font-size: 0.92rem;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .document-process-page .office-map-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            min-height: 28px;
            padding: 4px 8px;
            margin-left: auto;
            border: 1px solid rgba(20,54,156,0.18);
            border-radius: 8px;
            background: #eef4ff;
            color: var(--ns-blue);
            cursor: pointer;
            transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }

        .document-process-page .office-map-btn:hover {
            background: var(--ns-blue);
            color: white;
            transform: translateY(-1px);
        }

        .document-process-page .results-status {
            margin: 24px 0 -4px;
            color: var(--ns-muted);
            font-size: 0.86rem;
        }

        .document-process-page .pagination-wrap {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin: 18px 0 4px;
            flex-wrap: wrap;
        }

        .document-process-page .page-link-btn {
            min-width: 54px;
            min-height: 52px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            border: 1px solid rgba(20,54,156,0.24);
            background: rgba(255,255,255,0.86);
            color: var(--ns-blue-deep);
            text-decoration: none;
            padding: 8px 14px;
            font-size: 1rem;
            font-weight: 800;
            box-shadow: 0 4px 10px rgba(20,54,156,0.04);
            transition: background 0.2s ease, color 0.2s ease, transform 0.2s ease;
        }

        .document-process-page .pagination-top {
            justify-content: flex-end;
            margin-top: 12px;
        }

        .document-process-page .pagination-top .page-link-btn {
            min-width: 44px;
            min-height: 42px;
            padding: 8px 12px;
        }

        .document-process-page .page-link-btn:hover {
            background: #e8efff;
            border-color: var(--ns-blue);
            color: var(--ns-blue-deep);
            transform: translateY(-1px);
        }

        .document-process-page .page-link-btn.active {
            background: var(--ns-blue);
            border-color: var(--ns-blue);
            color: white;
            box-shadow: 0 8px 16px rgba(20,54,156,0.2);
        }

        .document-process-page .process-card {
            background: rgba(255,255,255,0.68);
            border: 2px solid color-mix(in srgb, var(--legend-color) 42%, white);
            border-radius: 18px;
            padding: 12px 14px 12px;
            box-shadow: 0 8px 22px rgba(20, 54, 156, 0.05);
            min-height: 150px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: space-between;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            text-decoration: none;
            color: inherit;
            cursor: pointer;
        }

        .document-process-page .process-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 24px rgba(20, 54, 156, 0.08);
        }

        .document-process-page .process-card-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            background: color-mix(in srgb, var(--legend-color) 13%, white);
            color: var(--legend-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
            border: 1px solid color-mix(in srgb, var(--legend-color) 28%, white);
            margin-bottom: 10px;
        }

        .document-process-page .process-card-content {
            width: 100%;
            min-width: 0;
        }

        .document-process-page .process-title {
            color: var(--ns-blue-deep);
            font-size: clamp(0.95rem, 1.2vw, 1.25rem);
            font-weight: 800;
            margin: 0 0 6px;
            line-height: 1.18;
            letter-spacing: 0.02em;
        }

        .document-process-page .process-description {
            color: var(--ns-muted);
            font-size: 0.85rem;
            line-height: 1.35;
            margin: 0;
        }

        .document-process-page .process-cta {
            margin-top: 10px;
            display: inline-block;
            color: var(--ns-blue);
            text-decoration: none;
            font-weight: 800;
            letter-spacing: 0.02em;
            text-transform: uppercase;
            font-size: 0.78rem;
        }

        .document-process-page .process-cta i {
            margin-left: 8px;
        }

        .document-process-page .meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 18px;
        }

        .document-process-page .meta-pill {
            background: var(--ns-light);
            color: var(--ns-blue-deep);
            border-radius: 999px;
            padding: 7px 12px;
            font-size: 0.83rem;
            font-weight: 700;
        }

        .document-process-page .section-title {
            font-size: 0.9rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--ns-muted);
            font-weight: 800;
            margin: 18px 0 10px;
        }

        .document-process-page .checklist,
        .document-process-page .step-list,
        .document-process-page .person-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            gap: 10px;
        }

        .document-process-page .checklist li,
        .document-process-page .step-list li,
        .document-process-page .person-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #f8faff;
            border-radius: 12px;
            padding: 12px 14px;
            color: var(--ns-text);
            line-height: 1.5;
        }

        .document-process-page .checklist li i,
        .document-process-page .step-list li i,
        .document-process-page .person-list li i {
            color: var(--ns-success);
            margin-top: 3px;
        }

        .document-process-page .requirement-highlight-text {
            display: inline;
            padding: 3px 6px;
            border-radius: 5px;
            background: #fff0a8;
            color: #6b4b00;
            font-weight: 800;
            box-shadow: inset 0 -2px 0 #f2b705;
        }

        .document-process-page .empty-state {
            background: white;
            border: 1px solid rgba(15,23,42,0.08);
            border-radius: 18px;
            padding: 30px 24px;
            color: var(--ns-muted);
            text-align: center;
            margin-top: 24px;
        }

        @media (max-width: 768px) {
            .document-process-page .process-list {
                grid-template-columns: 1fr;
            }

            .document-process-page .pagination-wrap {
                justify-content: flex-start;
                gap: 6px;
                margin: 12px 0 8px;
            }

            .document-process-page .pagination-wrap .page-link-btn {
                min-width: 40px;
                min-height: 40px;
                padding: 6px 10px;
                border-radius: 8px;
                font-size: 0.88rem;
            }

            .document-process-page .pagination-top {
                justify-content: flex-start;
            }

            .document-process-page .tracking-wrap {
                margin: 0 auto;
            }

            .document-process-page .legend-sidebar {
                position: static;
                width: auto;
                height: auto;
                margin-bottom: 20px;
                border-radius: 16px;
                box-shadow: none;
                overflow: visible;
            }

            .document-process-page .process-card {
                padding: 18px;
            }
        }
    </style>
</head>
<body class="document-process-page">
    <div class="tracking-wrap">
        <?php
        $headerTitle = 'Document Processes';
        $headerColor = 'var(--ns-blue)';
        $headerColorDeep = 'var(--ns-blue-deep)';

        if ($selectedOffice !== '') {
            foreach ([
                ['key' => 'admission', 'label' => 'Office of Admission and Registration', 'color' => 'red'],
                ['key' => 'guidance', 'label' => 'Guidance and Counselling Office', 'color' => '#16a34a'],
                ['key' => 'student-affairs', 'label' => 'Office of Student Affairs / Scholarship and Financial Assistance', 'color' => '#2563eb'],
                ['key' => 'library', 'label' => 'University Library Services', 'color' => '#0891b2'],
                ['key' => 'discipline', 'label' => 'Office of Student Discipline and Development', 'color' => 'black'],
                ['key' => 'accounting', 'label' => 'Accounting Office', 'color' => '#9333ea'],
                ['key' => 'cashier', 'label' => 'Cashier Office', 'color' => '#f59e0b'],
                ['key' => 'corporate', 'label' => 'Corporate and Community Affairs Office', 'color' => '#c026d3'],
                ['key' => 'health', 'label' => 'Health Services', 'color' => '#65a30d'],
                ['key' => 'security', 'label' => 'Civil Security Unit', 'color' => '#b91c1c'],
                ['key' => 'mis', 'label' => 'Management Information Systems', 'color' => '#4f46e5'],
                ['key' => 'other', 'label' => 'Other Services', 'color' => 'gray'],
            ] as $legendItem) {
                if ($legendItem['key'] === $selectedOffice) {
                    $headerTitle = htmlspecialchars((string) $legendItem['label'], ENT_QUOTES, 'UTF-8') . ' Documents';
                    $headerColor = $legendItem['color'];
                    $headerColorDeep = $legendItem['color'];
                    break;
                }
            }
        }
        ?>
        <div class="document-process-page header-card d-flex justify-content-between align-items-center flex-wrap gap-3" style="background: linear-gradient(135deg, <?= htmlspecialchars($headerColor, ENT_QUOTES, 'UTF-8'); ?>, <?= htmlspecialchars($headerColorDeep, ENT_QUOTES, 'UTF-8'); ?>);">
            <div>
                <h2><i class="fa-solid fa-file-lines me-2"></i><?= $headerTitle; ?></h2>
            </div>
        </div>

        <aside class="document-process-page legend-sidebar" aria-label="Document office legend">
            <div class="legend-sidebar-header">
                <div class="legend-title"><i class="fa-solid fa-layer-group"></i>Document Office Legend</div>
            </div>
            <div class="legend-items">
                <a class="legend-item legend-item-link legend-all-link <?= $selectedOffice === '' ? 'active' : ''; ?>" href="documents.php" aria-current="<?= $selectedOffice === '' ? 'page' : 'false'; ?>">
                    <span class="legend-dot"></span>
                    <span>All document offices</span>
                </a>
                <?php foreach ([
                    ['key' => 'admission', 'label' => 'Office of Admission and Registration', 'color' => 'red'],
                    ['key' => 'guidance', 'label' => 'Guidance and Counselling Office', 'color' => '#16a34a'],
                    ['key' => 'student-affairs', 'label' => 'Office of Student Affairs / Scholarship and Financial Assistance', 'color' => '#2563eb'],
                    ['key' => 'library', 'label' => 'University Library Services', 'color' => '#0891b2'],
                    ['key' => 'discipline', 'label' => 'Office of Student Discipline and Development', 'color' => 'black'],
                    ['key' => 'accounting', 'label' => 'Accounting Office', 'color' => '#9333ea'],
                    ['key' => 'cashier', 'label' => 'Cashier Office', 'color' => '#f59e0b'],
                    ['key' => 'corporate', 'label' => 'Corporate and Community Affairs Office', 'color' => '#c026d3'],
                    ['key' => 'health', 'label' => 'Health Services', 'color' => '#65a30d'],
                    ['key' => 'security', 'label' => 'Civil Security Unit', 'color' => '#b91c1c'],
                    ['key' => 'mis', 'label' => 'Management Information Systems', 'color' => '#4f46e5'],
                    ['key' => 'other', 'label' => 'Other Services', 'color' => 'gray'],
                ] as $legendItem): ?>
                    <a class="legend-item legend-item-link <?= $selectedOffice === $legendItem['key'] ? 'active' : ''; ?>" style="--legend-color: <?= htmlspecialchars($legendItem['color'], ENT_QUOTES, 'UTF-8'); ?>" href="?office=<?= urlencode($legendItem['key']); ?>" aria-current="<?= $selectedOffice === $legendItem['key'] ? 'page' : 'false'; ?>">
                        <span class="legend-dot" style="--legend-color: <?= htmlspecialchars($legendItem['color'], ENT_QUOTES, 'UTF-8'); ?>"></span>
                        <span><?= htmlspecialchars($legendItem['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </aside>

        <?php if (!empty($db_error)): ?>
            <div class="empty-state">
                <h4 class="mb-2">Database not loaded yet</h4>
                <p><?= htmlspecialchars($db_error, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        <?php elseif (empty($processes)): ?>
            <div class="empty-state">
                <h4 class="mb-2">No document processes found</h4>
                <p>Import the document data first or create the tables in the database.</p>
            </div>
        <?php else: ?>
            <div class="document-process-page results-status">
                Showing <?= (int) $totalProcesses; ?> document<?= $totalProcesses === 1 ? '' : 's'; ?><?= $selectedOffice !== '' ? ' for the selected office' : ''; ?>.
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="pagination-wrap pagination-top" aria-label="Document page navigation">
                    <?php if ($currentPage > 1): ?>
                        <a class="page-link-btn" href="?page=<?= (int) ($currentPage - 1); ?><?= $selectedOffice !== '' ? '&office=' . urlencode($selectedOffice) : ''; ?>" aria-label="Previous page" title="Previous page"><i class="fa-solid fa-arrow-left"></i></a>
                    <?php endif; ?>
                    <?php if ($currentPage < $totalPages): ?>
                        <a class="page-link-btn" href="?page=<?= (int) ($currentPage + 1); ?><?= $selectedOffice !== '' ? '&office=' . urlencode($selectedOffice) : ''; ?>" aria-label="Next page" title="Next page"><i class="fa-solid fa-arrow-right"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="document-process-page process-list">
                <?php foreach ($paginatedProcesses as $process): ?>
                    <button type="button"
                            class="process-card"
                            style="--legend-color: <?= htmlspecialchars($process['legend']['color'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-bs-toggle="modal"
                            data-bs-target="#processModal-<?= (int) $process['process_id']; ?>">
                        <div class="process-card-icon">
                            <i class="fa-solid fa-file-lines"></i>
                        </div>

                        <div class="process-card-content">
                            <h3 class="process-title"><?= htmlspecialchars(strtoupper(strtolower((string) $process['title'])), ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p class="process-description">
                                <?php
                                    $summary = trim((string) ($process['who_may_avail'] ?? ''));
                                    if ($summary === '' && !empty($process['requirements'])) {
                                        $summary = $process['requirements'][0];
                                    }
                                    if ($summary === '') {
                                        $summary = 'Step-by-step guide on how to request and get your school documents.';
                                    }
                                    echo htmlspecialchars('For ' . $summary, ENT_QUOTES, 'UTF-8');
                                ?>
                            </p>
                            <span class="process-cta">
                                View Steps <i class="fa-solid fa-arrow-right"></i>
                            </span>
                        </div>
                    </button>
                <?php endforeach; ?>
            </div>

            <?php foreach ($paginatedProcesses as $process): ?>
                <div class="modal fade" id="processModal-<?= (int) $process['process_id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
                        <div class="modal-content border-0 overflow-hidden">
                            <div class="modal-header border-0" style="background: linear-gradient(135deg, var(--ns-blue), var(--ns-blue-deep)); color: white;">
                                <h5 class="modal-title fw-bold"><?= htmlspecialchars(ucwords(strtolower((string) $process['title'])), ENT_QUOTES, 'UTF-8'); ?></h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-0">
                                <iframe src="docx.php?id=<?= (int) $process['process_id']; ?>" style="width: 100%; height: 80vh; border: 0; display: block; background: white;"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if ($totalPages > 1): ?>
                <div class="pagination-wrap" aria-label="Document pages">
                    <?php if ($currentPage > 1): ?>
                        <a class="page-link-btn" href="?page=<?= (int) ($currentPage - 1); ?><?= $selectedOffice !== '' ? '&office=' . urlencode($selectedOffice) : ''; ?>">Prev</a>
                    <?php endif; ?>

                    <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                        <a class="page-link-btn <?= $page === $currentPage ? 'active' : ''; ?>" href="?page=<?= (int) $page; ?><?= $selectedOffice !== '' ? '&office=' . urlencode($selectedOffice) : ''; ?>" aria-current="<?= $page === $currentPage ? 'page' : 'false'; ?>"><?= (int) $page; ?></a>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <a class="page-link-btn" href="?page=<?= (int) ($currentPage + 1); ?><?= $selectedOffice !== '' ? '&office=' . urlencode($selectedOffice) : ''; ?>">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const resetLegendScroll = function () {
                window.scrollTo({ top: 0, left: 0, behavior: 'auto' });
                document.querySelectorAll('.legend-items').forEach(function (container) {
                    container.scrollTop = 0;
                    container.scrollLeft = 0;
                });
            };

            document.querySelectorAll('.legend-item-link, .page-link-btn').forEach(function (link) {
                link.addEventListener('click', resetLegendScroll);
            });

            window.addEventListener('pageshow', resetLegendScroll);
        });
    </script>
</body>
</html>
