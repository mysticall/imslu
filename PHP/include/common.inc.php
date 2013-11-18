<?php
require_once dirname(__FILE__).'/classes/class.cpdoinstance.php';
require_once dirname(__FILE__).'/classes/class.cweboperator.php';
require_once dirname(__FILE__).'/classes/class.coperators.php';

/************ COOKIES ************/
function get_cookie($name, $default_value = null) {
    if (isset($_COOKIE[$name])) {
        return $_COOKIE[$name];
    }

    return $default_value;
}

?>
