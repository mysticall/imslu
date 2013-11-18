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
 
class CSession  implements SessionHandlerInterface {

	private $dbh;

	public function __construct(\PDO $dbh) {

		$this->dbh = $dbh;
	}

	public function close() {

	return true;
	}

	public function destroy($id) {

		$sql = 'DELETE FROM sessions WHERE sessionid = ?';
		$sth = $this->dbh->prepare($sql);

		$sth->bindParam(1, $id );
   		$sth->execute();
	return true;
	}

	public function gc($maxlifetime) {

		$maxlifetime = ini_get('session.gc_maxlifetime');
		$old = time() - $maxlifetime;

		$sql = 'DELETE FROM sessions WHERE set_time < ?';
		$sth = $this->dbh->prepare($sql);

		$sth->bindParam(1, $old);
		$sth->execute();
	return true;
	}
	
	public function open($save_path , $session_name) {
	
	return true;
	}
	
	public function read($id) {

		$sql = 'SELECT data FROM sessions WHERE sessionid = ? LIMIT 1';

	   	$sth = $this->dbh->prepare($sql);
		$sth->bindParam(1, $id);
		$sth->execute();
		$rows = $sth->fetch(PDO::FETCH_OBJ);
		$data = is_object($rows) ? $rows->data : null;

		return $data;
	}

	public function write($id, $data) {

		$ip = $_SERVER['REMOTE_ADDR'];
		$browser = $_SERVER['HTTP_USER_AGENT'];
		$login_string = hash('sha512', $ip.$browser);
		$time = time(); 

		$sql = 'REPLACE INTO sessions (sessionid, set_time, data, login_string)
				VALUES (?, ?, ?, ?)';
		$sth = $this->dbh->prepare($sql);
		$sth->bindParam(1, $id);
		$sth->bindParam(2, $time);
		$sth->bindParam(3, $data);
		$sth->bindParam(4, $login_string);
		$sth->execute(); 

		return true;
	}
}
?>
