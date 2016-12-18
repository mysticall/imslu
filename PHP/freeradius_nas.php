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

 //Only System Admin have acces to NAS
if(OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type']) {

    $db = new PDOinstance();

    $nas_type = array(
    	'cisco' => 'cisco',
    	'computone' => 'computone',
    	'livingston' => 'livingston',
    	'max40xx' => 'max40xx',
    	'multitech' => 'multitech',
    	'netserver' => 'netserver',
    	'pathras' => 'pathras',
    	'patton' => 'patton',
    	'portslave' => 'portslave',
    	'tc' => 'tc',
    	'usrhiper' => 'usrhiper',
    	'other' => 'other'
    	);


    ####### PAGE HEADER #######
    $page['title'] = 'FreeRadius NAS';

    require_once dirname(__FILE__).'/include/page_header.php';

    ####### Display messages #######
    echo !empty($_SESSION['msg']) ? '<div id="msg" class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
    $_SESSION['msg'] = null;

    // Security key for comparison
    $_SESSION['form_key'] = md5(uniqid(mt_rand(), true));


    ####### New #######
    if(!empty($_GET['new'])) {

        $form =
"<script type=\"text/javascript\">
<!--
function validateForm() {

    if (document.getElementById(\"nasname\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('NAS name'))."\");
        document.getElementById(\"nasname\").focus();
        return false;
    }
    if (document.getElementById(\"shortname\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('NAS short name'))."\");
        document.getElementById(\"shortname\").focus();
        return false;
    }
}
//-->
</script>
    <form action=\"freeradius_nas_apply.php\" onsubmit=\"return validateForm();\" method=\"post\">
      <table>
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th  colspan=\"2\">
              <label>"._('New NAS').": </label>
              <label class=\"info_right\"><a href=\"freeradius_nas.php\">["._('back')."]</a></label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('NAS name')." * </label>
            </td>
            <td class=\"dd\">
              <input id=\"nasname\" type=\"text\" name=\"nasname\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('NAS short name')." * </label>
            </td>
            <td class=\"dd\">
              <input id=\"shortname\" type=\"text\" name=\"shortname\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('NAS type')."</label>
            </td>
            <td class=\"dd\">
".combobox('', 'type', null, $nas_type)."
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('NAS ports')."</label>
            </td>
            <td class=\"dd\">
              <input type=\"text\" name=\"ports\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('NAS secret')."</label>
            </td>
            <td class=\"dd\">
              <input type=\"text\" name=\"secret\" value=\"secret\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('NAS server')."</label>
            </td>
            <td class=\"dd\">
              <input type=\"text\" name=\"server\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('NAS SNMP community')."</label>
            </td>
            <td class=\"dd\">
              <input type=\"text\" name=\"community\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('NAS description')."</label>
            </td>
            <td class=\"dd\">
              <input type=\"text\" name=\"description\" value=\"RADIUS Client\">
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
    </form>\n";

        echo $form;
    }
    ####### Edit #######
    elseif (!empty($_GET['id'])) {

        settype($_GET['id'], "integer");
        if($_GET['id'] == 0) {
            header("Location: freeradius_nas.php");
            exit;
        }

        $sql = 'SELECT id,nasname,shortname,type,ports,secret,server,community,description FROM nas WHERE id = ? LIMIT 1';
        $sth = $db->dbh->prepare($sql);
        $sth->bindParam(1, $_GET['id'], PDO::PARAM_INT);
        $sth->execute();
        $get_nas = $sth->fetch(PDO::FETCH_ASSOC);

        $form =
"<script type=\"text/javascript\">
<!--
function validateForm() {

    if (document.getElementById(\"nasname\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('NAS name'))."\");
        document.getElementById(\"nasname\").focus();
        return false;
    }
    if (document.getElementById(\"shortname\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('NAS short name'))."\");
        document.getElementById(\"shortname\").focus();
        return false;
    }
}
//-->
</script>
    <form action=\"freeradius_nas_apply.php\" method=\"post\">
      <table>
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th  colspan=\"2\">
              <label>"._('NAS').": ".chars($get_nas['nasname'])."</label>
              <label class=\"info_right\"><a href=\"freeradius_nas.php\">["._('back')."]</a></label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('NAS name')." *</label>
            </td>
            <td class=\"dd\">
              <input id=\"nasname\" name=\"nasname\" class=\"input\" type=\"text\" value=\"".chars($get_nas['nasname'])."\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('NAS short name')." *</label>
            </td>
            <td class=\"dd\">
              <input id=\"shortname\" name=\"shortname\" class=\"input\" type=\"text\" value=\"".chars($get_nas['shortname'])."\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('NAS type')."</label>
            </td>
            <td class=\"dd\">
".combobox('', 'type', $get_nas['type'], $nas_type)."
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('NAS ports')."</label>
            </td>
            <td class=\"dd\">
              <input type=\"text\" name=\"ports\" value=\"".chars($get_nas['ports'])."\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('NAS secret')."</label>
            </td>
            <td class=\"dd\">
              <input type=\"text\" name=\"secret\" value=\"".chars($get_nas['secret'])."\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('NAS server')."</label>
            </td>
            <td class=\"dd\">
              <input type=\"text\" name=\"server\" value=\"".chars($get_nas['server'])."\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('NAS SNMP community')."</label>
            </td>
            <td class=\"dd\">
              <input type=\"text\" name=\"community\" value=\"".chars($get_nas['community'])."\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('NAS description')."</label>
            </td>
            <td class=\"dd\">
              <input type=\"text\" name=\"description\" value=\"".chars($get_nas['description'])."\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label class=\"red\">"._('delete')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"checkbox\" type=\"checkbox\" name=\"del\">
            </td>
          </tr>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"hidden\" name=\"nas\" value='".serialize($get_nas)."'>
              <input class=\"button\" type=\"submit\" name=\"edit\" id=\"save\" value=\""._('save')."\">
              <input class=\"button\" type=\"submit\" name=\"delete\" value=\""._('delete')."\">
            </td>
          </tr>
        </tbody>
      </table>
    </form>\n";

        echo $form;
    }
    else {

    ####### Set Table variable #######
    $table = new Table();
	$table->colspan = 9;
    $table->action = 'freeradius_nas.php';
	$table->info_field1 = _('total').": ";
	$table->info_field2 = _('FreeRadius NAS');
	$table->info_field3 = "<label class=\"info_right\"><a href=\"freeradius_nas.php?new=1\">["._('new NAS')."]</a></label>";
    $table->link_action = 'freeradius_nas.php';
	$table->link = TRUE;
	$table->th_array = array(
		1 => _('id'),
		2 => _('nas name'),
		3 => _('nas short name'),
		4 => _('nas type'),
		5 => _('nas ports'),
		6 => _('nas secret'),
		7 => _('nas server'),
		8 => _('nas SNMP community'),
		9 => _('nas description')
		);

	$table->th_array_style = 'style="table-layout: fixed; width: 3%"';

	$sql = 'SELECT id,nasname,shortname,type,ports,secret,server,community,description FROM nas';
	$sth = $db->dbh->prepare($sql);
	$sth->execute();
	$table->td_array = $sth->fetchAll(PDO::FETCH_ASSOC);
	echo $table->ctable();

    }
    require_once dirname(__FILE__).'/include/page_footer.php';
}
else {
	header('Location: profile.php');
}
?>
