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

if((OPERATOR_TYPE_LINUX_ADMIN == CWebOperator::$data['type']) || (OPERATOR_TYPE_ADMIN == CWebOperator::$data['type'])) {

	$db = new CPDOinstance();
	$coperators = new COperator();
	$ctable = new CTable();
	$sysadmin_rights = (OPERATOR_TYPE_LINUX_ADMIN == CWebOperator::$data['type']);
	$admin_rights = (OPERATOR_TYPE_ADMIN == CWebOperator::$data['type']);

	if(!$sysadmin_rights) {

		$OPERATOR_GROUPS = array(
				1 => _('Cashiers'),
				2 => _('Network technicians')
				);
	}


###################################################################################################
	// PAGE HEADER
###################################################################################################

	$page['title'] = 'Operators';
	$page['file'] = 'operators.php';

	require_once dirname(__FILE__).'/include/page_header.php';


#####################################################
	// Display messages
#####################################################
	echo !empty($_SESSION['msg']) ? '<div class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
	$_SESSION['msg'] = null;

	// Security key for comparison
	$_SESSION['form_key_old'] = $_SESSION['form_key'];
	$_SESSION['form_key'] = md5(uniqid(mt_rand(), true));

###################################################################################################
	// Edit Operator
###################################################################################################

	if (empty($_POST['action']) && !empty($_POST['operid']) && $_SESSION['form_key_old'] === $_POST['form_key']) {

		$operid = $_POST['operid'];
		$get_operator = $coperators->get($db, $operid);

	$form =
"    <form action=\"operators_apply.php\" method=\"post\">
      <table class=\"tableinfo\">
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th  colspan=\"2\">
              <label>"._('Profile').": ".chars($get_operator['name'])."</label>
            </th>
          </tr>\n";

	//Only System Admin can change alias
	$form .= ($sysadmin_rights) ?
"          <tr>
            <td class=\"dt right\">
              <label>"._('Alias')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"alias\" value=\"".chars($get_operator['alias'])."\"> 
            </td>
          </tr>\n" : '';

	$form .=
"          <tr>
            <td class=\"dt right\">
              <label>"._('Name')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"name\" value=\"".chars($get_operator['name'])."\" >
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Password')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"password\" name=\"password1\" id=\"password1\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Password (once again)')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"password\" name=\"password2\" id=\"password2\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Language')."</label>
            </td>
            <td class=\"dd\">
              <select class=\"input select\" name=\"lang\">\n";

	// append languages to form list
	$locales = $LOCALES;
	$languages_unable_set = 0;


	// Search for operator languarge
	foreach ($locales as $key => $value) {
			
		if ($key == $get_operator['lang']) {

			$found[$get_operator['lang']] = $value;

			unset($locales[$key]);
			$locales_new = $found + $locales;
		}	
	}

	foreach ($locales_new as $loc_id => $loc_name) {
		// checking if this locale exists in the system. The only way of doing it is to try and set one
		// trying to set only the LC_MONETARY locale to avoid changing LC_NUMERIC
		$locale_exists = setlocale(LC_MONETARY , locale_unix($loc_id)) || $loc_id == 'en_US' ? 'yes' : 'no';

		if ($locale_exists != 'yes') {

		$form .=
"              <option value=\"$loc_id\" disabled>$loc_name</option>\n";
		$languages_unable_set++;
		}
		else {
			$form .=
"              <option value=\"$loc_id\">$loc_name</option>\n";
		}
	}

	$form .= 
"              </select>";

	$form .= ($languages_unable_set > 0) ? "&nbsp; <span class=\"red\">"._('Some of locales for the languages are not installed on the web server.')."</span>\n" : "\n";

	// restoring original locale
	setlocale(LC_MONETARY, locale_unix($get_operator['lang']));

	$form .=
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Theme')."</label>
            </td>
            <td class=\"dd\">
".combobox('input select', 'theme', $get_operator['theme'], $THEMES)."\n
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('URL (after login)')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"url\" size=\"50\" maxlength=\"255\" value=\"".chars($get_operator['url'])."\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Group')."</label>
            </td>
            <td class=\"dd\">
".combobox('input select', 'type', $get_operator['type'], $OPERATOR_GROUPS)."\n
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label class=\"red\">"._('delete')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"checkbox\" name=\"del\">
            </td>
          </tr>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"hidden\" name=\"operid\" value=\"{$get_operator['operid']}\">
              <input type=\"hidden\" name=\"alias_old\" value=\"".chars($get_operator['alias'])."\">
              <input type=\"submit\" name=\"save\" id=\"save\" value=\""._('save')."\" onclick=\"formhash(this.form, this.form.password1, 'p1'); formhash(this.form, this.form.password2, 'p2');\">
              <input type=\"submit\" name=\"delete\" id=\"delete\" value=\""._('delete')."\">
            </td>
          </tr>
        </tbody>
      </table>
    </form>\n";
	
	echo $form;
	}

###################################################################################################
// Add new Operator
###################################################################################################

	if(isset($_POST['action']) && $_POST['action'] == 'addoperator') {

		$form =
"    <form action=\"operators_apply.php\" method=\"post\">
      <table class=\"tableinfo\">
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th  colspan=\"2\">
              <label>"._('New operator')."</label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Alias')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"alias\">";
		$form .= (isset($_POST['msg_alias'])) ? "&nbsp;<span class=\"red\">{$_POST['msg_alias']}</span>\n" : "\n";

		$form .=
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Name')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"name\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Password')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"password\" name=\"password1\" id=\"password1\">";
		$form .= (isset($_POST['msg_password'])) ? "&nbsp;<span class=\"red\">{$_POST['msg_password']}</span>\n" : "\n";

		$form .=
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Password (once again)')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"password\" name=\"password2\" id=\"password2\">";
		$form .= (isset($_POST['msg_password'])) ? "&nbsp;<span class=\"red\">{$_POST['msg_password']}</span>\n" : "\n";

		$form .=
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Language')."</label>
            </td>
            <td class=\"dd\">
              <select class=\"input select\" name=\"lang\">\n";

		// append languages to form list
		$locales = $LOCALES;
		$languages_unable_set = 0;


		$default_lang['en_US'] = $locales['en_US'];
		unset($locales['en_US']);
		$locales_new = $default_lang + $locales;

		foreach ($locales_new as $loc_id => $loc_name) {
			// checking if this locale exists in the system. The only way of doing it is to try and set one
			// trying to set only the LC_MONETARY locale to avoid changing LC_NUMERIC
			$locale_exists = setlocale(LC_MONETARY , locale_unix($loc_id)) || $loc_id == 'en_US' ? 'yes' : 'no';

			if ($locale_exists != 'yes') {

				$form .=
"              <option value=\"$loc_id\" disabled>$loc_name</option>\n";
				$languages_unable_set++;
			}
			else {
				$form .=
"              <option value=\"$loc_id\">$loc_name</option>\n";
			}
		}

		$form .= 
"              </select>";

		$form .= ($languages_unable_set > 0) ? "&nbsp; <span class=\"red\">". _('Some of locales for the languages are not installed on the web server.') ."</span>\n" : "\n";

		// restoring original locale
		setlocale(LC_MONETARY, locale_unix('en_US'));

		$form .=
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Theme')."</label>
            </td>
            <td class=\"dd\">
".combobox('input select', 'theme', null, $THEMES)."\n
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('URL (after login)')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"url\" size=\"50\" maxlength=\"255\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Group')."</label>
            </td>
            <td class=\"dd\">
".combobox('input select', 'type', null, $OPERATOR_GROUPS)."\n
            </td>
          </tr>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"submit\" name=\"savenew\" id=\"savenew\" value=\""._('save')."\" onclick=\"formhash(this.form, this.form.password1, 'p1'); formhash(this.form, this.form.password2, 'p2');\">
            </td>
          </tr>
        </tbody>
      </table>
    </form>\n";

	echo $form;
	}


###################################################################################################
// Set CTable variable and create dynamic html table
###################################################################################################

	// Set CTable variable
	$ctable->form_name = 'operators';
	$ctable->table_name = 'operators';
	$ctable->colspan = 5;
	$ctable->info_field1 = _('total').": ";
	$ctable->info_field2 = _('Operators');

	$items1 = array('' => '', 'addoperator' => _('add operator'));
	$combobox_form_submit = "<label class=\"info_right\">". _('action') .": \n".  combobox_onchange('input select', 'action', $items1, null) ."</label>";

	$ctable->info_field3 = $combobox_form_submit;
	$ctable->onclick_id = true;
	$ctable->th_array = array(1 => _('ID'), 2 => _('alias'), 3 => _('name'), 4 => _('language'), 5 => _('group'));
	$ctable->th_array_style = 'style="table-layout: fixed; width: 3%"';
	$ctable->td_array = $coperators->get($db, null);
	$ctable->form_key = $_SESSION['form_key'];
	echo $ctable->ctable();


	require_once dirname(__FILE__).'/include/page_footer.php';
}
else {
	header('Location: profile.php');
}
?>


