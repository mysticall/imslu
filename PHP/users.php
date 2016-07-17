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
 
//enable debug mode
error_reporting(E_ALL); ini_set('display_errors', 'On');

require_once dirname(__FILE__).'/include/common.php';

// Check for active session
if (empty($_COOKIE['imslu_sessionid']) || !$Operator->authentication($_COOKIE['imslu_sessionid'])) {

    header('Location: index.php');
    exit;
}

# Must be included after session check
require_once dirname(__FILE__).'/include/config.php';

$db = new PDOinstance();
$now = date('Y-m-d H:i:s');
$locationid = (!empty($_GET['locationid']) && $_GET['locationid'] != 'all') ? $_GET['locationid'] : '';
$service = (!empty($_GET['service']) && $_GET['service'] != 'all') ? $_GET['service'] : '';
$status = (!empty($_GET['payment']) && $_GET['payment'] != 'all') ? $_GET['payment'] : '';
$more = (!empty($_GET['more']) && $_GET['more'] != 'all') ? $_GET['more'] : '';
$activ = (!empty($_GET['activity']) && $_GET['activity'] != 'all') ? $_GET['activity'] : '';
$pool = (!empty($_GET['pool']) && $_GET['pool'] != 'all') ? $_GET['pool'] : '';

// count
$count_users = 0;
$count_location = array();
$count_no_location = 0;
$count_services = array();
$count_free_access = 0;
$count_not_excluding = 0;
$count_expired = 0;
$count_paid = 0;
$count_obligation = 0;

$count_ip = 0;
$count_pool = array();
$count_pppoe = 0;
$count_active = 0;
$count_inactive = 0;

$pool = array();
$all = array();
$pppoe = array();
$active = array();
$inactive = array();

// SQL
$_sql = '';

$sql_expired = '';
$sql_paid = '';
$sql_obligation = '';

$sql_pppoe = '';
$sql_active = '';
$sql_inactive = '';

####### PAGE HEADER #######
$page['title'] = 'Users';

require_once dirname(__FILE__).'/include/page_header.php';

####### Display messages #######
echo !empty($_SESSION['msg']) ? '<div id="msg" class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
$_SESSION['msg'] = null;


### locations ###
$sql = 'SELECT id,name FROM location';
$sth = $db->dbh->prepare($sql);
$sth->execute();
$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

if ($rows) {
    for ($i = 0; $i < count($rows); ++$i) {

        $location_[$rows[$i]['id']] = $rows[$i]['name'];
        $count_location[$rows[$i]['id']] = 0;
    }
}

### services ###
$sql = 'SELECT name FROM services GROUP BY name';
$sth = $db->dbh->prepare($sql);
$sth->execute();
$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

if ($rows) {
    for ($i = 0; $i < count($rows); ++$i) {

        $services_[$rows[$i]['name']] = $rows[$i]['name'];
        $count_services[$rows[$i]['name']] = 0;
    }
}

### payments ###
$sql = 'SELECT userid, unpaid, limited, expires FROM payments WHERE expires > :expires GROUP BY userid';
$sth = $db->dbh->prepare($sql);
$sth->bindValue(':expires', $now, PDO::PARAM_INT);
$sth->execute();
$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

if ($rows) {
    for ($i = 0; $i < count($rows); ++$i) {

        $payments_[$rows[$i]['userid']] = $rows[$i];
    }
}

####### Get user info and payment #######
$sql = 'SELECT * FROM users';
$sth = $db->dbh->prepare($sql);
$sth->execute();
$users = $sth->fetchAll(PDO::FETCH_ASSOC);

if (!empty($users)) {

    $count_users = count($users);
    for ($i=0; $i < $count_users; $i++) {

        if (!empty($location_[$users[$i]['locationid']])) {
            $count_location[$users[$i]['locationid']]++;
        }
        else {
            $count_no_location++;
        }

        $count_services[$users[$i]['service']]++;

        if ($users[$i]['free_access'] == 'y') {
            $count_free_access++;
        }
        if ($users[$i]['not_excluding'] == 'y') {
            $count_not_excluding++;
        }

        if (!empty($payments_[$users[$i]['userid']]) && $payments_[$users[$i]['userid']]['unpaid'] == 0) {
            $sql_paid .= " userid={$users[$i]['userid']} OR";
            $count_paid++;
        }
        elseif (!empty($payments_[$users[$i]['userid']]) && $payments_[$users[$i]['userid']]['unpaid'] == 1) {
            $sql_obligation .= " userid={$users[$i]['userid']} OR";
            $count_obligation++;
        }
        else {
            $sql_expired .= " userid={$users[$i]['userid']} OR";
            $count_expired++;
        }
    }
}

### locations ###
$location = array('all' => _('all')." ({$count_users})", 'no_loc' => _('no location')." ({$count_no_location})");
foreach ($location_ as $key => $value) {
    $location[$key] = "{$value} ({$count_location[$key]})";
}

### services ###
$services = array('all' => _('all')." ({$count_users})");
foreach ($services_ as $key => $value) {
    $services[$key] = "{$value} ({$count_services[$key]})";
}
unset($services_);

### payments ###
$payments = array(
    'all' => _('all')." ({$count_users})",
    'paid' => _('paid')." ({$count_paid})",
    'expired' => _('expired')." ({$count_expired})",
    'obligation' => _('obligation')." ({$count_obligation})"
    );
unset($payments_);

### more ###
$more_ = array(
    'all' => _('all')." ({$count_users})",
    'free_access' => _('free access')." ({$count_free_access})",
    'not_excluding' => _('not excluding')." ({$count_not_excluding})"
    );

####### IP & pool #######

### activity ###
$cmd = "cat /tmp/ip_activity";
$result = shell_exec($cmd);
foreach (explode("\n", $result) as $value) {

    $activity_[$value] = $value;
}

if ($USE_PPPoE) {
    $cmd = "cat /tmp/ip_activity_pppoe";
    $result = shell_exec($cmd);
    foreach (explode("\n", $result) as $value) {

        $activity_[$value] = $value;
    }
}

### pool ###
$sql = 'SELECT pool FROM ip GROUP BY pool';
$sth = $db->dbh->prepare($sql);
$sth->execute();
$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

if ($rows) {
    for ($i = 0; $i < count($rows); ++$i) {

        $pools_[$rows[$i]['pool']] = $rows[$i]['pool'];
        $count_pool[$rows[$i]['pool']] = 0;
    }
}

$sql = 'SELECT * FROM ip WHERE userid != 0';
$sth = $db->dbh->prepare($sql);
$sth->execute();
$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

if (!empty($rows)) {

    $count_ip = count($rows);
    for ($i=0; $i < $count_ip; $i++) {

        $count_pool[$rows[$i]['pool']]++;

        if ($rows[$i]['protocol'] == 'PPPoE') {
            $count_pppoe++;
        }
        if (!empty($activity_[$rows[$i]['ip']])) {
            $count_active++;
        }
        else{
            $count_inactive++;
        }

        if (!empty($activ)) {
            if ($activ == 'pppoe' && $rows[$i]['protocol'] == 'PPPoE') {
                $pppoe[$rows[$i]['userid']][$rows[$i]['id']] = $rows[$i];
                $sql_pppoe .= "userid={$rows[$i]['userid']} OR ";
            }
            elseif ($activ == 'active' && !empty($activity_[$rows[$i]['ip']])) {
                $active[$rows[$i]['userid']][$rows[$i]['id']] = $rows[$i];
                $sql_active .= "userid={$rows[$i]['userid']} OR ";
            }
            elseif ($activ == 'inactive') {
                $inactive[$rows[$i]['userid']][$rows[$i]['id']] = $rows[$i];
                $sql_inactive .= "userid={$rows[$i]['userid']} OR ";
            }
        }
        else {
            $all[$rows[$i]['userid']][$rows[$i]['id']] = $rows[$i];
        }
    }
}

### activity ###
$activity = array(
    'all' => _('all')." ({$count_ip})",
    'pppoe' => _('pppoe')." ({$count_pppoe})",
    'active' => _('active')." ({$count_active})",
    'inactive' => _('inactive')." ({$count_inactive})"
    );

### pool ###
$pools = array('all' => _('all')." ({$count_ip})");
foreach ($pools_ as $key => $value) {
    $pools[$key] = "{$value} ({$count_pool[$key]})";
}
unset($pool_);

$form =
"    <form action=\"{$_SERVER['PHP_SELF']}\" method=\"get\">
      <table>
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th>"._('location')."</th>
            <th>".combobox('', 'locationid', $locationid, $location)."</th>
            <th>"._('services')."</th>
            <th>".combobox('', 'service', $service, $services)."</th>
            <th>"._('status')."</th>
            <th>".combobox('', 'payment', $status, $payments)."</th>
            <th></th>
          </tr>
          <tr class=\"header_top\">
            <th>"._('more')."</th>
            <th>".combobox('', 'more', $more, $more_)."</th>
            <th>"._('activity')."</th>
            <th>".combobox('', 'activity', $activ, $activity)."</th>
            <th>"._('pool')."</th>
            <th>".combobox('', 'pool', $pool, $pools)."</th>
            <th><input class=\"button\" type=\"submit\" name=\"show\" value=\""._('search')."\"></th>
          </tr>
        </tbody>
      </table>
    </form>\n";

echo $form;


####### Show Users #######
if (!empty($_GET['show'])) {

    if (!empty($activ)) {
        if ($activ == 'pppoe') {
            $ip = $pppoe;
            $_sql .= $sql_pppoe;
        }
        elseif ($activ == 'active') {
            $ip = $active;
            $_sql .= $sql_active;
        }
        elseif ($activ == 'inactive') {
            $ip = $inactive;
            $_sql .= $sql_inactive;
        }
    }
    else {
        $ip = $all;
    }

    if (!empty($status)) {
        if ($status == 'paid'){
            $_sql .= $sql_paid;
        }
        if ($status == 'expired'){
            $_sql .= $sql_expired;
        }
        if ($status == 'obligation'){
            $_sql .= $sql_obligation;
        }
    }

    $_sql = (!empty($_sql)) ? "WHERE (".substr($_sql, 0, -3).") " : "WHERE userid != 0 ";

    $_sql .= ($locationid) ? 'AND locationid = :locationid ' : '';
    $_sql .= ($service) ? 'AND service = :service ' : '';
    $_sql .= ($more) ? "AND {$more} = :more " : '';

    ####### Get user info and payment #######
    $sql = "SELECT * FROM users {$_sql}";
        $sth = $db->dbh->prepare($sql);

    if ($locationid) {
        if ($locationid == 'no_loc') {
            $sth->bindValue(':locationid', 0, PDO::PARAM_INT);
        }
        else {
            $sth->bindValue(':locationid', $locationid, PDO::PARAM_INT);
        }
    }
    if ($service) {
        $sth->bindValue(':service', $service, PDO::PARAM_STR);
    }
    if ($more) {
        $sth->bindValue(':more', 'y', PDO::PARAM_STR);
    }

    $sth->execute();
    $users = $sth->fetchAll(PDO::FETCH_ASSOC);


        $form =
"      <table>
        <thead id=\"thead\">
          <tr class=\"header_top\">
            <th colspan=\"13\">
              <label style=\"float: left;\">". _('total').": ".count($users)."</label>
              <label>". _('users')."</label>
            </th>
          </tr> \n";

        $form .=
"          <tr class=\"header\">
            <th>"._('id')."</th>
            <th>"._('name')."</th>
            <th>"._('location')."</th>
            <th>"._('address')."</th>
            <th>"._('phone')."</th>
            <th>"._('service')."</th>
            <th>"._('pay')."</th>
            <th>"._('IP address')."</th>
            <th>"._('vlan')."</th>
            <th>"._('mac')."</th>
            <th>"._('username')."</th>
          </tr>
        </thead>
        <tbody>\n";


        for ($i = 0; $i < count($users); ++$i) {

            $class = ($i % 2 == 0) ? "class=\"even_row\"" : "class=\"odd_row\"";
            $pay = ($users[$i]['pay'] == '0.00') ? "" : $users[$i]['pay'];
            $free_access = ($users[$i]['free_access'] == 'y') ? _('Yes') : _('No');
            $not_excluding = ($users[$i]['not_excluding'] == 'y') ? _('Yes') : _('No');
            $user_location = (!empty($location_[$users[$i]['locationid']])) ? $location_[$users[$i]['locationid']] : '';

            if (!empty($ip[$users[$i]['userid']])) {

                $ip_address = '';
                $count_ip = count($ip[$users[$i]['userid']]);
                $int = 1;
                foreach($ip[$users[$i]['userid']] as $value) {

                    $ip_activity = (!empty($activity_[$value['ip']])) ? "style=\"background-color: #77e0a8;\"" : "style=\"background-color: #ffc1c1;\"";
                    $ip_address .= ($int == 1) ?
"            <td {$ip_activity}>{$value['ip']}</td>
            <td>{$value['vlan']}</td>
            <td>{$value['mac']}</td>
            <td>{$value['username']}</td> \n" :

"          <tr {$class}>
            <td {$ip_activity}>{$value['ip']}</td>
            <td>{$value['vlan']}</td>
            <td>{$value['mac']}</td>
            <td>{$value['username']}</td> 
          </tr> \n";
                    $int++;
                }
            }
            else {
                $count_ip = 1;
                $ip_address = 
"            <td></td>
            <td></td>
            <td></td>
            <td></td> \n";
            }

            $form .= 
"          <tr {$class}>
            <td rowspan=\"{$count_ip}\">{$users[$i]['userid']}</td>
            <td rowspan=\"{$count_ip}\"><a  class=\"bold\" href=\"user.php?userid={$users[$i]['userid']}\">".chars($users[$i]['name'])."</a></td>
            <td rowspan=\"{$count_ip}\">{$user_location}</td>
            <td rowspan=\"{$count_ip}\">".chars($users[$i]['address'])."</td>
            <td rowspan=\"{$count_ip}\">".chars($users[$i]['phone_number'])."</td>
            <td rowspan=\"{$count_ip}\">{$users[$i]['service']}</td>
            <td rowspan=\"{$count_ip}\">{$pay}</td>
{$ip_address}
          </tr> \n";
        }

        $form .=
"        </tbody>
      </table> \n";

        echo $form;
}
?>
