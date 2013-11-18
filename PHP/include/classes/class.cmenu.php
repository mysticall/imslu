<?php 
/*
 * MSIUL version 0.1-alpha
 *
 * Copyright © 2013 MSIUL Developers
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
 
class CMenu {

	public $menu_top;
	public $menu_right;

	public function __construct() {
		
		require_once(dirname(dirname(__FILE__))).'/menu.php';

		$this->menu_top = $menu_top;
		$this->menu_right = $menu_right;
	}
	
	function menu_top($div_class) {

	$menu_top = $this->build_menu($this->menu_top, $div_class);
	return $menu_top;
	}

	function menu_right($div_class) {

	$menu_right = $this->build_menu($this->menu_right, $div_class);
	return $menu_right;
	}
	
	function build_menu($menu, $div_class) {
		
		$out = "      <div class=\"$div_class\"> \n";
		$out .= "        <ul> \n";

		for ($i = 1; $i <= count($menu); ++$i) {
	
			if ($menu[$i]['check_anyright']) {
				
				$out .= "        <li class=\"{$menu[$i]['class']}\"><a href=\"{$menu[$i]['link']}\">";
				$out .= "<img src=\"images/general/{$menu[$i]['img']}\">  {$menu[$i]['name']}</a> \n";

				if (isset($menu[$i]['submenu']) && is_array($menu[$i]['submenu'])) {
					
					$submenu = $menu[$i]['submenu'];
					$out .= $this->submenu($submenu);
				}

				$out .= "        </li> \n";
			}
		}
		
		$out .= "        </ul> \n";
		$out .= "      </div> \n";
		return $out;
	}
	
	function submenu($submenu) {

		$out = "          <ul> \n";

		for ($i = 1; $i <= count($submenu); ++$i) {
			
			if ($submenu[$i]['check_anyright']){
				
				$out .= "            <li><a href=\"{$submenu[$i]['link']}\">";
				$out .= "<img src=\"images/general/{$submenu[$i]['img']}\">  {$submenu[$i]['name']}</a> \n";
				$out .= "            </li> \n";
			}
		}
		
		$out .= "          </ul> \n";
		return $out;
	}

	public function __destruct() {
		
		$this->menu_top = null;
		$this->menu_right = null;
	}
}
?>
