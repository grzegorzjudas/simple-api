<?php

	namespace Module\user;

	class Module extends \MBase implements \MInterface {
		/* protected $_method */
		/* protected $_params */
		/* protected $_data */

		public function install() {
			$inst = new \InstallScript();

			$inst->setTable('users');
			$inst->setColumn('users', 'id', 'INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY');
			$inst->setColumn('users', 'login', 'VARCHAR(128) NOT NULL');
			$inst->setColumn('users', 'username', 'VARCHAR(128) NOT NULL');
			$inst->setColumn('users', 'password', 'VARCHAR(128) NOT NULL');
			$inst->setColumn('users', 'email', 'VARCHAR(128) NOT NULL');
			$inst->setColumn('users', 'access_level', 'TINYINT(1) NOT NULL');
			$inst->setColumn('users', 'created', 'DATETIME NOT NULL');
			$inst->setColumn('users', 'activated', 'TINYINT(1) NOT NULL');

			$inst->setTable('sessions');
			$inst->setColumn('sessions', 'id', 'INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY');
			$inst->setColumn('sessions', 'users_id', 'INT(11) UNSIGNED');
			$inst->setColumn('sessions', 'token', 'VARCHAR(128) NOT NULL');
			$inst->setColumn('sessions', 'created', 'DATETIME NOT NULL');
			$inst->setColumn('sessions', 'last_used', 'DATETIME NOT NULL');
			$inst->setColumn('sessions', 'ip', 'VARCHAR(15)');
			$inst->setIndex('sessions', 'users_id');
			$inst->setRelation('sessions.users_id', 'users.id');

			return $inst;
		}

		public function setRequirements() {
			$this->_setDatabaseRequired(true);
			$this->_setAllowedMethods(['GET', 'POST', 'PUT']);
			$this->_setStrictRouteMode(true);

			$this->_addRoute('login/', 'PUT', 'signIn');
			$this->_addRoute('register/', 'POST', 'create');
			$this->_addRoute('activate/:login/', 'GET', 'activate');
			$this->_addRoute('/', 'GET', 'getFromToken');
		}

		public function init() {
			switch($this->_method) {
				case "GET": return $this->getFromToken();
				case "POST": return $this->create();
				case "PUT": return $this->signIn();
				default: return \Response::error('module-invalid-method', 'Method Not Allowed');
			}
		}

		public function getFromToken($token = null) {
			/* Get token if not provided */
			if(is_null($token)) {
				$token = SEC_TOKEN_HEADER ? \Headers::get('Token') : $_COOKIE['Token'];
			}

			/* Token error */
			if(is_null($token)) return \Response::error('user-not-signedin');
			if($this->_isUserSignedIn() === false) {
				if(!$this->_getUserSession($token, true)) return \Response::error('user-invalid-token');
				else return \Response::error('user-expired-token');
			}

			/* Fetch user data */
			$userid = $this->_getUserSession($token)[DB_TABLE_USERS . '_id'];
			$query = "SELECT * FROM " . DB_TABLE_USERS . " WHERE id = '" . $userid . "'";

			$q = $this->_db->query($query);
			$result = $q->fetch_assoc();

			/* Filter final object */
			return array_toint($result);
		}

		public function getByParam($param, $value) {
			$query = "SELECT * FROM " . DB_TABLE_USERS . " WHERE " . $param . " = '" . $value . "'";
			$q = $this->_db->query($query);

			return array_toint($q->fetch_assoc());
		}

		public function create() {
			$this->_setFieldsRequired([DB_COL_USERS_USERNAME, DB_COL_USERS_PASSWORD, DB_COL_USERS_EMAIL]);

			/* Not all required fields available */
			if(!$this->_requiredFieldsPresent()) {
				return \Response::verror('user-no-field', ['FNAME' => $this->_getMissingField()]);
			}

			/* Invalid username */
			if(strlen($this->_data[DB_COL_USERS_USERNAME]) < SEC_USER_MINLEN) {
				return \Response::verror('user-invalid-namelen', ['NLEN' => SEC_USER_MINLEN]);
			}

			/* Invalid password */
			if(strlen($this->_data[DB_COL_USERS_PASSWORD]) < SEC_PWD_MINLEN) {
				return \Response::verror('user-invalid-pwdlen', ['PLEN' => SEC_PWD_MINLEN]);
			}

			/* Invalid e-mail */
			if(!filter_var($this->_data[DB_COL_USERS_EMAIL], FILTER_VALIDATE_EMAIL)) {
				return \Response::error('user-invalid-email');
			}

			$u = $this->_data;
			$u[DB_COL_USERS_LOGIN] = hash(SEC_DATA_ENCRYPTION, $this->_data[DB_COL_USERS_USERNAME]);

			/* Check if user/e-mail exists */
			$query = "SELECT id FROM " . DB_TABLE_USERS . " WHERE " . DB_COL_USERS_LOGIN . " = '" . $u[DB_COL_USERS_LOGIN] . "'";
			if(SEC_EMAIL_UNIQUE) $query .= " OR " . DB_COL_USERS_EMAIL . " = '" . $u[DB_COL_USERS_EMAIL] . "'";
			$q = $this->_db->query($query);

			if($q->num_rows > 0) {
				if(SEC_EMAIL_UNIQUE) return \Response::error('user-not-elunique');
				else return \Response::error('user-not-lunique');
			}

			/* Send confirmation e-mail, if required */
			if(SEC_EMAIL_CONFIRM) {
				$vars = [
					'TPL_USERNAME' => $u[DB_COL_USERS_USERNAME],
					'TPL_APPNAME' => SYSTEM_APP_NAME,
					'TPL_ACTURL' => SYSTEM_APP_URL . 'activate/' . $u[DB_COL_USERS_LOGIN] . '/'
				];
				$tpl = $this->_getTemplate('mail_register', $vars);

				$headers = "";
				$headers .= "From: " . SYSTEM_APP_REGMAIL . "\n";
				$headers .= "MIME-Version: 1.0\n";
				$headers .= "Content-Type: text/html; charset=" . SYSTEM_CHARSET_DEFAULT . "";

				mail($u[DB_COL_USERS_EMAIL], \Lang::get('user-register-mailtopic'), $tpl, $headers);
			}

			$isActive = !SEC_EMAIL_CONFIRM ? 1 : 0;
			$date = time();

			$query = "INSERT INTO " . DB_TABLE_USERS . " VALUES(
				null, 
				'{$u[DB_COL_USERS_LOGIN]}', 
				'{$u[DB_COL_USERS_USERNAME]}', 
				'{$u[DB_COL_USERS_PASSWORD]}', 
				'{$u[DB_COL_USERS_EMAIL]}', 
				0, 
				FROM_UNIXTIME('$date'), 
				$isActive)";
			$q = $this->_db->query($query);

			if(SEC_EMAIL_CONFIRM) return \Response::success(\Lang::get('user-register-mailsent'), 201);
			else return \Response::success(\Lang::get('user-register-created'), 201);
		}

		public function isActivated($login) {
			$query = "SELECT " . DB_COL_USERS_ACTIVATED . " FROM " . DB_TABLE_USERS . " WHERE " . DB_COL_USERS_LOGIN . " = '" . $login . "'";
			$q = $this->_db->query($query);

			return $q->fetch_assoc()[DB_COL_USERS_ACTIVATED];
		}

		public function activate($login = null) {
			if(is_null($login)) {
				$login = $this->_route['login'];
			}

			if($this->isActivated($login)) {
				return \Response::error('user-not-inactive');
			}

			$query = "SELECT " . DB_COL_USERS_ID . " FROM " . DB_TABLE_USERS . " WHERE " . DB_COL_USERS_LOGIN . " = '" . $login . "'";
			$q = $this->_db->query($query);

			if($q->num_rows === 0) {
				return \Response::error('user-not-exist');
			}

			$query = "UPDATE " . DB_TABLE_USERS . " SET " . DB_COL_USERS_ACTIVATED . " = 1 WHERE " . DB_COL_USERS_LOGIN . " = '" . $login . "'";
			$q = $this->_db->query($query);

			return \Response::success(\Lang::get('user-register-activated'));
		}

		public function signIn() {
			/* If already signed in, proxy to /GET */
			if($this->_isUserSignedIn()) return $this->getFromToken();

			/* Check if authorization data exists */
			if(!$_SERVER['PHP_AUTH_USER'] || !$_SERVER['PHP_AUTH_PW']) {
				return \Response::error('user-no-data');
			}

			/* Find the user with specified login */
			$query = "SELECT * FROM " . DB_TABLE_USERS . " WHERE " . DB_COL_USERS_LOGIN . " = '" . $_SERVER['PHP_AUTH_USER'] . "'";
			$q = $this->_db->query($query);

			if(!$q || $q->num_rows === 0) {
				return \Response::error('user-not-exist');
			}

			/* Check password */
			$result = $q->fetch_assoc();
			if($result[DB_COL_USERS_PASSWORD] !== $_SERVER['PHP_AUTH_PW']) {
				return \Response::error('user-invalid-pwd');
			}

			/* Is activated */
			if(SEC_EMAIL_CONFIRM && $result[DB_COL_USERS_ACTIVATED] === '0') {
				return \Response::error('user-not-active');
			}

			/* Create and save token to db */
			$time = time();
			$token = $this->createToken($result[DB_COL_USERS_EMAIL] . ':' . $_SERVER['REMOTE_ADDR'] . ':' . $time);

			$query = "INSERT INTO " . DB_TABLE_SESSIONS . " VALUES(null, $result[id], '$token', FROM_UNIXTIME('$time'), FROM_UNIXTIME('$time'), '$_SERVER[REMOTE_ADDR]')";
			$q = $this->_db->query($query);

			/* Set Token header or cookie */
			if(defined('SEC_TOKEN_HEADER') && SEC_TOKEN_HEADER) \Headers::set('Token', $token);
			else setcookie('Token', $token, SEC_TOKEN_LIFETIME);

			/* Filter final value */
			return [
				'token' => $token,
				'username' => $result[DB_COL_USERS_USERNAME],
				'email' => $result[DB_COL_USERS_EMAIL]
			];
		}

		public function createToken($string) {
			return hash(SEC_DATA_ENCRYPTION, $string);
		}
	}