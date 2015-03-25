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

//Only System Admin have acces to FreeRadius SQLIPPOOL
if (OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type']) {

	$db = new PDOinstance();

###################################################################################################
	// PAGE HEADER
###################################################################################################

	$page['title'] = 'freeRadius IP pool';
	$page['file'] = 'freeradius_sqlippool.php';

	require_once dirname(__FILE__).'/include/page_header.php';


	$sql = 'SELECT pool_name FROM radippool GROUP BY pool_name';
	$sth = $db->dbh->prepare($sql);
	$sth->execute();
	$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

	if ($rows) {
		for ($i = 0; $i < count($rows); ++$i) {

			$row[$rows[$i]['pool_name']] = $rows[$i]['pool_name'];
		}
		$pool_name = array('' => '') + $row;
	}
	else {
		$pool_name = array('' => '');
	}

	$order_by = array(
		'' => '',
		'username ASC' => _('username')." "._('up'),
		'username DESC' => _('username')." "._('down'),
		'expiry_time ASC' => _('expiry time')." "._('up'),
		'expiry_time DESC' => _('expiry time')." "._('down'),
		'id ASC' => _('id')." "._('up'),
		'id DESC' => _('id')." "._('down')
		);

    $items = array(
        '' => '',
        'addippool' => _('add IP pool')
        );

    $form =
"    <form action=\"{$_SERVER['PHP_SELF']}\" method=\"post\">
      <table class=\"tableinfo\">
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th>
              <label style=\"margin: 1px 3px 1px;\">"._('name').combobox('input select', 'pool_name', null, $pool_name)."</label>
              <label style=\"margin: 1px 3px 1px;\">"._('order by').combobox('input select', 'order_by', null, $order_by)."</label>
              <input type=\"hidden\" name=\"show\" id=\"show\">
              <label class=\"generator\" style=\"margin: 1px 5px 1px;\" onclick=\"document.getElementById('show').value = 'true'; this.form.submit()\">"._('show')."</label>
              <label class=\"info_right\">". _('action') .": \n".  combobox_onchange('input select', 'action', $items, null) ."</label>
            </th>
          </tr>
        </tbody>
      </table>
    </form>\n";

    echo $form;
	
#####################################################
	// Display messages
#####################################################
	echo !empty($_SESSION['msg']) ? '<div class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
	$_SESSION['msg'] = null;

    // Security key for comparison
    $_SESSION['form_key'] = md5(uniqid(mt_rand(), true));


###################################################################################################
	// Add new SQLIPPOOL
###################################################################################################	

	if (!empty($_POST['action']) && $_POST['action'] == 'addippool') {
	
        $form =
"    <form action=\"freeradius_sqlippool_apply.php\" method=\"post\">
      <table class=\"tableinfo\">
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th  colspan=\"2\">
              <label>"._('Add new IP address or IP address range')."</label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('pool name')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"pool_name\">";
        $form .= (isset($_POST['msg_pool_name'])) ? "&nbsp;<span class=\"red\">{$_POST['msg_pool_name']}</span>\n" : "\n";
        $form .=
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('start IP address')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"framedipaddress_start\">";
        $form .= (isset($_POST['msg_framedipaddress_start'])) ? "&nbsp;<span class=\"red\">{$_POST['msg_framedipaddress_start']}</span>\n" : "\n";
        $form .=
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('end IP address')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"framedipaddress_end\">";
        $form .= (isset($_POST['msg_framedipaddress_end'])) ? "&nbsp;<span class=\"red\">{$_POST['msg_framedipaddress_end']}</span>\n" : "\n";
        $form .=
"            </td>
          </tr>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"hidden\" name=\"pool_key\" value=\"0\">
              <input type=\"submit\" name=\"save_sqlippool\" id=\"save\" value=\""._('save')."\">
            </td>
          </tr>
        </tbody>
      </table>
    </form>\n";

        echo $form;
}		

###################################################################################################
	// Set CTable variable and create dynamic html table
###################################################################################################

	if (!empty($_POST['show'])) {

        $table = new Table();
		$table->form_name = 'sqlippool';
        $table->action = 'freeradius_sqlippool_apply.php';
		$table->table_name = 'freeradius_sqlippool';
		$table->colspan = 9;
		$table->info_field1 = _('total').": ";
		$table->info_field2 = _('freeRadius IP pool');

		$items1 = array(
			'' => '',
			'delete' => _('delete selected'),
			'change_pool_name' => _('change pool name')
			);

		$combobox_form_submit = "<label class=\"info_right\">". _('action') .": \n".  combobox_onchange('input select', 'action', $items1, "confirm_delete('sqlippool', this[this.selectedIndex].value)") ."</label>";

		$table->info_field3 = $combobox_form_submit;
		$table->checkbox = true;
		$table->th_array = array(
			1 => _('id'),
			2 => _('name'),
			3 => _('IP address'),
			4 => _('nas IP address'),
			5 => _('mac'),
			6 => _('expiry time'),
			7 => _('username'),
			8 => _('nas port')
			);


		$pool_name = (!empty($_POST['pool_name'])) ? $_POST['pool_name'] : '';
		$order_by = (!empty($_POST['order_by'])) ? $_POST['order_by'] : '';

		$_pool_name = (!empty($pool_name)) ? ' WHERE pool_name = :pool_name' : '';
		$_order_by = (!empty($order_by)) ? " ORDER BY $order_by" : '';

		$sql = "SELECT id,pool_name,framedipaddress,nasipaddress,callingstationid,expiry_time,username,pool_key 
				FROM radippool$_pool_name$_order_by";
		$sth = $db->dbh->prepare($sql);

		if (!empty($_pool_name)) {
			$sth->bindValue(':pool_name', $pool_name, PDO::PARAM_STR);
		}

		$sth->execute();
		$table->td_array = $sth->fetchAll(PDO::FETCH_ASSOC);
		$table->form_key = $_SESSION['form_key'];

		echo $table->ctable();
	}

	require_once dirname(__FILE__).'/include/page_footer.php';
}
else {
	header('Location: profile.php');
}
?>
