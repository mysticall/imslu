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

###################################################################################################
    // PAGE HEADER
###################################################################################################

$page['title'] = 'Edit ticket';
$page['file'] = 'user_tikets_edit.php';

require_once dirname(__FILE__).'/include/page_header.php';


#####################################################
    // Display messages
#####################################################
echo !empty($_SESSION['msg']) ? '<div class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
$_SESSION['msg'] = null;


###################################################################################################
    // Edit User
###################################################################################################

if (!empty($_POST['ticketid'])) {

    $ticketid = $_POST['ticketid'];
    settype($ticketid, "integer");

    // Security key for comparison
    $_SESSION['form_key'] = md5(uniqid(mt_rand(), true));

    // Select requests
    $sql = 'SELECT * FROM tickets WHERE ticketid = :ticketid LIMIT 1';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':ticketid', $ticketid, PDO::PARAM_INT);
    $sth->execute();
    $ticket = $sth->fetch(PDO::FETCH_ASSOC);
    
    if(!$ticket) {

        header("Location: user_tickets.php?userid=$userid");
        exit;
    }
    
    $userid = $ticket['userid'];

#####################################################
    // Get user info
#####################################################
    $sql = 'SELECT `name`, `address`
            FROM `users`
            WHERE `userid` = :userid LIMIT 1';
    $sth = $db->dbh->prepare($sql);
    $sth->bindParam(':userid', $userid, PDO::PARAM_INT);
    $sth->execute();
    $rows = $sth->fetch(PDO::FETCH_ASSOC);
    $user_info = "{$rows['name']}, {$rows['address']}";

#####################################################
    // Get avalible operators
#####################################################   
    $sql = 'SELECT `operid`, `name` 
            FROM `operators`
            WHERE `type` = ? OR type = ?';

    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(1, OPERATOR_TYPE_TECHNICIAN);
    $sth->bindValue(2, OPERATOR_TYPE_ADMIN);
    $sth->execute();
    $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

    if ($rows) {
        for ($i = 0; $i < count($rows); ++$i) {

            $operator_name[$rows[$i]['operid']] = $rows[$i]['name'];
        }
        $operators = array('' => '') + $operator_name;
    }
    else {
        $operators = array('' => '');
    }

$form =
"    <form name=\"new_request\" action=\"user_tickets_apply.php\" method=\"post\">
      <table class=\"tableinfo\">
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th colspan=\"2\">
              <label>". chars($user_info) ."</label>
              <label class=\"info_right\">
                <a href=\"user_info.php?userid={$userid}\">["._('info')."]</a>
                <a href=\"user_edit.php?userid={$userid}\">["._('edit')."]</a>
                <a href=\"user_payments.php?userid={$userid}\">["._('payments')."]</a>
                <a href=\"user_tickets.php?userid={$userid}\">["._('tickets')."]</a>
              </label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('status')."</label>
            </td>
            <td class=\"dd\">
".combobox('input select', 'status', $ticket['status'], $ticket_status)."
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('created')."</label>
            </td>
            <td class=\"dd\">
              <label>{$ticket['created']}</label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('assign')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"assign\" id=\"assign\" value=\"{$ticket['assign']}\">
              <img src=\"js/calendar/img.gif\" id=\"f_trigger_b1\">
              <script type=\"text/javascript\">
                Calendar.setup({
                  inputField     :    \"assign\",
                  ifFormat       :    \"%Y-%m-%d %H:%M:%S\",
                  showsTime      :    true,
                  button         :    \"f_trigger_b1\",
                  singleClick    :    true,
                  step           :    1
                });
              </script>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('end')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"end\" id=\"end\" value=\"{$ticket['end']}\">
              <img src=\"js/calendar/img.gif\" id=\"f_trigger_b2\">
              <script type=\"text/javascript\">
                Calendar.setup({
                  inputField     :    \"end\",
                  ifFormat       :    \"%Y-%m-%d %H:%M:%S\",
                  showsTime      :    true,
                  button         :    \"f_trigger_b2\",
                  singleClick    :    true,
                  step           :    1
                });
              </script>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('changed')."</label>
            </td>
            <td class=\"dd\">
              <label>{$ticket['changed']}</label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('closed')."</label>
            </td>
            <td class=\"dd\">
              <label>{$ticket['closed']}</label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('operator')."</label>
            </td>
            <td class=\"dd\">
".combobox('input select', 'operid', $ticket['operid'], $operators)."
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('notes')."</label>
            </td>
            <td class=\"dd\">
              <textarea name=\"notes\" cols=\"55\" rows=\"3\">".chars($ticket['notes'])."</textarea>
            </td>
          </tr>
        </tbody>
        <tfoot>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"ticketid\" value=\"{$ticket['ticketid']}\">
              <input type=\"hidden\" name=\"userid\" value=\"{$ticket['userid']}\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"submit\" name=\"edit\" id=\"save\" value=\""._('save')."\">
            </td>
          </tr>
        </tfoot>
      </table>
    </form>\n";

    echo $form;
}

require_once dirname(__FILE__).'/include/page_footer.php';
?>
