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

//System Admin have acces
if(OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type']) {

	$db = new PDOinstance();

	####### PAGE HEADER #######
	$page['title'] = 'Services';
	$page['file'] = 'services.php';

	require_once dirname(__FILE__).'/include/page_header.php';

	####### Display messages #######
	echo !empty($_SESSION['msg']) ? '<div id="msg" class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
	$_SESSION['msg'] = null;


    // Security key for comparison
    $_SESSION['form_key'] = md5(uniqid(mt_rand(), true));

    $sql = 'SELECT kind_trafficid, name FROM kind_traffic';
    $sth = $db->dbh->prepare($sql);
    $sth->execute();
    $kind_traffic = $sth->fetchAll(PDO::FETCH_ASSOC);

    ####### Edit #######
    if(!empty($kind_traffic) && !empty($_GET['edit'])) {

        $sql = 'SELECT * FROM services WHERE name = :name';
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(':name', $_GET['edit'], PDO::PARAM_STR);
        $sth->execute();
        $services = $sth->fetchAll(PDO::FETCH_ASSOC);

        if(!empty($services[0])) {

            foreach($kind_traffic as $value) {

                $values[$value['kind_trafficid']] = $value['name'];
            }
            $kind_traffic = $values;

            foreach($services as $value) {

                $values[$value['kind_trafficid']] = $value;
            }
            unset($services);
            $first_key = key($values);

            $js = "";
            $form =
"    <form name=\"new_service\" action=\"services_apply.php\" onsubmit=\"return validateForm();\" method=\"post\">
      <table class=\"tableinfo\">
        <tbody id=\"thead\">
          <tr class=\"header_top\">
            <th colspan=\"4\">
              <label>"._('service - price')."</label>
              <label class=\"info_right\"><a href=\"services.php\">[ "._('back')." ]</a></label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">"._('id')."</td>
            <td colspan=\"3\">{$values[$first_key]['serviceid']}</td>
          </tr>
          <tr>
            <td class=\"dt right\">"._('name')." *</td>
            <td colspan=\"3\">
              <input id=\"name\" class=\"input\" type=\"text\" name=\"name\" value=\"{$values[$first_key]['name']}\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">"._('price')."</td>
            <td colspan=\"3\">
              <input size=\"7\" class=\"input\" type=\"text\" name=\"price\" value=\"{$values[$first_key]['price']}\">
            </td>
          </tr>
          <tr>
            <td></td>
            <td class=\"bold\">"._('min')." </td>
            <td class=\"bold\">"._('max')." *</td>
            <td></td>
          </tr>\n";

            $i = 0;
            foreach ($values as $key => $services) {

                if(!empty($services['serviceid'])) {

                    $form .=
"          <tr>
            <td class=\"dt right\">"._('IN')." {$kind_traffic[$services['kind_trafficid']]} </td>
            <td class=\"dd2\">
              <input size=\"7\" class=\"input\" type=\"text\" name=\"update[$i][in_min]\" value=\"{$services['in_min']}\">
            </td>
            <td class=\"dd2\">
              <input id=\"{$i}_in_max\" size=\"7\" class=\"input\" type=\"text\" name=\"update[$i][in_max]\" value=\"{$services['in_max']}\">
            </td>
            <td></td>
          </tr>
          <tr>
            <td class=\"dt right\">"._('OUT')." {$kind_traffic[$services['kind_trafficid']]} </td>
            <td class=\"dd2\">
              <input size=\"7\" class=\"input\" type=\"text\" name=\"update[$i][out_min]\" value=\"{$services['out_min']}\">
            </td>
            <td class=\"dd2\">
              <input id=\"{$i}_out_max\" size=\"7\" class=\"input\" type=\"text\" name=\"update[$i][out_max]\" value=\"{$services['out_max']}\">
              <input type=\"hidden\" name=\"update[$i][serviceid]\" value=\"{$services['serviceid']}\">
            </td>
            <td></td>
          </tr>
          <tr>\n";

                $js .=
"    if (document.getElementById(\"{$i}_in_max\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('IN')." {$kind_traffic[$services['kind_trafficid']]}")."\");
        document.getElementById(\"{$i}_in_max\").focus();
        return false;
    }
    if (document.getElementById(\"{$i}_out_max\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('OUT')." {$kind_traffic[$services['kind_trafficid']]}")."\");
        document.getElementById(\"{$i}_out_max\").focus();
        return false;
    }\n";
                }
                else {

                    $form .=
"          <tr>
            <td class=\"dt right\">"._('IN')." {$services} </td>
            <td class=\"dd2\">
              <input size=\"7\" class=\"input\" type=\"text\" name=\"insert[$i][in_min]\" value=\"32kbit\">
            </td>
            <td class=\"dd2\">
              <input id=\"{$i}_in_max\" size=\"7\" class=\"input\" type=\"text\" name=\"insert[$i][in_max]\">
            </td>
            <td></td>
          </tr>
          <tr>
            <td class=\"dt right\">"._('OUT')." {$services} </td>
            <td class=\"dd2\">
              <input size=\"7\" class=\"input\" type=\"text\" name=\"insert[$i][out_min]\" value=\"32kbit\">
            </td>
            <td class=\"dd2\">
              <input id=\"{$i}_out_max\" size=\"7\" class=\"input\" type=\"text\" name=\"insert[$i][out_max]\">
              <input type=\"hidden\" name=\"insert[$i][kind_trafficid]\" value=\"{$key}\">
              <input type=\"hidden\" name=\"insert[$i][name]\" value=\"{$values[$first_key]['name']}\">
              <input type=\"hidden\" name=\"insert[$i][price]\" value=\"{$values[$first_key]['price']}\">
            </td>
            <td></td>
          </tr>
          <tr>\n";

                $js .=
"    if (document.getElementById(\"{$i}_in_max\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('IN')." {$services}")."\");
        document.getElementById(\"{$i}_in_max\").focus();
        return false;
    }
    if (document.getElementById(\"{$i}_out_max\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('OUT')." {$services}")."\");
        document.getElementById(\"{$i}_out_max\").focus();
        return false;
    }\n";
                }
            $i++;
        }

        $form .=
"          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
              <label style=\"color: red;\">"._('delete')."</label>
            </td>
            <td colspan=\"3\">
              <input class=\"input\" type=\"checkbox\" name=\"del\">
            </td>
          </tr>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td colspan=\"3\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"hidden\" name=\"old\" value='".json_encode($values)."'>
              <input type=\"submit\" name=\"edit\" id=\"save\" value=\""._('save')."\">
              <input type=\"submit\" name=\"delete\" value=\""._('delete')."\">
            </td>
          </tr>
        </tbody>
      </table>
    </form>
<script type=\"text/javascript\">
<!--
function validateForm() {

    if (document.getElementById(\"name\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('name'))."\");
        document.getElementById(\"name\").focus();
        return false;
    }
{$js}
    return true;
}
//-->
</script>\n";

        echo $form;
        }
    }

    ####### New #######
	if(!empty($kind_traffic) && !empty($_GET['new'])) {

		$js = "";
		$form =
"    <form name=\"new_service\" action=\"services_apply.php\" onsubmit=\"return(validateForm());\" method=\"post\">
      <table class=\"tableinfo\">
        <tbody id=\"thead\">
          <tr class=\"header_top\">
            <th colspan=\"4\">
              <label>"._('service - price')."</label>
              <label class=\"info_right\"><a href=\"services.php\">[ "._('back')." ]</a></label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">"._('id')."</td>
            <td colspan=\"3\"></td>
          </tr>
          <tr>
            <td class=\"dt right\">"._('name')." *</td>
            <td colspan=\"3\">
              <input id=\"name\" class=\"input\" type=\"text\" name=\"name\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">"._('price')."</td>
            <td colspan=\"3\">
              <input size=\"7\" class=\"input\" type=\"text\" name=\"price\">
            </td>
          </tr>
          <tr>
            <td></td>
            <td class=\"bold\">"._('min')." </td>
            <td class=\"bold\">"._('max')." *</td>
            <td></td>
          </tr>\n";

    for ($i = 0; $i < count($kind_traffic); ++$i) {

        $form .=
"          <tr>
            <td class=\"dt right\">"._('IN')." {$kind_traffic[$i]['name']} </td>
            <td class=\"dd2\">
              <input size=\"7\" class=\"input\" type=\"text\" name=\"kind_traffic[$i][in_min]\" value=\"32kbit\">
            </td>
            <td class=\"dd2\">
              <input id=\"{$i}_in_max\" size=\"7\" class=\"input\" type=\"text\" name=\"kind_traffic[$i][in_max]\">
            </td>
            <td></td>
          </tr>
          <tr>
            <td class=\"dt right\">"._('OUT')." {$kind_traffic[$i]['name']} </td>
            <td class=\"dd2\">
              <input size=\"7\" class=\"input\" type=\"text\" name=\"kind_traffic[$i][out_min]\" value=\"32kbit\">
            </td>
            <td class=\"dd2\">
              <input id=\"{$i}_out_max\" size=\"7\" class=\"input\" type=\"text\" name=\"kind_traffic[$i][out_max]\">
              <input type=\"hidden\" name=\"kind_traffic[$i][kind_trafficid]\" value=\"{$kind_traffic[$i]['kind_trafficid']}\">
            </td>
            <td></td>
          </tr>
          <tr>\n";

        $js .=
"    if (document.getElementById(\"{$i}_in_max\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('IN')." {$kind_traffic[$i]['name']}")."\");
        document.getElementById(\"{$i}_in_max\").focus();
        return false;
    }
    if (document.getElementById(\"{$i}_out_max\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('OUT')." {$kind_traffic[$i]['name']}")."\");
        document.getElementById(\"{$i}_out_max\").focus();
        return false;
    }\n";
    }
        $form .=
"          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td colspan=\"3\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"submit\" name=\"new\" id=\"save\" value=\""._('save')."\">
            </td>
          </tr>
        </tbody>
      </table>
    </form>
<script type=\"text/javascript\">
<!--
function validateForm() {

    if (document.getElementById(\"name\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('name'))."\");
        document.getElementById(\"name\").focus();
        return false;
    }
{$js}
    return true;
}
//-->
</script>\n";

        echo $form;
    }

    ####### List #######
    if(!empty($kind_traffic) && empty($_GET['edit']) && empty($_GET['new']) ) {

        $sql = 'SELECT * FROM services';
        $sth = $db->dbh->prepare($sql);
        $sth->execute();
        $services = $sth->fetchAll(PDO::FETCH_ASSOC);

        foreach($kind_traffic as $value) {

            $values[$value['kind_trafficid']] = $value['name'];
        }
        $kind_traffic = $values;
        unset($values);

        $form =
"      <table class=\"tableinfo\">
          <tr class=\"header_top\">
            <th colspan=\"9\">
              <label class=\"info_right\"><a href=\"services.php?new=1\">[ "._('new service')." ]</a></label>
            </th>
          </tr>
          <tr class=\"header_top\">
            <th rowspan=\"3\">"._('id')."</th>
            <th rowspan=\"3\">"._('name')."</th>
            <th colspan=\"5\">"._('speeds')."</th>
            <th rowspan=\"2\">"._('data')."</th>
            <th rowspan=\"3\">"._('action')."</th>
          </tr>
          <tr class=\"header_top\">
            <th rowspan=\"2\">"._('kind')."</th>
            <th colspan=\"2\">"._('IN')."</th>
            <th colspan=\"2\">"._('OUT')."</th>
          </tr>
          <tr class=\"bold odd_row\">
            <th>"._('min')."</th>
            <th>"._('max')."</th>
            <th>"._('min')."</th>
            <th>"._('max')."</th>
            <th>"._('price')."</th>
          </tr>\n";

        foreach($services as $value) {

            $values[$value['name']][$value['kind_trafficid']] = $value;
        }
        unset($services);

        foreach($values as $value) {
            $count_value = count($value);
            $i = 1;
            foreach($value as $services) {
                $form .=
"          <tr>
            <td>{$services['serviceid']}</td>\n";

                $form .= ($i == 1) ? "            <td rowspan=\"{$count_value}\"><a href=\"services.php?edit={$services['name']}\">{$services['name']}</a></td>\n" : "";
                $form .=
"           <td>{$kind_traffic[$services['kind_trafficid']]}</td>
            <td>{$services['in_min']}</td>
            <td>{$services['in_max']}</td>
            <td>{$services['out_min']}</td>
            <td>{$services['out_max']}</td>\n";

                $form .= ($i == '1') ? "            <td rowspan=\"{$count_value}\">{$services['price']}</td>\n" : "";
                $form .= ($i == '1') ? "            <td rowspan=\"{$count_value}\"><a href=\"services.php?edit={$services['name']}\">[ "._('edit')." ]</a></td>\n" : "";
                $form .=
"          </tr>\n";
                $i++;
            }
        }

        $form .=
"      </table>\n";

        echo $form;
    }

    require_once dirname(__FILE__).'/include/page_footer.php';
}
else {
	header('Location: profile.php');
}
?>
