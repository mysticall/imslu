<?php
require_once dirname(__FILE__).'/gettextwrapper.inc.php';
require_once dirname(__FILE__).'/locales.inc.php';

// Load config file
$CONFIG_FILE = '/etc/imslu/config.php';
require_once realpath(dirname($CONFIG_FILE)).DIRECTORY_SEPARATOR.basename($CONFIG_FILE);

# Set system language
if (!empty($_SESSION['locale'])) {

    bindtextdomain('frontend', 'locale');
    setlocale(LC_ALL, $_SESSION['locale']);
    textdomain('frontend');
}
else {
    // initializing gettext translations depending on language selected by operator
    $locales = locale_unix($_SESSION['operatorData']['lang']);
    $locale_found = false;
    bindtextdomain('frontend', 'locale');

    foreach ($locales as $locale) {
        putenv("LC_ALL=$locale");
        putenv("LANG=$locale");
        putenv("LANGUAGE=$locale");

        if (setlocale(LC_ALL, $locale)) {

            $locale_found = true;
            $_SESSION['locale'] = $locale;
            break;
        }
    }

    if (!$locale_found && $_SESSION['operatorData']['lang'] != 'en_US') {

        //Locale to default
        setlocale(LC_ALL, array('C', 'POSIX', 'en', 'en_US', 'en_US.UTF-8', 'English_United States.1252'));

        $_SESSION['msg'] .= "Locale for language ".$_SESSION['lang']." is not found on the web server. Tried to set: ".implode(', ', $locales).". Unable to translate interface.<br>";
    }

    textdomain('frontend');
}

// init mb strings if it's available
init_mbstrings();

require_once dirname(__FILE__).'/defines.inc.php';
require_once dirname(__FILE__).'/func.inc.php';
require_once dirname(__FILE__).'/audit.inc.php';
require_once dirname(__FILE__).'/combobox.inc.php';
require_once dirname(__FILE__).'/classes/class.ctable.php';

$VERSION = 'IMSLU 0.1-alpha';

// array - Available languages in the system.
$LOCALES = array(
    'bg_BG' => _('Bulgarian (bg_BG)'),      
    'en_US' => _('English (en_US)')
    );

// array - Available operator groups in the system.
$OPERATOR_GROUPS = array(
    1 => _('Cashiers'),
    2 => _('Network technicians'),
    3 => _('Administrators'),
    4 => _('System administrators')
    );

// array - Available themes in the system.
$THEMES = array(
    'originalgreen' => _('system default'),
    'originalblue' => _('original blue'),
    );

// array - Freeradisu operators
$freeradius_op = array(
    '=' => '=',
    ':=' => ':=',
    '+=' => '+=',
    '==' => '==',
    '!=' => '!=',
    '&gt;' => '&gt;',
    '&gt;=' => '&gt;=',
    '&lt;' => '&lt;',
    '&lt;=' => '&lt;=',
    '=~' => '=~',
    '!~' => '!~',
    '=*' => '=*',
    '!*' => '!*'
    );
    
?>