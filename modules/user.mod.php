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
			/* DEVELOPMENT */
			// $this->_method = "POST";
			// $this->_data[DB_COL_USER] = "user01";
			// $this->_data[DB_COL_PWD] = hash(SEC_DATA_ENCRYPTION, "tajemnehaslo");
			// $this->_data[DB_COL_EMAIL] = "user01@simpleapi.com";

			/* user1 */
			// $_SERVER['PHP_AUTH_USER'] = 'b4275b899e66e4961338099e2ff0a4065c07d30ccc3f7ad44d8cee6b197be24453463b68ad5d364b71b4f4bfc0650fe3fd25dada71557bb6f4050bb433e46c50';
			// $_SERVER['PHP_AUTH_PW'] = 'b109f3bbbc244eb82441917ed06d618b9008dd09b3befd1b5e07394c706a8bb980b1d7785e5976ec049b46df5f1326af5a2ea6d103fd07c95385ffab0cacbc86';
			
			/* user2 */
			// $_SERVER['PHP_AUTH_USER'] = '887ad6a742d43ad98d149b3c7f3de605c9bdf43dc148e4519cbfa021833bdba78d2a19eaf7dbd4158447651ee7f75dbcbc1f3a3199137c77f6af066216161397';
			// $_SERVER['PHP_AUTH_PW'] = '48c8ff36caedd22d610d034e8a3ea1b0e8a79d8e046c76a197d452b2689322ccc53350a67682e976e8cdadd8d3fd892beb3e64fddef3418ed11a9bec7ea1cef3';
			
			$this->_setDatabaseRequired(true);
			$this->_setAllowedMethods(['GET', 'POST', 'PUT']);
		}

		public function init() {

			switch($this->_method) {
				case "GET": return $this->getUser();
				case "POST": return $this->createUser();
				case "PUT": return $this->signUser();
			}

			return Response::error(Lang::get('module-invalid-method'), 'module-invalid-method', 'Method Not Allowed');
		}

		public function getUser() {
			/* Get token */
			$token = defined('SEC_TOKEN_HEADER') && SEC_TOKEN_HEADER ? Headers::get('Token') : $_COOKIE['Token'];

			/* Token error */
			if(!$this->_isUserSignedIn()) {
				if(is_null($token)) return Response::error(Lang::get('user-not-signedin'), 'user-not-signedin', 'Unauthorized');
				else return Response::error(Lang::get('user-invalid-token'), 'user-invalid-token', 'Unauthorized');
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
			$this->_setFieldsRequired([DB_COL_USER, DB_COL_PWD, DB_COL_EMAIL]);

			/* Not all required fields available */
			if(!$this->_requiredFieldsPresent()) {
				return Response::error(Lang::get('user-no-requirements'), 'user-no-requirements');
			}

			/* Invalid username */
			if(strlen($this->_data[DB_COL_USER]) < SEC_USER_MINLEN) {
				return Response::error(Lang::get('user-invalid-name'), 'user-invalid-name');
			}

			/* Invalid password */
			if(strlen($this->_data[DB_COL_PWD]) < SEC_PWD_MINLEN) {
				return Response::error(Lang::get('user-invalid-pwd'), 'user-invalid-pwd');
			}

			/* Invalid e-mail */
			if(!filter_var($this->_data[DB_COL_EMAIL], FILTER_VALIDATE_EMAIL)) {
				return Response::error(Lang::get('user-invalid-email'), 'user-invalid-email');
			}

			$u = $this->_data;
			$u[DB_COL_LOGIN] = hash(SEC_DATA_ENCRYPTION, $this->_data[DB_COL_USER]);

			/* Check if user/e-mail exists */
			$query = "SELECT id FROM " . DB_TABLE_USERS . " WHERE " . DB_COL_LOGIN . " = '" . $u[DB_COL_LOGIN] . "'";
			if(SEC_EMAIL_UNIQUE) $query .= " OR " . DB_COL_EMAIL . " = '" . $u[DB_COL_EMAIL] . "'";
			$q = $this->_db->query($query);

			if($q->num_rows > 0) {
				return Response::error(Lang::get('user-not-unique'), 'user-not-unique');
			}

			/* Send confirmation e-mail, if required */
			if(SEC_EMAIL_CONFIRM) {
				$vars = [
					'TPL_USERNAME' => $u[DB_COL_USER],
					'TPL_APPNAME' => SYSTEM_APP_NAME,
					'TPL_ACTURL' => SYSTEM_APP_URL . 'user/activate/' . $u[DB_COL_LOGIN] . '/'
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
				FROM_UNIXTIME('$date'), 
				$isActive)";
			$q = $this->_db->query($query);

			return Response::success(null, 201);
		}

		public function signUser() {
			/* If already signed in, proxy to /GET */
			if($this->_isUserSignedIn()) return $this->getUser();

			/* Check if authorization data exists */
			if(!$_SERVER['PHP_AUTH_USER'] || !$_SERVER['PHP_AUTH_PW']) {
				return Response::error(Lang::get('user-no-data'), 'user-no-data', 400);
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