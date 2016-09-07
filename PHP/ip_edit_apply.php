<?php
/*
 * IMSLU version 0.2-alpha
 *
 * Copyright © 2016 IMSLU Developers
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

if ($_SESSION['form_key'] !== $_POST['form_key']) {

    header('Location: index.php');
    exit;
}

# Must be included after session check
require_once dirname(__FILE__).'/include/config.php';
require_once dirname(__FILE__).'/include/network.php';

$db = new PDOinstance();
$admin_permissions = (OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type'] || OPERATOR_TYPE_ADMIN == $_SESSION['data']['type']);
$cashier_permissions = (OPERATOR_TYPE_CASHIER == $_SESSION['data']['type']);
$technician_permissions = (OPERATOR_TYPE_TECHNICIAN == $_SESSION['data']['type']);

$old = json_decode($_POST['old'], true);
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

    // The IP address is changed.
    if ($old['ip'] != $_POST['ip']) {

        // Add audit
        add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_IP, "IP address {$old['ip']} changed with {$_POST['ip']}.", json_encode($old));

        ip_remove($db, $old);
        $_POST['userid'] = $old['userid'];
        ip_add($db, $_POST);

        header("Location: user.php?userid={$_POST['userid']}");
        exit;
    }
    else {

    $update = array();

    // VLANs (the condition for PPPoE is out below)
    if ($USE_VLANS && $old['protocol'] != 'PPPoE') {

        if ($old['vlan'] != $_POST['vlan']) {

            $update['vlan'] = $_POST['vlan'];

            // added a new vlan
            if(empty($old['vlan']) && !empty($_POST['vlan'])) {

                $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh ip_add '{$old['ip']}' '{$_POST['vlan']}' '{$_POST['mac']}' 2>&1";
                $result = shell_exec($cmd);
#                $_SESSION['msg'] .= ($result) ? "$result <br>" : "";
            }
            // vlan deleted
            elseif(!empty($old['vlan']) && empty($_POST['vlan'])) {

                $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh ip_rem '{$old['ip']}' '{$old['vlan']}' 2>&1";
                $result = shell_exec($cmd);
#                $_SESSION['msg'] .= ($result) ? "$result <br>" : "";
            }
            // vlan changed
            elseif(!empty($old['vlan']) && !empty($_POST['vlan'])) {

                $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh ip_rem '{$old['ip']}' '{$old['vlan']}' 2>&1";
                $result = shell_exec($cmd);
#                $_SESSION['msg'] .= ($result) ? "$result <br>" : "";

                $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh ip_add '{$old['ip']}' '{$_POST['vlan']}' '{$_POST['mac']}' '{$_POST['free_mac']}' 2>&1";
                $result = shell_exec($cmd);
#                $_SESSION['msg'] .= ($result) ? "$result <br>" : "";
            }
        }

        if ($old['mac'] != $_POST['mac']) {

            if (!empty($_POST['mac']) && !IsValidMAC($_POST['mac'])) {

                $_SESSION['msg'] .= _('Invalid MAC Address!')."<br>";
                header("Location: user.php?userid={$old['userid']}");
                exit;
            }

            $update['mac'] = $_POST['mac'];

            // if VLAN is not empty, the following rules are applied above
            if (empty($update['vlan'])) {

                // added a new mac
                if(empty($old['mac']) && !empty($_POST['mac'])) {
    
                    $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh mac_add '{$old['ip']}' '{$_POST['vlan']}' '{$_POST['mac']}' '{$_POST['free_mac']}' 2>&1";
                    $result = shell_exec($cmd);
#                    $_SESSION['msg'] .= ($result) ? "$result <br>" : "";
                }
                // mac deleted
                elseif(!empty($old['mac']) && empty($_POST['mac'])) {
    
                    $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh mac_rem '{$old['ip']}' '{$old['vlan']}' 2>&1";
                    $result = shell_exec($cmd);
#                    $_SESSION['msg'] .= ($result) ? "$result <br>" : "";
                }
                // mac changed
                elseif(!empty($old['mac']) && !empty($_POST['mac'])) {
    
                    $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh mac_rem '{$old['ip']}' '{$old['vlan']}' 2>&1";
                    $result = shell_exec($cmd);
#                    $_SESSION['msg'] .= ($result) ? "$result <br>" : "";
    
                    $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh mac_add '{$old['ip']}' '{$_POST['vlan']}' '{$_POST['mac']}' '{$_POST['free_mac']}' 2>&1";
                    $result = shell_exec($cmd);
#                    $_SESSION['msg'] .= ($result) ? "$result <br>" : "";
                }
            }
        }

        if ($old['free_mac'] != $_POST['free_mac']) {

            $update['free_mac'] = $_POST['free_mac'];

            if (empty($update['vlan']) &&  empty($update['mac'])) {

                // free_mac = y
                if($_POST['free_mac'] == 'y') {
    
                    $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh mac_rem '{$old['ip']}' '{$old['vlan']}' 2>&1";
                    $result = shell_exec($cmd);
#                    $_SESSION['msg'] .= ($result) ? "$result <br>" : "";    
                }
                // free_mac = n
                elseif($_POST['free_mac'] == 'n') {

                    $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh mac_add '{$old['ip']}' '{$_POST['vlan']}' '{$_POST['mac']}' '{$_POST['free_mac']}' 2>&1";
                    $result = shell_exec($cmd);
#                    $_SESSION['msg'] .= ($result) ? "$result <br>" : "";
                }
            }
        }
    }

    // PPPoE
    if ($USE_PPPoE && empty($update['vlan']) && empty($update['mac'])) {

        $pppoe_old = array();
        $pppoe_new = array();

        // Update only VLAN information in DB
        if ($old['vlan'] != $_POST['vlan']) {

            $update['vlan'] = $_POST['vlan'];
        }
        if ($old['mac'] != $_POST['mac']) {

            if (!empty($_POST['mac']) && !IsValidMAC($_POST['mac'])) {

                $_SESSION['msg'] .= _('Invalid MAC Address!')."<br>";
                header("Location: user.php?userid={$old['userid']}");
                exit;
            }

            $update['mac'] = $_POST['mac'];
            $pppoe_old['mac'] = $old['mac'];
            $pppoe_new['mac'] = $_POST['mac'];
        }
        if ($old['free_mac'] != $_POST['free_mac']) {

            $update['free_mac'] = $_POST['free_mac'];
        }
        if ($old['groupname'] != $_POST['groupname']) {

            $pppoe_old['groupname'] = $old['groupname'];
            $pppoe_new['groupname'] = $_POST['groupname'];
        }
        if ($old['username'] != $_POST['username']) {

            $update['username'] = $_POST['username'];
            $pppoe_old['username'] = $old['username'];
            $pppoe_new['username'] = $_POST['username'];
        }
        if ($old['pass'] != $_POST['pass']) {

            $update['pass'] = $_POST['pass'];
            $pppoe_old['pass'] = $old['pass'];
            $pppoe_new['pass'] = $_POST['pass'];
        }

        if(!empty($pppoe_old)) {

            $pppoe_old['username'] = $old['username'];
            $pppoe_new['username'] = $_POST['username'];
            $pppoe_old['ip'] = $old['ip'];
            pppoe_update($db, $pppoe_old, $pppoe_new);
        }

        // The protocol is changed.
        if ($old['protocol'] != $_POST['protocol']) {

            $update['protocol'] = $_POST['protocol'];

            // Point-to-Point Protocol over Ethernet (PPPoE)
            if ($old['protocol'] != 'PPPoE' && $_POST['protocol'] == 'PPPoE') {

                $_POST['userid'] = $old['userid'];
                pppoe_add($db, $_POST);

                $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh ip_rem '{$old['ip']}' '{$old['vlan']}' 2>&1";
                $result = shell_exec($cmd);
#                $_SESSION['msg'] .= ($result) ? "$result <br>" : "";
            }
            // Internet Protocol with static address
            if ($old['protocol'] == 'PPPoE' && $_POST['protocol'] != 'PPPoE') {

                pppoe_remove($db, $old['ip'], $_POST['username']);

                $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh ip_add '{$old['ip']}' '{$_POST['vlan']}' '{$_POST['mac']}' '{$_POST['free_mac']}' 2>&1";
                $result = shell_exec($cmd);
#                $_SESSION['msg'] .= ($result) ? "$result <br>" : "";
            }
        }
    }

    if (!$USE_VLANS && !$USE_PPPoE) {

        if ($old['mac'] != $_POST['mac']) {

            if (!empty($_POST['mac']) && !IsValidMAC($_POST['mac'])) {

                $_SESSION['msg'] .= _('Invalid MAC Address!')."<br>";
                header("Location: user.php?userid={$old['userid']}");
                exit;
            }

            $update['mac'] = $_POST['mac'];

            // added a new mac
            if(empty($old['mac']) && !empty($_POST['mac'])) {
    
                $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh mac_add '{$old['ip']}' '' '{$_POST['mac']}' '{$_POST['free_mac']}' 2>&1";
                $result = shell_exec($cmd);
#                $_SESSION['msg'] .= ($result) ? "$result <br>" : "";
            }
            // mac deleted
            elseif(!empty($old['mac']) && empty($_POST['mac'])) {
    
                $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh mac_rem '{$old['ip']}' 2>&1";
                $result = shell_exec($cmd);
#                $_SESSION['msg'] .= ($result) ? "$result <br>" : "";
            }
            // mac changed
            elseif(!empty($old['mac']) && !empty($_POST['mac'])) {
    
                $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh mac_rem '{$old['ip']}' 2>&1";
                $result = shell_exec($cmd);
#                $_SESSION['msg'] .= ($result) ? "$result <br>" : "";
    
                $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh mac_add '{$old['ip']}' '' '{$_POST['mac']}' '{$_POST['free_mac']}' 2>&1";
                $result = shell_exec($cmd);
#                $_SESSION['msg'] .= ($result) ? "$result <br>" : "";
            }
        }

        if ($old['free_mac'] != $_POST['free_mac']) {

            $update['free_mac'] = $_POST['free_mac'];

            if (empty($update['vlan']) &&  empty($update['mac'])) {

                // free_mac = y
                if($_POST['free_mac'] == 'y') {
    
                    $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh mac_rem '{$old['ip']}' 2>&1";
                    $result = shell_exec($cmd);
#                    $_SESSION['msg'] .= ($result) ? "$result <br>" : "";    
                }
                // free_mac = n
                elseif($_POST['free_mac'] == 'n') {

                    $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh mac_add '{$old['ip']}' '' '{$_POST['mac']}' '{$_POST['free_mac']}' 2>&1";
                    $result = shell_exec($cmd);
#                    $_SESSION['msg'] .= ($result) ? "$result <br>" : "";
                }
            }
        }
    }

    // The internet access for IP address is changed.
    if ($old['stopped'] != $_POST['stopped']) {

        $update['stopped'] = $_POST['stopped'];

        // Stop
        if ($old['stopped'] == 'n' && $_POST['stopped'] == 'y') {

            // Add audit
            add_audit($db, AUDIT_ACTION_DISABLE, AUDIT_RESOURCE_IP, "The internet access for IP address {$old['ip']} stopped.");

            $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh ip_stop {$old['ip']} 2>&1";
            $result = shell_exec($cmd);
#            $_SESSION['msg'] .= ($result) ? "$result <br>" : "";
        }
        // Allow
        if ($old['stopped'] == 'y' && $_POST['stopped'] == 'n') {

            $now = date('Y-m-d H:i:s');

            // Add audit
            add_audit($db, AUDIT_ACTION_ENABLE, AUDIT_RESOURCE_IP, "The internet access for IP address {$old['ip']} allowed.");

            $sql = 'SELECT users.free_access, payments.expires
                    FROM users
                    LEFT JOIN payments
                    ON users.userid = payments.userid
                    WHERE users.userid = :userid ORDER BY payments.expires DESC LIMIT 1';
            $sth = $db->dbh->prepare($sql);
            $sth->bindValue(':userid', $old['userid'], PDO::PARAM_INT);
            $sth->execute();
            $user = $sth->fetch(PDO::FETCH_ASSOC);

            if ($user['free_access'] == 'y' || $user['expires'] > $now) {

                $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh ip_allow {$old['ip']} 2>&1";
                $result = shell_exec($cmd);
#                $_SESSION['msg'] .= ($result) ? "$result <br>" : "";
            }
        }
    }

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
    exit;
}
?>