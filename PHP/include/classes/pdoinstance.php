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
 
require_once dirname(__FILE__).'/session.php';

/**
 * Auto create new PDO istance 
 */
class PDOinstance {

	/**
	 * @param $dbh - PDO instance
	 */
	public $dbh;
    private $sth;
		
	/**
	 * @param Databese configuration patch 
	 */
	private static $DATABASE_CONFIGURATION = '/etc/imslu/database_config.php';
	
	private static $dsn;
	private static $dbtype;
	private static $dbserver;
	private static $dbname;
	private static $dbuser;
	private static $dbpass;
	private static $driver_options = array(
	PDO::ATTR_EMULATE_PREPARES => false, 
	PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
	PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
	PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING );
	
    public function __construct() {

		if (!isset(self::$dsn, self::$dbuser, self::$dbpass)) {		
		require_once realpath(dirname(self::$DATABASE_CONFIGURATION)).DIRECTORY_SEPARATOR.basename(self::$DATABASE_CONFIGURATION);
			
		self::$dbtype = $dbtype;
		self::$dbserver = $dbserver;
		if (empty(self::$dbserver)) {
    		self::$dbserver = 'localhost';
		}
		self::$dbname = $dbname;
		self::$dbuser = $dbuser;
		self::$dbpass = $dbpass;

		self::$dsn = self::$dbtype .':host='. self::$dbserver .';dbname='. self::$dbname;
		}
		else {

			if (empty(self::$dbserver)) {
    			self::$dbserver = 'localhost';
			}
		}
   		try { 
			$this->dbh = new PDO(self::$dsn, self::$dbuser, self::$dbpass, self::$driver_options);
    	}
    	catch (PDOException $e) {

    		//die ('SQL Error');
			echo $e->getMessage(); 
			exit();
    	}
	}

    public function bind($pos, $value, $type = null, $sth) {
    	
        if (is_null($type)) {
            switch (true) {
                case is_int($value);
                    $type = PDO::PARAM_INT;
                    break;

                case is_bool($value);
                    $type = PDO::PARAM_BOOL;
                    break;

                case (empty($value) || is_null($value));
                    $type = PDO::PARAM_NULL;
                    break;

                default :
                    $type = PDO::PARAM_STR;
            }
        }
        $sth->bindParam($pos, $value, $type);
    }
	
	/**
	 * Use array for PDOStatement
	 * 
	 * @param string $sql - for SQL Querie
	 * @param array @array - for PDOStatement::bindParam
	 */
	function prepare_array($sql, $array) {

		$sth = $this->dbh->prepare($sql);

		$i = 1;
		foreach ($array as &$value) {

			$this->bind($i, $value, null, $sth);
			$i++;
		}
		$sth->execute();
	}

	
	/**
	 * Start or Continue a session 
	 */
    public function start_session_handler() {
		
        $session_name = 'imslu_sessionid';

        $session = new \Session($this->dbh);

        session_set_save_handler($session, true);
        session_register_shutdown('register shutdown');

        // $use_https - set to true if using https
        $use_https = false;
        $httponly = true;

        ini_set('session.gc_probability', 100);
        ini_set('session.gc_divisor', 100);

        ini_set('session.use_only_cookies', 1);

        $params = session_get_cookie_params();
        session_set_cookie_params($params["lifetime"], $params["path"], $params["domain"], $use_https, $httponly);

        session_name($session_name);

        session_start();
    }

	/**
	 * Destroy session 
	 */
    public function destroy_session_handler() {
		
        $session_name = 'imslu_sessionid';

        $session = new \Session($this->dbh);

        session_set_save_handler($session, true);

        $_SESSION = array();

        $params = session_get_cookie_params();
        setcookie(session_name($session_name), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);

        session_destroy();
        header('Location: ./');
    }


    public function __destruct() {
        $this->dbh = null;
    }	
}
?>
