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

# Must be included after session check
require_once dirname(__FILE__).'/include/config.php';

$page['title'] = 'Ping - Arping';
$page['file'] = 'ping.php';

require_once dirname(__FILE__).'/include/page_header.php';

settype($_GET['packetsize'], "integer");
$packetsize = ($_GET['packetsize'] > 0 && $_GET['packetsize'] < 11025) ? $_GET['packetsize'] : 1024;

settype($_GET['count'], "integer");
$count = ($_GET['count'] > 0 && $_GET['count'] < 26) ? $_GET['count'] : 15;

$ipaddress = (!empty($_GET['ipaddress'])) ? $_GET['ipaddress'] : '';
$_GET['resource'] = (!empty($_GET['resource'])) ? $_GET['resource'] : '';

switch($_GET['resource']) {
	case "ping":
		$resource = array('ping' => 'ping', 'arping' => 'arping');
		$cmd = "$PING -s $packetsize -c $count $ipaddress 2>&1";
		break;
	case "arping":
		$resource = array('arping' => 'arping', 'ping' => 'ping');
		$iface = ($USE_VLANS && !empty($_GET['vlan'])) ? $_GET['vlan'] : $IFACE_INTERNAL;
		$cmd = "$SUDO $ARPING -i $iface -c $count $ipaddress 2>&1";
		break;
	case "":
		$resource = array('ping' => 'ping', 'arping' => 'arping');
		$cmd = '';
		break;
}

    echo
"    <form method=\"get\">
      <table class=\"tableinfo\">
        <tbody id=\"tbody\">
          <tr class=\"header_top\">
            <th>
              <label style=\"margin: 1px 3px 1px;\">".combobox('input select', 'resource', null, $resource)."</label>
              <label style=\"margin: 1px 3px 1px;\">
               <input class=\"input\" type=\"text\" name=\"packetsize\" value=\"$packetsize\" maxlength=\"5\" size=\"5\">
              </label>
              <label style=\"margin: 1px 3px 1px;\">
               <input class=\"input\" type=\"text\" name=\"count\" value=\"$count\" maxlength=\"3\" size=\"3\">
              </label>
               <input class=\"input\" type=\"text\" name=\"ipaddress\" value=\"$ipaddress\">
              <input type=\"submit\" value=\""._('start')."\">
            </th>
          </tr>
        </tbody>
      </table>
    </form>
      <table class=\"tableinfo\">
          <tr class=\"header_top\">
            <th>
              <label> </label>
            </th>
          </tr>
          <tr>
            <td>
              <textarea style=\"margin-top:10px; margin-left:17%; width: 64%; height: 270px;\">\n";

$descriptorspec = array(
    0 => array("pipe", "r"),   // stdin is a pipe that the child will read from
    1 => array("pipe", "w"),   // stdout is a pipe that the child will write to
    2 => array("pipe", "w")    // stderr is a pipe that the child will write to
);
$cwd = '/tmp';

$process = proc_open($cmd, $descriptorspec, $pipes, $cwd, array());

if (is_resource($process)) {

	ob_flush();
	flush();
    while ($s = fgets($pipes[1])) {

        echo $s;
    	ob_flush();
    	flush();
    }
        
    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $return_value = proc_close($process);
    ob_end_flush();
}

echo
"              </textarea>
            </td>
          </tr>
      </table>\n";

require_once dirname(__FILE__).'/include/page_footer.php';