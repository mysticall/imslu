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

$sysadmin_rights = (OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type']);
$admin_rights = (OPERATOR_TYPE_ADMIN == $_SESSION['data']['type']);

if ($sysadmin_rights || $admin_rights) {

	$db = new PDOinstance();
    $actionid = (!empty($_GET['actionid'])) ? $_GET['actionid'] : '';
    $resourceid = (!empty($_GET['resourceid'])) ? $_GET['resourceid'] : '';
    $operid = (!empty($_GET['operid'])) ? $_GET['operid'] : '';
    $fromdate = (!empty($_GET['fromdate'])) ? $_GET['fromdate'] : date("Y-m-d", strtotime("-3 days")).' 00:00';
    $todate = (!empty($_GET['todate'])) ? $_GET['todate'] : date('Y-m-d H:i');

    ####### PAGE HEADER #######
	$page['title'] = 'Audit';

	require_once dirname(__FILE__).'/include/page_header.php';

    ####### Display messages #######
    echo !empty($_SESSION['msg']) ? '<div id="msg" class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
    $_SESSION['msg'] = null;

	$action = array(
		'' => '',
		AUDIT_ACTION_LOGIN => _('login'),
		AUDIT_ACTION_LOGOUT => _('logout'),
		AUDIT_ACTION_ADD => _('add'),
		AUDIT_ACTION_UPDATE => _('update'),
		AUDIT_ACTION_DELETE => _('delete'),
		AUDIT_ACTION_ENABLE => _('enable'),
		AUDIT_ACTION_DISABLE => _('disable')
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
"    <form action=\"{$_SERVER['PHP_SELF']}\" method=\"get\">
      <table>
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th>
              <label>"._('action').combobox('middle', 'actionid', $actionid, $action)."</label>
              <label>"._('resource').combobox('middle', 'resourceid', $resourceid, $resource)."</label>
              <label>"._('operator').combobox('middle', 'operid', $operid, $operator)."</label>
              <label>"._('from date')."
                <input id=\"fromdate\" style=\"width: 120px;\" type=\"text\" name=\"fromdate\" value=\"{$fromdate}\">
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
                <input id=\"todate\" style=\"width: 120px;\" type=\"text\" name=\"todate\" value=\"{$todate}\">
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
              <input class=\"button\" type=\"submit\" name=\"show\" value=\""._('search')."\">
            </th>
          </tr>
        </tbody>
      </table>
    </form>\n";

	echo $form;

	if(!empty($_GET['auditid'])) {

        settype($_GET['auditid'], "integer");
        if($_GET['auditid'] == 0) {
            header("Location: audit.php");
            exit;
        }

		$sql = "SELECT actionid, resourceid, oper_alias, date_time, ip, details, oldvalue, newvalue 
				FROM auditlog
				WHERE auditid = :auditid";
		$sth = $db->dbh->prepare($sql);
		$sth->bindValue(':auditid', $_GET['auditid'], PDO::PARAM_INT);
		$sth->execute();
		$audit = $sth->fetch(PDO::FETCH_ASSOC);

		$audit['actionid'] = $action[$audit['actionid']];
		$audit['resourceid'] = $resource[$audit['resourceid']];

		$form =
"     <table>
        <tbody id=\"user_info\">
          <tr class=\"header_top\">
            <th>
            </th>
          </tr>
          <tr>
            <td class=\"dd\">
              <label><b>"._('action').": </b>{$audit['actionid']}</label>
            </td>
          </tr>
          <tr>
            <td class=\"dd\">
              <label><b>"._('resource').": </b>{$audit['resourceid']}</label>
            </td>
          </tr>
          <tr>
            <td class=\"dd\">
              <label><b>"._('operator').": </b>".chars($audit['oper_alias'])."</label>
            </td>
          </tr>
          <tr>
            <td class=\"dd\">
              <label><b>"._('date').": </b>{$audit['date_time']}</label>
            </td>
          </tr>
          <tr>
            <td class=\"dd\">
              <label><b>"._('IP address').": </b>{$audit['ip']}</label>
            </td>
          </tr>
          <tr>
            <td class=\"dd\">
              <label><b>"._('details').": </b>".chars($audit['details'])."</label>
            </td>
          </tr>
          <tr>
            <td class=\"dd\">
              <label><b>"._('old value').":</b></label><br>
              <textarea class=\"large\" rows=\"3\" readonly>".chars($audit['oldvalue'])."</textarea>
            </td>
          </tr>
          <tr>
            <td class=\"dd\">
              <label><b>"._('new value').":</b></label><br>
              <textarea class=\"large\" rows=\"3\" readonly>".chars($audit['newvalue'])."</textarea>
            </td>
          </tr>
        </tbody>
      </table>\n";

		echo $form;
	}


    ####### Set CTable variables #######
	if (!empty($_GET['show'])) {

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
                $rows[$i]['details'] = substr($rows[$i]['details'], 0, 25);
				$rows[$i]['oldvalue'] = substr($rows[$i]['oldvalue'], 0, 25);
				$rows[$i]['newvalue'] = substr($rows[$i]['newvalue'], 0, 25);
			}
		}

		$table = new Table();
		$table->colspan = 11;
		$table->info_field1 = _('total').": ";
		$table->info_field2 = _('Audit');
        $table->link_action = 'audit.php';
		$table->link = true;
		$table->th_array = array(
			1 => _('id'),
			2 => _('action'),
			3 => _('resource'),
			4 => _('operator'),
			5 => _('date'),
			6 => _('IP address'),
			7 => _('details'),
			8 => _('old value'),
			9 => _('new value')
			);

		$table->td_array = $rows;

		echo $table->ctable();
	}

	require_once dirname(__FILE__).'/include/page_footer.php';
}
else {
	header('Location: profile.php');
}
?>
