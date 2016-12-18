<?php
/*
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

    $db = new PDOinstance();
    if (!empty($_POST['clear']) && !empty($_POST['vlan'])) {

        $sql = "UPDATE ip SET vlan='' WHERE vlan = :vlan";
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(':vlan', $_POST['vlan']);
        $sth->execute();

        $_SESSION['msg'] = _('Changes are applied successfully.')."<br>";
        header("Location: administration.php");
    }
}
?>
