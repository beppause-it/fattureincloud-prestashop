<?php

/**
* FattureInCloud Prestashop Module
*
*  @author    Websuvius di Michele Matto <michele@websuvius.it>
*  @copyright FattureInCloud - Madbit Entertainment S.r.l.
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_2_3_0($module)
{
    // New configuration values
    if (!Configuration::updateValue('FATTUREINCLOUD_RECEIPTS_CREATE', 0)
        || !Configuration::updateValue('FATTUREINCLOUD_RECEIPTS_NUMERATION', 'REC-PS')
    ) {
        return false;
    }

    // Add receipt fields to fattureInCloud table
    try {
        $sql_new_receipt_fields =  'ALTER TABLE `'. _DB_PREFIX_.'fattureInCloud`
            ADD `fic_receipt_id` bigint(20),
            ADD `fic_receipt_download_token` varchar(255),
            ADD `fic_receipt_download_url` varchar(255),
            ADD `fic_receipt_number` varchar(255)';
        Db::getInstance()->execute($sql_new_receipt_fields);
    } catch (Exception $e) {
        // ignore (fields may already exist)
    }

    return true;
}

