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
 
/**
 * This function create dynamic html combobox
 * 
 * @param $class - variable for <select class='$class'>
 * @param $name - variable for <select name='$name'>
 * @param string $selected - user defined options - Language, Theme ...
 * @param array $items - array with items for dropdown menu
 */
function combobox($class, $name, $selected, $items) {
	
	if (isset($selected)) {

		foreach ($items as $key => $value) {
			
			if ($key == $selected) {
				
				$found[$selected] = $value;

				unset($items[$key]);
				$items = $found + $items;
			}	
		}
	}

	$dropdown = "              <select id=\"$name\" class=\"$class\" name=\"$name\">\n";

	foreach ($items as $key => $value) {

		$dropdown .= "                <option value=\"".chars($key)."\">".chars($value)."</option>\n";
	}
	
	$dropdown .= "              </select>";
	return $dropdown;
}

/**
 * This function create dynamic html combobox with JavaScrip OnChange
 * 
 * @param str $class - variable for <select class='$class'>
 * @param str $name - variable for <select name='$name'>
 * @param array $items - array with items for dropdown menu
 * @param str $add_function - add javascript function
 */
function combobox_onchange($class, $name, $items, $add_function) {
	
	if (isset($add_function)) {
		$dropdown = "              <select class=\"$class\" name=\"$name\" OnChange=\"$add_function\">\n";
	}
	else {
		$dropdown = "              <select class=\"$class\" name=\"$name\" OnChange=\"this.form.submit()\">\n";
	}
	
	if (is_array($items)) {
		
		foreach ($items as $key => $value) {
		
			$dropdown .= "                <option value=\"".chars($key)."\">".chars($value)."</option>\n";
		}
	}
	$dropdown .= "              </select>";

	return $dropdown;
}
?>
