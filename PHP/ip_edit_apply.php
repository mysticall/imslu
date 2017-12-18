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

// enable debug mode
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
unset($_POST['form_key']);

// Must be included after session check
require_once dirname(__FILE__).'/include/config.php';
require_once dirname(__FILE__).'/include/network.php';

$db = new PDOinstance();
$admin_permissions = (OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type'] || OPERATOR_TYPE_ADMIN == $_SESSION['data']['type']);
$cashier_permissions = (OPERATOR_TYPE_CASHIER == $_SESSION['data']['type']);
$technician_permissions = (OPERATOR_TYPE_TECHNICIAN == $_SESSION['data']['type']);

$old = json_decode($_POST['old'], true);
unset($_POST['old']);
$id = $old['id'];

####### Delete ####### 
// Onli System Admin or Admin can delete IP
if (!empty($_POST['delete']) && !empty($_POST['del_ip']) && $admin_permissions) {

    // Add audit
    add_audit($db, AUDIT_ACTION_DELETE, AUDIT_RESOURCE_IP, "IP address {$old['ip']} is deleted.", json_encode($old));

    $_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";

    ip_remove($db, $old);

    header("Location: user.php?userid={$old['userid']}");
    exit;
}

####### Edit ####### 
if (!empty($_POST['edit'])) {

    if (!check_protocol($old['protocol']) || !check_protocol($_POST['protocol'])) {

        error_protocol($_POST['protocol'], $old['userid']);
        exit;
    }

    // Validate IP Address
    if (!filter_var($_POST['ip'], FILTER_VALIDATE_IP)) {

        $_SESSION['msg'] .= _('Invalid IP Address!')."<br>";
        header("Location: user.php?userid={$old['userid']}");
        exit;
    }
    // Validate MAC
    if (!empty($_POST['mac']) && !IsValidMAC($_POST['mac'])) {

        $_SESSION['msg'] .= _('Invalid MAC Address!')."<br>";
        header("Location: user.php?userid={$old['userid']}");
        exit;
    }

    $_POST['userid'] = $old['userid'];

    // The IP address is changed.
    if ($old['ip'] != $_POST['ip']) {

        // Add audit
        add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_IP, "IP address {$old['ip']} changed with {$_POST['ip']}.", json_encode($old));

        ip_remove($db, $old);
        ip_add($db, $_POST);

        header("Location: user.php?userid={$_POST['userid']}");
        exit;
    }
    else {

        $update = array();

        // The protocol is changed.
        if ($old['protocol'] != $_POST['protocol']) {

            $update['protocol'] = $_POST['protocol'];

            // Old protocol
            switch ($old['protocol']) {
                case "IP":
                    // The new protocol is DHCP
                    if ($_POST['protocol'] == 'DHCP') {

                        dhcp_add($_POST);
                        check_vlan($old, $_POST);
                        check_mac($old, $_POST);
                    }
                    // The new protocol is PPPoE
                    elseif ($_POST['protocol'] == 'PPPoE') {

                        pppoe_add($db, $_POST);
                        ip_rem_static($old);
                    }
                    else {

                        error_protocol($_POST['protocol'], $old['userid']);
                    }

                    check_changes($old, $_POST);
                    break;
                case "DHCP":
                    // The new protocol is IP
                    if ($_POST['protocol'] == 'IP') {

                        check_vlan($old, $_POST);
                        check_mac($old, $_POST);
                        dhcp_rem($old['ip']);
                    }
                    // The new protocol is PPPoE
                    elseif ($_POST['protocol'] == 'PPPoE') {

                        pppoe_add($db, $_POST);
                        ip_rem_static($old);
                        dhcp_rem($old['ip']);
                    }
                    else {

                        error_protocol($_POST['protocol'], $old['userid']);
                    }

                    check_changes($old, $_POST);
                    break;
                case "PPPoE":
                    pppoe_remove($db, $old['ip'], $_POST['username']);

                    // The new protocol is IP
                    if ($_POST['protocol'] == 'IP') {

                        ip_add_static($_POST);
                    }
                    // The new protocol is DHCP
                    if ($_POST['protocol'] == 'DHCP') {

                        dhcp_add($_POST);
                        ip_add_static($_POST);
                    }

                    check_changes($old, $_POST);
                    break;
                default:
                    error_protocol($_POST['protocol'], $old['userid']);
                    break;
            }
        }
        else {
            switch ($old['protocol']) {
                case "IP":
                    check_vlan($old, $_POST);
                    check_mac($old, $_POST);
                    break;
                case "DHCP":
                    check_vlan($old, $_POST);
                    check_mac($old, $_POST);
                    check_dhcp($old, $_POST);
                    break;
                case "PPPoE":
                    pppoe_update($db, $old, $_POST);
                    break;
                default:
                    error_protocol($_POST['protocol'], $old['userid']);
                    break;
            }
        }

        check_stopped($db, $old, $_POST);
        check_changes($old, $_POST);

        if (!empty($update)) {

            $i = 1;
            foreach($update as $key => $value) {
                $keys[$i] = $key;
                $values[$i] = $value;

                $i++;
            }

            $sql = 'UPDATE ip SET '.implode(' = ?, ', $keys).' = ? WHERE ip = ?';

            array_push($values, $old['ip']);
            $db->prepare_array($sql, $values);
        }
    }

    $_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";
    header("Location: user.php?userid={$old['userid']}");
}

$_SESSION['msg'] .= _('No changes have been made.')."<br>";
header("Location: user.php?userid={$old['userid']}");
?>
