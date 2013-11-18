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
 
// enable debug mode
 error_reporting(E_ALL); ini_set('display_errors', 'On');

require_once dirname(__FILE__).'/include/common.inc.php';

if (!CWebOperator::checkAuthentication(get_cookie('imslu_sessionid'))) {
	header('Location: index.php');
	exit;
}

# Must be included after session check
require_once dirname(__FILE__).'/include/config.inc.php';

$sysadmin_rights = (OPERATOR_TYPE_LINUX_ADMIN == CWebOperator::$data['type']);
$admin_rights = (OPERATOR_TYPE_ADMIN == CWebOperator::$data['type']);

$db = new CPDOinstance();

###################################################################################################
	// PAGE HEADER
###################################################################################################

$page['title'] = 'Operator profile';
$page['file'] = 'profile.php';

require_once dirname(__FILE__).'/include/page_header.php';

#####################################################
	// Display messages
#####################################################
echo !empty($_SESSION['msg']) ? '<div class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
$_SESSION['msg'] = null;

// Security key for comparison
$_SESSION['form_key'] = md5(uniqid(mt_rand(), true));

$sql = 'SELECT alias,name, url, lang, theme FROM operators WHERE operid = :operid';
$sth = $db->dbh->prepare($sql);
$sth->bindValue(':operid', CWebOperator::$data['operid'], PDO::PARAM_INT);
$sth->execute();
$oper_info = $sth->fetch(PDO::FETCH_ASSOC);

$form =
"    <form action=\"profile_apply.php\" method=\"post\">
      <table class=\"tableinfo\">
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th  colspan=\"2\">
              <label>"._('Profile').": ".chars($oper_info['name'])."</label>
            </th>
          </tr>\n";

//Only System Admin can change alias
$form .= ($sysadmin_rights) ? "
          <tr>
            <td class=\"dt right\">
              <label>"._('Alias')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"alias\" value=\"".chars($oper_info['alias'])."\">
            </td>
          </tr>\n" : '';

$form .= "
          <tr>
            <td class=\"dt right\">
              <label>"._('Name')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"name\" value=\"".chars($oper_info['name'])."\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Password')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"password\" name=\"password1\">
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Password (once again)')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"password\" name=\"password2\">
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
			
	if ($key == $oper_info['lang']) {

		$found[$oper_info['lang']] = $value;

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

$form .= ($languages_unable_set > 0) ? "&nbsp; <span class=\"red\">". _('Some of locales for the languages are not installed on the web server.') ."</span> \n" : "\n";

// restoring original locale
setlocale(LC_MONETARY, locale_unix($oper_info['lang']));

$form .=
"            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('Theme')."</label>
            </td>
            <td class=\"dd\">
".combobox('input select', 'theme', $oper_info['theme'], $THEMES)."\n
            </td>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('URL (after login)')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"url\" size=\"50\" maxlength=\"255\" value=\"".chars($oper_info['url'])."\">
            </td>
          </tr>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"hidden\" name=\"alias_old\" value=\"".chars($oper_info['alias'])."\">
              <input type=\"submit\" name=\"save\" value=\""._('save')."\" onclick=\"formhash(this.form, this.form.password1, 'p1'); formhash(this.form, this.form.password2, 'p2');\">
            </td>
          </tr>
        </tbody>
      </table>
    </form>";

echo $form;

require_once dirname(__FILE__).'/include/page_footer.php';
?>
