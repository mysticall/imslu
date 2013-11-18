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

require_once dirname(__FILE__).'/include/common.inc.php';

?>
<!doctype html>
<html>
   <head>
	<title>Login</title>
	<meta name="Author" content="MSIUL Developers" >
	<meta charset="utf-8" >
	<link rel="stylesheet" type="text/css" href="css.css" >
	<script type="text/javascript" src="js/sha512.js"></script>
	<script type="text/javascript" src="js/func.js"></script>
  </head>
  <body>
<?php
if (!empty($_POST['alias']) && !empty($_POST['p'])) {

    if(CWebOperator::login($_POST['alias'], $_POST['p'])) {

        $url = (!empty(CWebOperator::$data['url'])) ? CWebOperator::$data['url'] : 'profile.php';
        header("Location: $url");
        exit;
    }
}

// Check for active session
if (CWebOperator::checkAuthentication(get_cookie('imslu_sessionid'))) {

	header('Location: profile.php');
	exit;
}
?>
    <form action="<?php echo $_SERVER['PHP_SELF'];?>" method="post" class="login_container">
      <div  style="margin-top: 17px; background: #cef3d1;">
        <label class="login_label">Username:</label>
        <input class="login_input" type="text" name="alias">
        <label class="login_label">Password:</label>
        <input class="login_input" type="password" name="password">
        <input type="submit" class="login_submit" name="login" value="Login" onclick="formhash(this.form, this.form.password, 'p')">
      </div>
    </form>
  </body>
</html>
