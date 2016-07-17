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

$sysadmin_permissions = (OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type']);
$admin_permissions = (OPERATOR_TYPE_ADMIN == $_SESSION['data']['type']);

if ($sysadmin_permissions || $admin_permissions) {

	$db = new PDOinstance();
    $fromdate = (!empty($_GET['fromdate'])) ? $_GET['fromdate'] : date("Y-m-d", strtotime("-3 days")).' 00:00';
    $todate = (!empty($_GET['todate'])) ? $_GET['todate'] : date('Y-m-d H:i');

    #######  Reset attempts from IP #######
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


    ####### PAGE HEADER #######
	$page['title'] = 'Login attempts';

	require_once dirname(__FILE__).'/include/page_header.php';

    ####### Display messages #######
    echo !empty($_SESSION['msg']) ? '<div id="msg" class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
    $_SESSION['msg'] = null;

	$form =
"    <form action=\"{$_SERVER['PHP_SELF']}\" method=\"get\">
      <table>
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th>
              <label style=\"margin: 1px 3px 1px;\">"._('from date')."
                <input style=\"width: 120px;\" type=\"text\" name=\"fromdate\" id=\"fromdate\" value=\"{$fromdate}\">
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
                <input style=\"width: 120px;\" type=\"text\" name=\"todate\" id=\"todate\" value=\"{$todate}\">
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


    ####### Set CTable variables #######
	if (!empty($_GET['show'])) {

		// Select user IP Addresses
		$sql = "SELECT attempt_ip, attempt_failed, attempt_time, alias 
				FROM login_attempts
				WHERE attempt_time > :fromdate AND attempt_time < :todate
				ORDER BY attempt_time DESC";
		$sth = $db->dbh->prepare($sql);
		$sth->bindValue(':fromdate', strtotime($fromdate), PDO::PARAM_INT);
		$sth->bindValue(':todate', strtotime($todate), PDO::PARAM_INT);
		$sth->execute();
		$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

		if (!empty($rows)) {
			
			for ($i = 0; $i < count($rows); ++$i) {

				$rows[$i]['attempt_time'] = date('Y-m-d H:i', "{$rows[$i]['attempt_time']}");
			}
		}

        $table = new Table();
        $table->form_name = 'login_attempts';
		$table->colspan = 5;
        $table->checkbox = TRUE;
		$table->info_field1 = _('total').": ";
		$table->info_field2 = _('Login attempts');

        $items1 = array(
            '' => '',
            'reset' => _('reset selected')
            );

		$combobox_form_submit  = "<label class=\"info_right\">". _('action') .": \n".  combobox_onchange('', 'action', $items1, "confirm_delete('login_attempts', this[this.selectedIndex].value)") ."</label> \n";

		$table->info_field3 = $combobox_form_submit;
		$table->checkbox = true;

		$table->th_array = array(
			1 => _('IP address'),
			2 => _('attempts'),
			3 => _('date'),
			4 => _('alias')
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
