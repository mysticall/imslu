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

	if (!empty($_POST['action']) && $_POST['action'] == 'delete' && !empty($_POST['attempt_ip'])) {

		$sql = 'DELETE FROM `login_attempts` WHERE attempt_ip = :attempt_ip';
		$db->dbh->beginTransaction();
		$sth = $db->dbh->prepare($sql);
		$sth->bindParam(':attempt_ip', $value, PDO::PARAM_INT);

		$attempt_ip = $_POST['attempt_ip'];

		foreach ($attempt_ip as $value) {

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
sqldump.mysqldump(tabe_name = 'login_attempts', info = '$info')
END";

		$result = shell_exec($command);
		$_SESSION['msg'] = (empty($result)) ? _s('Backup of table %s success.', 'login_attempts').'<br>' : _s('Backup of table %s failed', 'login_attempts').' - '.$result.'<br>';

		if(($_POST['action'] == 'backup_delete') && empty($result)) {

			$sql = 'DELETE FROM `login_attempts`';
			$sth = $db->dbh->exec($sql);

			$_SESSION['msg'] .= _s('The contents of the table %s was deleted.', 'login_attempts').'<br>';
		}

		unset($_POST);
	}

###################################################################################################
	// Reset attempts from IP
###################################################################################################

	if (!empty($_POST['action']) && $_POST['action'] == 'reset' && !empty($_POST['attempt_ip'])) {

		$sql = 'UPDATE `login_attempts` SET attempt_failed = :attempt_failed WHERE attempt_ip = :attempt_ip';
		$db->dbh->beginTransaction();
		$sth = $db->dbh->prepare($sql);
		$sth->bindValue(':attempt_failed', 0, PDO::PARAM_INT);
		$sth->bindParam(':attempt_ip', $value, PDO::PARAM_INT);

		$attempt_ip = $_POST['attempt_ip'];

		foreach ($attempt_ip as $value) {

			$sth->execute();
		}

		$db->dbh->commit();

		unset($_POST);
	}


###################################################################################################
	// PAGE HEADER
###################################################################################################
	
	$page['title'] = 'Login attempts';
	$page['file'] = 'login_attempts.php';

	require_once dirname(__FILE__).'/include/page_header.php';

	$form =
"    <form action=\"{$_SERVER['PHP_SELF']}\" method=\"post\">
      <table class=\"tableinfo\">
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th>
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

###################################################################################################
	// Set CTable variables and create dynamic html table
###################################################################################################	

	if (isset($_POST['show'])) {
		
		$fromdate = (!empty($_POST['fromdate'])) ? strtotime($_POST['fromdate']) : strtotime(date("Y-m-d", strtotime("-3 days")));
		$todate = (!empty($_POST['todate'])) ? strtotime($_POST['todate']) : time();
		
		// Select user IP Addresses
		$sql = "SELECT attempt_ip, attempt_failed, attempt_time, alias 
				FROM login_attempts
				WHERE attempt_time > :fromdate AND attempt_time < :todate
				ORDER BY attempt_time DESC";
		$sth = $db->dbh->prepare($sql);
		$sth->bindValue(':fromdate', $fromdate, PDO::PARAM_INT);
		$sth->bindValue(':todate', $todate, PDO::PARAM_INT);
		$sth->execute();
		$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

		if (!empty($rows)) {
			
			for ($i = 0; $i < count($rows); ++$i) {

				$rows[$i]['attempt_time'] = date('Y-m-d H:i', "{$rows[$i]['attempt_time']}");
			}
		}

		$ctable->form_name = 'login_attempts';
		$ctable->table_name = 'login_attempts';
		$ctable->colspan = 5;
		$ctable->info_field1 = _('total').": ";
		$ctable->info_field2 = _('Login attempts');

		if ($sysadmin_rights) {
			$items1 = array(
				'' => '',
				'reset' => _('reset selected'),
				'backup' => _('backup'),
				'backup_delete' => _('backup and delete'),
				'delete' => _('delete selected')
				);
		}
		else {
			$items1 = array(
				'' => '',
				'reset' => _('reset selected')
				);
		}
		
		$combobox_form_submit  = "<label class=\"info_right\">". _('action') .": \n".  combobox_onchange('input select', 'action', $items1, "confirm_delete('login_attempts', this[this.selectedIndex].value)") ."</label> \n";
	
		$ctable->info_field3 = $combobox_form_submit;
		$ctable->checkbox = true;

		$ctable->th_array = array(
			1 => _('IP address'),
			2 => _('attempts'),
			3 => _('date'),
			4 => _('alias')
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
