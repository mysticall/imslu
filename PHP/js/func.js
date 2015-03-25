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

function formhash(form, password, passwd_name) {
	// Create a new element input, this will be out hashed password field.
	var passwd = document.createElement("input");

	// Add the new element to our form.
	form.appendChild(passwd);
	passwd.name = passwd_name;
	passwd.type = "hidden";

	if (password.value) {
		
		passwd.value = hex_sha512(password.value);

		// Make sure the plaintext password doesn't get sent.
		password.value = "";
	}
	else {
		
		passwd.value = "";
	}
}

/**
 * This function is used from PHP function ctable() in class.ctable.php
 * 
 * @param form - variable for <form name="form">
 * @param id - variable for <input id="id">
 * @param name - variable for <input name="name">
 * @param value - variable for <input value="value">
 * 
 **/
function change_input(form_name, id, name, value) {

	document.getElementById(id).name = name;
	document.getElementById(id).value = value;
	
	document.forms[form_name].submit();
}


function confirm_delete(form_name, selected) {

	if (selected == "delete") {

		if(confirm('WARNING: All selected will be deleted!')) {
			
			document.forms[form_name].submit();
		}
		else {
			
			var form_elements = document.forms[form_name].elements;

			for (var i = 0; i < form_elements.length; i++) {
				
				if(form_elements[i].type == "checkbox") {
					form_elements[i].checked = false;
				}
			}
		}
	}
	else {
		
		if (selected) {
			
			document.forms[form_name].submit();
		}
	}
}

/**
 * This function is used from PHP function ctable() in class.ctable.php
 * 
 * @param form_name - variable for form name <form name="name">
 * @param main_id - variable for main (top) checkbox <input id="id">
 * 
 **/

function check_unchek(form_name, main_id) {

	var main_checkbox = document.getElementById(main_id).checked;
	var form_elements = document.forms[form_name].elements;

	for (var i = 0; i < form_elements.length; i++) {
		if(form_elements[i].type == "checkbox") {
			form_elements[i].checked = main_checkbox;
		}
	}
}

/**
 * This function is used for add Freeradius Attribute
 * 
 * @param id - variable for <ul id="id">
 * @param selected - variable for <select> </select> - for auto find selected use: this[this.selectedIndex].value 
 **/
function add_attribute(id, name, selected) {
	
	var new_tr = document.createElement("tr");
	
	new_tr.innerHTML =  "<td class='dt right'>" +
						"  <label>"+ selected + "</label>" +
						"  <select class='input select' name='op[" + selected + "]'>" +
						"    <option value='='>=</option>" +
						"    <option value=':='>:=</option>" +
						"    <option value='+='>+=</option>" +
						"    <option value='=='>==</option>" +
						"    <option value='!='>!=</option>" +
						"    <option value='&gt;'>&gt;</option>" +
						"    <option value='&gt;='>&gt;=</option>" +
						"    <option value='&lt;'>&lt;</option>" +
						"    <option value='&lt;='>&lt;=</option>" +
						"    <option value='=~'>=~</option>" +
						"    <option value='!~'>!~</option>" +
						"    <option value='=*'>=*</option>" +
						"    <option value='!*'>!*</option>" +
						"  </select>";
	new_tr.innerHTML += "</td>";
	
	new_tr.innerHTML += "<td class='dd'>" +
						"  <input class='input' type='text' name='" + name + "[" + selected + "]' >";
	new_tr.innerHTML += "</td>";

	if (selected) {
		
		document.getElementById(id).appendChild(new_tr);
	}
}

/**
 * This function is used for add new User on Form
 * 
 * @param id - variable for <table id="id">
 * @param selected - variable for <select> </select> - for auto find selected use: this[this.selectedIndex].value 
 **/
function add_pppoe(id, selected) {
	
	var new_tr = document.createElement("tr");
	
	new_tr.innerHTML = 	"<td class='dt right'>" +
						"  <label>"+ 'Username' + "</label>" +
						"</td>";
	
	new_tr.innerHTML += "<td class='dd'>" +
						"  <input class='input' type='text' name='" + 'pppoe' + "[" + 'username' + "]' id='username' onkeyup=\"user_exists(\'username\', \'radcheck\')\"><label id='hint'></label>" +
						"</td>";

	new_tr2 = document.createElement("tr");
	
	new_tr2.innerHTML = "<td class='dt right'>" +
						"  <label>"+ 'Password' + "</label>" +
						"</td>";
	
   new_tr2.innerHTML += "<td class='dd'>" +
						"  <input id=\"password\" class='input' type='text' name='" + 'pppoe' + "[" + 'password' + "]' >" +
						"  <label class=\"generator\" onclick=\"generatepassword(document.getElementById('password'), 8);\" >Generate</label>" +
						"</td>";
						
	if (selected) {
		
		document.getElementById(id).appendChild(new_tr);
		document.getElementById(id).appendChild(new_tr2);
	}
}

/**
 *
 * This function is used to check the username in table radcheck
 * 
 **/
function user_exists(id, table) {
	
	var value = document.getElementById(id).value;
	var xmlhttp;
	
    if (value.length < 3) {
    	document.getElementById("hint").innerHTML = "";
        document.getElementById("save").disabled = true;
    }
    
    if (value.length == 0) {
        document.getElementById("save").disabled = false;
    }

	if (value.length >= 3) {
		
		if (window.XMLHttpRequest) {
			// code for IE7+, Firefox, Chrome, Opera, Safari
            xmlhttp = new XMLHttpRequest();
        }
        else {
        	// code for IE6, IE5
            xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
        }
		
        xmlhttp.onreadystatechange = function() {
        	
            if (xmlhttp.readyState==4 && xmlhttp.status==200) {
            	
                var response = xmlhttp.responseText.replace(/(\r\n|\n|\r)/gm,"");
                
                if (response == "free") {
                	document.getElementById("hint").innerHTML = "&nbsp; &nbsp;<span style='color: #00c500; font-weight:bold;'>" + response + "</span>";
                	document.getElementById("save").disabled = false;
                }
                if (response == "taken") {
                	document.getElementById("hint").innerHTML = "&nbsp; &nbsp;<span style='color: #ff0000; font-weight:bold;'>" + response + "</span>";
                	document.getElementById("save").disabled = true;
                }
            }
        };

        var post = "table="+table +"&value="+encodeURIComponent(value);

		xmlhttp.open("POST", "is_exists.php", true);
	    xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");	
		xmlhttp.send(post);
	}
}

