<?php
switch($_GET['clean']) {
    case 'log':
        clean_log_tables();
    break;
    case 'var':
        clean_var_directory();
    break;
    case 'cache':
ini_set("display_errors", 1);

require '/var/www/magento/app/Mage.php';
Mage::app('admin')->setUseSessionInUrl(false);
Mage::getConfig()->init();

$types = Mage::app()->getCacheInstance()->getTypes();

try {
    echo "Cleaning data cache... \n";
    flush();
    foreach ($types as $type => $data) {
        echo "Removing $type ... ";
        echo Mage::app()->getCacheInstance()->clean($data["tags"]) ? "[OK]" : "[ERROR]";
        echo "\n";
    }
} catch (exception $e) {
    die("[ERROR:" . $e->getMessage() . "]");
}

echo "\n";

try {
    echo "Cleaning stored cache... ";
    flush();
    echo Mage::app()->getCacheInstance()->clean() ? "[OK]" : "[ERROR]";
    echo "\n\n";
} catch (exception $e) {
    die("[ERROR:" . $e->getMessage() . "]");
}

try {
    echo "Cleaning merged JS/CSS...";
    flush();
    Mage::getModel('core/design_package')->cleanMergedJsCss();
    Mage::dispatchEvent('clean_media_cache_after');
    echo "[OK]\n\n";
} catch (Exception $e) {
    die("[ERROR:" . $e->getMessage() . "]");
}

try {
    echo "Cleaning image cache... ";
    flush();
    echo Mage::getModel('catalog/product_image')->clearCache();
    echo "[OK]\n";
} catch (exception $e) {
    die("[ERROR:" . $e->getMessage() . "]");
}
    break;
}

function clean_log_tables() {
    $xml = simplexml_load_file('./app/etc/local.xml', NULL, LIBXML_NOCDATA);

    if(is_object($xml)) {
        $db['host'] = $xml->global->resources->default_setup->connection->host;
        $db['name'] = $xml->global->resources->default_setup->connection->dbname;
        $db['user'] = $xml->global->resources->default_setup->connection->username;
        $db['pass'] = $xml->global->resources->default_setup->connection->password;
        $db['pref'] = $xml->global->resources->db->table_prefix;

        $tables = array(
            'adminnotification_inbox',
            'aw_core_logger',
            'dataflow_batch_export',
            'dataflow_batch_import',
            'log_customer',
            'log_quote',
            'log_summary',
            'log_summary_type',
            'log_url',
            'log_url_info',
            'log_visitor',
            'log_visitor_info',
            'log_visitor_online',
            'index_event',
            'report_event',
            'report_viewed_product_index',
            'report_compared_product_index',
            'catalog_compare_item',
            'catalogindex_aggregation',
            'catalogindex_aggregation_tag',
            'catalogindex_aggregation_to_tag'
        );

        mysql_connect($db['host'], $db['user'], $db['pass']) or die(mysql_error());
        mysql_select_db($db['name']) or die(mysql_error());

        foreach($tables as $table) {
            @mysql_query('TRUNCATE `'.$db['pref'].$table.'`');
        }
    } else {
        exit('Unable to load local.xml file');
    }
}


function clean_var_directory() {
    $dirs = array(
        'downloader/.cache/',
        'downloader/pearlib/cache/*',
        'downloader/pearlib/download/*',
        'var/cache/',
        'var/locks/',
        'var/log/',
        'var/report/',
        'var/session/',
        'var/tmp/'
    );

    foreach($dirs as $dir) {
        exec('rm -rf '.$dir);
    }
}

?>