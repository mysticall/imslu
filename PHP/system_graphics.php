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
require_once dirname(__FILE__).'/include/func.php';

// Check for active session
if (empty($_COOKIE['imslu_sessionid']) || !$Operator->authentication($_COOKIE['imslu_sessionid'])) {

    header('Location: index.php');
    exit;
}

# Must be included after session check
require_once dirname(__FILE__).'/include/config.php';

####### PAGE HEADER #######
$page['title'] = 'System Graphics';

require_once dirname(__FILE__).'/include/page_header.php';

####### Display messages #######
echo !empty($_SESSION['msg']) ? '<div id="msg" class="msg"><label>'. $_SESSION['msg'] .'</label></div>' : '';
$_SESSION['msg'] = null;

$graph_width = (isMobile()) ? " style=\"width:99.5%;\"" : "";

    $form =
"      <table>
        <tbody>";

if (!empty($_GET['graph_imq_traffic'])) {

    ### IMQ GRAPHICS ###
    $cmd = "/etc/imslu/scripts/system-graphics.sh graph_imq";
    $result = shell_exec($cmd);

    $form .=
"          <tr class=\"header_top\">
            <th>"._('Bits per second')."</th>
          </tr>
          <tr>
		    <td><br>"._('Daily Graph (5 minute averages)')."<img src=\"rrd/imq_traffic-day.png\"{$graph_width}></a></td>
          </tr>
          <tr>
		    <td><br>"._('Weekly Graph (30 minute averages)')."<img src=\"rrd/imq_traffic-week.png\"{$graph_width}></a></td>
          </tr>
          <tr>
		    <td><br>"._('Monthly Graph (2 hour averages)')."<img src=\"rrd/imq_traffic-month.png\"{$graph_width}></a></td>
          </tr>
          <tr>
            <td><br>"._('Yearly Graph (12 hour averages)')."<img src=\"rrd/imq_traffic-year.png\"{$graph_width}></a></td>
          </tr>";
}
elseif (!empty($_GET['graph_imq_packets'])) {

    ### IMQ GRAPHICS ###
    $cmd = "/etc/imslu/scripts/system-graphics.sh graph_imq";
    $result = shell_exec($cmd);

    $form .=
"
          <tr class=\"header_top\">
            <th>"._('Packets per second')."</th>
          </tr>
          <tr>
            <td><br>"._('Daily Graph (5 minute averages)')."<img src=\"rrd/imq_packets-day.png\"{$graph_width}></a></td>
          </tr>
          <tr>
            <td><br>"._('Weekly Graph (30 minute averages)')."<img src=\"rrd/imq_packets-week.png\"{$graph_width}></a></td>
          </tr>
          <tr>
            <td><br>"._('Monthly Graph (2 hour averages)')."<img src=\"rrd/imq_packets-month.png\"{$graph_width}></a></td>
          </tr>
          <tr>
            <td><br>"._('Yearly Graph (12 hour averages)')."<img src=\"rrd/imq_packets-year.png\"{$graph_width}></a></td>
          </tr>";
}
else {
    $form .=
"          <tr class=\"header_top\">
            <th>"._('Daily Graph (5 minute averages)')."</th>
          </tr>
          <tr>
            <td><a href=\"system_graphics.php?graph_imq_traffic=1\"><img src=\"rrd/imq_traffic-day.png\"{$graph_width}></a></td>
          </tr>
          <tr>
            <td><a href=\"system_graphics.php?graph_imq_packets=1\"><img src=\"rrd/imq_packets-day.png\"{$graph_width}></a></td>
          </tr>";
}

$form .=
"        </tbody>
       </table>";
echo $form;
require_once dirname(__FILE__).'/include/page_footer.php';
?>
