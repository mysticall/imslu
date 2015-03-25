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

###################################################################################################
	// PAGE HEADER
###################################################################################################

$page['title'] = 'Users';
$page['file'] = 'users.php';

require_once dirname(__FILE__).'/include/page_header.php';


$more = array(
    '' => '',
    'free_access' => _('free access'),
    'not_excluding' => _('not excluding')
    );

#####################################################
    // Get avalible locations
#####################################################
    $sql = 'SELECT id,name FROM location';
    $sth = $db->dbh->prepare($sql);
    $sth->execute();
    $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

    if ($rows) {
        
        for ($i = 0; $i < count($rows); ++$i) {

            $location_name[$rows[$i]['id']] = $rows[$i]['name'];
        }
    }
        
    if (!empty($location_name)) {
        
        $location = array('' => '') + $location_name;
    }
    else {
        $location = array('' => '');
    }

$form =
"    <form action=\"{$_SERVER['PHP_SELF']}\" method=\"get\">
      <table class=\"tableinfo\">
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th>
              <label style=\"margin: 1px 3px 1px;\">"._('the location').combobox('input select', 'locationid', null, $location)." </label>
              <label style=\"margin: 1px 3px 1px;\">"._('more').combobox('input select', 'more', null, $more)." </label>
              <input type=\"hidden\" name=\"show\" value=\"true\">
              <label class=\"generator\" style=\"margin: 1px 5px 1px;\" onclick=\"this.form.submit()\">". _('show')."</label>
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


###################################################################################################
	// Show Users
###################################################################################################	

if (isset($_GET['show'])) {

    $locationid = (!empty($_GET['locationid'])) ? $_GET['locationid'] : '';
    $_location = ($locationid) ? ' WHERE locationid = :locationid' : '';

    if (!empty($_GET['more'])) {
        $_more = ($_location) ? " AND {$_GET['more']} = :more" : " WHERE {$_GET['more']} = :more";
    }
    else {
        $_more = '';
    }

    // Select users info
    $sql = "SELECT userid, name, locationid, address, phone_number, free_access, not_excluding 
    		FROM users$_location$_more";
    $sth = $db->dbh->prepare($sql);

    if ($_location) {
	   $sth->bindValue(':locationid', $locationid, PDO::PARAM_INT);
    }
    if ($_more) {
       $sth->bindValue(':more', 1, PDO::PARAM_INT);
    }

    $sth->execute();
    $rows = $sth->fetchAll(PDO::FETCH_ASSOC);


    $form =
"      <table class=\"tableinfo\">
        <thead id=\"thead\">
          <tr class=\"header_top\">
            <th colspan=\"6\">
              <label style=\"float: left;\">". _('total').": ".count($rows)."</label>
              <label>". _('users')."</label>
            </th>
          </tr> \n";

    $form .=
"          <tr class=\"header\">
            <th>"._('name')."</th>
            <th>"._('the location')."</th>
            <th>"._('address')."</th>
            <th>"._('phone')."</th>
            <th>"._('free access')."</th>
            <th>"._('not excluding')."</th>
          </tr>
        </thead>
        <tbody>\n";

    if (isset($rows[0])) {

        for ($i = 0; $i < count($rows); ++$i) {

            $free_access = ($rows[$i]['free_access'] == 1) ? _('yes') : _('no');
            $not_excluding = ($rows[$i]['not_excluding'] == 1) ? _('yes') : _('no');
            $user_location = (!empty($location[$rows[$i]['locationid']])) ? $location[$rows[$i]['locationid']] : '';

            if ($i % 2 == 0) {

                $form .= 
"          <tr class=\"even_row\">
            <td><a href=\"user_info.php?userid={$rows[$i]['userid']}\">".chars($rows[$i]['name'])."</a></td>
            <td>$user_location</td>
            <td>".chars($rows[$i]['address'])."</td>
            <td>".chars($rows[$i]['phone_number'])."</td>
            <td>$free_access</td>
            <td>$not_excluding</td>
          </tr> \n";

            }
            else {

                $form .= 
"          <tr class=\"odd_row\">
            <td><a href=\"user_info.php?userid={$rows[$i]['userid']}\">".chars($rows[$i]['name'])."</a></td>
            <td>$user_location</td>
            <td>".chars($rows[$i]['address'])."</td>
            <td>".chars($rows[$i]['phone_number'])."</td>
            <td>$free_access</td>
            <td>$not_excluding</td>
          </tr> \n";

            }
        }
    }

    $form .=          
"        </tbody>
      </table> \n";

    echo $form;
}

require_once dirname(__FILE__).'/include/page_footer.php';
?>
