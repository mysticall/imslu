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


function confirm_delete(form_name, selected, message) {

	if (selected == "delete") {

		if(confirm(message)) {
			
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

function checkPass(message1, message2) {

    var password1 = document.getElementById("password1");
    var password2 = document.getElementById("password2");
    var msg = document.getElementById("pass_msg");

    if (password1.value != "" && password2.value != "" && password1.value == password2.value) {

        document.getElementById("save").disabled = false;
        password2.style.backgroundColor = "#cef3d1";
        msg.style.color = "#00c500";
        msg.innerHTML = message1;
    }
    else if (password1.value != "" && password2.value == "") {
        document.getElementById("save").disabled = true;
    }
    else {
        document.getElementById("save").disabled = true;
        password2.style.backgroundColor = "#FF5555";
        msg.style.color = "#DC0000";
        msg.innerHTML = message2;
    }
}

/**
 *
 * This function is used to check value in a mysql database
 * 
 **/
function value_exists(id, table, valueid, msg) {

	var value = document.getElementById(id).value;
	var xmlhttp = new XMLHttpRequest();

    xmlhttp.onreadystatechange = function() {
        if (xmlhttp.readyState==4 && xmlhttp.status==200) {

            if (xmlhttp.responseText == 0) {

                add_new_msg(msg);
                document.getElementById("save").disabled = true;
            }
            if (xmlhttp.responseText == 1) {

                document.getElementById("save").disabled = false;
                if (document.getElementById("msg")) {
                    document.getElementById("msg").remove();
                }
            }
        }
    };

    xmlhttp.open("GET", "value_exists.php?table="+table+"&value="+value+"&valueid="+valueid, true);
    xmlhttp.send();
}

function add_new_msg(message) {

    if (document.getElementById("msg")) {
		var msg = document.createTextNode(message);
        var item = document.getElementById("msg");
        item.replaceChild(msg, item.childNodes[0]);
	}
    else {
        var msg = document.createElement("DIV");
        msg.id = "msg";
        msg.className = "msg";
        msg.innerHTML = "<label>" + message + "</label>";
        var item = document.getElementById("middle_container");
        item.insertBefore(msg, item.childNodes[0]);
    }
}
