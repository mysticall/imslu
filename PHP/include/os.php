<?php
$OS = 'FreeBSD';
//$OS = 'Linux';

if ($OS == 'FreeBSD') {
  $CONFIG_FILE = '/usr/local/etc/imslu/config.php';
  $DATABASE_CONFIGURATION = '/usr/local/etc/imslu/database_config.php';
}
elseif ($OS == 'Linux') {
  $CONFIG_FILE = '/etc/imslu/config.php';
  $DATABASE_CONFIGURATION = '/etc/imslu/database_config.php';
}
?>
