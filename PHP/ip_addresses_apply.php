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

// enable debug mode
error_reporting(E_ALL); ini_set('display_errors', 'On');

require_once dirname(__FILE__).'/include/common.php';

// Check for active session
if (empty($_COOKIE['imslu_sessionid']) || !$Operator->authentication($_COOKIE['imslu_sessionid'])) {

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

    ####### Delete ####### 
    if (!empty($_POST['action']) && $_POST['action'] == 'delete' && !empty($_POST['id'])) {

        $id = $_POST['id'];

        $sql = 'DELETE FROM `ip` WHERE id = :id AND userid=0';
        $db->dbh->beginTransaction();
        $sth = $db->dbh->prepare($sql);
        $sth->bindParam(':id', $value, PDO::PARAM_INT);

        foreach ($id as $value) {

            $sth->execute();
        }

        $db->dbh->commit();

        // Add audit
        add_audit($db, AUDIT_ACTION_DELETE, AUDIT_RESOURCE_STATIC_IP, "IP addresses are deleted.", "ID - ".implode(',', $id));

        $_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";
        unset($_POST);
        header("Location: ip_addresses.php");
        exit;
    }


    ####### Edit #######
    if (!empty($_POST['edit'])) {

        $pool = $_POST['pool'];
        
        $sql = 'UPDATE ip SET pool = :pool WHERE id = :id';
        $db->dbh->beginTransaction();
        $sth = $db->dbh->prepare($sql);
        $sth->bindParam(':pool', $pool, PDO::PARAM_STR);
        $sth->bindParam(':id', $value, PDO::PARAM_INT);

        $id = unserialize($_POST['id']);

        foreach ($id as $value) {

            $sth->execute();
        }

        $db->dbh->commit();

        // Add audit
        add_audit($db, AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_STATIC_IP, "pool name is changed.");

        $_SESSION['msg'] .= _('Changes are applied successfully.')."<br>";
        unset($_POST);
        header("Location: ip_addresses.php");
        exit;
    }


    ####### New #######
    if (!empty($_POST['new'])) {

        if (!filter_var($_POST['ipaddress_start'], FILTER_VALIDATE_IP)) {

            $_SESSION['msg'] .= _('Invalid start IP Address!')."<br>";
            header("Location: ip_addresses.php?new=1&pool={$_POST['pool']}&ipaddress_start={$_POST['ipaddress_start']}&ipaddress_end={$_POST['ipaddress_end']}");
            exit;
        }

        // Check and insert in database IP address range
        if (!empty($_POST['ipaddress_end'])) {

            if (!filter_var($_POST['ipaddress_end'], FILTER_VALIDATE_IP)) {
                
                $_SESSION['msg'] .= _('Invalid end IP Address!')."<br>";
                header("Location: ip_addresses.php?new=1&pool={$_POST['pool']}&ipaddress_start={$_POST['ipaddress_start']}&ipaddress_end={$_POST['ipaddress_end']}");
                exit;
            }

            $ip_start = ip2long($_POST['ipaddress_start']);
            $ip_end = ip2long($_POST['ipaddress_end']);

            if ($ip_start > $ip_end) {

                $_SESSION['msg'] .= _('Start IP address must be less!')."<br>";
                $_SESSION['msg'] .= _('End IP address must be larger!')."<br>";
                header("Location: ip_addresses.php?new=1&pool={$_POST['pool']}&ipaddress_start={$_POST['ipaddress_start']}&ipaddress_end={$_POST['ipaddress_end']}");
                exit;
            }

            for ($i = $ip_start; $i <= $ip_end; ++$i) {
                
                $ip[$i] = long2ip($i);
            }

            $sql = 'INSERT INTO ip (ip, pool) VALUES (:ip, :pool)';
            $db->dbh->beginTransaction();
            $sth = $db->dbh->prepare($sql);
            $sth->bindParam(':ip', $value, PDO::PARAM_STR);
            $sth->bindParam(':pool', chars($_POST['pool']), PDO::PARAM_STR);

            foreach ($ip as $value) {

                $sth->execute();
            }
    
            $db->dbh->commit();

            unset($ip);
        }
        else {
            // Insert IP address in database
            $sql = 'INSERT INTO ip (ip, pool) VALUES (:ip, :pool)';
            $sth = $db->dbh->prepare($sql);
            $sth->bindValue(':ip', $_POST['ipaddress_start'], PDO::PARAM_STR);
            $sth->bindValue(':pool', chars($_POST['pool']), PDO::PARAM_STR);
            $sth->execute();
        }

        $msg = !empty($_POST['ipaddress_end']) ? _s('IP address range %s - %s is added successfully.', $_POST['ipaddress_start'], $_POST['ipaddress_end'])."<br>" : _s('IP address %s is added successfully.', $_POST['ipaddress_start'])."<br>";
        
        // Add audit
        add_audit($db, AUDIT_ACTION_ADD, AUDIT_RESOURCE_STATIC_IP, "IP addresses are added successfully.", null, $msg);

        $_SESSION['msg'] .=  $msg;
        unset($_POST);
        header("Location: ip_addresses.php");
        exit;
    }


    ####### Edit #######
    if (!empty($_POST['action'])) {

    ####### Display messages #######
    echo !empty($_SESSION['msg']) ? '<div id="msg" class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
    $_SESSION['msg'] = null;

    // Security key for comparison
    $_SESSION['form_key'] = md5(uniqid(mt_rand(), true));

    ####### PAGE HEADER #######
    $page['title'] = 'Static IP addresses apply';
    $page['file'] = 'static_ippool_apply.php';

    require_once dirname(__FILE__).'/include/page_header.php';

    ####### Edit #######
    if ($_POST['action'] == 'change_pool' && !empty($_POST['id'])) {

        $form =
"<script type=\"text/javascript\">
<!--
function validateForm() {

    if (document.getElementById(\"pool\").value == \"\") {

        add_new_msg(\""._s('Please fill the required field: %s', _('pool'))."\");
        document.getElementById(\"pool\").focus();
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
              <label>"._('Change pool name for IP address or IP address range')."</label>
            </th>
          </tr>
          <tr>
            <td class=\"dt right\">
              <label>"._('pool')."</label>
            </td>
            <td class=\"dd\">
              <input id=\"pool\" class=\"input\" type=\"text\" name=\"pool\">
            </td>
          </tr>
          <tr class=\"odd_row\">
            <td class=\"dt right\" style=\"border-right-color:transparent;\">
            </td>
            <td class=\"dd\">
              <input type=\"hidden\" name=\"form_key\" value=\"{$_SESSION['form_key']}\">
              <input type=\"hidden\" name=\"id\" value='".serialize($_POST['id'])."'>
              <input class=\"button\" type=\"submit\" name=\"edit\" value=\""._('save')."\">
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

       header("Location: ip_addresses.php");
    }
}
?>
