<?php
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="document_import_template.csv"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$rows = [
    [
        'document_title',
        'office_name',
        'classification',
        'transaction_type',
        'who_may_avail',
        'requirement_name',
        'where_to_secure',
        'step_number',
        'client_step',
        'agency_action',
        'fees',
        'processing_time',
        'person_responsible',
        'total_fees',
        'total_time',
    ],
    [
        'Sample documents',
        'Sample office',
        'Sample Classification',
        'Sample Transactions',
        'Sample Client',
        'Sample requirement 1',
        'Sample Where to secure 1',
        '1',
        '1. Sample',
        '1.1 Sample',
        '100',
        '2 minutes',
        'Sample',
        '300.00',
        '3 hours',
    ],
    [
        'Sample documents',
        'Sample office',
        'Sample Classification',
        'Sample Transactions',
        'Sample Client',
        'Sample requirement 2',
        '',
        '2',
        '2. Sample',
        '2.1 Sample',
        '',
        '1 hour, 45 minutes',
        'Sample',
        '',
        '',
    ],
    [
        'Sample documents',
        'Sample office',
        'Sample Classification',
        'Sample Transactions',
        'Sample Client',
        'Sample requirement 3',
        'Sample Where to secure 2',
        '2',
        '',
        '2.2 Sample',
        '200',
        '',
        'Sample',
        '',
        '',
    ],
    [
        'Sample documents',
        'Sample office',
        'Sample Classification',
        'Sample Transactions',
        'Sample Client',
        'Sample requirement 4',
        'Sample Where to secure 3',
        '3',
        '',
        '3.1 Sample',
        '',
        '4 Days',
        '',
        '',
        '',
    ],
];

$output = fopen('php://output', 'w');
if ($output === false) {
    exit;
}

foreach ($rows as $row) {
    fputcsv($output, $row);
}

fclose($output);

