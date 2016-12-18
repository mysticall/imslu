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
$page['title'] = 'Location';

require_once dirname(__FILE__).'/include/page_header.php';

####### Display messages #######
echo !empty($_SESSION['msg']) ? '<div id="msg" class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
$_SESSION['msg'] = null;


####### New #######
if(!empty($_GET['new'])) {

    $form =
"<script type=\"text/javascript\">
<!--
function validateForm() {

    if (document.getElementById(\"name\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('name'))."\");
        document.getElementById(\"name\").focus();
        return false;
    }
}
//-->
</script>
    <form name=\"new_location\" action=\"user_location_apply.php\" onsubmit=\"return validateForm();\" method=\"post\">
      <table>
        <tbody id=\"thead\">
          <tr class=\"header_top\">
            <th colspan=\"2\">
              <label>"._('new location')."</label>
              <label class=\"info_right\"><a href=\"user_location.php\">["._('back')."]</a></label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('name')."</label>
            </td>
            <td class=\"dd\">
              <input id=\"name\" name=\"name\" type=\"text\">
            </td>
          </tr>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input id=\"save\" class=\"button\" type=\"submit\" name=\"save_new\" value=\""._('save')."\">
            </td>
          </tr>
        </tbody>
      </table>
    </form>\n";

    echo $form;
}
####### Edit #######
elseif(!empty($_GET['id'])) {
	
    settype($_GET['id'], "integer");
    if($_GET['id'] == 0) {
        header("Location: freeradius_nas.php");
        exit;
    }

	$sql = 'SELECT id,name FROM location WHERE id = ? LIMIT 1';
	$sth = $db->dbh->prepare($sql);
	$sth->bindParam(1, $_GET['id'], PDO::PARAM_INT);
	$sth->execute();
	$get_location = $sth->fetch(PDO::FETCH_ASSOC);

    $form =
"<script type=\"text/javascript\">
<!--
function validateForm() {

    if (document.getElementById(\"name\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('name'))."\");
        document.getElementById(\"name\").focus();
        return false;
    }
}
//-->
</script>
    <form name=\"edit_location\" action=\"user_location_apply.php\" onsubmit=\"return validateForm();\" method=\"post\">
      <table>
        <tbody id=\"thead\">
          <tr class=\"header_top\">
            <th colspan=\"2\">
              <label>"._('edit location').": ".chars($get_location['name'])."</label>
              <label class=\"info_right\"><a href=\"user_location.php\">["._('back')."]</a></label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('name')."</label>
            </td>
            <td class=\"dd\">
              <input id=\"name\" name=\"name\" type=\"text\" value=\"".chars($get_location['name'])."\">            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label style=\"color: red;\">"._('delete')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"checkbox\" type=\"checkbox\" name=\"del\">
            </td>
          </tr>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"hidden\" name=\"id\" value=\"{$get_location['id']}\">
              <input class=\"button\" type=\"submit\" name=\"save_edited\" id=\"save\" value=\""._('save')."\">
              <input class=\"button\" type=\"submit\" name=\"delete\" value=\""._('delete')."\">
            </td>
          </tr>
        </tbody>
      </table>
    </form>\n";

    echo $form;
}
else {

####### Set CTable variable #######
	$table = new Table();
	$table->form_name = 'location';
	$table->colspan = 2;
	$table->info_field1 = _('total').": ";
	$table->info_field2 = _('location');
    $table->info_field3 = "<label class=\"info_right\"><a href=\"user_location.php?new=1\">["._('new location')."]</a></label>";
    $table->link_action = 'user_location.php';
    $table->link = TRUE;
	$table->th_array = array(
		1 => _('id'),
		2 => _('location')
		);

	$sql = 'SELECT id,name FROM location';
	$sth = $db->dbh->prepare($sql);
	$sth->execute();
	$table->td_array = $sth->fetchAll(PDO::FETCH_ASSOC);
	echo $table->ctable();
}
	require_once dirname(__FILE__).'/include/page_footer.php';
?>
