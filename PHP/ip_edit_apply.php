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

  // Validate IP Address
  if (!filter_var($_POST['ip'], FILTER_VALIDATE_IP)) {

    $_SESSION['msg'] .= _('Invalid IP Address!')."<br>";
    header("Location: user.php?userid={$ip['userid']}");
    exit;
  }
  // Validate MAC
  if (!empty($_POST['mac']) && !IsValidMAC($_POST['mac'])) {

    $_SESSION['msg'] .= _('Invalid MAC Address!')."<br>";
    header("Location: user.php?userid={$old['userid']}");
    exit;
  }

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

    // The protocol is changed.
    if ($old['protocol'] != $_POST['protocol']) {

      $update['vlan'] = $_POST['vlan'];
      $update['mac'] = $_POST['mac'];
      $update['free_mac'] = $_POST['free_mac'];
      $update['protocol'] = $_POST['protocol'];

      // Point-to-Point Protocol over Ethernet (PPPoE)
      if ($old['protocol'] != 'PPPoE' && $_POST['protocol'] == 'PPPoE') {

        $_POST['userid'] = $old['userid'];
        pppoe_add($db, $_POST);

        $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh ip_rem '{$old['ip']}' '{$old['vlan']}'";
        shell_exec($cmd);
      }
      // Internet Protocol with static address
      if ($old['protocol'] == 'PPPoE' && $_POST['protocol'] != 'PPPoE') {

        pppoe_remove($db, $old['ip'], $_POST['username']);

        $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh ip_add '{$old['ip']}' '{$_POST['vlan']}' '{$_POST['free_mac']}' '{$_POST['mac']}'";
        shell_exec($cmd);
      }
    }

    // Internet Protocol with static address
    if (empty($update['protocol']) && $old['protocol'] == 'IP') {

      // VLANs
      if ($USE_VLANS && $old['vlan'] != $_POST['vlan']) {

        $update['vlan'] = $_POST['vlan'];

        // added a new vlan
        if(empty($old['vlan']) && !empty($_POST['vlan'])) {

          $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh ip_add '{$old['ip']}' '{$_POST['vlan']}' '{$_POST['free_mac']}' '{$_POST['mac']}'";
          shell_exec($cmd);
        }
        // vlan deleted
        elseif(!empty($old['vlan']) && empty($_POST['vlan'])) {

          $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh ip_rem '{$old['ip']}' '{$old['vlan']}'";
          shell_exec($cmd);
        }
        // vlan changed
        elseif(!empty($old['vlan']) && !empty($_POST['vlan'])) {

          $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh ip_rem '{$old['ip']}' '{$old['vlan']}'";
          shell_exec($cmd);

          $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh ip_add '{$old['ip']}' '{$_POST['vlan']}' '{$_POST['free_mac']}' '{$_POST['mac']}'";
          shell_exec($cmd);
        }
      }

      if ($old['mac'] != $_POST['mac']) {

        $update['mac'] = $_POST['mac'];

        // if VLAN is not empty, the following rules are applied above
        if (empty($update['vlan'])) {

          // added a new mac
          if(empty($old['mac']) && !empty($_POST['mac'])) {

            $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh mac_add '{$old['ip']}' '{$_POST['vlan']}' '{$_POST['free_mac']}' '{$_POST['mac']}'";
            shell_exec($cmd);
          }
          // mac deleted
          elseif(!empty($old['mac']) && empty($_POST['mac'])) {

            $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh mac_rem '{$old['ip']}' '{$old['vlan']}'";
            shell_exec($cmd);
          }
          // mac changed
          elseif(!empty($old['mac']) && !empty($_POST['mac'])) {

            $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh mac_rem '{$old['ip']}' '{$old['vlan']}'";
            shell_exec($cmd);

            $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh mac_add '{$old['ip']}' '{$_POST['vlan']}' '{$_POST['free_mac']}' '{$_POST['mac']}'";
            shell_exec($cmd);
          }
        }
      }

      if ($old['free_mac'] != $_POST['free_mac']) {

        $update['free_mac'] = $_POST['free_mac'];

        if (empty($update['vlan']) && empty($update['mac'])) {

          // free_mac = y
          if($_POST['free_mac'] == 'y') {

            $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh mac_rem '{$old['ip']}' '{$old['vlan']}'";
            shell_exec($cmd);  
          }
          // free_mac = n
          elseif($_POST['free_mac'] == 'n') {

            $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh mac_add '{$old['ip']}' '{$_POST['vlan']}' '{$_POST['free_mac']}' '{$_POST['mac']}'";
            shell_exec($cmd);
          }
        }
      }

      // Update username and password information in DB
      if ($old['username'] != $_POST['username']) {
        $update['username'] = $_POST['username'];
      }
      if ($old['pass'] != $_POST['pass']) {
        $update['pass'] = $_POST['pass'];
      }
    }

    // Point-to-Point Protocol over Ethernet (PPPoE)
    if ($USE_PPPoE && empty($update['protocol']) && $old['protocol'] == 'PPPoE') {

      $pppoe_old = array();
      $pppoe_new = array();

      // Update only VLAN information in DB
      if ($old['vlan'] != $_POST['vlan']) {

        $update['vlan'] = $_POST['vlan'];
      }
      if ($old['mac'] != $_POST['mac']) {

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
    }

    // Stop or allow internet access for IP address.
    if ($old['stopped'] != $_POST['stopped']) {

      $update['stopped'] = $_POST['stopped'];

      // Stop
      if ($old['stopped'] == 'n' && $_POST['stopped'] == 'y') {

        // Add audit
        add_audit($db, AUDIT_ACTION_DISABLE, AUDIT_RESOURCE_IP, "The internet access for IP address {$old['ip']} stopped.");

        // Stop internet access for IP address
        ip_stop($old['ip']);
      }
      // Allow
      if ($old['stopped'] == 'y' && $_POST['stopped'] == 'n') {

        $sql = 'SELECT free_access, serviceid, expires FROM users WHERE userid = :userid LIMIT 1';
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(':userid', $old['userid'], PDO::PARAM_INT);
        $sth->execute();
        $user = $sth->fetch(PDO::FETCH_ASSOC);

        $now = date ("YmdHis");
        $expire = date("YmdHis", strtotime("{$user['expires']}"));

        if ($user['free_access'] == 'y' || $expire > $now) {

          // Add audit
          add_audit($db, AUDIT_ACTION_ENABLE, AUDIT_RESOURCE_IP, "The internet access for IP address {$old['ip']} allowed.");

          // Allow internet access for IP address
          ip_allow($old['ip'], $user['serviceid']);
        }
      }
    }

    // Update notes for IP address.
    if ($old['notes'] != $_POST['notes']) {

      $update['notes'] = $_POST['notes'];
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
}
?>