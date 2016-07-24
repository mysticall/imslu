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

####### PAGE HEADER #######
$page['title'] = 'User Payments';

require_once dirname(__FILE__).'/include/page_header.php';


####### Display messages #######
echo !empty($_SESSION['msg']) ? '<div id="msg" class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
$_SESSION['msg'] = null;


if (!empty($_GET['userid'])) {

    # !!! Prevent problems !!!
    $userid = $_GET['userid'];
    settype($userid, "integer");
    if($userid == 0) {
        
        header("Location: users.php");
        exit;
    }

    ####### Services #######
    $sql = 'SELECT name,price FROM services';
    $sth = $db->dbh->prepare($sql);
    $sth->execute();
    $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

    if ($rows) {
        for ($i = 0; $i < count($rows); ++$i) {

            $services[$rows[$i]['name']] = $rows[$i]['price'];
        }
    }

    $sql = 'SELECT * FROM users WHERE userid = :userid LIMIT 1';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':userid', $userid, PDO::PARAM_INT);
    $sth->execute();
    $user = $sth->fetch(PDO::FETCH_ASSOC);


    $sql = 'SELECT * FROM payments WHERE userid = :userid ORDER BY expires DESC';
    $sth = $db->dbh->prepare($sql);
    $sth->bindParam(':userid', $userid, PDO::PARAM_INT);
    $sth->execute();
    $payments = $sth->fetchAll(PDO::FETCH_ASSOC);

    $disabled = 0;
    if ($payments) {
        foreach ($payments as $value) {
            if ($value['unpaid'] == 1 || $value['limited'] == 1) {
                $disabled++;
                break;
            }
        }
    }

    // Security key for comparison
    $_SESSION['form_key'] = md5(uniqid(mt_rand(), true));

    // Compare date of payment
    $expire = (!empty($payments[0]['expires'])) ? strtotime("{$payments[0]['expires']}") : time();

    ####### Edit #######
    if (!empty($_GET['id']) && !empty($payments[0]) && $admin_permissions) {

        for ($i = 0; $i < count($payments); ++$i) {
            if ($payments[$i]['id'] == $_GET['id']) {

                $payment = $payments[$i];
                break;
            }
        }

        if (isset($payment)) {

            $form =
"    <form action=\"user_payments_apply.php\" method=\"post\">
      <table>
        <tbody>
          <tr class=\"header_top\">
            <th  colspan=\"2\">
              <label>"._('payment').": ".$payment['id']."</label>
              <label class=\"info_right\">
                <a href=\"user_payments.php?userid={$userid}\">["._('back')."]</a>
              </label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('obligation')."</label>
            </td>
            <td class=\"dd\">
              <label>{$payment['date_payment1']}   ".chars($payment['operator1'])."</label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('payment')."</label>
            </td>
            <td class=\"dd\">
              <label>{$payment['date_payment2']}   ".chars($payment['operator2'])."</label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('expires')."</label>
            </td>
            <td class=\"dd\">
              <input id=\"expires_edit\" type=\"text\" name=\"expires\" value=\"{$payment['expires']}\" $readonly>\n";

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
              <input type=\"text\" name=\"sum\" value=\"{$payment['sum']}\" $readonly>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('notes')."</label>
            </td>
            <td class=\"dd\">
              <textarea name=\"notes\" rows=\"2\">".chars($payment['notes'])."</textarea>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._s('%s days', $LIMITED_INTERNET_ACCESS)."</label>
            </td>
            <td class=\"dd\">
              <label>{$payment['limited']}</label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('obligation')."</label>
            </td>
            <td class=\"dd\">
              <label>{$payment['unpaid']}</label>
            </td>
          </tr>\n";

            $form .= ($admin_permissions) ? "
          <tr>
            <td class=\"dt right\">
              <label><span class=\"red\">"._('delete')."</span></label>
            </td>
            <td class=\"dd\">
              <input class=\"checkbox\" type=\"checkbox\" name=\"del\">
            </td>
          </tr>\n" : '';

            $form .= "
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input class=\"button\" type=\"submit\" name=\"save\" value=\""._('save')."\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"hidden\" name=\"expire\" value=\"{$expire}\">
              <input type=\"hidden\" name=\"old\" value='".json_encode($user)."'>
              <input type=\"hidden\" name=\"old_p\" value='".json_encode($payment)."'>\n";

            $form .= ($admin_permissions) ? "
              <input class=\"button\" type=\"submit\" name=\"delete\" value=\""._('delete')."\">\n" : '';

            $form .= "
            </td>
          </tr>
        </tbody>
      </table>
    </form>";

        echo $form;
        }
    }
    else {
    ####### Show payments #######
    if (!empty($payments[0]['expires']) && strtotime("{$payments[0]['expires']}") > time()) {

        $time = strtotime(substr($payments[0]['expires'], 0, 10));
        $expires = date("Y-m-d", strtotime("+1 month", $time))." 23:59";
        $limited_access = date("Y-m-d", strtotime("+$LIMITED_INTERNET_ACCESS days", $time))." 23:59";
    }
    else {
        $expires = date("Y-m-d", strtotime("+1 month"))." 23:59";
        $limited_access = date("Y-m-d", strtotime("+$LIMITED_INTERNET_ACCESS days"))." 23:59";
    }

    $sum = ($user['pay'] != 0.00 && $user['pay'] != $services[$user['service']]) ? $user['pay'] : $services[$user['service']];


    $form =
"    <form name=\"payment\" action=\"user_payments_apply.php\" method=\"post\">
      <table>
        <tbody>
          <tr class=\"header_top\">
            <th>
              <label class=\"info_right\"><a href=\"user.php?userid={$userid}\">["._('back')."]</a></label>
            </th>
          </tr> \n";

    if ($disabled > 0) {
        $form .=
"          <tr class=\"header_top red\">
            <th>
              <label>"._('The user has obligation!')."</label>
            </th>
          </tr> \n";
    }
    elseif ($user['free_access'] == 'y') {

        $form .=
"          <tr class=\"header_top red\">
            <th>
              <label>"._('User is exempt from payment!')."</label>
            </th>
          </tr> \n";
    }
    else {
        $form .=
"          <tr class=\"header_top\">
            <th>
              ". _('sum') ." <input id=\"sum\" class=\"middle\" type=\"text\" name=\"sum\" value=\"{$sum}\" {$readonly}>
              ". _('expires')." <input id=\"expires\" style=\"width: 125px;\" type=\"text\" name=\"expires\" value=\"{$expires}\" {$readonly}> \n";

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

        $form .=
"              ". _('notes') ." <input id=\"notes\" type=\"text\" name=\"notes\">
                <input class=\"button\" type=\"submit\" name=\"limited_access\" value=\""._s('%s days', $LIMITED_INTERNET_ACCESS)."\">
                <input class=\"button\" type=\"submit\" name=\"obligation\" value=\""._('obligation')."\">
                <input class=\"button\" type=\"submit\" name=\"payment\" value=\""._('payment')."\">
                <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
                <input type=\"hidden\" name=\"limited\" value=\"{$limited_access}\">
                <input type=\"hidden\" name=\"expire\" value=\"{$expire}\">
                <input type=\"hidden\" name=\"old\" value='".json_encode($user)."'>
            </th>
          </tr> \n";
    }

    $form .=
"        </tbody>
      </table>
    </form>
    <form name=\"user_payments\" action=\"user_payments_apply.php\" method=\"post\">
      <table>
        <header>
          <tr class=\"header_top\">
            <th colspan=\"8\">
              <label style=\"float: left;\">". _('total').": ".count($payments) ."</label>
              <label>". _s('Payments of %s', chars($user['name'])) ."</label>
            </th>
          </tr>
          <tr class=\"header\">
            <th>"._('id')."</th>
            <th>"._('obligation')."</th>
            <th>"._('payment')."</th>
            <th>"._('expires')."</th>
            <th>"._('sum')."</th>
            <th>"._('notes')."</th>
            <th>"._s('%s days', $LIMITED_INTERNET_ACCESS)."</th>
            <th>"._('obligation')."</th>
          </tr>
        </thead>
        <tbody>\n";

    if (!empty($payments[0])) {

        $total_sum = null;

        for ($i = 0; $i < count($payments); ++$i) {

            $total_sum = $total_sum + $payments[$i]['sum'];
            $class = ($i % 2 == 0) ? "class=\"even_row" : "class=\"odd_row";
            $red = ($payments[$i]['unpaid'] == 1 || $payments[$i]['limited'] == 1) ? "red" : "";
            $id = ($admin_permissions) ? "<a href=\"user_payments.php?userid={$user['userid']}&id={$payments[$i]['id']}\">{$payments[$i]['id']}</a>" : "{$payments[$i]['id']}";

            $form .=
"          <tr {$class} {$red}\">
            <td>{$id}</td>
            <td>{$payments[$i]['date_payment1']} ".chars($payments[$i]['operator1'])."</td>
            <td>{$payments[$i]['date_payment2']} ".chars($payments[$i]['operator2'])."</td>
            <td>{$payments[$i]['expires']}</td>
            <td>{$payments[$i]['sum']}</td>
            <td>".chars($payments[$i]['notes'])."</td>\n";

            $form .= ($payments[$i]['limited'] == 1) ?
"            <td><a href=\"user_payments_apply.php?pay_limited=1&userid={$user['userid']}&id={$payments[$i]['id']}&expire={$expire}\">["._('payment')."]</a></td> \n" :
"            <td></td> \n";

            $form .= ($payments[$i]['unpaid'] == 1) ?
"            <td><a href=\"user_payments_apply.php?pay_unpaid=1&userid={$user['userid']}&id={$payments[$i]['id']}&expire={$expire}\">["._('payment')."]</a></td> \n" :
"            <td></td> \n";

                $form .=
"          </tr> \n";
        }

        $form .=
"          <tr class=\"header\" style=\"text-align: right;\">
            <th colspan=\"8\">
              <label>"._('total sum').": ".$total_sum."</label>
            </th>
          </tr> \n";
    }

    $form .=          
"          <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
        </tbody>
      </table>
    </form> \n";

echo $form;
    }
}

require_once dirname(__FILE__).'/include/page_footer.php';
?>