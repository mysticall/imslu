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

/**
 * @param int - Block IP address after 'n' failed attempts.
 */
$block_ip = '7';

$msg = !empty($GLOBALS['msg']) ? $GLOBALS['msg'] : '';
$GLOBALS['$msg'] = null;


if (!empty($_POST['alias']) && !empty($_POST['password'])) {

    $operator = array(
        'alias' => $_POST['alias'],
        'password' => $_POST['password'],
        'ip' => $_SERVER['REMOTE_ADDR'],
        'browser' => $_SERVER['HTTP_USER_AGENT'],
        'block_ip' => $block_ip
        );

    if($Operator->login($operator)) {

        $url = (!empty($_SESSION['data']['url'])) ? $_SESSION['data']['url'] : 'profile.php';
        header("Location: $url");
        exit;
    }
}
elseif (!empty($_POST['login']) && (empty($_POST['alias']) || empty($_POST['password']))) {

    $msg = _('Enter a name and password.');
}

// Check for active session
if (!empty($_COOKIE['imslu_sessionid']) && $Operator->authentication($_COOKIE['imslu_sessionid'])) {

    header('Location: profile.php');
    exit;
}


$form = 
"<!doctype html>
<html>
<head>
	<title>Login imslu</title>
	<meta name=\"Author\" content=\"MSIUL Developers\" >
	<meta charset=\"utf-8\" >
	<link rel=\"stylesheet\" type=\"text/css\" href=\"css.css\" > \n";

$css = (!empty($_COOKIE['theme']) && ($_COOKIE['theme'] != 'originalgreen')) ? "<link rel=\"stylesheet\" type=\"text/css\" href=\"styles/themes/{$_COOKIE['theme']}/main.css\">" : "";

$form .= 
"   $css
	<script type=\"text/javascript\" src=\"js/func.js\"></script>
</head>
  <body>
    <form action=\"{$_SERVER['PHP_SELF']}\" method=\"post\" class=\"login_container\">
      <div  style=\"margin-top: 17px;\">
        <label class=\"login_label\">"._('name').":</label>
        <input class=\"login_input\" type=\"text\" name=\"alias\">
        <label class=\"login_label\">"._('password').":</label>
        <input class=\"login_input\" type=\"password\" name=\"password\">
        <input type=\"submit\" class=\"login_submit\" name=\"login\" value=\""._('login')."\">
      </div>
      <div class=\"login_msg\">
        <label>{$msg}</label>
      </div>
    </form>
  </body>
</html>";

echo $form;

?>
