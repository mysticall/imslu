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

####### PAGE HEADER #######
$page['title'] = 'The location of User';
$page['file'] = 'user_location.php';

require_once dirname(__FILE__).'/include/page_header.php';


####### Display messages #######
echo !empty($_SESSION['msg']) ? '<div class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
$_SESSION['msg'] = null;


####### New #######
if(isset($_POST['action']) && $_POST['action'] == 'newlocation') {

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
      <table class=\"tableinfo\">
        <tbody id=\"thead\">
          <tr class=\"header_top\">
            <th colspan=\"2\">
              <label>"._('new location')."</label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('name')."</label>
            </td>
            <td class=\"dd\">
              <input id=\"name\" name=\"name\" class=\"input\" type=\"text\">
            </td>
          </tr>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"submit\" name=\"save_new\" id=\"save\" value=\""._('save')."\">
            </td>
          </tr>
        </tbody>
      </table>
    </form>\n";

    echo $form;
}


####### Edit #######
if(!empty($_POST['id'])) {
	
	$id = $_POST['id'];

	$sql = 'SELECT id,name FROM location WHERE id = ? LIMIT 1';
	$sth = $db->dbh->prepare($sql);
	$sth->bindParam(1, $id, PDO::PARAM_INT);
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
      <table class=\"tableinfo\">
        <tbody id=\"thead\">
          <tr class=\"header_top\">
            <th colspan=\"2\">
              <label>"._('edit location').": ".chars($get_location['name'])."</label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('name')."</label>
            </td>
            <td class=\"dd\">
              <input id=\"name\" name=\"name\" class=\"input\" type=\"text\" value=\"".chars($get_location['name'])."\">            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label style=\"color: red;\">"._('delete')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"checkbox\" name=\"del\">
            </td>
          </tr>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"hidden\" name=\"id\" value=\"{$get_location['id']}\">
              <input type=\"submit\" name=\"save_edited\" id=\"save\" value=\""._('save')."\">
              <input type=\"submit\" name=\"delete\" value=\""._('delete')."\">
            </td>
          </tr>
        </tbody>
      </table>
    </form>\n";

    echo $form;
}


####### Set CTable variable #######
	$table = new Table();
	$table->form_name = 'location';
	$table->table_name = 'user_location';
	$table->colspan = 2;
	$table->info_field1 = _('total').": ";
	$table->info_field2 = _('the location');

	$items1 = array(
		'' => '',
		'newlocation' => _('new location')
		);

	$combobox_form_submit = "<label class=\"info_right\">". _('action') .": \n".  combobox_onchange('input select', 'action', $items1, null) ."</label>";

	$table->info_field3 = $combobox_form_submit;
	$table->onclick_id = true;
	$table->th_array = array(
		1 => _('id'),
		2 => _('the location')
		);

	$sql = 'SELECT id,name FROM location';
	$sth = $db->dbh->prepare($sql);
	$sth->execute();
	$table->td_array = $sth->fetchAll(PDO::FETCH_ASSOC);
	echo $table->ctable();

	require_once dirname(__FILE__).'/include/page_footer.php';
?>
