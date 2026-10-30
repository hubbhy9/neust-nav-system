<?php
require_once __DIR__ . '/../libraries/db.php';

$link = $pdo ?? $conn ?? $mysqli ?? $db ?? null;
if ($link === null) {
    die('db.php did not expose a connection variable ($pdo, $conn, $mysqli or $db).');
}

function db_all($link, string $sql, array $params = []): array
{
    if ($link instanceof PDO) {
        $st = $link->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    $st = $link->prepare($sql);
    if ($params) {
        $st->bind_param(str_repeat('s', count($params)), ...$params);
    }
    $st->execute();
    return $st->get_result()->fetch_all(MYSQLI_ASSOC);
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function group_steps(array $steps): array
{
    $groups = [];
    foreach ($steps as $s) {
        $action = (string)($s['agency_action'] ?? '');
        $client = (string)($s['client_step'] ?? '');

        if (preg_match('/^\s*(\d+)\.\d+/u', $action, $m)) {
            $n = (int)$m[1];
        } elseif (preg_match('/^\s*(\d+)\s*[.)]/u', $client, $m)) {
            $n = (int)$m[1];
        } else {
            $n = (int)($s['step_number'] ?? 0);
        }

        if (!isset($groups[$n])) {
            $groups[$n] = ['client' => [], 'rows' => []];
        }

        $text = trim($client);
        if ($text !== '' && !in_array($text, $groups[$n]['client'], true)) {
            $groups[$n]['client'][] = $text;
        }

        $groups[$n]['rows'][] = $s;
    }

    ksort($groups);
    return $groups;
}

function split_ref(string $text): array
{
    if (preg_match('/^\s*(\d+\.\d+)\s*(.*)$/su', $text, $m)) {
        return [$m[1], $m[2]];
    }

    return ['', trim($text)];
}

function strip_num(string $text): string
{
    return trim(preg_replace('/^\s*\d+\s*[.)]\s*/u', '', $text));
}

function parse_fee_items(string $fees): ?array
{
    $pieces = explode(', ', str_replace("\n", ', ', $fees));
    $items = [];
    $buffer = '';

    foreach ($pieces as $p) {
        $buffer = $buffer === '' ? trim($p) : $buffer . ', ' . trim($p);
        if (preg_match('/^(.*?)(?::|\s-\s)\s*(.+)$/su', $buffer, $m)) {
            $items[] = [trim($m[1]), trim($m[2])];
            $buffer = '';
        }
    }

    if ($buffer !== '' || count($items) < 3) {
        return null;
    }

    return $items;
}

function render_fees(string $fees): string
{
    $f = trim($fees);
    if ($f === '' || strcasecmp($f, 'none') === 0 || strcasecmp($f, 'not applicable') === 0) {
        return '<span class="muted">' . ($f === '' ? 'None' : e($f)) . '</span>';
    }

    if (mb_strlen($f) <= 60) {
        return '<span class="fee-short">' . nl2br(e($f)) . '</span>';
    }

    $items = parse_fee_items($f);
    $count = $items ? count($items) : null;

    $out = '<details class="fees"><summary>View fee breakdown'
        . ($count ? ' <span class="count">' . $count . ' items</span>' : '') . '</summary>';

    if ($items) {
        $out .= '<ul class="fee-list">';
        foreach ($items as [$label, $amt]) {
            $out .= '<li><span>' . e($label) . '</span><b>' . e($amt) . '</b></li>';
        }
        $out .= '</ul>';
    } else {
        $out .= '<p>' . nl2br(e($f)) . '</p>';
    }

    return $out . '</details>';
}

$allDocs = db_all($link, 'SELECT id, title FROM documents ORDER BY title');

$selected = $_GET['id'] ?? 'all';
$search   = trim($_GET['q'] ?? '');

$sql = "SELECT d.id, d.title, d.who_may_avail,
               o.name AS office_name,
               c.name AS classification,
               GROUP_CONCAT(DISTINCT tt.name ORDER BY tt.name SEPARATOR '; ') AS transaction_type
        FROM documents d
        LEFT JOIN offices o ON o.id = d.office_id
        LEFT JOIN classifications c ON c.id = d.classification_id
        LEFT JOIN document_transaction_types dtt ON dtt.document_id = d.id
        LEFT JOIN transaction_types tt ON tt.id = dtt.transaction_type_id
        GROUP BY d.id, d.title, d.who_may_avail, o.name, c.name";

$where = [];
$params = [];

if ($selected !== 'all' && ctype_digit((string)$selected)) {
    $where[] = 'd.id = ?';
    $params[] = $selected;
}

if ($search !== '') {
    $where[] = 'd.title LIKE ?';
    $params[] = '%' . $search . '%';
}

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY d.title';
$docs = db_all($link, $sql, $params);

$reqByDoc = [];
$stepsByDoc = [];

if ($docs) {
    $ids = array_column($docs, 'id');
    $in = implode(',', array_fill(0, count($ids), '?'));

    foreach (db_all($link, "SELECT * FROM requirements WHERE document_id IN ($in) ORDER BY id", $ids) as $r) {
        $reqByDoc[$r['document_id']][] = $r;
    }

    foreach (db_all($link, "SELECT * FROM document_steps WHERE document_id IN ($in) ORDER BY document_id, step_number, id", $ids) as $s) {
        $stepsByDoc[$s['document_id']][] = $s;
    }
}
