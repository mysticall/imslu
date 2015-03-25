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

$admin_permissions = (OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type'] || OPERATOR_TYPE_ADMIN == $_SESSION['data']['type']);

###################################################################################################
	// PAGE HEADER
###################################################################################################

$page['title'] = 'Payments';
$page['file'] = 'user_payments.php';

require_once dirname(__FILE__).'/include/page_header.php';


//Only System Admin or Admin have full access to payments
if ($admin_permissions) {

###################################################################################################
	// Reporting payments
###################################################################################################

	if (!empty($_POST['reporting']) && !empty($_POST['id']) && is_array($_POST['id'])) {

		$sql = 'UPDATE `payments` SET reported = :reported WHERE id = :id';
		$db->dbh->beginTransaction();
		$sth = $db->dbh->prepare($sql);
		$sth->bindValue(':reported', 1, PDO::PARAM_INT);
		$sth->bindParam(':id', $value, PDO::PARAM_INT);

		$id = $_POST['id'];

		foreach ($id as $value) {

			$sth->execute();
		}

		$db->dbh->commit();

		// Add audit
		add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_PAYMENTS, "Payments are reported.", json_encode($id));

		$_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";
		unset($_POST);
	}


	$action = array(
		'unreported' => _('unreported'),
		'reported' => _('reported'),
		'obligations' => _('obligations'),
		'limited' => _s('%s days', $LIMITED_INTERNET_ACCESS),
		//'turnover' => _('Turnover for the day'),
		);


	$sql = 'SELECT alias FROM operators';
	$sth = $db->dbh->prepare($sql);
	$sth->execute();
	$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

	if ($rows) {
		for ($i = 0; $i < count($rows); ++$i) {

			$row[$rows[$i]['alias']] = $rows[$i]['alias'];
		}
		$operator = array('' => '') + $row;
	}
	else {
		$operator = array('' => '');
	}
}
else {
	$action = array(
		'unreported' => _('unreported'),
		);

	$operator = array(
		$_SESSION['data']['alias'] => $_SESSION['data']['alias']
		);
}

$form =
"    <form action=\"{$_SERVER['PHP_SELF']}\" method=\"post\">
      <table class=\"tableinfo\">
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th>
              <label style=\"margin: 1px 3px 1px;\">".combobox('input select', 'action', null, $action)."</label>
              <label style=\"margin: 1px 3px 1px;\">"._('operator').combobox('input select', 'operator', null, $operator)."</label>
              <label style=\"margin: 1px 3px 1px;\">"._('from date')."
                <input class=\"input\" style=\"padding: 1px 3px 1px 3px;\" type=\"text\" name=\"fromdate\" id=\"fromdate\" size=\"17\" value=\"".date("Y-m").'-01 00:00'."\">
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


if (isset($_POST['show'])) {

	// Security key for comparison
	$_SESSION['form_key'] = md5(uniqid(mt_rand(), true));
	
	$fromdate = (!empty($_POST['fromdate'])) ? $_POST['fromdate'] : date("Y-m-d", strtotime("-3 days")).' 00:00';
	$todate = (!empty($_POST['todate'])) ? $_POST['todate'] : date('Y-m-d H:i');
	$operator = (!empty($_POST['operator'])) ? $_POST['operator'] : '';


	if ($_POST['action'] == 'unreported') {

		$_operator = (!empty($operator)) ? ' AND operator2 = :operator2' : '';

		// Select unreported payments
		$sql = "SELECT *
				FROM payments 
				WHERE date_payment2 > :fromdate AND date_payment2 < :todate AND reported = :reported$_operator
				ORDER BY id DESC";
		$sth = $db->dbh->prepare($sql);
		$sth->bindValue(':fromdate', $fromdate, PDO::PARAM_INT);
		$sth->bindValue(':todate', $todate, PDO::PARAM_INT);
		$sth->bindValue(':reported', 0, PDO::PARAM_INT);

		if (!empty($_operator)) {
			$sth->bindValue(':operator2', $operator, PDO::PARAM_INT);
		}

		$sth->execute();
		$payments_info = $sth->fetchAll(PDO::FETCH_ASSOC);

		// Select payments by operator
		$sql = "SELECT operator2 as operator, SUM(sum) as totalsum
				FROM payments 
				WHERE date_payment2 > :fromdate AND date_payment2 < :todate AND reported = :reported$_operator
				GROUP BY operator2 WITH ROLLUP";
		$sth = $db->dbh->prepare($sql);
		$sth->bindValue(':fromdate', $fromdate, PDO::PARAM_INT);
		$sth->bindValue(':todate', $todate, PDO::PARAM_INT);
		$sth->bindValue(':reported', 0, PDO::PARAM_INT);

		if (!empty($_operator)) {
			$sth->bindValue(':operator2', $operator, PDO::PARAM_INT);
		}

		$sth->execute();
		$payments_operator = $sth->fetchAll(PDO::FETCH_ASSOC);
	}

	elseif ($_POST['action'] == 'reported') {

		$_operator = (!empty($operator)) ? ' AND operator2 = :operator2' : '';

		// Select reported payments
		$sql = "SELECT * 
				FROM payments 
				WHERE date_payment2 > :fromdate AND date_payment2 < :todate AND reported = :reported$_operator
				ORDER BY id DESC";
		$sth = $db->dbh->prepare($sql);
		$sth->bindValue(':fromdate', $fromdate, PDO::PARAM_INT);
		$sth->bindValue(':todate', $todate, PDO::PARAM_INT);
		$sth->bindValue(':reported', 1, PDO::PARAM_INT);

		if (!empty($_operator)) {
			$sth->bindValue(':operator2', $operator, PDO::PARAM_INT);
		}

		$sth->execute();
		$payments_info = $sth->fetchAll(PDO::FETCH_ASSOC);

		// Select payments by operator
		$sql = "SELECT operator2 as operator, SUM(sum) as totalsum
				FROM payments 
				WHERE date_payment2 > :fromdate AND date_payment2 < :todate AND reported = :reported$_operator
				GROUP BY operator2 WITH ROLLUP";
		$sth = $db->dbh->prepare($sql);
		$sth->bindValue(':fromdate', $fromdate, PDO::PARAM_INT);
		$sth->bindValue(':todate', $todate, PDO::PARAM_INT);
		$sth->bindValue(':reported', 1, PDO::PARAM_INT);

		if (!empty($_operator)) {
			$sth->bindValue(':operator2', $operator, PDO::PARAM_INT);
		}

		$sth->execute();
		$payments_operator = $sth->fetchAll(PDO::FETCH_ASSOC);
	}

	elseif ($_POST['action'] == 'obligations') {

		$_operator = (!empty($operator)) ? ' AND operator1 = :operator1' : '';

		// Select unpaid payments
		$sql = "SELECT * 
				FROM payments 
				WHERE date_payment1 > :fromdate AND date_payment1 < :todate AND unpaid = :unpaid$_operator
				ORDER BY id DESC";
		$sth = $db->dbh->prepare($sql);
		$sth->bindValue(':fromdate', $fromdate, PDO::PARAM_INT);
		$sth->bindValue(':todate', $todate, PDO::PARAM_INT);
		$sth->bindValue(':unpaid', 1, PDO::PARAM_INT);

		if (!empty($_operator)) {
			$sth->bindValue(':operator1', $operator, PDO::PARAM_INT);
		}
		
		$sth->execute();
		$payments_info = $sth->fetchAll(PDO::FETCH_ASSOC);

		// Select payments by operator
		$sql = "SELECT operator1 as operator, SUM(sum) as totalsum
				FROM payments 
				WHERE date_payment1 > :fromdate AND date_payment1 < :todate AND unpaid = :unpaid$_operator
				GROUP BY operator1 WITH ROLLUP";
		$sth = $db->dbh->prepare($sql);
		$sth->bindValue(':fromdate', $fromdate, PDO::PARAM_INT);
		$sth->bindValue(':todate', $todate, PDO::PARAM_INT);
		$sth->bindValue(':unpaid', 1, PDO::PARAM_INT);

		if (!empty($_operator)) {
			$sth->bindValue(':operator1', $operator, PDO::PARAM_INT);
		}

		$sth->execute();
		$payments_operator = $sth->fetchAll(PDO::FETCH_ASSOC);
	}

	elseif ($_POST['action'] == 'limited') {

		$_operator = (!empty($operator)) ? ' AND operator1 = :operator1' : '';

		// Select limited access payments
		$sql = "SELECT * 
				FROM payments 
				WHERE date_payment1 > :fromdate AND date_payment1 < :todate AND limited = :limited$_operator
				ORDER BY id DESC";
		$sth = $db->dbh->prepare($sql);
		$sth->bindValue(':fromdate', $fromdate, PDO::PARAM_INT);
		$sth->bindValue(':todate', $todate, PDO::PARAM_INT);
		$sth->bindValue(':limited', 1, PDO::PARAM_INT);

		if (!empty($_operator)) {
			$sth->bindValue(':operator1', $operator, PDO::PARAM_INT);
		}

		$sth->execute();
		$payments_info = $sth->fetchAll(PDO::FETCH_ASSOC);

		// Select payments by operator
		$sql = "SELECT operator1 as operator, SUM(sum) as totalsum
				FROM payments 
				WHERE date_payment1 > :fromdate AND date_payment1 < :todate AND limited = :limited$_operator
				GROUP BY operator1 WITH ROLLUP";
		$sth = $db->dbh->prepare($sql);
		$sth->bindValue(':fromdate', $fromdate, PDO::PARAM_INT);
		$sth->bindValue(':todate', $todate, PDO::PARAM_INT);
		$sth->bindValue(':limited', 1, PDO::PARAM_INT);

		if (!empty($_operator)) {
			$sth->bindValue(':operator1', $operator, PDO::PARAM_INT);
		}

		$sth->execute();
		$payments_operator = $sth->fetchAll(PDO::FETCH_ASSOC);
	}



	if ($_POST['action'] == 'unreported' && isset($payments_info[0]) && $admin_permissions) {

		$items1 = array(
			'' => '',
			'reporting' => _('reporting of selected'),
			);

		$report = "<label class=\"info_right\">". _('action') .": \n".  combobox_onchange('input select', 'reporting', $items1, null) ."</label>";
		$checkbox = "<th style=\"table-layout: fixed; width: 3px;\"><input class=\"input\" type=\"checkbox\" id=\"all\" onclick=\"check_unchek('payments', 'all')\"></th> \n";
		$colspan = 7;
	}
	else {
		$report = null;
		$checkbox = null;
		$colspan = 6;
	}
	
	$form =
"    <form name=\"payments\" action=\"payments.php\" method=\"post\">
      <table class=\"tableinfo\">
        <thead id=\"thead\">
          <tr class=\"header_top\">
            <th colspan=\"$colspan\">
              <label style=\"float: left;\">". _('total').": ".count($payments_info)."</label>
              $report
            </th>
          </tr> \n";

	$form .=
"          <tr class=\"header\">
            $checkbox
            <th>"._('name')."</th>
            <th>"._('obligation')."</th>
            <th>"._('payment')."</th>
            <th>"._('expires')."</th>
            <th>"._('notes')."</th>
            <th>"._('sum')."</th>
          </tr>
        </thead>
        <tbody>\n";

	if (isset($payments_info[0])) {
		


		$sum = array(
			'total' => ''
			);

		for ($i = 0; $i < count($payments_info); ++$i) {

			//$sum['operator2'] = (isset($sum['operator2']) && $payments_info[$i] == isset($sum['operator2']) ? $sum['operator2'] + $payments_info[$i]['sum'] : $sum['operator2'] = $payments_info[$i]['sum'];
			$sum['total'] = $sum['total'] + $payments_info[$i]['sum'];
			$red = ($payments_info[$i]['unpaid'] == 1 || $payments_info[$i]['limited'] == 1) ? "red" : "";


			if ($i % 2 == 0) {

				$form .= 
"          <tr class=\"even_row $red\"> \n";

				if (!empty($checkbox)) {
					$form .= "            <td><input class=\"input\" type=\"checkbox\" id=\"checkbox\" name=\"id[{$payments_info[$i]['id']}]\" value=\"{$payments_info[$i]['id']}\"></td> \n";
				}

				$form .= 
"            <td><a href=\"user_payments.php?userid={$payments_info[$i]['userid']}&id={$payments_info[$i]['id']}\">".chars($payments_info[$i]['name'])."</a></td>
            <td>{$payments_info[$i]['date_payment1']}   ".chars($payments_info[$i]['operator1'])."</td>
            <td>{$payments_info[$i]['date_payment2']}   ".chars($payments_info[$i]['operator2'])."</td>
            <td>{$payments_info[$i]['expires']}</td>
            <td>".chars($payments_info[$i]['notes'])."</td>
            <td>{$payments_info[$i]['sum']}</td>
          </tr> \n";

			}
			else {

				$form .= 
"          <tr class=\"odd_row $red\"> \n";

				if (!empty($checkbox)) {
					$form .= "            <td><input class=\"input\" type=\"checkbox\" id=\"checkbox\" name=\"id[{$payments_info[$i]['id']}]\" value=\"{$payments_info[$i]['id']}\"></td> \n";
				}

				$form .= 
"            <td><a href=\"user_payments.php?userid={$payments_info[$i]['userid']}&id={$payments_info[$i]['id']}\">".chars($payments_info[$i]['name'])."</a></td>
            <td>{$payments_info[$i]['date_payment1']}   ".chars($payments_info[$i]['operator1'])."</td>
            <td>{$payments_info[$i]['date_payment2']}   ".chars($payments_info[$i]['operator2'])."</td>
            <td>{$payments_info[$i]['expires']}</td>
            <td>".chars($payments_info[$i]['notes'])."</td>
            <td>{$payments_info[$i]['sum']}</td>
          </tr> \n";

			}
		}

		$count = count($payments_operator) - 1;
		for ($i = 0; $i < $count; ++$i) {
			
		$form .=
"          <tr class=\"header\">
            <td colspan=\"".($colspan - 1)."\" style=\"text-align: right;\">
              <label>".chars($payments_operator[$i]['operator']).": </label>
            </td>
            <td>
              <label>{$payments_operator[$i]['totalsum']}</label>
            </td>
          </tr> \n";
		}

		$form .=
"          <tr class=\"header\">
            <td colspan=\"".($colspan - 1)."\" style=\"text-align: right;\">
              <label>"._('total sum').": </label>
            </td>
            <td>
              <label>{$payments_operator[$count]['totalsum']}</label>
            </td>
          </tr> \n";
	}

	$form .=          
"        </tbody>
      </table>
    </form> \n";

echo $form;
}

require_once dirname(__FILE__).'/include/page_footer.php';
?>
