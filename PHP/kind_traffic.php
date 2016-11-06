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

//System Admin have acces to location
if(OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type']) {

    $db = new PDOinstance();

    ####### PAGE HEADER #######
    $page['title'] = 'Kind Traffic';

    require_once dirname(__FILE__).'/include/page_header.php';


    ####### Display messages ####### 
    echo !empty($_SESSION['msg']) ? '<div id="msg" class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
    $_SESSION['msg'] = null;


    ####### Edit #######
    // Security key for comparison
    $_SESSION['form_key'] = md5(uniqid(mt_rand(), true));

    $sql = 'SELECT * FROM kind_traffic';
    $sth = $db->dbh->prepare($sql);
    $sth->execute();
    $kind_traffic = $sth->fetchAll(PDO::FETCH_ASSOC);

    if(!empty($kind_traffic) && empty($_GET['new'])) {

        $js = "";
        $form =
"    <form name=\"kind_traffic\" action=\"kind_traffic_apply.php\" onsubmit=\"return validateForm();\" method=\"post\">
      <table>
        <tbody id=\"thead\">
          <tr class=\"header_top\">
            <th colspan=\"2\">
              <label>"._('kinds of traffic')."</label>
              <label class=\"info_right\"><a href=\"kind_traffic.php?new=1\">["._('new traffic')."]</a></label>
            </th>
          </tr>\n";

    for ($i = 0; $i < count($kind_traffic); ++$i) {

        $form .=
"          <tr class=\"odd_row\">
            <td class=\"dt right\">
              <label>"._('id')."</label>
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"kind_traffic[$i][id]\" value=\"{$kind_traffic[$i]['id']}\">
              <label style=\"font-weight: bold;\"> {$kind_traffic[$i]['id']} </label>
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('name')." *</label>
            </td>
            <td class=\"dd\">
              <input id=\"{$i}_name\" class=\"input\" type=\"text\" name=\"kind_traffic[$i][name]\" value=\"{$kind_traffic[$i]['name']}\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('notes')."</label>
            </td>
            <td class=\"dd\">
              <textarea name=\"kind_traffic[$i][notes]\" cols=\"55\" rows=\"2\">".chars($kind_traffic[$i]['notes'])."</textarea>
            </td>
          </tr>\n";

    // The first type of traffic can not be deleted.
    if ($i != 0) {
      $form .=
"          <tr>
            <td class=\"dt right\">
              <label style=\"color: red;\">"._('delete')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"checkbox\" type=\"checkbox\" name=\"kind_traffic[$i][del]\">
            </td>
          </tr>\n";
    }
        $js .=
"    if (document.getElementById(\"{$i}_name\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('name'))."\");
        document.getElementById(\"{$i}_name\").focus();
        return false;
    }\n";
    }

        $form .=
"          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"hidden\" name=\"old\" value='".json_encode($kind_traffic)."'>
              <input id=\"save\" class=\"button\" type=\"submit\" name=\"edit\" value=\""._('save')."\">
              <input class=\"button\" type=\"submit\" name=\"delete\" value=\""._('delete')."\">
            </td>
          </tr>
        </tbody>
      </table>
    </form>
<script type=\"text/javascript\">
<!--
function validateForm() {

{$js}
    return true;
}
//-->
</script>\n";

        echo $form;
}

    ####### New ####### 
    if(!empty($_GET['new'])) {

        $form =
"    <form name=\"kind_traffic\" action=\"kind_traffic_apply.php\" onsubmit=\"return(validateForm());\" method=\"post\">
      <table>
        <tbody id=\"thead\">
          <tr class=\"header_top\">
            <th colspan=\"2\">
              <label>"._('kinds of traffic')."</label>
              <label class=\"info_right\"><a href=\"kind_traffic.php\">["._('back')."]</a></label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('id')."</label>
            </td>
            <td class=\"dd\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('name')." *</label>
            </td>
            <td class=\"dd\">
              <input id=\"name\" class=\"input\" type=\"text\" name=\"name\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('notes')."</label>
            </td>
            <td class=\"dd\">
              <textarea name=\"notes\" cols=\"55\" rows=\"2\"></textarea>
            </td>
          </tr>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input id=\"save\" class=\"button\" type=\"submit\" name=\"new\" value=\""._('save')."\">
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
    return true;
}
//-->
</script>\n";

        echo $form;
}

    require_once dirname(__FILE__).'/include/page_footer.php';
}
else {
    header('Location: profile.php');
}
?>