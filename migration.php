**********************************
* Simple PHP /MySQL migration tool
**********************************
Usage: php migration.php [--update | --rollback]
Default action is "update"

<?php
chdir(realpath(__DIR__));

//Load prototype class
require_once('amf_migration.php');

//Get config
if (file_exists('config.php')) {
    require_once('config.php');
} else {
    die('[Error] config file not exists');
}


if (empty(amf_migration_config::$dir) || !is_dir(amf_migration_config::$dir) || !file_exists(amf_migration_config::$dir)) {
    amf_custom_logger('dir not found', 'error');
    die();
}





//Get command
$cmd = getopt('', array('update:', 'rollback:'));
$action = ((!empty($cmd['update'])) || (empty($cmd['rollback']))) ? 'update' : 'rollback';


//Scan directory for files
$dir = realpath($cmd['dir']) . DIRECTORY_SEPARATOR;
$files = array_diff(scandir($dir), array('..', '.'));

if (empty($files)) {
    amf_custom_logger('Nothing to do', 'info');
    die();
}

//Connect to DB
amf_migration::$db = new mysqli(amf_migration_config::$db_host, amf_migration_config::$db_user, amf_migration_config::$db_pass, amf_migration_config::$db_name);
if (!empty(amf_migration_config::$encoding)) {
    amf_migration::$db->set_charset(amf_migration_config::$encoding);
}

if (!is_object(amf_migration::$db)) {
    amf_custom_logger('could not connect to DB','error');
    die();
}


//Check if migration table is not exists
//Create table if not exists

//Run script
foreach ($files as $entry) {
    $fname = $dir . $entry;
    if (!is_file($fname)) {
        continue;
    }
    require_once($fname);
    $tmp = explode('.', $entry);
    $tmp = array_pop($tmp);
    $classname = 'migration_' . join('_', $tmp);
    $id = preg_replace('#[^a-z0-9_\\-]+#si', strtolower(join('_', $tmp)));
    /**
     * @var amf_migration $class
     */
    $class = new $classname();
    $sql = 'select * from ' . amf_migration_config::$table . ' where id="'.$id.'"';
    $res = amf_migration::$db->query($sql);
    if ($action == 'update') {
        //check if $id not exists
        if ($res->num_rows > 0) {
            continue;
        }
        $result = $class->update();
    } else {
        //check if $id _exists
        if ($res->num_rows == 0) {
            continue;
        }
        $result = $class->rollback();
    }
    if (!$result) {
        amf_custom_logger('on ' . $classname . ' : ' . $class->error_message,'error');
        die();
    } else {
        if ($action == 'update') {
            $sql = 'insert into ' . amf_migration_config::$table . ' set id="'.$id.'", date_created=NOW()';
        } else {
            $sql = 'delete from ' . amf_migration_config::$table . ' where id="'.$id.'"';
        }
        amf_custom_logger('Success: ' . $action . ' ' . $id, 'info');
        amf_migration::$db->query($sql);
    }
}




function amf_custom_logger($message, $log_level = 'info') {
    echo '[' . strtoupper($log_level) . '] ' . $message . PHP_EOL;
};