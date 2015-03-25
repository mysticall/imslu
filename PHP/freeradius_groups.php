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

//Only System Admin have acces to Groups
if (OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type']) {

	$db = new PDOinstance();

###################################################################################################
	// PAGE HEADER
###################################################################################################
	$page['title'] = 'freeRadius groups';
	$page['file'] = 'freeradius_groups.php';

	require_once dirname(__FILE__).'/include/page_header.php';


#####################################################
	// Display messages 
#####################################################
	echo !empty($_SESSION['msg']) ? '<div class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
	$_SESSION['msg'] = null;

    // Security key for comparison
    $_SESSION['form_key'] = md5(uniqid(mt_rand(), true));


	$sql = 'SELECT pool_name FROM radippool GROUP BY pool_name';
	$sth = $db->dbh->prepare($sql);
	$sth->execute();
	$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

	if ($rows) {

		for ($i = 0; $i < count($rows); ++$i) {

			$pool_name[$rows[$i]['pool_name']] = $rows[$i]['pool_name'];
		}
	}
	else {
	
		echo '<label class="middle_container tableinfo" style="font-size:18px; font-weight:bold; color: #ff0000;">'. 
			_('Please, first add IP addresses for FreeRADIUS in the "IP addresses".') .'<label>';

		require_once dirname(__FILE__).'/include/page_footer.php';
		exit;
	}

###################################################################################################
	// Add new Freeradius Goup
###################################################################################################	

    if (!empty($_POST['action']) && $_POST['action'] == 'addgroup') {

    	// Radgroupcheck
    	$radgroupcheck_attributes = array(
    		'' => '', 
    		'NAS-IP-Address' => 'NAS-IP-Address',
    		'NAS-Identifier' => 'NAS-Identifier',
    		'NAS-Port' => 'NAS-Port',
    		'NAS-Port-Type' => 'NAS-Port-Type'
    		);

    	// Radgroupreply
    	$radgroupreply_attributes = array(
    		'' => '',
    		'PPPD-Downstream-Speed-Limit-1' => 'PPPD-Downstream-Speed-Limit-1',
    		'PPPD-Upstream-Speed-Limit-1' => 'PPPD-Upstream-Speed-Limit-1',
    		'PPPD-Downstream-Speed-Limit-2' => 'PPPD-Downstream-Speed-Limit-2',
    		'PPPD-Upstream-Speed-Limit-2' => 'PPPD-Upstream-Speed-Limit-2'
    		);

    $form =
"    <form name=\"edit_user\" action=\"freeradius_groups_apply.php\" method=\"post\">
      <table class=\"tableinfo\">
        <thead id=\"thead\">
          <tr class=\"header_top\">
            <th colspan=\"2\">
              <label>"._('new freeRadius group')."</label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('name')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"new_groupname\" id=\"new_groupname\" onkeyup=\"user_exists('new_groupname', 'radgroupcheck')\">
              <label id=\"hint\"></label>";
    $form .= (isset($_POST['msg_groupname'])) ? "&nbsp;<span class=\"red\">{$_POST['msg_groupname']}</span>\n" : "\n";
    $form .=
"            </td>
          </tr>
          <tr class=\"odd_row\">
            <td class=\"dt right\">
              <label> radgroupcheck </label>
            </td>
            <td class=\"dd\">
              <a href=\"http://freeradius.org/rfc/attributes.html\" target=\"_blank\">"._('For more information about attributes')."</a>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Add attribute')."</label>
            </td>
            <td class=\"dd\">
".combobox_onchange('input select', 'add_attribute[radgroupcheck]', $radgroupcheck_attributes, 'add_attribute(\'thead\', \'radgroupcheck\', this[this.selectedIndex].value)')."
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label> Auth-Type </label>
".combobox('input select', 'op[Auth-Type]', ':=', $freeradius_op)."
            </td>
			<td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"radgroupcheck[Auth-Type]\" value=\"CHAP\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label> Pool-Name </label>
".combobox('input select', 'op[Pool-Name]', ':=', $freeradius_op)."
            </td>
			<td class=\"dd\">
".combobox('input select', 'radgroupcheck[Pool-Name]', null, $pool_name)."
            </td>
          </tr>
        </thead>
        <tbody id=\"tbody\">
          <tr class=\"odd_row\">
            <td class=\"dt right\">
              <label> radgroupreply </label>
            </td>
            <td class=\"dd\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Add attribute')."</label>
            </td>
            <td class=\"dd\">
".combobox_onchange('input select', 'add_attribute[radgroupreply]', $radgroupreply_attributes, 'add_attribute(\'tbody\', \'radgroupreply\', this[this.selectedIndex].value)')."
            </td>
          </tr>	
          <tr>
            <td class=\"dt right\">
              <label> Framed-Protocol </label>
".combobox('input select', 'op[Framed-Protocol]', null, $freeradius_op)."
            </td>
			<td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"radgroupreply[Framed-Protocol]\" value=\"PPP\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label> Service-Type </label>
".combobox('input select', 'op[Service-Type]', null, $freeradius_op)."
            </td>
			<td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"radgroupreply[Service-Type]\" value=\"Framed-User\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label> Framed-MTU </label>
".combobox('input select', 'op[Framed-MTU]', null, $freeradius_op)."
            </td>
			<td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"radgroupreply[Framed-MTU]\" value=\"1500\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label> Framed-Compression </label>
".combobox('input select', 'op[Framed-Compression]', null, $freeradius_op)."
            </td>
			<td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"radgroupreply[Framed-Compression]\" value=\"None\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label> Acct-Interim-Interval </label>
".combobox('input select', 'op[Acct-Interim-Interval]', null, $freeradius_op)."
            </td>
			<td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"radgroupreply[Acct-Interim-Interval]\" value=\"180\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label> PPPD-Downstream-Speed-Limit </label>
".combobox('input select', 'op[PPPD-Downstream-Speed-Limit]', null, $freeradius_op)."
            </td>
			<td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"radgroupreply[PPPD-Downstream-Speed-Limit]\">
              <label style=\"font-weight: bold;\"> Kbit </label> &nbsp 
              <label style=\"color: red;\">"._('If empty, shaper will not work!')."</label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label> PPPD-Upstream-Speed-Limit </label>
".combobox('input select', 'op[PPPD-Upstream-Speed-Limit]', null, $freeradius_op)."
            </td>
			<td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"radgroupreply[PPPD-Upstream-Speed-Limit]\">
              <label style=\"font-weight: bold;\"> Kbit </label>&nbsp 
              <label style=\"color: red;\">"._('If empty, shaper will not work!')."</label>
            </td>
          </tr>
        </tbody>
        <tfoot>
          <tr class=\"odd_row\">
            <td class=\"dt right\">
              <label style=\"color: red;\"> <?php echo _('Empty attributes will not be saved!'); ?> </label>
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"submit\" name=\"add_new_group\" id=\"save\" value=\""._('save')."\">
            </td>
          </tr>
        </tfoot>
      </table>
    </form>\n";

        echo $form;
    }

###################################################################################################
	// Edit Freeradius Goup
###################################################################################################	

    if (!empty($_POST['groupname'])) {
		
    	$groupname = $_POST['groupname'];

    	$sql = 'SELECT groupname,attribute,op,value FROM radgroupcheck WHERE groupname = ?';
    	$sth = $db->dbh->prepare($sql);
    	$sth->bindParam(1, $groupname, PDO::PARAM_STR);
    	$sth->execute();
    	$group_radgroupcheck = $sth->fetchAll(PDO::FETCH_ASSOC);

    	// Radgroupcheck
    	$radgroupcheck_attributes = array(
    		'' => '',
    		'Auth-Type' => 'Auth-Type',
    		'Pool-Name' => 'Pool-Name',
    		'NAS-IP-Address' => 'NAS-IP-Address',
    		'NAS-Identifier' => 'NAS-Identifier',
    		'NAS-Port' => 'NAS-Port',
    		'NAS-Port-Type' => 'NAS-Port-Type'
    		);


	   for ($i = 0; $i < count($group_radgroupcheck); ++$i) {

            if ($i == 0) {

            $form =
"    <form name=\"change_group\" action=\"freeradius_groups_apply.php\" method=\"post\">
      <table class=\"tableinfo\">
        <thead id=\"thead\">
          <tr class=\"header_top\">
            <th colspan=\"2\">
				<label>"._('group').": ".chars($group_radgroupcheck[$i]['groupname'])."</label>
            </th>
          </tr>
          <tr class=\"odd_row\">
            <td class=\"dt right\">
              <label> radgroupcheck </label>
            </td>
            <td class=\"dd\">
              <a href=\"http://freeradius.org/rfc/attributes.html\" target=\"_blank\">"._('For more information about attributes')."</a>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Add attribute')."</label>
            </td>
            <td class=\"dd\">
".combobox_onchange('input select', 'add_attribute[radgroupcheck]', $radgroupcheck_attributes, "add_attribute('thead', 'radgroupcheck', this[this.selectedIndex].value)")."
            </td>
          </tr>\n";
            }

            if ($group_radgroupcheck[$i]['attribute'] == 'Pool-Name') {

                $form .=
"          <tr>
            <td class=\"dt right\">
				<label>{$group_radgroupcheck[$i]['attribute']}</label>
".combobox('input select', "op[{$group_radgroupcheck[$i]['attribute']}]", $group_radgroupcheck[$i]['op'], $freeradius_op)."
            </td>
            <td class=\"dd\">
".combobox('input select', 'radgroupcheck[Pool-Name]', $group_radgroupcheck[$i]['value'], $pool_name)."
            </td>
          </tr>\n";

            }
            else {
			
                $form .=
"          <tr>
            <td class=\"dt right\">
				<label>{$group_radgroupcheck[$i]['attribute']}</label>
".combobox('input select', "op[{$group_radgroupcheck[$i]['attribute']}]", $group_radgroupcheck[$i]['op'], $freeradius_op)."
            </td>
            <td class=\"dd\">
				<input class=\"input\" type=\"text\" name=\"radgroupcheck[{$group_radgroupcheck[$i]['attribute']}]\" value=\"".chars($group_radgroupcheck[$i]['value'])."\">
            </td>
          </tr>\n";

                if ($i == count($group_radgroupcheck) -1) {
			
				    $form .=		
"        </thead>\n";
	 		    }
            }
        }

        $sql = 'SELECT groupname,attribute,op,value FROM radgroupreply WHERE groupname = ?';
        $sth = $db->dbh->prepare($sql);
    	$sth->bindParam(1, $groupname, PDO::PARAM_STR);
    	$sth->execute();
    	$group_radgroupreply = $sth->fetchAll(PDO::FETCH_ASSOC);
	
    	// Radgroupreply
    	$radgroupreply_attributes = array(
    		'' => '',
    		'Framed-Protocol' => 'Framed-Protocol',
    		'Service-Type' => 'Service-Type',
    		'Framed-MTU' => 'Framed-MTU',
    		'Framed-Compression' => 'Framed-Compression',
    		'Acct-Interim-Interval' => 'Acct-Interim-Interval',
    		'PPPD-Downstream-Speed-Limit' => 'PPPD-Downstream-Speed-Limit',
    		'PPPD-Upstream-Speed-Limit' => 'PPPD-Upstream-Speed-Limit',
    		'PPPD-Downstream-Speed-Limit-1' => 'PPPD-Downstream-Speed-Limit-1',
    		'PPPD-Upstream-Speed-Limit-1' => 'PPPD-Upstream-Speed-Limit-1',
    		'PPPD-Downstream-Speed-Limit-2' => 'PPPD-Downstream-Speed-Limit-2',
    		'PPPD-Upstream-Speed-Limit-2' => 'PPPD-Upstream-Speed-Limit-2'
    		);

    	for ($i = 0; $i < count($group_radgroupreply); ++$i) {

            if ($i == 0) {

    			$form .=
"        <tbody id=\"tbody\">
          <tr class=\"odd_row\">
            <td class=\"dt right\">
              <label> radgroupreply </label>
            </td>
            <td class=\"dd\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Add attribute')."</label>
            </td>
            <td class=\"dd\">
".combobox_onchange('input select', 'add_attribute[radgroupreply]', $radgroupreply_attributes, "add_attribute('tbody', 'radgroupreply', this[this.selectedIndex].value)")."
            </td>
          </tr>\n";
		  }
		
                $form .=
"          <tr>
            <td class=\"dt right\">
              <label>{$group_radgroupreply[$i]['attribute']}</label>
".combobox('input select', 'op['. $group_radgroupreply[$i]['attribute'] .']', $group_radgroupreply[$i]['op'], $freeradius_op)."
            </td>
            <td class=\"dd\">
				<input class=\"input\" type=\"text\" name=\"radgroupreply[{$group_radgroupreply[$i]['attribute']}]\" value=\"".chars($group_radgroupreply[$i]['value'])."\">
            </td>
          </tr>\n";

		  if ($i == count($group_radgroupreply) - 1) {
			
                $form .=		
"        </tbody>
        <tfoot>
          <tr>
            <td class=\"dt right\">
              <label style=\"color: red;\">"._('delete')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"checkbox\" name=\"del\">
            </td>
          </tr>
          <tr class=\"odd_row\">
            <td class=\"dt right\">
              <label style=\"color: red;\">"._('Empty attributes will be deleted!')."</label>
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"hidden\" name=\"group_radgroupcheck\" value='".serialize($group_radgroupcheck)."'>
              <input type=\"hidden\" name=\"group_radgroupreply\" value='".serialize($group_radgroupreply)."'>
              <input type=\"hidden\" name=\"group\" value=\"".chars($groupname)."\">
              <input type=\"submit\" name=\"change_group\" value=\""._('save')."\">
              <input type=\"submit\" name=\"delete\" value=\""._('delete')."\">
            </td>
          </tr>
        </tfoot>
      </form>\n";
            }
        }	
	
	# Show freeRadius Group 
	echo $form;
    }


###################################################################################################
	// Set Table variable and create dynamic html table
###################################################################################################	

	//Check available groups
	$sql = 'SELECT groupname, GROUP_CONCAT(value) FROM radgroupcheck GROUP BY groupname';

	$sth = $db->dbh->prepare($sql);
	$sth->execute();
	$groupname = $sth->fetchAll(PDO::FETCH_ASSOC);

	if (!empty($groupname[0])) {

		for ($i = 0; $i < count($groupname); ++$i) {
		
			// Set $table->td_array
			$values = explode(',', $groupname[$i]['GROUP_CONCAT(value)']);
			$groups[$i] = array('groupname' => chars($groupname[$i]['groupname'])) + array('Auth-Type' => chars($values[0])) + array('Pool-Name' => chars($values[1]));
		}	
	}
	else {

		$groups = null;
	}

    $table = new Table();
	// Create dynamic html table
	$table->form_name = 'groups';
	$table->table_name = 'freeradius_groups';
	$table->info_field1 = _('total').": ";
	$table->info_field2 = _('freeRadius groups');
	
	$items1 = array(
		'' => '',
		'addgroup' => _('add group')
		);

	$combobox_form_submit = "<label class=\"info_right\">". _('action') .": \n".  combobox_onchange('input select', 'action', $items1, null) ."</label>";

	$table->info_field3 = $combobox_form_submit;
	$table->onclick_id = true;
	$table->th_array = array(
		1 => _('name'),
		2 => 'Auth-Type',
		3 => 'Pool-Name'
		);

	$table->th_array_style = 'style="table-layout: fixed; width: 33%;"';
	$table->td_array = $groups;
	echo $table->ctable();


	require_once dirname(__FILE__).'/include/page_footer.php';
}
else {
	header('Location: profile.php');
}
?>
