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

	require_once dirname(__FILE__).'/include/page_header.php';

	####### Display messages #######
	echo !empty($_SESSION['msg']) ? '<div id="msg" class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
	$_SESSION['msg'] = null;


    // Security key for comparison
    $_SESSION['form_key'] = md5(uniqid(mt_rand(), true));

    $sql = 'SELECT name FROM kind_traffic';
    $sth = $db->dbh->prepare($sql);
    $sth->execute();
    $kind_traffic = $sth->fetchAll(PDO::FETCH_ASSOC);

    ####### Edit #######
  if(!empty($_GET['edit'])) {

    $sql = 'SELECT * FROM services WHERE serviceid = :serviceid';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':serviceid', $_GET['edit'], PDO::PARAM_STR);
    $sth->execute();
    $services = $sth->fetch(PDO::FETCH_ASSOC);

    $js = "";
    $form =
"    <form name=\"new_service\" action=\"services_apply.php\" onsubmit=\"return validateForm();\" method=\"post\">
      <table>
        <tbody id=\"thead\">
          <tr class=\"header_top\">
            <th colspan=\"4\">
              <label>"._('service - price')."</label>
              <label class=\"info_right\"><a href=\"services.php\">["._('back')."]</a></label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">"._('id')."</td>
            <td colspan=\"3\">{$services['serviceid']}</td>
          </tr>
          <tr>
            <td class=\"dt right\">"._('name')." *</td>
            <td colspan=\"3\">
              <input id=\"name\" type=\"text\" name=\"update[name]\" value=\"{$services['name']}\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">"._('price')."</td>
            <td colspan=\"3\">
              <input type=\"text\" name=\"update[price]\" value=\"{$services['price']}\">
            </td>
          </tr>
          <tr class=\"bold\">
            <td></td>
            <td>"._('min')." </td>
            <td>"._('max')." *</td>
            <td></td>
          </tr>\n";

    $int = 0;
    foreach ($kind_traffic as $value) {

      $in_min = "in_min{$int}";
      $in_max = "in_max{$int}";
      $in_max2 = ($services[$in_max] != 'NULL') ? $services[$in_max] : '';
      $out_min = "out_min{$int}";
      $out_max = "out_max{$int}";
      $out_max2 = ($services[$out_max] != 'NULL') ? $services[$out_max] : '';
      $form .=
"          <tr>
            <td class=\"dt right\">"._('IN')." {$value['name']} </td>
            <td class=\"dd2\">
              <input class=\"middle\" type=\"text\" name=\"update[{$in_min}]\" value=\"{$services[$in_min]}\">
            </td>
            <td class=\"dd2\">
              <input id=\"$in_max\" class=\"middle\" type=\"text\" name=\"update[{$in_max}]\" value=\"{$in_max2}\">
            </td>
            <td></td>
          </tr>
          <tr>
            <td class=\"dt right\">"._('OUT')." {$value['name']} </td>
            <td class=\"dd2\">
              <input class=\"middle\" type=\"text\" name=\"update[{$out_min}]\" value=\"{$services[$out_min]}\">
            </td>
            <td class=\"dd2\">
              <input id=\"$out_max\" class=\"middle\" type=\"text\" name=\"update[{$out_max}]\" value=\"{$out_max2}\">
            </td>
            <td></td>
          </tr>
          <tr>\n";

      $js .=
"    if (document.getElementById(\"{$in_max}\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('IN')." {$value['name']}")."\");
        document.getElementById(\"{$in_max}\").focus();
        return false;
    }
    if (document.getElementById(\"{$out_max}\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('OUT')." {$value['name']}")."\");
        document.getElementById(\"{$out_max}\").focus();
        return false;
    }\n";

      $int++;
    }

    $form .=
"          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
              <label style=\"color: red;\">"._('delete')."</label>
            </td>
            <td colspan=\"3\">
              <input class=\"checkbox\" type=\"checkbox\" name=\"del\">
            </td>
          </tr>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td colspan=\"3\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"hidden\" name=\"old\" value='".json_encode($services)."'>
              <input  id=\"save\" class=\"button\" type=\"submit\" name=\"edit\"value=\""._('save')."\">
              <input class=\"button\" type=\"submit\" name=\"delete\" value=\""._('delete')."\">
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

  ####### New #######
  else if(!empty($_GET['new'])) {

    $js = "";
    $form =
"    <form name=\"new_service\" action=\"services_apply.php\" onsubmit=\"return validateForm();\" method=\"post\">
      <table class=\"tableinfo\">
        <tbody id=\"thead\">
          <tr class=\"header_top\">
            <th colspan=\"4\">
              <label>"._('service - price')."</label>
              <label class=\"info_right\"><a href=\"services.php\">["._('back')."]</a></label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">"._('id')."</td>
            <td colspan=\"3\"></td>
          </tr>
          <tr>
            <td class=\"dt right\">"._('name')." *</td>
            <td colspan=\"3\">
              <input id=\"name\" type=\"text\" name=\"insert[name]\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">"._('price')."</td>
            <td colspan=\"3\">
              <input type=\"text\" name=\"insert[price]\">
            </td>
          </tr>
          <tr>
            <td></td>
            <td class=\"bold\">"._('min')." </td>
            <td class=\"bold\">"._('max')." *</td>
            <td></td>
          </tr>\n";

    $int = 0;
    foreach ($kind_traffic as $value) {

      $in_min = "in_min{$int}";
      $in_max = "in_max{$int}";
      $out_min = "out_min{$int}";
      $out_max = "out_max{$int}";
      $form .=
"          <tr>
            <td class=\"dt right\">"._('IN')." {$value['name']} </td>
            <td class=\"dd2\">
              <input class=\"middle\" type=\"text\" name=\"insert[{$in_min}]\" value=\"32kbit\">
            </td>
            <td class=\"dd2\">
              <input id=\"$in_max\" class=\"middle\" type=\"text\" name=\"insert[{$in_max}]\">
            </td>
            <td></td>
          </tr>
          <tr>
            <td class=\"dt right\">"._('OUT')." {$value['name']} </td>
            <td class=\"dd2\">
              <input class=\"middle\" type=\"text\" name=\"insert[{$out_min}]\" value=\"32kbit\">
            </td>
            <td class=\"dd2\">
              <input id=\"$out_max\" class=\"middle\" type=\"text\" name=\"insert[{$out_max}]\">
            </td>
            <td></td>
          </tr>
          <tr>\n";

      $js .=
"    if (document.getElementById(\"{$in_max}\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('IN')." {$value['name']}")."\");
        document.getElementById(\"{$in_max}\").focus();
        return false;
    }
    if (document.getElementById(\"{$out_max}\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('OUT')." {$value['name']}")."\");
        document.getElementById(\"{$out_max}\").focus();
        return false;
    }\n";

      $int++;
    }
    $form .=
"          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td colspan=\"3\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input  id=\"save\" class=\"button\" type=\"submit\" name=\"new\" value=\""._('save')."\">
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
  else {

    $sql = 'SELECT * FROM services';
    $sth = $db->dbh->prepare($sql);
    $sth->execute();
    $services = $sth->fetchAll(PDO::FETCH_ASSOC);

    $form =
"      <table class=\"tableinfo\">
          <tr class=\"header_top\">
            <th colspan=\"9\">
              <label class=\"info_right\"><a href=\"services.php?new=1\">["._('new service')."]</a></label>
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

    $count_value = count($kind_traffic);
    for ($i=0; $i < count($services); ++$i) {

      $int=0;
      foreach($kind_traffic as $value) {

        $in_min = "in_min{$int}";
        $in_max = "in_max{$int}";
        $out_min = "out_min{$int}";
        $out_max = "out_max{$int}";
        $form .= "          <tr>\n";
        $form .= ($int == 0) ? "            <td rowspan=\"{$count_value}\">{$services[$i]['serviceid']}</td>\n" : "";
        $form .= ($int == 0) ? "            <td rowspan=\"{$count_value}\"><a class=\"bold\" href=\"services.php?edit={$services[$i]['serviceid']}\">{$services[$i]['name']}</a></td>\n" : "";
        $form .=
"           <td>{$value['name']}</td>
            <td>{$services[$i][$in_min]}</td>
            <td>{$services[$i][$in_max]}</td>
            <td>{$services[$i][$out_min]}</td>
            <td>{$services[$i][$out_max]}</td>\n";

        $form .= ($int == 0) ? "            <td rowspan=\"{$count_value}\">{$services[$i]['price']}</td>\n" : "";
        $form .= ($int == 0) ? "            <td rowspan=\"{$count_value}\"><a class=\"bold\" href=\"services.php?edit={$services[$i]['serviceid']}\">["._('edit')."]</a></td>\n" : "";
        $form .=
"          </tr>\n";
        $int++;
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
