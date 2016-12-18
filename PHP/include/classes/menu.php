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
 
class Menu {

	public $top_menu;
	public $right_menu;

	public function __construct() {
		
		require_once(dirname(dirname(__FILE__))).'/menu.php';

		$this->top_menu = $top_menu;
		$this->right_menu = $right_menu;
	}
	
	function top_menu($div_class) {

	$top_menu = $this->build_menu($this->top_menu, $div_class);
	return $top_menu;
	}

	function right_menu($div_class) {

	$right_menu = $this->build_menu($this->right_menu, $div_class);
	return $right_menu;
	}
	
	function build_menu($menu, $div_class) {

        $out = 
"      <div class=\"{$div_class}\">
        <ul> \n";

		for ($i = 1; $i <= count($menu); ++$i) {
	
			if ($menu[$i]['check_permissions']) {

                $out .=
"        <li class=\"{$menu[$i]['class']}\"><a href=\"{$menu[$i]['link']}\"><img src=\"images/general/{$menu[$i]['img']}\">  {$menu[$i]['name']}</a>";

				if (!empty($menu[$i]['submenu']) && is_array($menu[$i]['submenu'])) {
					
					$submenu = $menu[$i]['submenu'];
					$out .= $this->submenu($submenu);
				}

                $out .=
 "        </li> \n";
			}
		}

$out .=		
"        </ul>
      </div> \n";

		return $out;
	}
	
	function submenu($submenu) {

        $out =
"          <ul> \n";

		for ($i = 1; $i <= count($submenu); ++$i) {
			
			if ($submenu[$i]['check_permissions']){

                $out .=
"            <li><a href=\"{$submenu[$i]['link']}\"><img src=\"images/general/{$submenu[$i]['img']}\">  {$submenu[$i]['name']}</a></li> \n";
			}
		}

        $out .= 
"          </ul> \n";

		return $out;
	}

	public function __destruct() {
		
		$this->top_menu = null;
		$this->right_menu = null;
	}
}
?>
