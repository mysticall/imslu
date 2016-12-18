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

####### PAGE HEADER #######
$page['title'] = 'User Tickets';

require_once dirname(__FILE__).'/include/page_header.php';


####### Display messages #######
echo !empty($_SESSION['msg']) ? '<div id="msg" class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
$_SESSION['msg'] = null;

####### Edit #######
if (!empty($_GET['userid'])) {

    # !!! Prevent problems !!!
    $userid = $_GET['userid'];
    settype($userid, "integer");
    if($userid == 0) {
        
        header("Location: users.php");
        exit;
    }

    ####### Get user info and tickets #######
    $sql = 'SELECT name, address FROM users WHERE userid = :userid LIMIT 1';
    $sth = $db->dbh->prepare($sql);
    $sth->bindParam(':userid', $userid, PDO::PARAM_INT);
    $sth->execute();
    $rows = $sth->fetch(PDO::FETCH_ASSOC);
    $user = "{$rows['name']}, {$rows['address']}";

    $sql = 'SELECT `ticketid`, `status`, `add`, `assign`, `notes`
            FROM `tickets`
            WHERE `userid` = :userid ORDER BY `assign` DESC';
    $sth = $db->dbh->prepare($sql);
    $sth->bindParam(':userid', $userid, PDO::PARAM_INT);
    $sth->execute();
    $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

    if ($rows) {
        for ($i = 0; $i < count($rows); ++$i) {

            $rows[$i]['status'] = $ticket_status[$rows[$i]['status']];
        }

        $table = new Table();
        $table->form_name = 'tickets';
        $table->colspan = 5;
        $table->info_field1 = _('total').": ";
        $table->info_field2 = _s('tickets of %s', chars($user));
        $table->info_field3 = 
"              <label class=\"info_right\">
                <a href=\"user.php?userid={$userid}\">["._('back')."]</a>
                <a href=\"user_tickets_add.php?userid={$userid}&new=1\">["._('new ticket')."]</a>
              </label>\n";

        $table->link_action = 'user_tickets_edit.php';
        $table->link = TRUE;
        $table->th_array = array(
            1 => _('id'),
            2 => _('status'),
            3 => _('date'),
            4 => _('assign'),
            5 => _('notes')
        );

        $table->td_array = $rows;
        $table->input_submit = "      <input type=\"hidden\" name=\"userid\" value=\"{$userid}\">\n";
        echo $table->ctable();
    }
    else {
        echo
"      <table>
        <thead id=\"thead\">
          <tr class=\"header_top\">
            <th colspan=\"2\">
              <label>". _s('tickets of %s', chars($user)) ."</label>
              <label class=\"info_right\">
                <a href=\"user.php?userid={$userid}\">["._('back')."]</a>
                <a href=\"user_tickets_add.php?userid={$userid}&new=1\">["._('new ticket')."]</a>
              </label>
            </th>
          </tr>
        </thead>
      </table>";
    }
}

require_once dirname(__FILE__).'/include/page_footer.php';
?>
