<?php
// Load config file
$CONFIG_FILE = '/etc/imslu/config.php';
require_once realpath(dirname($CONFIG_FILE)).DIRECTORY_SEPARATOR.basename($CONFIG_FILE);

require_once dirname(__FILE__).'/defines.php';
require_once dirname(__FILE__).'/func.php';
require_once dirname(__FILE__).'/audit.php';
require_once dirname(__FILE__).'/combobox.php';
require_once dirname(__FILE__).'/classes/table.php';

$VERSION = 'IMSLU 0.1-alpha-1';

// array - Available languages in the system.
$LOCALES = array(      
    'en_US' => _('English (en_US)'),
    'bg_BG' => _('Bulgarian (bg_BG)')
    );

// array - Available operator groups in the system.
$OPERATOR_GROUPS = array(
    1 => _('cashiers'),
    2 => _('network technicians'),
    3 => _('administrators'),
    4 => _('system administrators')
    );

// array - Available themes in the system.
$THEMES = array(
    'originalgreen' => _('default'),
    'originalblue' => _('original blue'),
    );

// array - Status for requests
$request_status = array(
            '0' => _('to call'),
            '1' => _('will call'),
            '2' => _('pending'),
            '3' => _('connected'),
            '4' => _('refused'),
            '5' => _('closed')
            );

$ticket_status = array(
            '0' => _('closed'),
            '1' => _('open')
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