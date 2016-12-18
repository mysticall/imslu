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

require_once dirname(__FILE__).'/config.php';

/**
 * This function add all PPPoE rules for username
 * 
 * @param Array $pppoe - ['userid'], ['mac'], ['username'], ['pass'], ['groupname']
 */
function pppoe_add($db, $pppoe) {

    $str = strip_tags($pppoe['username']);
    $username = preg_replace('/\s+/', '_', $str);
    $password = strip_tags($pppoe['pass']);

    $db->dbh->beginTransaction();
    $sql = 'INSERT INTO radcheck ( userid, username, attribute, op, value) VALUES (:userid, :username, :attribute, :op, :value)';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':userid', $pppoe['userid'], PDO::PARAM_INT);
    $sth->bindValue(':username', $username, PDO::PARAM_STR);
    $sth->bindValue(':attribute', 'Cleartext-Password');
    $sth->bindValue(':op', ':=');
    $sth->bindValue(':value', $password, PDO::PARAM_STR);
    $sth->execute();

    $sql = 'INSERT INTO radcheck (userid, username, attribute, op, value) VALUES (:userid, :username, :attribute, :op, :value)';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':userid', $pppoe['userid'], PDO::PARAM_INT);
    $sth->bindValue(':username', $username, PDO::PARAM_STR);
    $sth->bindValue(':attribute', 'Calling-Station-Id');
    $sth->bindValue(':value', $pppoe['mac'], PDO::PARAM_STR);
    $sth->bindValue(':op', ':=');
    $sth->execute();

    $sql = 'INSERT INTO radusergroup (username, groupname, userid) VALUES (:username, :groupname, :userid)';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':username', $username, PDO::PARAM_STR);
    $sth->bindValue(':groupname', $pppoe['groupname'], PDO::PARAM_STR);
    $sth->bindValue(':userid', $pppoe['userid'], PDO::PARAM_INT);
    $sth->execute();
    $db->dbh->commit();
}

/**
 * This function update PPPoE rules for username
 * 
 * @param Array $pppoe_old, $pppoe_new - ['ip'], ['mac'], ['username'], ['pass'], ['groupname']
 */
function pppoe_update($db, $pppoe_old, $pppoe_new) {

    global $SUDO;
    global $IMSLU_SCRIPTS;

    $db->dbh->beginTransaction();

    if (isset($pppoe_old['pass']) && $pppoe_old['pass'] !=  $pppoe_new['pass']) {

        $password = strip_tags($pppoe_new['pass']);

        $sql = 'UPDATE radcheck SET value = :value WHERE username = :username AND attribute = :attribute';
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(':value', $password);
        $sth->bindValue(':username', $pppoe_old['username'], PDO::PARAM_STR);
        $sth->bindValue(':attribute', 'Cleartext-Password');
        $sth->execute();
    }

    if (isset($pppoe_old['mac']) && $pppoe_old['mac'] != $pppoe_new['mac']) {

        $sql = 'UPDATE radcheck SET value = :value WHERE username = :username AND attribute = :attribute';
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(':value', $pppoe_new['mac']);
        $sth->bindValue(':username', $pppoe_old['username'], PDO::PARAM_STR);
        $sth->bindValue(':attribute', 'Calling-Station-Id');
        $sth->execute();
    }

    if (isset($pppoe_old['groupname']) && $pppoe_old['groupname'] != $pppoe_new['groupname']) {

        $sql = 'UPDATE radusergroup SET groupname = :groupname WHERE username = :username';
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(':groupname', $pppoe_new['groupname'], PDO::PARAM_STR);
        $sth->bindValue(':username', $pppoe_old['username'], PDO::PARAM_STR);
        $sth->execute();
    }

    if ($pppoe_old['username'] != $pppoe_new['username']) {

        $str = strip_tags($pppoe_new['username']);
        $username = preg_replace('/\s+/', '_', $str);

        $sql = 'SELECT id FROM radcheck WHERE username = :username';
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(':username', $pppoe_old['username'], PDO::PARAM_STR);
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
            $sth->bindValue(':user_name', $pppoe_old['username'], PDO::PARAM_STR);
            $sth->execute();
        }
    }

    $db->dbh->commit();

    $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh pppd_kill '{$pppoe_old['ip']}/32' 2>&1";
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

    $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh pppd_kill '{$ip}/32' 2>&1";
    shell_exec($cmd);
}

/**
 * This function add all rules for new IP address
 * 
 * @param array $ip - ['userid'], ['ip'], ['vlan'], ['mac'], ['free_mac'], ['username'], ['pass'], ['protocol'], ['stopped'], ['notes']
 */
function ip_add($db, $ip) {

    global $SUDO;
    global $IMSLU_SCRIPTS;

    if (!filter_var($ip['ip'], FILTER_VALIDATE_IP)) {

        $_SESSION['msg'] .= _('Invalid IP Address!')."<br>";
        header("Location: user.php?userid={$ip['userid']}");
        exit;
    }
    if (!empty($ip['mac']) && !IsValidMAC($ip['mac'])) {

        $_SESSION['msg'] .= _('Invalid MAC Address!')."<br>";
        header("Location: user.php?userid={$ip['userid']}");
        exit;
    }

    $now = date ("YmdHis");
    $expire = date("YmdHis", strtotime("{$user['expires']}"));

    ####### Get user info, ip adresses and payment #######
    $sql = 'SELECT users.*, payments.id as payment_id, payments.expires
            FROM users
            LEFT JOIN (SELECT id, userid, expires FROM payments WHERE userid = :payments_userid ORDER BY id DESC, expires DESC LIMIT 1) AS payments
            ON users.userid = payments.userid
            WHERE users.userid = :userid LIMIT 1';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':payments_userid', $ip['userid'], PDO::PARAM_INT);
    $sth->bindValue(':userid', $ip['userid'], PDO::PARAM_INT);
    $sth->execute();
    $user = $sth->fetch(PDO::FETCH_ASSOC);

    $sql = 'UPDATE ip SET userid = :userid, vlan = :vlan, mac = :mac, free_mac = :free_mac, username = :username, pass = :pass, protocol = :protocol, stopped = :stopped, notes = :notes  WHERE ip = :ip AND userid=0';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':userid', $ip['userid'], PDO::PARAM_INT);
    $sth->bindValue(':vlan', $ip['vlan'], PDO::PARAM_STR);
    $sth->bindValue(':mac', $ip['mac'], PDO::PARAM_STR);
    $sth->bindValue(':free_mac', $ip['free_mac'], PDO::PARAM_STR);
    $sth->bindValue(':username', $ip['username'], PDO::PARAM_STR);
    $sth->bindValue(':pass', $ip['pass'], PDO::PARAM_STR);
    $sth->bindValue(':protocol', $ip['protocol'], PDO::PARAM_STR);
    $sth->bindValue(':stopped', $ip['stopped'], PDO::PARAM_STR);
    $sth->bindValue(':notes', $ip['notes'], PDO::PARAM_STR);
    $sth->bindValue(':ip', $ip['ip'], PDO::PARAM_STR);
    $sth->execute();

    if (!empty($ip['username']) && ($ip['protocol'] == 'PPPoE')) {

        pppoe_add($db, $ip);
    }
    else {

        $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh ip_add '{$ip['ip']}' '{$ip['vlan']}' '{$ip['mac']}' '{$ip['free_mac']}' 2>&1";
        shell_exec($cmd);
    }

    // Start internet access
    if ($ip['stopped'] == 'n' && ($user['free_access'] == 'y' || $expire > $now || substr($user['created'],0,10) == date('Y-m-d'))) {

        $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh ip_allow '{$ip['ip']}' 2>&1";
        shell_exec($cmd);
    }

    // Add tc filter for IP
    $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh tc_filter_add '{$ip['ip']}' '{$ip['userid']}' 2>&1";
    shell_exec($cmd);
}

/**
 * This function removes all rules for IP address
 * 
 * @param array $ip - ['id'], ['userid'], ['ip'], ['vlan'], ['username'], ['pass']
 */
function ip_remove($db, $ip) {

    global $SUDO;
    global $IMSLU_SCRIPTS;

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
    $sth->bindValue(':id', $ip['id'], PDO::PARAM_INT);
    $sth->execute();

    if (!empty($ip['username']) && ($ip['protocol'] == 'PPPoE')) {

        pppoe_remove($db, $ip['ip'], $ip['username']);
    }
    else {

        $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh ip_rem '{$ip['ip']}' '{$ip['vlan']}' 2>&1";
        shell_exec($cmd);
    }

    // Stop internet access for IP
    $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh ip_stop {$ip['ip']} 2>&1";
    shell_exec($cmd);

    // Remove tc filter for IP
    $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh tc_filter_delete {$ip['ip']} 2>&1";
    shell_exec($cmd);
}
?>