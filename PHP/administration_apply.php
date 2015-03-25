<?php
/*
 * IMSLU version 0.1-alpha
 *
 * Copyright © 2013 IMSLU Developers
 * 
 * Please, see the doc/AUTHORS for more information about authors!
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

//enable debug mode
error_reporting(E_ALL); ini_set('display_errors', 'On');

require_once dirname(__FILE__).'/include/common.php';

// Check for active session
if (empty($_COOKIE['imslu_sessionid']) || !$Operator->authentication($_COOKIE['imslu_sessionid'])) {

    header('Location: index.php');
    exit;
}
if ($_SESSION['form_key'] !== $_POST['form_key']) {

    header('Location: index.php');
    exit;
}

# Must be included after session check
require_once dirname(__FILE__).'/include/config.php';

if((OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type']) || (OPERATOR_TYPE_ADMIN == $_SESSION['data']['type'])) {

    if (!empty($_POST['start_vlan_mac_check'])) {
      
        $cmd = "$SUDO $PYTHON $IMSLU_SCRIPTS/secondary_rules.py  > /dev/null 2>&1 &";
        $result = shell_exec($cmd);

        $_SESSION['msg'] = _('Started searching for vlan, mac.');
        header("Location: administration.php");
    }

    if (!empty($_POST['stop_vlan_mac_check'])) {

        $cmd = "ps -e -o pid,args | grep secondary_rules.py | grep -v grep | awk '{print $1}' | xargs $SUDO $KILL -9";
        $result = shell_exec($cmd);

        $_SESSION['msg'] = _('The process has stopped.')." $result";
        header("Location: administration.php");
    }
}
?>
