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

# Must be included after session check
require_once dirname(__FILE__).'/include/config.php';

$db = new PDOinstance();

$page['title'] = 'PPPoE sessions';
$page['file'] = 'user_pppoe_sessions.php';

require_once dirname(__FILE__).'/include/page_header.php';

####### Display messages #######
echo !empty($_SESSION['msg']) ? '<div id="msg" class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
$_SESSION['msg'] = null;


if (!empty($_GET['userid']) && !empty($_GET['username'])) {


    # !!! Prevent problems !!!
    $userid = $_GET['userid'];
    settype($userid, "integer");
    if($userid == 0) {
        
        header("Location: users.php");
        exit;
    }

    $username = $_GET['username'];

    ####### Set CTable variable #######
    $table = new Table();
    $table->form_name = 'pppoe_sessions';
    $table->colspan = 8;
    $table->info_field1 = _('total').": ";
    $table->info_field2 = _('username').": ".chars($username);
    $table->info_field3 =
"              <label class=\"info_right\">
                <a href=\"user.php?userid={$userid}\">["._('back')."]</a>
              </label>\n";

    $table->th_array = array(
        1 => _('NAS'),
        2 => _('start time'),
        3 => _('stop time'),
        4 => _('session time'),
        5 => _('upload'),
        6 => _('download'),
        7 => _('mac'),
        8 => _('IP address')
        );

    $sql = 'SELECT nasipaddress, acctstarttime, acctstoptime, acctsessiontime, acctinputoctets, acctoutputoctets, callingstationid, framedipaddress
            FROM radacct WHERE username = :username ORDER BY acctstarttime DESC';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':username', $username, PDO::PARAM_STR);
    $sth->execute();
    $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

    if ($rows) {

        $now = time();
        for ($i = 0; $i < count($rows); ++$i) {

            if (!$rows[$i]['acctstoptime']) {

                $rows[$i]['acctstoptime'] = 'n/a';
                $rows[$i]['acctsessiontime'] = time2str($now - strtotime($rows[$i]['acctstarttime']));
                $rows[$i]['acctinputoctets'] = bytes2str($rows[$i]['acctinputoctets']);
                $rows[$i]['acctoutputoctets'] = bytes2str($rows[$i]['acctoutputoctets']);
            }
            else {

                $rows[$i]['acctsessiontime'] = time2str($rows[$i]['acctsessiontime']);
                $rows[$i]['acctinputoctets'] = bytes2str($rows[$i]['acctinputoctets']);
                $rows[$i]['acctoutputoctets'] = bytes2str($rows[$i]['acctoutputoctets']);
            }
        }
    }

    $table->td_array = $rows;
    echo $table->ctable();

    require_once dirname(__FILE__).'/include/page_footer.php';
}

?>
