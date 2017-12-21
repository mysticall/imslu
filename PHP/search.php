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

# Must be included after session check
require_once dirname(__FILE__).'/include/config.php';

$db = new PDOinstance();
$search = (!empty($_GET['search'])) ? chars($_GET['search']) : '';
$more = (!empty($_GET['more'])) ? $_GET['more'] : '';

####### PAGE HEADER #######
$page['title'] = 'Search';

require_once dirname(__FILE__).'/include/page_header.php';

####### Display messages #######
echo !empty($_SESSION['msg']) ? '<div id="msg" class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
$_SESSION['msg'] = null;

$more_ = array(
    0 => '',
    1 => "----- "._('user')." -----",
    2 => _('id'),
    3 => _('name'),
    4 => _('address'),
    5 => _('phone'),
    6 => _('notes'),
    7 => "------- "._('ip')." -------",
    8 => _('id'),
    9 => _('vlan'),
    10 => _('mac'),
    11 => _('username'),
    12 => _('password'),
    13 => _('notes')
    );

$form =
"<script type=\"text/javascript\">
<!--
window.onload = function() {
    document.getElementById(\"search\").focus();
};
//-->
</script>
    <form action=\"{$_SERVER['PHP_SELF']}\" method=\"get\">
      <table class=\"tableinfo\">
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th>
              <input id=\"search\" type=\"text\" name=\"search\" value=\"{$search}\">
              <label>".combobox('', 'more', $more, $more_)." </label>
              <input class=\"button\" type=\"submit\" name=\"show\" value=\""._('search')."\">
            </th>
          </tr>
        </tbody>
      </table>
    </form>\n";

echo $form;


####### Show Users #######
if (!empty($_GET['show']) && $search) {

    ### services ###
    $sql = 'SELECT serviceid, name FROM services GROUP BY name';
    $sth = $db->dbh->prepare($sql);
    $sth->execute();
    $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rows as $value) {
        $services[$value['serviceid']] = $value['name'];
    }

    // Static IP activity
    if ($OS == 'FreeBSD') {

    }
    elseif ($OS == 'Linux') {
        $cmd = "ip -s neighbour show | grep -v 'FAILED'";
        $result = shell_exec($cmd);
        foreach (explode("\n", $result) as $value) {
            if (!empty($value)) {
                $tmp = explode(" ", $value);
                $used = ($tmp[5] == "ref") ? explode("/", $tmp[8]) : explode("/", $tmp[6]);

                if ($used[1] < 31 || $used[2] < 31) {
                    $activity_[$tmp[0]] = $tmp[0];
                }
            }
        }
    }

    // PPPoE activity
    if ($USE_PPPoE) {
        $cmd = "cat /tmp/ip_activity_pppoe";
        $result = shell_exec($cmd);
        foreach (explode("\n", $result) as $value) {
            if (!empty($value)) {
                $activity_[$value] = $value;
            }
        }
    }

    if ($more == 0) {

        // Select users info
        $sql = "SELECT * FROM users WHERE name LIKE ? OR address LIKE ? OR phone_number LIKE ? OR notes LIKE ?";
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(1, "%{$search}%", PDO::PARAM_STR);
        $sth->bindValue(2, "%{$search}%", PDO::PARAM_STR);
        $sth->bindValue(3, "%{$search}%", PDO::PARAM_STR);
        $sth->bindValue(4, "%{$search}%", PDO::PARAM_STR);
        $sth->execute();
        $users= $sth->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($users)) {

            $userid = '';
            foreach($users as $value) {
                $userid .= "userid={$value['userid']} OR ";
            }

            $sql = "SELECT * FROM ip WHERE userid != 0 AND {$userid}";
            $sth = $db->dbh->prepare(substr($sql, 0, -3));
            $sth->execute();
            $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
        }
        else {

            // Select ip info
            $sql = "SELECT * FROM ip WHERE ip LIKE ? OR vlan LIKE ? OR mac LIKE ? OR username LIKE ? OR pass LIKE ? OR notes LIKE ?";
            $sth = $db->dbh->prepare($sql);
            $sth->bindValue(1, "%{$search}%", PDO::PARAM_STR);
            $sth->bindValue(2, "%{$search}%", PDO::PARAM_STR);
            $sth->bindValue(3, "%{$search}%", PDO::PARAM_STR);
            $sth->bindValue(4, "%{$search}%", PDO::PARAM_STR);
            $sth->bindValue(5, "%{$search}%", PDO::PARAM_STR);
            $sth->bindValue(6, "%{$search}%", PDO::PARAM_STR);
            $sth->execute();
            $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($rows)) {

                $userid = '';
                foreach($rows as $value) {
                    $userid .= "userid={$value['userid']} OR ";
                }

                $sql = "SELECT * FROM users WHERE {$userid}";
                $sth = $db->dbh->prepare(substr($sql, 0, -3));
                $sth->execute();
                $users = $sth->fetchAll(PDO::FETCH_ASSOC);
            }
        }
    }
    elseif ($more < 7) {

switch($more) {
    case 1:
        $row = "user";
        break;
    case 2:
        $row = "userid";
        break;
    case 3:
        $row = "name";
        break;
    case 4:
        $row = "address";
        break;
    case 5:
        $row = "phone_number";
        break;
    case 6:
        $row = "notes";
        break;
}

        // Select users info
        $sql = "SELECT * FROM users WHERE {$row} LIKE ?";
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(1, "%{$search}%", PDO::PARAM_STR);
        $sth->execute();
        $users= $sth->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($users)) {

            $userid = '';
            foreach($users as $value) {
                $userid .= "userid={$value['userid']} OR ";
            }

            $sql = "SELECT * FROM ip WHERE userid != 0 AND {$userid}";
            $sth = $db->dbh->prepare(substr($sql, 0, -3));
            $sth->execute();
            $rows = $sth->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    elseif ($more >= 7) {

switch($more) {
    case 7:
        $row = "ip";
        break;
    case 8:
        $row = "id";
        break;
    case 9:
        $row = "vlan";
        break;
    case 10:
        $row = "mac";
        break;
    case 11:
        $row = "username";
        break;
    case 12:
        $row = "pass";
        break;
    case 13:
        $row = "notes";
        break;
}

        // Select ip info
        $sql = "SELECT * FROM ip WHERE {$row} LIKE ?";
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(1, "%{$search}%", PDO::PARAM_STR);

        $sth->execute();
        $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($rows)) {

            $userid = '';
            foreach($rows as $value) {
                $userid .= "userid={$value['userid']} OR ";
            }

            $sql = "SELECT * FROM users WHERE {$userid}";
            $sth = $db->dbh->prepare(substr($sql, 0, -3));
            $sth->execute();
            $users = $sth->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    if (!empty($users)) {

        if (!empty($rows)) {
            $ip = array();
            for ($i=0; $i < count($rows); $i++) {
                $ip[$rows[$i]['userid']][$rows[$i]['id']] = $rows[$i];
            }
        }

        ####### Get avalible locations #######
        $sql = 'SELECT id,name FROM location';
        $sth = $db->dbh->prepare($sql);
        $sth->execute();
        $rows= $sth->fetchAll(PDO::FETCH_ASSOC);

        if ($rows) {

            $location = array('' => '');

            for ($i = 0; $i < count($rows); ++$i) {

                $location[$rows[$i]['id']] = $rows[$i]['name'];
            }
        }
        else {
            $location = array('' => '');
        }

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
            $user_location = (!empty($location[$users[$i]['locationid']])) ? $location[$users[$i]['locationid']] : '';

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
            <td rowspan=\"{$count_ip}\"><a class=\"bold\" href=\"user.php?userid={$users[$i]['userid']}\">".chars($users[$i]['name'])."</a></td>
            <td rowspan=\"{$count_ip}\">$user_location</td>
            <td rowspan=\"{$count_ip}\">".chars($users[$i]['address'])."</td>
            <td rowspan=\"{$count_ip}\">".chars($users[$i]['phone_number'])."</td>
            <td rowspan=\"{$count_ip}\">{$services[$users[$i]['serviceid']]}</td>
            <td rowspan=\"{$count_ip}\">{$pay}</td>
{$ip_address}
          </tr> \n";
        }

        $form .=          
"        </tbody>
      </table> \n";

        echo $form;
    }
}
?>
