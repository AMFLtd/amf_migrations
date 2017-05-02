<?php

class amf_migration
{
    /**
     * @var null|mysqli
     */
    static $db = null;
    var $error_message = '';
    function update() {
        return true;
    }

    function rollback() {
        return true;
    }

}