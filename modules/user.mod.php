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
			$_SERVER['PHP_AUTH_USER'] = 'b4275b899e66e4961338099e2ff0a4065c07d30ccc3f7ad44d8cee6b197be24453463b68ad5d364b71b4f4bfc0650fe3fd25dada71557bb6f4050bb433e46c50';
			$_SERVER['PHP_AUTH_PW'] = 'b109f3bbbc244eb82441917ed06d618b9008dd09b3befd1b5e07394c706a8bb980b1d7785e5976ec049b46df5f1326af5a2ea6d103fd07c95385ffab0cacbc86';

			switch($this->_method) {
				case "GET": return $this->getUser();
				case "POST": return $this->createUser();
				case "PUT": return $this->signUser();
			}

			return \Response::error(\Lang::get('module-invalid-method'), 'module-invalid-method', 405);
		}

		public function getUser() {
			/* Get token */
			$token = defined('SEC_TOKEN_HEADER') && SEC_TOKEN_HEADER ? \Headers::get('Token') : $_COOKIE['Token'];

			/* Token error */
			if(!$this->_isUserSignedIn()) {
				if(is_null($token)) return \Response::error(\Lang::get('user-not-signedin'), 'user-not-signedin', 401);
				else return \Response::error(\Lang::get('user-invalid-token'), 'user-invalid-token', 401);
			}

			/* Fetch user data */
			$userid = $this->_getUserSession($token)[DB_TABLE_USERS . '_id'];
			$query = "SELECT username, email FROM " . DB_TABLE_USERS . " WHERE id = '" . $userid . "'";

			$q = $this->_db->query($query);
			$result = $q->fetch_assoc();

			/* Filter final object */
			return [
				'username' => $result['username'],
				'email' => $result['email']
			];
		}

		public function createUser() {
			return [];
		}

		public function signUser() {
			/* If already signed in, proxy to /GET */
			if($this->_isUserSignedIn()) return $this->getUser();

			/* Check if authorization data exists */
			if(!$_SERVER['PHP_AUTH_USER'] || !$_SERVER['PHP_AUTH_PW']) {
				return \Response::error(\Lang::get('user-no-data'), 'user-no-data', 400);
			}

			/* Find the user with specified login */
			$query = "SELECT * FROM " . DB_TABLE_USERS . " WHERE " . DB_COL_LOGIN . " = '" . $_SERVER['PHP_AUTH_USER'] . "'";
			$q = $this->_db->query($query);

			if(!$q || $q->num_rows === 0) return false;

			/* Check password */
			$result = $q->fetch_assoc();
			if($result[DB_COL_PWD] !== $_SERVER['PHP_AUTH_PW']) return false;

			/* Create and save token to db */
			$time = time();
			$token = $this->createToken($result[DB_COL_EMAIL] . ':' . $_SERVER['REMOTE_ADDR'] . ':' . $time);

			$query = "INSERT INTO " . DB_TABLE_SESSIONS . " VALUES(null, $result[id], '$token', FROM_UNIXTIME('$time'), FROM_UNIXTIME('$time'), '$_SERVER[REMOTE_ADDR]')";
			$q = $this->_db->query($query);

			/* Set Token header or cookie */
			if(defined('SEC_TOKEN_HEADER') && SEC_TOKEN_HEADER) \Headers::set('Token', $token);
			else setcookie('Token', $token, SEC_TOKEN_LIFETIME);

			/* Filter final value */
			return [
				'token' => $token,
				'username' => $result[DB_COL_USER],
				'email' => $result[DB_COL_EMAIL]
			];
		}

		public function createToken($string) {
			return hash(SEC_DATA_ENCRYPTION, $string);
		}
	}