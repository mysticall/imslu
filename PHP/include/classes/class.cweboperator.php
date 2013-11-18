<?php
class CWebOperator {

	// ******************************************************************************
	// LOGIN
	// ******************************************************************************

	public static $data = null;

	public static function login($alias, $password) {

		$ip = $_SERVER['REMOTE_ADDR'];
		$browser = $_SERVER['HTTP_USER_AGENT'];

		$coperators = new COperator();

		self::$data = $coperators->login(array(
			'alias' => $alias,
			'password' => $password,
			'ip' => $ip,
			'browser' => $browser,
			'operatorData' => true));

		if (empty(self::$data)) {

			return false;
		}
		else {

            require_once dirname(__FILE__).'../../config.inc.php';
			$db = new CPDOinstance();

			// Add audit
			add_audit($db, AUDIT_ACTION_LOGIN, AUDIT_RESOURCE_SYSTEM, "Correct login $alias.");

			return true;
		}
	}

	public static function checkAuthentication($sessionid) {

		if (!empty($sessionid)) {

			$coperators = new COperator();

			self::$data = $coperators->checkAuthentication($sessionid);

			if (!empty(self::$data)) {

				return true;
			}
			else {
				
				return false;
			}
		}
		else {
				
			return false;
		}
	}
}
