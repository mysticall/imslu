<?php
require_once dirname(__FILE__).'/os.php';
require_once dirname(__FILE__).'/gettextwrapper.php';

# Set system language
if (!empty($_COOKIE['lang'])) {

    putenv('LC_MESSAGES='.$_COOKIE['lang']);
    setlocale(LC_MESSAGES, $_COOKIE['lang'].'.UTF-8');
    bindtextdomain('frontend', 'locale');
    textdomain('frontend');
    bind_textdomain_codeset('frontend', 'UTF-8');
}
else {

    putenv('LC_MESSAGES=en_US');
    setlocale(LC_MESSAGES, 'en_US.UTF-8');
    bindtextdomain('frontend', 'locale');
    textdomain('frontend');
    bind_textdomain_codeset('frontend', 'UTF-8');
}

require_once dirname(__FILE__).'/classes/pdoinstance.php';
require_once dirname(__FILE__).'/classes/operator.php';

/**
 * @param = new Operator instance, see include/classes/operator.php
 */
$Operator = new Operator;

?>
