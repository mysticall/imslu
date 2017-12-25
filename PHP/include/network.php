<?php
/**
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

require_once dirname(__FILE__).'/config.php';


function error_protocol($protocol, $userid) {

    $_SESSION['msg'] .= _s("Unsupported protocol %s", $protocol)."<br>";
    header("Location: user.php?userid={$userid}");
    exit;
}

/**
* This function add static routing and MAC for IP address
* 
* @param Array $var - ['userid'], ['ip'], ['vlan'], ['mac'], ['free_mac']
*/
function ip_add_static($var) {

    global $SUDO;
    global $IMSLU_SCRIPTS;

    $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh ip_add '{$var['ip']}' '{$var['vlan']}' '{$var['free_mac']}' '{$var['mac']}' > /dev/null &";
    shell_exec($cmd);
}

/**
* This function removes static routing and MAC for IP address
* 
* @param Array $var - ['ip'], ['vlan']
*/
function ip_rem_static($var) {

    global $SUDO;
    global $IMSLU_SCRIPTS;

    $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh ip_rem '{$var['ip']}' '{$var['vlan']}' > /dev/null &";
    shell_exec($cmd);
}

/**
* Allow internet access for IP address
* 
* @param String $ip, Integer $serviceid
*/
function ip_allow($ip, $serviceid) {

    global $SUDO;
    global $IMSLU_SCRIPTS;

    $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh ip_allow '{$ip}' '{$serviceid}'";
    shell_exec($cmd);
}

/**
* Stop internet access for IP address
* 
* @param String $ip
*/
function ip_stop($ip) {

    global $SUDO;
    global $IMSLU_SCRIPTS;

    $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh ip_stop '{$ip}'";
    shell_exec($cmd);
}

/**
* This function add static MAC for IP address
* 
* @param Array $var - ['ip'], ['vlan'], ['mac'], ['free_mac']
*/
function mac_add($var) {

    global $SUDO;
    global $IMSLU_SCRIPTS;

    $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh mac_add '{$var['ip']}' '{$var['vlan']}' '{$var['free_mac']}' '{$var['mac']}'";
    shell_exec($cmd);
}

/**
* This function removes static MAC for IP address
* 
* @param Array $var - ['ip'], ['vlan']
*/
function mac_rem($var) {

    global $SUDO;
    global $IMSLU_SCRIPTS;

    $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh mac_rem '{$var['ip']}' '{$var['vlan']}'";
    shell_exec($cmd);
}

/**
* Add DHCP support for IP address
* 
* @param Array $var -> ['ip'], ['mac']
*/
function dhcp_add($var) {

    global $SUDO;
    global $IMSLU_SCRIPTS;

    $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh dhcp_add '{$var['ip']}' '{$var['mac']}'";
    $status = shell_exec($cmd);

    if ($status > 0) {

        $_SESSION['msg'] .= _s("DHCP support for IP %s failed!", $var['ip'])."<br>";
        header("Location: user.php?userid={$var['userid']}");
        exit;
    }
}

/**
* Remove DHCP support for IP address
* 
* @param String $var
*/
function dhcp_rem($var) {

    global $SUDO;
    global $IMSLU_SCRIPTS;

    $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh dhcp_rem '{$var}' > /dev/null &";
    shell_exec($cmd);
}

/**
* This function add all PPPoE rules for username
* 
* @param Array $var - ['userid'], ['mac'], ['username'], ['pass'], ['groupname']
*/
function pppoe_add($db, $var) {

    $str = strip_tags($var['username']);
    $username = preg_replace('/\s+/', '_', $str);
    $password = strip_tags($var['pass']);
    $mac = strtolower($var['mac']);

    $db->dbh->beginTransaction();
    $sql = 'INSERT INTO radcheck ( userid, username, attribute, op, value) VALUES (:userid, :username, :attribute, :op, :value)';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':userid', $var['userid'], PDO::PARAM_INT);
    $sth->bindValue(':username', $username, PDO::PARAM_STR);
    $sth->bindValue(':attribute', 'Cleartext-Password');
    $sth->bindValue(':op', ':=');
    $sth->bindValue(':value', $password, PDO::PARAM_STR);
    $sth->execute();

    $sql = 'INSERT INTO radcheck (userid, username, attribute, op, value) VALUES (:userid, :username, :attribute, :op, :value)';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':userid', $var['userid'], PDO::PARAM_INT);
    $sth->bindValue(':username', $username, PDO::PARAM_STR);
    $sth->bindValue(':attribute', 'Calling-Station-Id');
    $sth->bindValue(':value', $mac, PDO::PARAM_STR);
    $sth->bindValue(':op', ':=');
    $sth->execute();

    $sql = 'INSERT INTO radusergroup (username, groupname, userid) VALUES (:username, :groupname, :userid)';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':username', $username, PDO::PARAM_STR);
    $sth->bindValue(':groupname', $var['groupname'], PDO::PARAM_STR);
    $sth->bindValue(':userid', $var['userid'], PDO::PARAM_INT);
    $sth->execute();
    $db->dbh->commit();
}

/**
* This function update PPPoE rules for username
* 
* @param Array $old, $new - ['ip'], ['mac'], ['username'], ['pass'], ['groupname']
*/
function pppoe_update($db, $old, $new) {

    global $SUDO;
    global $IMSLU_SCRIPTS;
    global $update;

    $db->dbh->beginTransaction();

    if ($old['pass'] !=  $new['pass']) {

        $password = strip_tags($new['pass']);
        $update['pass'] = $password;

        $sql = 'UPDATE radcheck SET value = :value WHERE username = :username AND attribute = :attribute';
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(':value', $password);
        $sth->bindValue(':username', $old['username'], PDO::PARAM_STR);
        $sth->bindValue(':attribute', 'Cleartext-Password');
        $sth->execute();
    }

    if ($old['mac'] != $new['mac']) {

        $mac = strtolower($new['mac']);
        $update['mac'] = $mac;

        $sql = 'UPDATE radcheck SET value = :value WHERE username = :username AND attribute = :attribute';
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(':value', $mac);
        $sth->bindValue(':username', $old['username'], PDO::PARAM_STR);
        $sth->bindValue(':attribute', 'Calling-Station-Id');
        $sth->execute();
    }

    if ($old['groupname'] != $new['groupname']) {

        $sql = 'UPDATE radusergroup SET groupname = :groupname WHERE username = :username';
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(':groupname', $new['groupname'], PDO::PARAM_STR);
        $sth->bindValue(':username', $old['username'], PDO::PARAM_STR);
        $sth->execute();
    }

    if ($old['username'] != $new['username']) {

        $str = strip_tags($new['username']);
        $username = preg_replace('/\s+/', '_', $str);
        $update['username'] = $username;

        $sql = 'SELECT id FROM radcheck WHERE username = :username';
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(':username', $old['username'], PDO::PARAM_STR);
        $sth->execute();
        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

        if ($rows) {

            for ($i = 0; $i < count($rows); ++$i) {

                $sql = 'UPDATE radcheck SET username = :username WHERE id = :id';
                $sth = $db->dbh->prepare($sql);
                $sth->bindValue(':username', $username, PDO::PARAM_STR);
                $sth->bindValue(':id', $rows[$i]['id']);
                $sth->execute();
            }

            $sql = 'UPDATE radusergroup SET username = :username WHERE username = :user_name';
            $sth = $db->dbh->prepare($sql);
            $sth->bindValue(':username', $username, PDO::PARAM_STR);
            $sth->bindValue(':user_name', $old['username'], PDO::PARAM_STR);
            $sth->execute();
        }
    }

    $db->dbh->commit();

    $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh pppd_kill '{$old['ip']}'";
    shell_exec($cmd);
}

/**
* This function removes all PPPoE rules for username
* 
* @param String $ip - IP address, $username - PPPoE username
*/
function pppoe_remove($db, $ip, $username) {

    global $SUDO;
    global $IMSLU_SCRIPTS;

    $sql = 'DELETE FROM radcheck  WHERE username = :username';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':username', $username);
    $sth->execute();

    $sql = 'DELETE FROM radusergroup  WHERE username = :username';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':username', $username);
    $sth->execute();

    $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh pppd_kill '{$ip}'";
    shell_exec($cmd);
}

/**
* This function add all rules for new IP address
* 
* @param array $var - ['userid'], ['ip'], ['vlan'], ['mac'], ['free_mac'], ['username'], ['pass'], ['protocol'], ['stopped'], ['notes']
*/
function ip_add($db, $var) {

    global $OS;
    global $SUDO;
    global $IMSLU_SCRIPTS;

    if (!filter_var($var['ip'], FILTER_VALIDATE_IP)) {

        $_SESSION['msg'] .= _('Invalid IP Address!')."<br>";
        header("Location: user.php?userid={$var['userid']}");
        exit;
    }
    if (!empty($var['mac']) && !IsValidMAC($var['mac'])) {

        $_SESSION['msg'] .= _('Invalid MAC Address!')."<br>";
        header("Location: user.php?userid={$var['userid']}");
        exit;
    }

    switch ($var['protocol']) {
        case "IP":
            ip_add_static($var);
            break;
        case "DHCP":
            ip_add_static($var);
            dhcp_add($var);
            break;
        case "PPPoE":
            pppoe_add($db, $var);
            break;
        default:
            error_protocol($var['protocol'], $var['userid']);
            break;
    }

    ####### Get user info, ip adresses and payment #######
    $sql = 'SELECT * FROM users WHERE userid = :userid LIMIT 1';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':userid', $var['userid'], PDO::PARAM_INT);
    $sth->execute();
    $user = $sth->fetch(PDO::FETCH_ASSOC);

    $str = strip_tags($var['username']);
    $username = preg_replace('/\s+/', '_', $str);
    $password = strip_tags($var['pass']);
    $mac = strtolower($var['mac']);

    $sql = 'UPDATE ip SET userid = :userid, vlan = :vlan, mac = :mac, free_mac = :free_mac, username = :username, pass = :pass, protocol = :protocol, stopped = :stopped, notes = :notes  WHERE ip = :ip AND userid=0';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':userid', $var['userid'], PDO::PARAM_INT);
    $sth->bindValue(':vlan', $var['vlan'], PDO::PARAM_STR);
    $sth->bindValue(':mac', $mac, PDO::PARAM_STR);
    $sth->bindValue(':free_mac', $var['free_mac'], PDO::PARAM_STR);
    $sth->bindValue(':username', $username, PDO::PARAM_STR);
    $sth->bindValue(':pass', $password, PDO::PARAM_STR);
    $sth->bindValue(':protocol', $var['protocol'], PDO::PARAM_STR);
    $sth->bindValue(':stopped', $var['stopped'], PDO::PARAM_STR);
    $sth->bindValue(':notes', $var['notes'], PDO::PARAM_STR);
    $sth->bindValue(':ip', $var['ip'], PDO::PARAM_STR);
    $sth->execute();

    $now = date ("YmdHis");
    $expire = date("YmdHis", strtotime("{$user['expires']}"));

    // Start internet access
    if ($var['stopped'] == 'n' && ($user['free_access'] == 'y' || $expire > $now || substr($user['created'],0,10) == date('Y-m-d'))) {

        ip_allow($var['ip'], $user['serviceid']);
    }

    if ($OS == 'Linux') {
        // Add tc filter for IP
        $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh tc_filter_add '{$var['ip']}' '{$var['userid']}'";
        shell_exec($cmd);
    }
}

/**
* This function removes all rules for IP address
* 
* @param array $var - ['id'], ['userid'], ['ip'], ['vlan'], ['username'], ['pass']
*/
function ip_remove($db, $var) {

    global $OS;
    global $SUDO;
    global $IMSLU_SCRIPTS;

    switch ($var['protocol']) {
        case "IP":
            ip_rem_static($var);
            break;
        case "DHCP":
            ip_rem_static($var);
            dhcp_rem($var['ip']);
            break;
        case "PPPoE":
            pppoe_remove($db, $var['ip'], $var['username']);
            break;
        default:
            error_protocol($var['protocol'], $var['userid']);
            break;
    }

    $sql = 'UPDATE ip SET userid = :userid, vlan = :vlan, mac = :mac, free_mac = :free_mac, username = :username, pass = :pass, protocol = :protocol, stopped = :stopped, notes = :notes  WHERE id = :id';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':userid', 0);
    $sth->bindValue(':vlan', '');
    $sth->bindValue(':mac', '');
    $sth->bindValue(':free_mac', 'n');
    $sth->bindValue(':username', '');
    $sth->bindValue(':pass', '');
    $sth->bindValue(':protocol', 'IP');
    $sth->bindValue(':stopped', 'n');
    $sth->bindValue(':notes', '');
    $sth->bindValue(':id', $var['id'], PDO::PARAM_INT);
    $sth->execute();

    // Stop internet access for IP
    ip_stop($var['ip']);

    if ($OS == 'Linux') {
        // Remove tc filter for IP
        $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh tc_filter_delete {$var['ip']}";
        shell_exec($cmd);
    }
}

function check_vlan($old, $new) {

    global $SUDO;
    global $IMSLU_SCRIPTS;
    global $update;

    if ($old['vlan'] != $new['vlan']) {

        $update['vlan'] = $new['vlan'];

        // added a new vlan
        if (empty($old['vlan']) && !empty($new['vlan'])) {

            ip_add_static($new);
        }
        // vlan deleted
        elseif (!empty($old['vlan']) && empty($new['vlan'])) {

            ip_rem_static($old);
        }
        // vlan changed
        elseif (!empty($old['vlan']) && !empty($new['vlan'])) {

            ip_rem_static($old);
            ip_add_static($new);
        }
    }
}

function check_mac($old, $new) {

    global $SUDO;
    global $IMSLU_SCRIPTS;
    global $update;

    if ($old['mac'] != $new['mac']) {

        $update['mac'] = strtolower($new['mac']);

        // if VLAN is not empty, the following rules are applied above
        if (empty($update['vlan'])) {

            // added a new mac
            if(empty($old['mac']) && !empty($new['mac'])) {

                mac_add($new);
            }
            // mac deleted
            elseif(!empty($old['mac']) && empty($new['mac'])) {

                mac_rem($old);
            }
            // mac changed
            elseif(!empty($old['mac']) && !empty($new['mac'])) {

                mac_rem($old);
                mac_add($new);
            }
        }
    }

    if ($old['free_mac'] != $new['free_mac']) {

        $update['free_mac'] = $new['free_mac'];

        if (empty($update['vlan']) && empty($update['mac'])) {

            // free_mac = y
            if($new['free_mac'] == 'y') {

                mac_rem($old);  
            }
            // free_mac = n
            elseif($new['free_mac'] == 'n') {

                mac_add($new);
            }
        }
    }
}

function check_dhcp($old, $new) {

    global $SUDO;
    global $IMSLU_SCRIPTS;

    if ($old['mac'] != $new['mac']) {

        // added a new mac
        if(empty($old['mac']) && !empty($new['mac'])) {

            dhcp_add($new);
        }
        // mac deleted
        elseif(!empty($old['mac']) && empty($new['mac'])) {

            dhcp_rem($old['ip']);
        }
        // mac changed
        elseif(!empty($old['mac']) && !empty($new['mac'])) {

            dhcp_rem($old['ip']);
            dhcp_add($new);
        }
    }
}

function check_stopped($db, $old, $new) {

    global $update;

    // Stop or allow internet access for IP address.
    if ($old['stopped'] != $new['stopped']) {

        $update['stopped'] = $new['stopped'];

        // Stop
        if ($old['stopped'] == 'n' && $new['stopped'] == 'y') {

            // Add audit
            add_audit($db, AUDIT_ACTION_DISABLE, AUDIT_RESOURCE_IP, "The internet access for IP address {$old['ip']} stopped.");

            // Stop internet access for IP address
            ip_stop($old['ip']);
        }
        // Allow
        elseif ($old['stopped'] == 'y' && $new['stopped'] == 'n') {

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
}

function check_changes($old, $new) {

    global $update;

    if ($old['vlan'] != $new['vlan'] && empty($update['vlan'])) {

        $update['vlan'] = $new['vlan'];
    }
    if ($old['mac'] != $new['mac'] && empty($update['mac'])) {

        $update['mac'] = strtolower($new['mac']);
    }
    if ($old['free_mac'] != $new['free_mac'] && empty($update['free_mac'])) {

        $update['free_mac'] = $new['free_mac'];
    }
    if ($old['username'] != $new['username'] && empty($update['username'])) {

        $str = strip_tags($new['username']);
        $update['username'] = preg_replace('/\s+/', '_', $str);
    }
    if ($old['pass'] != $new['pass'] && empty($update['pass'])) {

        $update['pass'] = strip_tags($new['pass']);
    }
    if ($old['notes'] != $new['notes']) {

        $update['notes'] = $new['notes'];
    }
}

function check_protocol($protocol) {

    switch ($protocol) {
        case "IP":
        case "DHCP":
        case "PPPoE":
            return true;
            break;
        default:
            return false;
            break;
    }
}

?>
