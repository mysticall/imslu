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
$page['title'] = 'Add ticket';

require_once dirname(__FILE__).'/include/page_header.php';

####### Display messages #######
echo !empty($_SESSION['msg']) ? '<div id="msg" class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
$_SESSION['msg'] = null;


####### New #######
if (!empty($_GET['userid']) && !empty($_GET['new'])) {

    # !!! Prevent problems !!!
    $userid = $_GET['userid'];
    settype($userid, "integer");
    if($userid == 0) {

        header("Location: users.php");
        exit;
    }

    // Security key for comparison
    $_SESSION['form_key'] = md5(uniqid(mt_rand(), true));

    ####### Get user info and tickets #######
    $sql = 'SELECT name, address FROM users WHERE userid = :userid LIMIT 1';
    $sth = $db->dbh->prepare($sql);
    $sth->bindParam(':userid', $userid, PDO::PARAM_INT);
    $sth->execute();
    $rows = $sth->fetch(PDO::FETCH_ASSOC);
    $user_info = "{$rows['name']} {$rows['address']}";

    ####### Get avalible operators ####### 
    $sql = 'SELECT operid, name FROM operators WHERE type = ? OR type = ?';
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
"<script type=\"text/javascript\">
<!--
function validateForm() {

    if (document.getElementById(\"notes\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('notes'))."\");
        document.getElementById(\"notes\").focus();
        return false;
    }
    return true;
}
//-->
</script>
    <form name=\"new_request\" action=\"user_tickets_apply.php\" onsubmit=\"return validateForm();\" method=\"post\">
      <table>
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th colspan=\"2\">
              <label>". chars($user_info) ."</label>
              <label class=\"info_right\">
                <a href=\"user_tickets.php?userid={$userid}\">["._('back')."]</a>
                <a href=\"user.php?userid={$userid}\">["._('user')."]</a>
              </label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('operator')."</label>
            </td>
            <td class=\"dd\">
".combobox('', 'operid', null, $operators)."
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('assign')."</label>
            </td>
            <td class=\"dd\">
              <input type=\"text\" name=\"assign\" id=\"assign\" value=\"\">
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
              <input type=\"text\" name=\"end\" id=\"end\" value=\"\">
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
              <label>"._('notes')."</label>
            </td>
            <td class=\"dd\">
              <textarea id=\"notes\" name=\"notes\" rows=\"2\"></textarea>
            </td>
          </tr>
        </tbody>
        <tfoot>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"userid\" value=\"{$userid}\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input id=\"save\" class=\"button\" type=\"submit\" name=\"new\" value=\""._('save')."\">
            </td>
          </tr>
        </tfoot>
      </table>
    </form>\n";

    echo $form;
}

require_once dirname(__FILE__).'/include/page_footer.php';
?>
