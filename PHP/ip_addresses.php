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

//Only System Admin have acces to Static IP Addresses
if (OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type']) {

    $db = new PDOinstance();
    $pool = (!empty($_GET['pool'])) ? $_GET['pool'] : '';
    $order_by = (!empty($_GET['order_by'])) ? $_GET['order_by'] : '';

    ####### PAGE HEADER #######
    $page['title'] = 'IP addresses';

    require_once dirname(__FILE__).'/include/page_header.php';

    ####### Display messages #######
    echo !empty($_SESSION['msg']) ? '<div id="msg" class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
    $_SESSION['msg'] = null;

    // Security key for comparison
    $_SESSION['form_key'] = md5(uniqid(mt_rand(), true));

    $sql = 'SELECT pool FROM ip GROUP BY pool';
    $sth = $db->dbh->prepare($sql);
    $sth->execute();
    $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

    if ($rows) {
        for ($i = 0; $i < count($rows); ++$i) {

            $row[$rows[$i]['pool']] = $rows[$i]['pool'];
        }
        $combobox1 = array('' => '') + $row;
    }
    else {
        $combobox1 = array('' => '');
    }

    $combobox2 = array(
        '' => '',
        'pool ASC' => _('pool')." "._('up'),
        'pool DESC' => _('pool')." "._('down'),
        'id ASC' => _('id')." "._('up'),
        'id DESC' => _('id')." "._('down')
        );

    $form =
"    <form action=\"{$_SERVER['PHP_SELF']}\" method=\"get\">
      <table>
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th>
              <label>"._('pool').combobox('middle', 'pool', $pool, $combobox1)."</label>
              <label>"._('order by').combobox('middle', 'order_by', $order_by, $combobox2)."</label>
              <input class=\"button\" type=\"submit\" name=\"show\" value=\""._('search')."\">
              <label class=\"info_right\"><a href=\"ip_addresses.php?new=1\">["._('new pool')."]</a></label>
            </th>
          </tr>
        </tbody>
      </table>
    </form>\n";

    echo $form;

    ####### New #######
    if (!empty($_GET['new'])) {

        $pool = !empty($_GET['pool']) ? $_GET['pool'] : '';
        $ipaddress_start = !empty($_GET['ipaddress_start']) ? $_GET['ipaddress_start'] : '';
        $ipaddress_end = !empty($_GET['ipaddress_end']) ? $_GET['ipaddress_end'] : '';

        $form =
"<script type=\"text/javascript\">
<!--
function validateForm() {

    if (document.getElementById(\"pool2\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('pool'))."\");
        document.getElementById(\"pool2\").focus();
        return false;
    }
    if (document.getElementById(\"ipaddress_start\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('start IP address'))."\");
        document.getElementById(\"ipaddress_start\").focus();
        return false;
    }
}
//-->
</script>
    <form action=\"ip_addresses_apply.php\" onsubmit=\"return validateForm();\" method=\"post\">
      <table>
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th colspan=\"2\">
              <label>"._('Add new IP address or IP address range')."</label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('pool')."</label>
            </td>
            <td class=\"dd\">
              <input id=\"pool2\" type=\"text\" name=\"pool\" value=\"{$pool}\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('start IP address')."</label>
            </td>
            <td class=\"dd\">
              <input id=\"ipaddress_start\" type=\"text\" name=\"ipaddress_start\" value=\"{$ipaddress_start}\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('end IP address')."</label>
            </td>
            <td class=\"dd\">
              <input type=\"text\" name=\"ipaddress_end\" value=\"{$ipaddress_end}\">
            </td>
          </tr>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input class=\"button\" type=\"submit\" name=\"new\" value=\""._('save')."\">
            </td>
          </tr>        
        </tbody>
      </table>
    </form>\n";

        echo $form;
    }
    else {

    ####### CTable #######
    if (!empty($_GET['show'])) {

        $table = new Table();
        $table->form_name = 'ip_addresses';
        $table->action = 'ip_addresses_apply.php';
        $table->colspan = 13;
        
        $table->info_field1 = _('total').": ";
        $table->info_field2 = _('IP addresses');
        $table->link_action = 'ip_edit.php';
        $table->link = TRUE;

        $items1 = array(
            '' => '',
            'delete' => _('delete selected'),
            'change_pool' => _('change pool')
            );

        $combobox_form_submit  = "<label class=\"info_right\">". _('action') .": \n".  combobox_onchange('', 'action', $items1, "confirm_delete('ip_addresses', this[this.selectedIndex].value, '". _('WARNING: All selected IP addresses will be deleted!') ."')") ."</label>";

        $table->info_field3 = $combobox_form_submit;
        $table->checkbox = true;
        $table->th_array = array(
            1 => _('id'),
            2 => _('userid'),
            3 => _('IP address'),
            4 => _('vlan'),
            5 => _('mac'),
            6 => _('free mac'),
            7 => _('username'),
            8 => _('password'),
            9 => _('pool'),
            10 => _('protocol'),
            11 => _('stopped')
            );

        $_pool = (!empty($pool)) ? 'WHERE pool = :pool' : '';
        $_order_by = (!empty($order_by)) ? " ORDER BY $order_by" : '';
        
        $sql = "SELECT id, userid, ip, vlan, mac, free_mac, username, pass, pool, protocol,stopped FROM ip $_pool$_order_by";
        $sth = $db->dbh->prepare($sql);

        if (!empty($_pool)) {
            $sth->bindValue(':pool', $pool, PDO::PARAM_STR);
        }

        $sth->execute();
        $table->td_array = $sth->fetchAll(PDO::FETCH_ASSOC);
        $table->form_key = $_SESSION['form_key'];

        echo $table->ctable();
    }
    }
    require_once dirname(__FILE__).'/include/page_footer.php';
}
else {
    header('Location: profile.php');
}
?>
