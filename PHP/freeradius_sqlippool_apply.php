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
if (empty($_COOKIE['imslu_sessionid']) || !$check->authentication($_COOKIE['imslu_sessionid'])) {

    header('Location: index.php');
    exit;
}
if ($_SESSION['form_key'] !== $_POST['form_key']) {

    header('Location: index.php');
    exit;
}

# Must be included after session check
require_once dirname(__FILE__).'/include/config.php';

//Only System Admin have acces to Static IP Addresses
if (OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type']) {

    $db = new PDOinstance();


###################################################################################################
    // Delete 
###################################################################################################

    if (!empty($_POST['action']) && $_POST['action'] == 'delete' && !empty($_POST['id'])) {

        $id = $_POST['id'];

        $sql = 'DELETE FROM `radippool` WHERE id = :id';
        $db->dbh->beginTransaction();
        $sth = $db->dbh->prepare($sql);
        $sth->bindParam(':id', $value, PDO::PARAM_INT);

        foreach ($id as $value) {

            $sth->execute();
        }
    
        $db->dbh->commit();

        // Add audit
        add_audit($db, AUDIT_ACTION_DELETE, AUDIT_RESOURCE_FREERADIUS, "IP addresses are deleted.", "ID - ".json_encode($id));

        $_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";
        unset($_POST);
        header("Location: freeradius_sqlippool.php");
        exit;
    }

###################################################################################################
    // Update Pool Name
###################################################################################################

    if (!empty($_POST['change_pool_name'])) {
        
        if (empty($_POST['pool_name'])) {
            
            $msg['msg_pool_name'] = _('Name cannot empty. Re-select IP addresses!');
            show_error_message('action', 'change_pool_name', 'id', $msg, 'freeradius_sqlippool.php');
            exit;
        }
        $pool_name = strip_tags($_POST['pool_name']);

        $sql = 'UPDATE `radippool` SET pool_name = :pool_name WHERE id = :id';
        $db->dbh->beginTransaction();
        $sth = $db->dbh->prepare($sql);
        $sth->bindParam(':pool_name', $pool_name, PDO::PARAM_STR);
        $sth->bindParam(':id', $value, PDO::PARAM_INT);

        $id = unserialize($_POST['id']);

        foreach ($id as $value) {

            $sth->execute();
        }

        $db->dbh->commit();

        // Add audit
        add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_FREERADIUS, "Pool Name is changed.");

        $_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";
        unset($_POST);
        header("Location: freeradius_sqlippool.php");
        exit;
    }

###################################################################################################
    // Save new SQLIPPOOL
###################################################################################################

    if (!empty($_POST['save_sqlippool'])) {
        
        $pool_key = $_POST['pool_key'];

        if (empty($_POST['pool_name'])) {
            
            $msg['msg_pool_name'] = _('Name cannot empty.');
            show_error_message('action', 'addippool', null, $msg, 'freeradius_sqlippool.php');
            exit;
        }
        $pool_name = strip_tags($_POST['pool_name']);

        if (empty($_POST['framedipaddress_start'])) {
            
            $msg['msg_framedipaddress_start'] = _('IP address cannot empty.');
            show_error_message('action', 'addippool', null, $msg, 'freeradius_sqlippool.php');
            exit;
        }
        $ip_start = $_POST['framedipaddress_start'];
        
        if (!filter_var($ip_start, FILTER_VALIDATE_IP)) {
            
            $msg['msg_framedipaddress_start'] = _('Not a valid IP address!');
            show_error_message('action', 'addippool', null, $msg, 'freeradius_sqlippool.php');
            exit;
        }
        
        // Check and insert in database IP address range
        if (!empty($_POST['framedipaddress_end'])) {
            
            $ip_end = $_POST['framedipaddress_end'];

            if (!filter_var($ip_end, FILTER_VALIDATE_IP)) {
                
                $msg['msg_framedipaddress_end'] = _('Not a valid IP address!');
                show_error_message('action', 'addippool', null, $msg, 'freeradius_sqlippool.php');
                exit;
            }
            
            $ip_start = ip2long($ip_start);
            $ip_end = ip2long($ip_end);

            if ($ip_start > $ip_end) {
                
                $msg['msg_framedipaddress_start'] = _('Start IP Address must be less!');
                $msg['msg_framedipaddress_end'] = _('End IP Address must be larger!');
                show_error_message('action', 'addippool', null, $msg, 'freeradius_sqlippool.php');
                exit;
            }
            
            for ($i = $ip_start; $i <= $ip_end; ++$i) {
                
                $ip[$i] = long2ip($i);
            }

            $sql = 'INSERT INTO `radippool` (`pool_name`,`framedipaddress`,`pool_key`) VALUES (:pool_name, :ip, :pool_key)';
            $db->dbh->beginTransaction();
            $sth = $db->dbh->prepare($sql);
            $sth->bindParam(':pool_name', $pool_name, PDO::PARAM_STR);
            $sth->bindParam(':ip', $value);
            $sth->bindParam(':pool_key', $pool_key, PDO::PARAM_INT);

            foreach ($ip as $value) {

                $sth->execute();
            }
    
            $db->dbh->commit();
        }
        else {
            // Insert IP address in database
            $sql = 'INSERT INTO `radippool` (`pool_name`,`framedipaddress`,`pool_key`) VALUES (?, ?, ?)';
            $sth = $db->dbh->prepare($sql);
            $sth->bindParam(1, $pool_name);
            $sth->bindParam(2, $ip_start);
            $sth->bindParam(3, $pool_key);
            $sth->execute();
        }

        $msg = !empty($_POST['framedipaddress_end']) ? _s('IP address range %s - %s is added successfully.', $_POST['framedipaddress_start'], $_POST['framedipaddress_end'])."<br>" : _s('IP address %s is added successfully.', $_POST['framedipaddress_start'])."<br>";
        
        // Add audit
        add_audit($db, AUDIT_ACTION_ADD, AUDIT_RESOURCE_FREERADIUS, "IP addresses are added successfully.", null, $msg);

        $_SESSION['msg'] .= $msg;
        unset($_POST);
        header("Location: freeradius_sqlippool.php");
        exit;
    }

  if (isset($_POST['action'])) {

#####################################################
    // Display messages
#####################################################
    echo !empty($_SESSION['msg']) ? '<div class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
    $_SESSION['msg'] = null;

    // Security key for comparison
    $_SESSION['form_key'] = md5(uniqid(mt_rand(), true));

    $_network_type = array('public' => _('public'), 'private' => _('private'));

###################################################################################################
    // PAGE HEADER
###################################################################################################

    $page['title'] = 'freeRadius IP pool apply';
    $page['file'] = 'freeradius_sqlippool_apply.php';

    require_once dirname(__FILE__).'/include/page_header.php';

###################################################################################################
    // Change Pool Name 
###################################################################################################

    if (!empty($_POST['action']) && $_POST['action'] == 'change_pool_name' && !empty($_POST['id'])) {

        $form =
"    <form action=\"freeradius_sqlippool_apply.php\" method=\"post\">
      <table class=\"tableinfo\">
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th  colspan=\"2\">
              <label>"._('Change pool name for IP address or IP address range')."</label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('pool name')."</label>
            </td>
            <td class=\"dd\">
              <input class=\"input\" type=\"text\" name=\"pool_name\">";
        $form .= (isset($_POST['msg_pool_name'])) ? "&nbsp;<span class=\"red\">{$_POST['msg_pool_name']}</span>\n" : "\n";
        $form .=
"            </td>
          </tr>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"hidden\" name=\"id\" value='".serialize($_POST['id'])."'>
              <input type=\"submit\" name=\"change_pool_name\" id=\"save\" value=\""._('save')."\">
            </td>
          </tr>
        </tbody>
      </table>
    </form>\n";

        echo $form;
    }

    require_once dirname(__FILE__).'/include/page_footer.php';
  }
    else {

        header("Location: freeradius_sqlippool.php");
    }
}
else {

    header("Location: freeradius_sqlippool.php");
}
?>