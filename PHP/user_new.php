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
$admin_permissions = (OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type'] || OPERATOR_TYPE_ADMIN == $_SESSION['data']['type']);
$disabled = ($admin_permissions) ? '' : ' disabled';


####### PAGE HEADER #######
$page['title'] = 'New User';
$page['file'] = 'user_new.php';

require_once dirname(__FILE__).'/include/page_header.php';


####### Display messages #######
echo !empty($_SESSION['msg']) ? '<div id="msg" class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
$_SESSION['msg'] = null;


// Security key for comparison
$_SESSION['form_key'] = md5(uniqid(mt_rand(), true));

####### Services #######
$sql = 'SELECT name,price FROM services';
$sth = $db->dbh->prepare($sql);
$sth->execute();
$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

if ($rows) {
    for ($i = 0; $i < count($rows); ++$i) {

        $services[$rows[$i]['name']] = $rows[$i]['name'] .' - '. $rows[$i]['price'];
    }
}
else {
    
    echo '<label class="middle_container tableinfo" style="font-size:18px; font-weight:bold; color: #ff0000;">'.
        _('Please contact your system administrator. The service missing.') .'<label>';

    require_once dirname(__FILE__).'/include/page_footer.php';
    exit;
}

####### Get avalible locations #######
$sql = 'SELECT id,name FROM location';
$sth = $db->dbh->prepare($sql);
$sth->execute();
$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

if ($rows) {
	for ($i = 0; $i < count($rows); ++$i) {

		$location_name[$rows[$i]['id']] = $rows[$i]['name'];
	}
	$location = array('' => '') + $location_name;
}
else {
	$location = array('' => '');
}

/*
 * When closing the request with "connected", the information is stored in $_SESSION['new_request'] and automatically transferred to new user form.
 * see request_apply.php - $_SESSION['new_request'] = array ();
 */
$name = !empty($_GET['name']) ? $_GET['name'] : "";
$address = !empty($_GET['address']) ? $_GET['address'] : "";
$phone_number = !empty($_GET['phone_number']) ? $_GET['phone_number'] : "";
$notes = !empty($_GET['notes']) ? $_GET['notes'] : "";

####### New #######
$form =
"<script type=\"text/javascript\">
<!--
function validateForm() {

    if (document.getElementById(\"name\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('name'))."\");
        document.getElementById(\"name\").focus();
        return false;
    }
    return true;
}
//-->
</script>
    <form name=\"new_user\" action=\"user_new_apply.php\" onsubmit=\"return(validateForm());\" method=\"post\">
      <table>
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th colspan=\"2\">
              <label>"._('new user')."</label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('name')." * </label>
            </td>
            <td class=\"dd\">
              <input id=\"name\" name=\"name\" class=\"input\" type=\"text\" value=\"{$name}\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('location')."</label>
            </td>
            <td class=\"dd\">
".combobox('', 'locationid', null, $location)."
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('address')."</label>
            </td>
            <td class=\"dd\">
              <input type=\"text\" name=\"address\" value=\"{$address}\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('phone')."</label>
            </td>
            <td class=\"dd\">
              <input type=\"text\" name=\"phone_number\" value=\"{$phone_number}\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('notes')."</label>
            </td>
			<td class=\"dd\">
              <textarea name=\"notes\" rows=\"2\">{$notes}</textarea>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('service')."</label>
            </td>
            <td class=\"dd\">
".combobox('', 'service', null, $services)."
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('pay')."</label>
            </td>
            <td class=\"dd\">
              <input type=\"text\" name=\"pay\" $disabled>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('free internet access')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"checkbox\" type=\"radio\" name=\"free_access\" value=\"y\" $disabled> "._('Yes')."
              <input class=\"checkbox\" type=\"radio\" name=\"free_access\" value=\"n\" checked> "._('No')."
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('not excluding')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"checkbox\" type=\"radio\" name=\"not_excluding\" value=\"y\" $disabled> "._('Yes')."
              <input class=\"checkbox\" type=\"radio\" name=\"not_excluding\" value=\"n\" checked> "._('No')."
            </td>
          </tr>
        </tbody>
        <tfoot>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input id=\"save\" class=\"button\" type=\"submit\" name=\"new\" value=\""._('save')."\">
            </td>
          </tr>
        </tfoot>
      </table>
    </form>\n";

echo $form;

require_once dirname(__FILE__).'/include/page_footer.php';
?>
