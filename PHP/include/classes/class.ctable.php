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
 
class CTable {

	// str $form_name - variable for <form name='$form_name'>
	public $form_name;

	public $action;
	public $method = 'post';
	// str $table_class - variable for <table class='$table_class'>
	public $table_class = 'tableinfo';

	// str $table_name - variable for <table name='$table_name'>
	public $table_name;

	// str $tr_top_class - variable for Top <tr class='$tr_top_class'>
	public $tr_top_class = 'header_top';

	// str $tr_class - variable for <tr class='$tr_class'>
	public $tr_class = 'header';

	public $colspan = 3;

	// str $info_field1 - variable for <th> $info_field1 </th>
	public $info_field1;

	// $info_field2 - example: 
	// $ctable->info_field2 = '              '. _('FreeRadius Groups') ."\n";
	public $info_field2;

	// html or str $info_field3
	public $info_field3;

	// boolean $checkbox - if true show checkbox in table
	public $checkbox;

	// boolean onclick_id - if allow click on id
	public $onclick_id;	

	// str $th_class - variable for <th class='$th_class'>
	public $th_class = 'input';

	// array $th_array - table header information fields <th> </th>
	public $th_array;

	// array $td_array - !!! working only with fetchAll !!! - table fields <td> </td>
	public $td_array;

	public $th_array_style;

	public $input_submit;
	public $form_key = null;

	public $first_key;
	public $first_value;
	
/**
 * This function create dynamic html table
 */
function ctable() {

	$table  = "    <form name=\"$this->form_name\" action=\"$this->action\" method=\"$this->method\"> \n";
	$table .= "      <table class=\"$this->table_class\" name=\"$this->table_name\"> \n";
	$table .= "        <tbody> \n";
	$table .= "          <tr class=\"$this->tr_top_class\"> \n";
	$table .= "            <th colspan=\"$this->colspan\"> \n";
	$table .= "              <label style=\"float: left;\">$this->info_field1".count($this->td_array)."</label> \n";
	$table .= "              $this->info_field2 \n";
	$table .= "              $this->info_field3 \n";
	$table .= "            </th> \n";
	$table .= "          </tr> \n";
	$table .= "          <tr class=\"$this->tr_class\"> \n";
	
	if (isset($this->checkbox)) {
		$table .= "            <th style=\"table-layout: fixed; width: 3px;\"><input class=\"$this->th_class\" type=\"checkbox\" id=\"all\" onclick=\"check_unchek('$this->form_name', 'all')\"></th> \n";
	}
	
	if (is_array($this->th_array)) {

		foreach ($this->th_array as $key => $value) {

			$table .= ($key == '1') ? "            <th $this->th_array_style> $value </th> \n" : "            <th> $value </th> \n";
		}
	}
	
	$table .= "          </tr> \n";

	if (isset($this->td_array)) {
		
	  for ($i = 0; $i < count($this->td_array); ++$i) {
		
		if ($i % 2 == 0) {
				
			$table .= "          <tr class=\"even_row\"> \n";

			$this->first_value = reset($this->td_array[$i]);
			$this->first_key = key($this->td_array[$i]);
			unset($this->td_array[$i][$this->first_key]);

			if (isset($this->checkbox)) {
				$table .= "            <td><input class=\"$this->th_class\" type=\"checkbox\" id=\"checkbox\" name=\"$this->first_key[$this->first_value]\" value=\"$this->first_value\"></td> \n";
			}
			if (isset($this->onclick_id)) {
				$table .= "            <td class=\"left_select\" onclick=\"change_input('$this->form_name', '$this->first_value', '$this->first_key', '$this->first_value');\">$this->first_value\n              <input id=\"$this->first_value\" type=\"hidden\" name value></td> \n";
			}
			else {
				$table .= "            <td>$this->first_value</td> \n";
			}

			foreach ($this->td_array[$i] as $value) {

				$table .= "            <td>".chars($value)."</td> \n";
			}

			$table .= "          </tr> \n";
		}
		else {
				
			$table .= "          <tr class=\"odd_row\"> \n";

			$this->first_value = reset($this->td_array[$i]);
			$this->first_key = key($this->td_array[$i]);
			unset($this->td_array[$i][$this->first_key]);

			if (isset($this->checkbox)) {
				$table .= "            <td><input class=\"$this->th_class\" type=\"checkbox\" id=\"checkbox\" name=\"$this->first_key[$this->first_value]\" value=\"$this->first_value\"></td> \n";
			}
			if (isset($this->onclick_id)) {
				$table .= "            <td class=\"left_select\" onclick=\"change_input('$this->form_name', '$this->first_value', '$this->first_key', '$this->first_value');\">$this->first_value\n              <input id=\"$this->first_value\" type=\"hidden\" name value></td> \n";
			}
			else {
				$table .= "            <td>$this->first_value</td> \n";
			}

			foreach ($this->td_array[$i] as $value) {

				$table .= "            <td>".chars($value)."</td> \n";
			}

			$table .= "          </tr> \n";
		}
	  }	
	}

	$table .= "        </tbody> \n";
	$table .= "      </table> \n";
	$table .= "      $this->input_submit";
	$table .= "      <input type=\"hidden\" name=\"form_key\" value=\"$this->form_key\">\n";
	$table .= "    </form> \n";

	return $table;
  }
}
	
