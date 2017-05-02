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
    die('[Error] dir is not defined');
}

if (empty(amf_migration_config::$dir) || !is_dir(amf_migration_config::$dir) || !file_exists(amf_migration_config::$dir)) {
    die('[Error] dir not found');
}

//Get command
$cmd = getopt('', array('update:', 'rollback:'));
$action = ((!empty($cmd['update'])) || (empty($cmd['rollback']))) ? 'update' : 'rollback';


//Scan directory for files
$dir = realpath($cmd['dir']) . DIRECTORY_SEPARATOR;
$files = array_diff(scandir($dir), array('..', '.'));

if (empty($files)) {
    die('[INFO] Nothing to do');
}

//Connect to DB
amf_migration::$db = new mysqli(amf_migration_config::$db_host, amf_migration_config::$db_user, amf_migration_config::$db_pass, amf_migration_config::$db_name);
if (!empty(amf_migration_config::$encoding)) {
    amf_migration::$db->set_charset(amf_migration_config::$encoding);
}

if (!is_object(amf_migration::$db)) {
    die('[ERROR] could not connect to DB');
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
    if ($action == 'update') {
        //todo: check if $id not exists
        $result = $class->update();
    } else {
        //todo: check if $id _exists
        $result = $class->rollback();
    }
    if (!$result) {
        die('[ERROR] on ' . $classname . ' : ' . $class->error_message);
    } else {
        if ($action == 'update') {
            $sql = 'insert into ' . amf_migration_config::$table . ' set id="'.$id.'", date_created=NOW()';
        } else {
            $sql = 'delete from ' . amf_migration_config::$table . ' where id="'.$id.'"';
        }
    }
}


