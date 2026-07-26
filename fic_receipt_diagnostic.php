<?php
/**
 * Temporary diagnostic script for FattureInCloud receipt duplicates.
 *
 * Upload this file to the PrestaShop root directory, open it with:
 *   https://example.com/fic_receipt_diagnostic.php?key=CHANGE_ME_FIC_DIAG_20260726
 * then delete it immediately after collecting the output.
 */

$secret = 'CHANGE_ME_FIC_DIAG_20260726';

if (!isset($_GET['key']) || $_GET['key'] !== $secret) {
    http_response_code(403);
    exit('Forbidden');
}

header('Content-Type: text/plain; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

$config = __DIR__ . '/config/config.inc.php';
if (!file_exists($config)) {
    exit("ERROR: config/config.inc.php not found. Upload this file to the PrestaShop root directory.\n");
}

require_once $config;

if (!defined('_DB_PREFIX_')) {
    exit("ERROR: _DB_PREFIX_ is not defined.\n");
}

$db = Db::getInstance();
$trackingTable = _DB_PREFIX_ . 'fattureInCloud';
$orderTable = _DB_PREFIX_ . 'orders';

function printRows($title, $rows)
{
    echo "\n== " . $title . " ==\n";

    if (!$rows) {
        echo "No rows.\n";
        return;
    }

    foreach ($rows as $row) {
        echo json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    }
}

function q($sql)
{
    return Db::getInstance()->executeS($sql);
}

echo "FattureInCloud Receipt Diagnostic\n";
echo "Generated at: " . date('Y-m-d H:i:s') . "\n";
echo "DB prefix: " . _DB_PREFIX_ . "\n";
echo "Tracking table: " . $trackingTable . "\n";

$tableExists = q('SHOW TABLES LIKE "' . pSQL($trackingTable) . '"');
if (!$tableExists) {
    exit("\nERROR: tracking table not found: " . $trackingTable . "\n");
}

printRows('Table columns', q('SHOW COLUMNS FROM `' . bqSQL($trackingTable) . '`'));

printRows(
    'Summary',
    q(
        'SELECT'
        . ' COUNT(*) AS total_rows,'
        . ' COUNT(DISTINCT ps_order_id) AS distinct_ps_orders,'
        . ' SUM(CASE WHEN fic_order_id IS NOT NULL THEN 1 ELSE 0 END) AS rows_with_fic_order,'
        . ' SUM(CASE WHEN fic_invoice_id IS NOT NULL THEN 1 ELSE 0 END) AS rows_with_fic_invoice,'
        . ' SUM(CASE WHEN fic_receipt_id IS NOT NULL THEN 1 ELSE 0 END) AS rows_with_fic_receipt'
        . ' FROM `' . bqSQL($trackingTable) . '`'
    )
);

printRows(
    'Duplicate tracking rows by ps_order_id',
    q(
        'SELECT'
        . ' ps_order_id,'
        . ' COUNT(*) AS tracking_rows,'
        . ' GROUP_CONCAT(id_fattureInCloud ORDER BY id_fattureInCloud SEPARATOR ",") AS tracking_ids,'
        . ' GROUP_CONCAT(COALESCE(fic_order_id, "NULL") ORDER BY id_fattureInCloud SEPARATOR ",") AS fic_order_ids,'
        . ' GROUP_CONCAT(COALESCE(fic_invoice_id, "NULL") ORDER BY id_fattureInCloud SEPARATOR ",") AS fic_invoice_ids,'
        . ' GROUP_CONCAT(COALESCE(fic_receipt_id, "NULL") ORDER BY id_fattureInCloud SEPARATOR ",") AS fic_receipt_ids,'
        . ' GROUP_CONCAT(COALESCE(fic_receipt_number, "NULL") ORDER BY id_fattureInCloud SEPARATOR ",") AS fic_receipt_numbers'
        . ' FROM `' . bqSQL($trackingTable) . '`'
        . ' GROUP BY ps_order_id'
        . ' HAVING COUNT(*) > 1'
        . ' ORDER BY tracking_rows DESC, ps_order_id DESC'
        . ' LIMIT 50'
    )
);

printRows(
    'Orders with multiple local receipt rows',
    q(
        'SELECT'
        . ' ps_order_id,'
        . ' COUNT(*) AS receipt_rows,'
        . ' COUNT(DISTINCT fic_receipt_id) AS distinct_receipt_ids,'
        . ' GROUP_CONCAT(id_fattureInCloud ORDER BY id_fattureInCloud SEPARATOR ",") AS tracking_ids,'
        . ' GROUP_CONCAT(COALESCE(fic_receipt_id, "NULL") ORDER BY id_fattureInCloud SEPARATOR ",") AS fic_receipt_ids,'
        . ' GROUP_CONCAT(COALESCE(fic_receipt_number, "NULL") ORDER BY id_fattureInCloud SEPARATOR ",") AS fic_receipt_numbers'
        . ' FROM `' . bqSQL($trackingTable) . '`'
        . ' WHERE fic_receipt_id IS NOT NULL'
        . ' GROUP BY ps_order_id'
        . ' HAVING COUNT(*) > 1 OR COUNT(DISTINCT fic_receipt_id) > 1'
        . ' ORDER BY receipt_rows DESC, ps_order_id DESC'
        . ' LIMIT 50'
    )
);

printRows(
    'Rows where robust receipt guard would block but current fic_receipt_id-only guard may not',
    q(
        'SELECT'
        . ' id_fattureInCloud, ps_order_id, fic_receipt_id, fic_receipt_number,'
        . ' CASE WHEN fic_receipt_download_token IS NULL OR fic_receipt_download_token = "" THEN "" ELSE "[present]" END AS fic_receipt_download_token,'
        . ' CASE WHEN fic_receipt_download_url IS NULL OR fic_receipt_download_url = "" THEN "" ELSE "[present]" END AS fic_receipt_download_url'
        . ' FROM `' . bqSQL($trackingTable) . '`'
        . ' WHERE (fic_receipt_id IS NULL OR fic_receipt_id = 0)'
        . ' AND ('
        . '   (fic_receipt_number IS NOT NULL AND fic_receipt_number <> "")'
        . '   OR (fic_receipt_download_token IS NOT NULL AND fic_receipt_download_token <> "")'
        . '   OR (fic_receipt_download_url IS NOT NULL AND fic_receipt_download_url <> "")'
        . ' )'
        . ' ORDER BY id_fattureInCloud DESC'
        . ' LIMIT 50'
    )
);

printRows(
    'Duplicate local receipt numbers',
    q(
        'SELECT'
        . ' fic_receipt_number,'
        . ' COUNT(*) AS occurrences,'
        . ' GROUP_CONCAT(ps_order_id ORDER BY ps_order_id SEPARATOR ",") AS ps_order_ids,'
        . ' GROUP_CONCAT(COALESCE(fic_receipt_id, "NULL") ORDER BY ps_order_id SEPARATOR ",") AS fic_receipt_ids'
        . ' FROM `' . bqSQL($trackingTable) . '`'
        . ' WHERE fic_receipt_number IS NOT NULL AND fic_receipt_number <> ""'
        . ' GROUP BY fic_receipt_number'
        . ' HAVING COUNT(*) > 1'
        . ' ORDER BY occurrences DESC, fic_receipt_number DESC'
        . ' LIMIT 50'
    )
);

if (isset($_GET['number']) && $_GET['number'] !== '') {
    $number = pSQL($_GET['number']);
    printRows(
        'Rows matching requested receipt number',
        q(
            'SELECT *'
            . ' FROM `' . bqSQL($trackingTable) . '`'
            . ' WHERE fic_receipt_number LIKE "%' . $number . '%"'
            . ' ORDER BY id_fattureInCloud DESC'
            . ' LIMIT 50'
        )
    );
}

if (isset($_GET['order_id']) && (int) $_GET['order_id'] > 0) {
    $orderId = (int) $_GET['order_id'];
    printRows(
        'Rows matching requested ps_order_id',
        q(
            'SELECT *'
            . ' FROM `' . bqSQL($trackingTable) . '`'
            . ' WHERE ps_order_id = ' . $orderId
            . ' ORDER BY id_fattureInCloud'
        )
    );

    printRows(
        'PrestaShop order status history for requested ps_order_id',
        q(
            'SELECT oh.id_order_history, oh.id_order, oh.id_order_state, oh.date_add, osl.name AS state_name'
            . ' FROM `' . _DB_PREFIX_ . 'order_history` oh'
            . ' LEFT JOIN `' . _DB_PREFIX_ . 'order_state_lang` osl'
            . '   ON osl.id_order_state = oh.id_order_state'
            . '  AND osl.id_lang = (SELECT id_lang FROM `' . _DB_PREFIX_ . 'lang` WHERE active = 1 ORDER BY id_lang LIMIT 1)'
            . ' WHERE oh.id_order = ' . $orderId
            . ' ORDER BY oh.date_add, oh.id_order_history'
        )
    );
}

echo "\nDone. Delete this file from the server now.\n";
