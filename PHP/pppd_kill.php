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

// enable debug mode
 error_reporting(E_ALL); ini_set('display_errors', 'On');

require_once dirname(__FILE__).'/include/common.php';

// Check for active session
if (empty($_COOKIE['imslu_sessionid']) || !$Operator->authentication($_COOKIE['imslu_sessionid'])) {

    header('Location: index.php');
    exit;
}

# Must be included after session check
require_once dirname(__FILE__).'/include/config.php';

###################################################################################################
// Kill PPPoE session
###################################################################################################

if (!empty($_GET['userid']) && !empty($_GET['ipaddress'])) {

    settype($_GET['userid'], "integer");

    if (filter_var($_GET['ipaddress'], FILTER_VALIDATE_IP)) {

        $cmd = "$SUDO $PYTHON $IMSLU_SCRIPTS/pppd_kill.py {$_GET['ipaddress']} 2>&1";
        $result = shell_exec($cmd);

        $_SESSION['msg'] .= (!empty($result)) ? "$result <br>" : "";
    }

    $page = (!empty($_GET['page']) && $_GET['page'] == 'edit') ? "user_edit.php" : "user_info.php";
    header("Location: $page?userid={$_GET['userid']}");
}
    
