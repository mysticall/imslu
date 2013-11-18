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

require_once dirname(__FILE__).'/include/common.inc.php';

if (!CWebOperator::checkAuthentication(get_cookie('imslu_sessionid'))) {
	header('Location: index.php');
	exit;
}

# Must be included after session check
require_once dirname(__FILE__).'/include/config.inc.php';

$sysadmin_rights = (OPERATOR_TYPE_LINUX_ADMIN == CWebOperator::$data['type']);
$admin_rights = (OPERATOR_TYPE_ADMIN == CWebOperator::$data['type']);

if ($sysadmin_rights || $admin_rights) {

	$db = new CPDOinstance();
	$ctable = new CTable();


###################################################################################################
	// Delet attempts from IP
###################################################################################################

	if (!empty($_POST['action']) && $_POST['action'] == 'delete' && !empty($_POST['auditid'])) {

		$sql = 'DELETE FROM `auditlog` WHERE auditid = :auditid';
		$db->dbh->beginTransaction();
		$sth = $db->dbh->prepare($sql);
		$sth->bindParam(':auditid', $value, PDO::PARAM_INT);

		$auditid = $_POST['auditid'];

		foreach ($auditid as $value) {

			$sth->execute();
		}

		$db->dbh->commit();

		unset($_POST);
	}

###################################################################################################
	// Backup and delete
###################################################################################################
	if (isset($_POST['action']) && ($_POST['action'] == 'backup' || $_POST['action'] == 'backup_delete')) {

		$alias = CWebOperator::$data['alias'];
		$info = ($_POST['action'] == 'backup_delete') ? "_PHP-backup-DELETE_".$alias."_" : "_PHP_".$alias."_";

# Please do not change the syntax.
$command = "$SUDO $PYTHON <<END
import sys
sys.path.append('$IMSLU_SCRIPTS')
import sqldump
sqldump.mysqldump(tabe_name = 'auditlog', info = '$info')
END";

		$result = shell_exec($command);
		$_SESSION['msg'] = (empty($result)) ? _s('Backup of table %s success.', 'auditlog').'<br>' : _s('Backup of table %s failed', 'auditlog').' - '.$result.'<br>';

		if(($_POST['action'] == 'backup_delete') && empty($result)) {

			$sql = 'DELETE FROM `auditlog`';
			$sth = $db->dbh->exec($sql);

			$_SESSION['msg'] .= _s('The contents of the table %s was deleted.', 'auditlog').'<br>';
		}

		unset($_POST);
	}


###################################################################################################
	// PAGE HEADER
###################################################################################################

	$page['title'] = 'Audit';
	$page['file'] = 'audit.php';

	require_once dirname(__FILE__).'/include/page_header.php';

	$action = array(
		'' => '',
		AUDIT_ACTION_LOGIN => _('login'),
		AUDIT_ACTION_LOGOUT => _('logout'),
		AUDIT_ACTION_ADD => _('add'),
		AUDIT_ACTION_UPDATE => _('update'),
		AUDIT_ACTION_DELETE => _('delete')
		);

	$resource = array('' => '',
		AUDIT_RESOURCE_SYSTEM => _('system'),
		AUDIT_RESOURCE_OPERATOR => _('operator'),
		AUDIT_RESOURCE_USER => _('user'),
		AUDIT_RESOURCE_IP => _('IP address'),
		AUDIT_RESOURCE_PPPOE => _('PPPoE'),
		AUDIT_RESOURCE_STATIC_IP => _('static IP addresses'),
		AUDIT_RESOURCE_FREERADIUS => 'FreeRADIUS',
		AUDIT_RESOURCE_PAYMENTS => _('payments'),
		);

	$sql = 'SELECT operid, alias FROM operators';
	$sth = $db->dbh->prepare($sql);
	$sth->execute();
	$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

	if ($rows) {
		for ($i = 0; $i < count($rows); ++$i) {

			$row[$rows[$i]['operid']] = $rows[$i]['alias'];
		}
		$operator = array('' => '') + $row;
	}
	else {
		$operator = array('' => '');
	}

	$form =
"    <form action=\"{$_SERVER['PHP_SELF']}\" method=\"post\">
      <table class=\"tableinfo\">
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th>
              <label style=\"margin: 1px 3px 1px;\">"._('action').combobox('input select', 'actionid', null, $action)."</label>
              <label style=\"margin: 1px 3px 1px;\">"._('resource').combobox('input select', 'resourceid', null, $resource)."</label>
              <label style=\"margin: 1px 3px 1px;\">"._('operator').combobox('input select', 'operid', null, $operator)."</label>
              <label style=\"margin: 1px 3px 1px;\">"._('from date')."
                <input class=\"input\" style=\"padding: 1px 3px 1px 3px;\" type=\"text\" name=\"fromdate\" id=\"fromdate\" size=\"17\" value=\"".date("Y-m-d", strtotime("-3 days"))." 00:00\">
                <img src=\"js/calendar/img.gif\" id=\"f_trigger_b1\">
                <script type=\"text/javascript\">
                Calendar.setup({
                  inputField     :    \"fromdate\",
                  ifFormat       :    \"%Y-%m-%d %H:%M\",
                  showsTime      :    true,
                  button         :    \"f_trigger_b1\",
                  singleClick    :    true,
                  step           :    1
                });
                </script>
              </label>
              <label>"._('to date')."
                <input class=\"input\" style=\"padding: 1px 3px 1px 3px;\" type=\"text\" name=\"todate\" id=\"todate\" size=\"17\" value=\"".date('Y-m-d H:i')."\">
                <img src=\"js/calendar/img.gif\" id=\"f_trigger_b2\">
                <script type=\"text/javascript\">
                Calendar.setup({
                  inputField     :    \"todate\",
                  ifFormat       :    \"%Y-%m-%d %H:%M\",
                  showsTime      :    true,
                  button         :    \"f_trigger_b2\",
                  singleClick    :    true,
                  step           :    1
                });
                </script>
              </label>
              <input type=\"hidden\" name=\"show\" value=\"true\">
              <label class=\"generator\" style=\"margin: 1px 5px 1px;\" onclick=\"this.form.submit()\">"._('show')."</label>
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


	if(!empty($_POST['auditid'])) {

		$id = $_POST['auditid'];

		$sql = "SELECT actionid, resourceid, oper_alias, date_time, ip, details, oldvalue, newvalue 
				FROM auditlog
				WHERE auditid = :auditid";
		$sth = $db->dbh->prepare($sql);
		$sth->bindValue(':auditid', $id, PDO::PARAM_INT);
		$sth->execute();
		$audit_info = $sth->fetch(PDO::FETCH_ASSOC);

		$audit_info['actionid'] = $action[$audit_info['actionid']];
		$audit_info['resourceid'] = $resource[$audit_info['resourceid']];

		$form =
"     <table class=\"tableinfo\">
        <tbody id=\"user_info\">
          <tr class=\"header_top\">
            <th>
            </th>
          </tr>
          <tr>
            <td class=\"dd\">
              <label><b>"._('action').": </b>{$audit_info['actionid']}</label>
            </td>
          </tr>
          <tr>
            <td class=\"dd\">
              <label><b>"._('resource').": </b>{$audit_info['resourceid']}</label>
            </td>
          </tr>
          <tr>
            <td class=\"dd\">
              <label><b>"._('operator').": </b>".chars($audit_info['oper_alias'])."</label>
            </td>
          </tr>
          <tr>
            <td class=\"dd\">
              <label><b>"._('date').": </b>{$audit_info['date_time']}</label>
            </td>
          </tr>
          <tr>
            <td class=\"dd\">
              <label><b>"._('IP address').": </b>{$audit_info['ip']}</label>
            </td>
          </tr>
          <tr>
            <td class=\"dd\">
              <label><b>"._('details').": </b>".chars($audit_info['details'])."</label>
            </td>
          </tr>
          <tr>
            <td class=\"dd\">
              <label><b>"._('old value').":</b></label><br>
              <textarea style=\"margin-top:10px; margin-left:10px; width: 97%; height: 130px;\" readonly>".chars($audit_info['oldvalue'])."</textarea>
            </td>
          </tr>
          <tr>
            <td class=\"dd\">
              <label><b>"._('new value').":</b></label><br>
              <textarea style=\"margin-top:10px; margin-left:10px; width: 97%; height: 130px;\" readonly>".chars($audit_info['newvalue'])."</textarea>
            </td>
          </tr>
        </tbody>
      </table>\n";

		echo $form;
	}


###################################################################################################
	// Set CTable variables and create dynamic html table
###################################################################################################	

	if (isset($_POST['show'])) {
		
		$actionid = (!empty($_POST['actionid'])) ? $_POST['actionid'] : '';
		$resourceid = (!empty($_POST['resourceid'])) ? $_POST['resourceid'] : '';
		$operid = (!empty($_POST['operid'])) ? $_POST['operid'] : '';
		$fromdate = (!empty($_POST['fromdate'])) ? $_POST['fromdate'] : date("Y-m-d", strtotime("-3 days")).' 00:00';
		$todate = (!empty($_POST['todate'])) ? $_POST['todate'] : date('Y-m-d H:i');

		$_actionid = (!empty($actionid)) ? ' AND actionid = :actionid' : '';
		$_resourceid = (!empty($resourceid)) ? ' AND resourceid = :resourceid' : '';
		$_operid = (!empty($operid)) ? ' AND operid = :operid' : '';
		
		// Select user IP Addresses
		$sql = "SELECT auditid, actionid, resourceid, oper_alias, date_time, ip, details, oldvalue, newvalue 
				FROM auditlog 
				WHERE date_time > :fromdate AND date_time < :todate$_actionid$_resourceid$_operid
				ORDER BY date_time DESC";
		$sth = $db->dbh->prepare($sql);
		$sth->bindValue(':fromdate', $fromdate, PDO::PARAM_INT);
		$sth->bindValue(':todate', $todate, PDO::PARAM_INT);
		
		if (!empty($_actionid)) {
			$sth->bindValue(':actionid', $actionid, PDO::PARAM_INT);
		}

		if (!empty($_resourceid)) {
			$sth->bindValue(':resourceid', $resourceid, PDO::PARAM_INT);
		}

		if (!empty($_operid)) {
			$sth->bindValue(':operid', $operid, PDO::PARAM_INT);
		}
		
		$sth->execute();
		$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

		if (!empty($rows)) {
			
			for ($i = 0; $i < count($rows); ++$i) {

				$rows[$i]['actionid'] = $action[$rows[$i]['actionid']];
				$rows[$i]['resourceid'] = $resource[$rows[$i]['resourceid']];
				$rows[$i]['oldvalue'] = substr($rows[$i]['oldvalue'], 0, 11);
				$rows[$i]['newvalue'] = substr($rows[$i]['newvalue'], 0, 11);
			}
		}

		$ctable->form_name = 'audit';
		$ctable->table_name = 'audit';
		$ctable->colspan = 11;
		$ctable->info_field1 = _('total').": ";
		$ctable->info_field2 = _('Audit');

		if ($sysadmin_rights) {
			
			$items1 = array(
				'' => '',
				'backup' => _('backup'),
				'backup_delete' => _('backup and delete'),
				'delete' => _('delete selected')
				);
		}
		else {
			$items1 = array(
				'' => '',
				'backup' => _('backup')
				);
		}
		
		$combobox_form_submit  = "<label class=\"info_right\">". _('action') .": \n".  combobox_onchange('input select', 'action', $items1, "confirm_delete('audit', this[this.selectedIndex].value)") ."</label> \n";

		$ctable->info_field3 = $combobox_form_submit;
		$ctable->checkbox = true;
		$ctable->onclick_id = true;
		$ctable->th_array = array(
			1 => _('ID'),
			2 => _('action'),
			3 => _('resource'),
			4 => _('operator'),
			5 => _('date'),
			6 => _('IP address'),
			7 => _('details'),
			8 => _('old value'),
			9 => _('new value')
			);

		$ctable->td_array = $rows;

		echo $ctable->ctable();
	}


	require_once dirname(__FILE__).'/include/page_footer.php';
}
else {
	header('Location: profile.php');
}
?>
