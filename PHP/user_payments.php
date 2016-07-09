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
$cashier_permissions = (OPERATOR_TYPE_CASHIER == $_SESSION['data']['type']);
$technician_permissions = (OPERATOR_TYPE_TECHNICIAN == $_SESSION['data']['type']);
$readonly = ($admin_permissions) ? '' : ' readonly';


###################################################################################################
	// PAGE HEADER
###################################################################################################

$page['title'] = 'User Payments';
$page['file'] = 'user_payments.php';

require_once dirname(__FILE__).'/include/page_header.php';


#####################################################
	// Display messages
#####################################################
echo !empty($_SESSION['msg']) ? '<div class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
$_SESSION['msg'] = null;


###################################################################################################
	// Edit User
###################################################################################################

if (!empty($_GET['userid'])) {

	# !!! Prevent problems !!!
	$userid = $_GET['userid'];
	settype($userid, "integer");
	if($userid == 0) {
		
		header("Location: users.php");
		exit;
	}

#####################################################
	// Get user info and payment
#####################################################

	$sql = 'SELECT users.*, traffic.price, radcheck.username
			FROM users
			LEFT JOIN traffic ON users.trafficid = traffic.trafficid
			LEFT JOIN radcheck ON users.userid = radcheck.userid
			WHERE users.userid = :userid LIMIT 1';
	$sth = $db->dbh->prepare($sql);
	$sth->bindValue(':userid', $userid, PDO::PARAM_INT);
	$sth->execute();
	$user_info = $sth->fetch(PDO::FETCH_ASSOC);


	$sql = 'SELECT *
			FROM payments
			WHERE userid = :userid ORDER BY expires DESC';
	$sth = $db->dbh->prepare($sql);
	$sth->bindParam(':userid', $userid, PDO::PARAM_INT);
	$sth->execute();
	$payments_info = $sth->fetchAll(PDO::FETCH_ASSOC);


	// Security key for comparison
	$_SESSION['form_key'] = md5(uniqid(mt_rand(), true));


###################################################################################################
	// Edit payment
###################################################################################################

	if (!empty($_GET['id']) && !empty($payments_info[0])) {
		
		for ($i = 0; $i < count($payments_info); ++$i) {
			
			if ($payments_info[$i]['id'] == $_GET['id']) {
				
				$payments_edit = $payments_info[$i];
				$_SESSION['payment_info'] = $payments_info[$i];
				break;
			}
		}

		if (isset($payments_edit)) {

			$form =
"    <form action=\"user_payments_apply.php\" method=\"post\">
      <table class=\"tableinfo\">
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th  colspan=\"2\">
              <label>"._('payment').": ".$payments_edit['id']."</label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('obligation')."</label>
            </td>
            <td class=\"dd\">
              <label>{$payments_edit['date_payment1']}   ".chars($payments_edit['operator1'])."</label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('payment')."</label>
            </td>
            <td class=\"dd\">
              <label>{$payments_edit['date_payment2']}   ".chars($payments_edit['operator2'])."</label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('expires')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"expires\" id=\"expires_edit\" size=\"17\" value=\"{$payments_edit['expires']}\" $readonly>\n";

			$form .= ($admin_permissions) ? "
                <img src=\"js/calendar/img.gif\" id=\"f_trigger_b1\">
                <script type=\"text/javascript\">
                Calendar.setup({
                  inputField     :    \"expires_edit\",
                  ifFormat       :    \"%Y-%m-%d %H:%M\",
                  showsTime      :    true,
                  button         :    \"f_trigger_b1\",
                  singleClick    :    true,
                  step           :    1
                });
                </script>\n" : '';

			$form .= "
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('sum')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"sum\" value=\"{$payments_edit['sum']}\" $readonly>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('notes')."</label>
            </td>
            <td class=\"dd\">
              <textarea name=\"notes\" cols=\"55\" rows=\"3\">".chars($payments_edit['notes'])."</textarea>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._s('%s days', $LIMITED_INTERNET_ACCESS)."</label>
            </td>
            <td class=\"dd\">
              <label>{$payments_edit['limited']}</label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('obligation')."</label>
            </td>
            <td class=\"dd\">
              <label>{$payments_edit['unpaid']}</label>
            </td>
          </tr>\n";

			$form .= ($admin_permissions) ? "
          <tr>
            <td class=\"dt right\">
              <label><span class=\"red\">"._('delete')."</span></label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"checkbox\" name=\"del\">
            </td>
          </tr>\n" : '';

			$form .= "
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"hidden\" name=\"id\" value=\"{$payments_edit['id']}\">
              <input type=\"hidden\" name=\"userid\" value=\"{$user_info['userid']}\">
              <input type=\"hidden\" name=\"name\" value=\"".chars($user_info['name'])."\">
              <input type=\"submit\" name=\"save\" id=\"save\" value=\""._('save')."\">\n";

			$form .= ($admin_permissions) ? "
              <input type=\"submit\" name=\"delete\" id=\"delete\" value=\""._('delete')."\">\n" : '';

			$form .= "
            </td>
          </tr>
        </tbody>
      </table>
    </form>";

		echo $form;
		}
	}

###################################################################################################
	// Show payments
###################################################################################################

	if (isset($payments_info[0]['expires']) && $payments_info[0]['expires'] > date('Y-m-d H:i')) {
		
		$time = strtotime("{$payments_info[0]['expires']}");
		$expires = date("Y-m-d", strtotime("+1 month", $time))." 23:59";
		$limited_access = date("Y-m-d", strtotime("+$LIMITED_INTERNET_ACCESS days", $time))." 23:59";
	}
	else {
		$expires = date("Y-m-d", strtotime("+1 month"))." 23:59";
		$limited_access = date("Y-m-d", strtotime("+$LIMITED_INTERNET_ACCESS days"))." 23:59";
	}

	$username = $user_info['name'];
	$sum = ($user_info['pay'] != 0.00 && $user_info['pay'] != $user_info['price']) ? $user_info['pay'] : $user_info['price'];


	$form =
"    <form name=\"user_payments\" action=\"user_payments_apply.php\" method=\"post\">
      <table class=\"tableinfo\">
        <thead id=\"thead\">
          <tr class=\"header_top\">
            <th colspan=\"8\">
              <label style=\"float: left;\">". _('total').": ".count($payments_info) ."</label>
              <label>". _s('payments of %s', chars($username)) ."</label>
              <label class=\"info_right\">
                <a href=\"user.php?userid={$userid}\">["._('back')."]</a>
                <a href=\"user_tickets.php?userid={$userid}\">["._('tickets')."]</a>
              </label>
            </th>
          </tr> \n";

	if ($user_info['free_access'] == 0) {

		$form .=
"          <tr class=\"header_top\">
            <th colspan=\"8\">

              <label>". _('sum') ."</label>
                <input class=\"input\" type=\"text\" name=\"sum\" id=\"sum\" size=\"5\" value=\"$sum\" $readonly>
              <label class=\"link2\">". _('expires')."
                <input class=\"input\" type=\"text\" name=\"expires\" id=\"expires\" size=\"17\" value=\"$expires\" $readonly> \n";

		if ($admin_permissions) {
			$form .=
"                <img src=\"js/calendar/img.gif\" id=\"f_trigger_b2\">
                <script type=\"text/javascript\">
                Calendar.setup({
                  inputField     :    \"expires\",
                  ifFormat       :    \"%Y-%m-%d %H:%M\",
                  showsTime      :    true,
                  button         :    \"f_trigger_b2\",
                  singleClick    :    true,
                  step           :    1
                });
                </script> \n";
		}

		// If user last payments "expires" < date - now, start internet access
		$start_internet = (empty($payments_info[0]['expires']) || $payments_info[0]['expires'] < date('Y-m-d H:i:s')) ? "true" : "";
		$form .=
"              </label>
              <label class=\"link2\">". _('notes') ."</label>
                <input class=\"input\" type=\"text\" name=\"notes\" id=\"notes\" size=\"35\">
                <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
                <input type=\"hidden\" name=\"start_internet\" value=\"$start_internet\">
                <input type=\"hidden\" name=\"userid\" value=\"{$user_info['userid']}\">
                <input type=\"hidden\" name=\"name\" value=\"".chars($user_info['name'])."\">
                <input type=\"hidden\" name=\"username\" value=\"".chars($user_info['username'])."\">
                <input type=\"hidden\" name=\"limited\" id=\"limited\" value=\"$limited_access\">
                <input type=\"hidden\" name=\"limited_access\" id=\"limited_access\" value=\"\">
              <label class=\"generator link2\" onclick=\"document.getElementById('limited_access').value = 'true'; this.form.submit();\">["._s('%s days', $LIMITED_INTERNET_ACCESS)."]</label>
                <input type=\"hidden\" name=\"obligation\" id=\"obligation\" value=\"\">
              <label class=\"generator link2\" onclick=\"document.getElementById('obligation').value = 'true'; this.form.submit();\">["._('obligation')."]</label>
                <input type=\"hidden\" name=\"payment\" id=\"payment\" value=\"\">
              <label class=\"generator link2\" onclick=\"document.getElementById('payment').value = 'true'; this.form.submit();\">["._('payment')."]</label>
            </th>
          </tr> \n";
	}
	else {

		$form .=
"          <tr class=\"header_top red\">
            <th colspan=\"8\">
              <label>"._('User is exempt from payments!')."</label>
            </th>
          </tr> \n";
	}

	$form .=
"          <tr class=\"header\">
            <th>"._('id')."</th>
            <th>"._('obligation')."</th>
            <th>"._('payment')."</th>
            <th>"._('expires')."</th>
            <th>"._('sum')."</th>
            <th>"._('notes')."</th>
            <th style=\"table-layout: auto; width: 69px;\">"._s('%s days', $LIMITED_INTERNET_ACCESS)."</th>
            <th style=\"table-layout: auto; width: 69px;\">"._('obligation')."</th>
          </tr>
        </thead>
        <tbody>\n";

	if (isset($payments_info[0])) {

		$total_sum = null;

		for ($i = 0; $i < count($payments_info); ++$i) {

			$total_sum = $total_sum + $payments_info[$i]['sum'];
			$red = ($payments_info[$i]['unpaid'] == 1 || $payments_info[$i]['limited'] == 1) ? "red" : "";

			if ($i % 2 == 0) {

				$form .= 
"          <tr class=\"even_row $red\">
            <td class=\"left_select\" onclick=\"location.href='user_payments.php?userid={$user_info['userid']}&id={$payments_info[$i]['id']}';\">{$payments_info[$i]['id']}</td>
            <td>{$payments_info[$i]['date_payment1']}   ".chars($payments_info[$i]['operator1'])."</td>
            <td>{$payments_info[$i]['date_payment2']}   ".chars($payments_info[$i]['operator2'])."</td>
            <td>{$payments_info[$i]['expires']}</td>
            <td>{$payments_info[$i]['sum']}</td>
            <td>".chars($payments_info[$i]['notes'])."</td>\n";

				if($payments_info[$i]['limited'] == 1) {
					$form .= 
"            <td class=\"left_select\" onclick=\"change_input('user_payments', '{$payments_info[$i]['id']}', 'pay_limited', '{$payments_info[$i]['id']}');\">["._('payment')."]
              <input id=\"{$payments_info[$i]['id']}\" type=\"hidden\" name value>
              <input type=\"hidden\" name=\"expires_{$payments_info[$i]['id']}\" value=\"{$payments_info[$i]['expires']}\"></td> \n";
				}
				else {
					$form .=
"            <td></td> \n";
				}

				if($payments_info[$i]['unpaid'] == 1) {
					$form .= 
"            <td class=\"left_select\" onclick=\"change_input('user_payments', '{$payments_info[$i]['id']}', 'pay_unpaid', '{$payments_info[$i]['id']}');\">["._('payment')."]
              <input id=\"{$payments_info[$i]['id']}\" type=\"hidden\" name value></td> \n";
				}
				else {
					$form .=
"            <td></td> \n";
				}

				$form .=
"          </tr> \n";

			}
			else {

				$form .= 
"          <tr class=\"odd_row $red\">
            <td class=\"left_select\" onclick=\"location.href='user_payments.php?userid={$user_info['userid']}&id={$payments_info[$i]['id']}';\">{$payments_info[$i]['id']}</td>
            <td>{$payments_info[$i]['date_payment1']}   ".chars($payments_info[$i]['operator1'])."</td>
            <td>{$payments_info[$i]['date_payment2']}   ".chars($payments_info[$i]['operator2'])."</td>
            <td>{$payments_info[$i]['expires']}</td>
            <td>{$payments_info[$i]['sum']}</td>
            <td>".chars($payments_info[$i]['notes'])."</td>\n";

				if($payments_info[$i]['limited'] == 1) {
					$form .= 
"            <td class=\"left_select\" onclick=\"change_input('user_payments', '{$payments_info[$i]['id']}', 'pay_limited', '{$payments_info[$i]['id']}');\">["._('payment')."]
              <input id=\"{$payments_info[$i]['id']}\" type=\"hidden\" name value>
              <input type=\"hidden\" name=\"expires_{$payments_info[$i]['id']}\" value=\"{$payments_info[$i]['expires']}\"></td> \n";
				}
				else {
					$form .=
"            <td></td> \n";
				}

				if($payments_info[$i]['unpaid'] == 1) {
					$form .= 
"            <td class=\"left_select\" onclick=\"change_input('user_payments', '{$payments_info[$i]['id']}', 'pay_unpaid', '{$payments_info[$i]['id']}');\">["._('payment')."]
              <input id=\"{$payments_info[$i]['id']}\" type=\"hidden\" name value></td> \n";
				}
				else {
					$form .=
"            <td></td> \n";
				}

				$form .=
"          </tr> \n";

			}
		}

		$form .=
"          <tr class=\"header\" style=\"text-align: right;\">
            <th colspan=\"8\">
              <label>"._('total sum').": ".$total_sum."</label>
            </th>
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
