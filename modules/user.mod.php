<?php

	namespace Module\user;

	class Module extends \MBase implements \MInterface {
		/* protected $_method */
		/* protected $_params */
		/* protected $_data */

		public function setRequirements() {
			$this->_setDatabaseRequired(true);
			$this->_setAllowedMethods(['GET', 'POST', 'PUT']);
		}

		public function init() {
			/* DEVELOPMENT */
			// $this->_method = "PUT";
			// $_SERVER['PHP_AUTH_USER'] = 'b4275b899e66e4961338099e2ff0a4065c07d30ccc3f7ad44d8cee6b197be24453463b68ad5d364b71b4f4bfc0650fe3fd25dada71557bb6f4050bb433e46c50';
			// $_SERVER['PHP_AUTH_PW'] = 'b109f3bbbc244eb82441917ed06d618b9008dd09b3befd1b5e07394c706a8bb980b1d7785e5976ec049b46df5f1326af5a2ea6d103fd07c95385ffab0cacbc86';

			switch($this->_method) {
				case "GET": return $this->getUser();
				case "POST": return $this->createUser();
				case "PUT": return $this->signUser();
			}

			return \Response::error(\Lang::get('module-invalid-method'), 'module-invalid-method', 405);
		}

		public function getUser() {
			if(defined('SEC_TOKEN_HEADER') && SEC_TOKEN_HEADER) $token = \Headers::get('Token');
			else $token = $_COOKIE['Token'];

			if(!$this->_isUserSignedIn()) {
				if(is_null($token)) return \Response::error(\Lang::get('user-not-signedin'), 'user-not-signedin', 401);
				else return \Response::error(\Lang::get('user-invalid-token'), 'user-invalid-token', 401);
			}

			$table_users = defined('DB_TABLE_USERS') ? DB_TABLE_USERS : 'users';

			$query = "SELECT * FROM $table_users WHERE id = {$this->_getUserSession($token)[$table_users . '_id']}";
			$q = $this->_db->query($query);
			
			$result = array_toint($q->fetch_assoc());

			return [
				'username' => $result['username'],
				'email' => $result['email']
			];
		}

		public function createUser() {
			
		}

		public function signUser() {
			/* If already signed in, proxy to /GET */
			if($this->_isUserSignedIn()) return $this->getUser();

			/* Check if authorization data exists */
			if(!$_SERVER['PHP_AUTH_USER'] || !$_SERVER['PHP_AUTH_PW']) {
				return \Response::error(\Lang::get('user-no-data'), 'user-no-data', 400);
			}

			/* Validate SQL table/column names */
			$table_users = defined('DB_TABLE_USERS') ? DB_TABLE_USERS : 'users';
			$table_sessions = defined('DB_TABLE_SESSIONS') ? DB_TABLE_SESSIONS : 'sessions';
			$col_login = defined('DB_COL_LOGIN') ? DB_COL_LOGIN : 'login';
			$col_username = defined('DB_COL_USER') ? DB_COL_USER : 'username';
			$col_password = defined('DB_COL_PASSWORD') ? DB_COL_PASSWORD : 'password';
			$col_email = defined('DB_COL_EMAIL') ? DB_COL_EMAIL : 'email';

			/* Find the user with specified login */
			$query = "SELECT * FROM $table_users WHERE $col_login = '$_SERVER[PHP_AUTH_USER]'";
			$q = $this->_db->query($query);

			if(!$q || $q->num_rows === 0) return false;

			/* Check password */
			$result = $q->fetch_assoc();
			if($result[$col_password] !== $_SERVER['PHP_AUTH_PW']) return false;

			/* Create and save token to db */
			$time = time();
			$token = $this->createToken($result[$col_email] . ':' . $_SERVER['REMOTE_ADDR'] . ':' . $time);

			$query = "INSERT INTO $table_sessions VALUES(null, $result[id], '$token', FROM_UNIXTIME('$time'), FROM_UNIXTIME('$time'), '$_SERVER[REMOTE_ADDR]')";
			$q = $this->_db->query($query);

			/* Set Token header or cookie */
			if(defined('SEC_TOKEN_HEADER') && SEC_TOKEN_HEADER) \Headers::set('Token', $token);
			else setcookie('Token', $token, defined('SEC_TOKEN_LIFETIME') ? SEC_TOKEN_LIFETIME : 86400);

			return [
				'username' => $result['username'],
				'email' => $result['email']
			];
		}

		public function createToken($string) {
			$enc = defined('SEC_DATA_ENCRYPTION') ? SEC_DATA_ENCRYPTION : 'sha512';

			return hash($enc, $string);
		}
	}