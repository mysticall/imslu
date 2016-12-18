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
 
class Table {

    // str $form_name - variable for <form name='$form_name'>
    public $form_name;

    public $action;
    public $method = 'post';

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

    // boolean link - if allow click on id
    public $link_action;
    public $link;    

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

    $table  =
"    <form name=\"$this->form_name\" action=\"$this->action\" method=\"$this->method\">
      <table>
        <tbody>
          <tr class=\"$this->tr_top_class\">
            <th colspan=\"$this->colspan\">
              <label style=\"float: left;\">$this->info_field1".count($this->td_array)."</label>
              $this->info_field2
              $this->info_field3
            </th>
          </tr>
          <tr class=\"$this->tr_class\">\n";

    if (isset($this->checkbox)) {
        $table .= "            <th style=\"table-layout: fixed; width: 11px;\"><input class=\"checkbox\" type=\"checkbox\" id=\"all\" onclick=\"check_unchek('$this->form_name', 'all')\"></th> \n";
    }

    if (is_array($this->th_array)) {
        foreach ($this->th_array as $key => $value) {

            $table .= ($key == '1') ? "            <th $this->th_array_style> $value </th> \n" : "            <th> $value </th> \n";
        }
    }

    $table .= "          </tr> \n";

    if (isset($this->td_array)) {
      for ($i = 0; $i < count($this->td_array); ++$i) {

        $class = ($i % 2 == 0) ? "class=\"even_row\"" : "class=\"odd_row\"";
        $table .= "          <tr $class> \n";

        $this->first_value = reset($this->td_array[$i]);
        $this->first_key = key($this->td_array[$i]);
        unset($this->td_array[$i][$this->first_key]);

        if (isset($this->checkbox)) {
            $table .= "            <td><input id=\"checkbox\" class=\"checkbox\" type=\"checkbox\" name=\"$this->first_key[$this->first_value]\" value=\"$this->first_value\"></td> \n";
        }
        if (isset($this->link)) {

            $table .= "            <td><a class=\"bold\" href=\"$this->link_action?{$this->first_key}={$this->first_value}\">$this->first_value</a></td> \n";
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

    $table .= 
"        </tbody>
      </table>
      $this->input_submit
      <input type=\"hidden\" name=\"form_key\" value=\"$this->form_key\">
    </form> \n";

    return $table;
  }
}
?>
