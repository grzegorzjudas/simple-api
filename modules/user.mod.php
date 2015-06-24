<?php

	namespace Module\user;

	use \Response;
	use \Lang;
	use \Headers;
	use \SimpleAPI;

	class Module extends \MBase implements \MInterface {
		/* protected $_method */
		/* protected $_params */
		/* protected $_data */

		public function setRequirements() {			
			$this->_setDatabaseRequired(true);
			$this->_setAllowedMethods(['GET', 'POST', 'PUT']);
		}

		public function init() {
			/* Development */
			// $this->_method = "POST";
			// $this->_data['username'] = 'username04';
			// $this->_data['password'] = 'abcbac';
			// $this->_data['email'] = 'username01a@a.a';

			switch($this->_method) {
				case "GET": return $this->get();
				case "POST": return $this->create();
				case "PUT": return $this->signIn();
				default: return Response::error('module-invalid-method', 'Method Not Allowed');
			}
		}

		public function get($token = null) {
			/* Get token */
			if(is_null($token)) {
				$token = SEC_TOKEN_HEADER ? Headers::get('Token') : $_COOKIE['Token'];
			}

			/* Token error */
			if(!$this->_isUserSignedIn()) {
				if(is_null($token)) return Response::error('user-not-signedin');
				else {
					if(!$this->_getUserSession($token, true)) return Response::error('user-invalid-token');
					else return Response::error('user-expired-token');
				}
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

		public function create() {
			$this->_setFieldsRequired([DB_COL_USER, DB_COL_PWD, DB_COL_EMAIL]);

			/* Not all required fields available */
			if(!$this->_requiredFieldsPresent()) {
				return Response::verror('user-no-field', ['FNAME' => $this->_getMissingField()]);
			}

			/* Invalid username */
			if(strlen($this->_data[DB_COL_USER]) < SEC_USER_MINLEN) {
				return Response::verror('user-invalid-namelen', ['NLEN' => SEC_USER_MINLEN]);
			}

			/* Invalid password */
			if(strlen($this->_data[DB_COL_PWD]) < SEC_PWD_MINLEN) {
				return Response::verror('user-invalid-pwdlen', ['PLEN' => SEC_PWD_MINLEN]);
			}

			/* Invalid e-mail */
			if(!filter_var($this->_data[DB_COL_EMAIL], FILTER_VALIDATE_EMAIL)) {
				return Response::error('user-invalid-email');
			}

			$u = $this->_data;
			$u[DB_COL_LOGIN] = hash(SEC_DATA_ENCRYPTION, $this->_data[DB_COL_USER]);

			/* Check if user/e-mail exists */
			$query = "SELECT id FROM " . DB_TABLE_USERS . " WHERE " . DB_COL_LOGIN . " = '" . $u[DB_COL_LOGIN] . "'";
			if(SEC_EMAIL_UNIQUE) $query .= " OR " . DB_COL_EMAIL . " = '" . $u[DB_COL_EMAIL] . "'";
			$q = $this->_db->query($query);

			if($q->num_rows > 0) {
				if(SEC_EMAIL_UNIQUE) return Response::error('user-not-elunique');
				else return Response::error('user-not-lunique');
			}

			/* Send confirmation e-mail, if required */
			if(SEC_EMAIL_CONFIRM) {
				$vars = [
					'TPL_USERNAME' => $u[DB_COL_USER],
					'TPL_APPNAME' => SYSTEM_APP_NAME,
					'TPL_ACTURL' => SYSTEM_APP_URL . 'activate/' . $u[DB_COL_LOGIN] . '/'
				];
				$tpl = $this->_getTemplate('mail_register', $vars);

				$headers = "";
				$headers .= "From: " . SYSTEM_APP_REGMAIL . "\n";
				$headers .= "MIME-Version: 1.0\n";
				$headers .= "Content-Type: text/html; charset=" . SYSTEM_CHARSET_DEFAULT . "";

				mail($u[DB_COL_EMAIL], Lang::get('user-register-mailtopic'), $tpl, $headers);
			}

			$isActive = !SEC_EMAIL_CONFIRM ? 1 : 0;
			$date = time();

			$query = "INSERT INTO " . DB_TABLE_USERS . " VALUES(
				null, 
				'{$u[DB_COL_LOGIN]}', 
				'{$u[DB_COL_USER]}', 
				'{$u[DB_COL_PWD]}', 
				'{$u[DB_COL_EMAIL]}', 
				0, 
				FROM_UNIXTIME('$date'), 
				$isActive)";
			$q = $this->_db->query($query);

			return Response::success(null, 201);
		}

		public function isActivated($login) {
			$query = "SELECT " . DB_COL_ACTIVATED . " FROM " . DB_TABLE_USERS . " WHERE " . DB_COL_LOGIN . " = '" . $login . "'";
			$q = $this->_db->query($query);

			return $q->fetch_assoc()[DB_COL_ACTIVATED];
		}

		public function activate($login) {
			if($this->isActivated($login)) {
				return Response::error('user-not-inactive');
			}

			$query = "UPDATE " . DB_TABLE_USERS . " SET " . DB_COL_ACTIVATED . " = 1 WHERE " . DB_COL_LOGIN . " = '" . $login . "'";
			$q = $this->_db->query($query);

			return Response::success(null);
		}

		public function signIn() {
			/* If already signed in, proxy to /GET */
			if($this->_isUserSignedIn()) return $this->getUser();

			/* Check if authorization data exists */
			if(!$_SERVER['PHP_AUTH_USER'] || !$_SERVER['PHP_AUTH_PW']) {
				return Response::error('user-no-data');
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
			if(defined('SEC_TOKEN_HEADER') && SEC_TOKEN_HEADER) Headers::set('Token', $token);
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