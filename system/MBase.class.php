<?php

	class MBase {
		protected $_params = [];
		protected $_method;
		protected $_data;
		protected $_db;

		private $rDatabase = false;
		private $rUser = false;
		private $rMethods = ['DELETE', 'GET', 'OPTIONS', 'POST', 'PUT'];

		public function __construct($params, $method = null) {
			$this->_params = $params;
			$this->_method = is_null($method) ? $_SERVER['REQUEST_METHOD'] : $method;
			$this->_data = $this->_parseData();

			if(DB::isConnected()) $this->_db = DB::getConnection();
		}

		public function requirementsResult() {
			/* Check database connection */
			if($this->rDatabase && !$this->_isDatabaseConnected()) {
				return \Response::error(\Lang::get('db-not-connected'), 'db-not-connected', 500);
			}

			/* Check whether user is signed in */
			if($this->rUser && !$this->_isUserSignedIn()) {
				return \Response::error(\Lang::get('user-not-signedin'), 'user-not-signedin', 401);
			}

			/* Check if module allows HTTP method used */
			if(!$this->_isMethodAllowed()) {
				Headers::set('Allow', $this->_getAllowedMethods());

				return \Response::error(\Lang::get('module-invalid-method'), 'system-invalid-method', 405);
			}

			return true;
		}

		protected function _isMethodAllowed() {
			return in_array($this->_method, $this->rMethods);
		}

		protected function _isDatabaseConnected() {
			return DB::isConnected();
		}

		protected function _isUserSignedIn() {
			if(defined('SEC_TOKEN_HEADER') && SEC_TOKEN_HEADER) $token = Headers::get('Token');
			else $token = $_COOKIE['Token'];

			if(!$this->_isDatabaseConnected()) return false;
			if(is_null($token)) return false;

			$userdata = $this->_getUserSession($token);

			if(!$userdata) return false;
			else return true;
		}

		protected function _getModuleName() {
			return explode('\\', get_class($this))[1];
		}

		protected function _getAllowedMethods() {
			return $this->rMethods;
		}

		protected function _getUserSession($token) {
			/* Validate SQL table/column names */
			$token_lifetime = defined('SEC_TOKEN_LIFETIME') ? SEC_TOKEN_LIFETIME : 86400;
			$table_sessions = defined('DB_TABLE_SESSIONS') ? DB_TABLE_SESSIONS : 'sessions';
			$col_token = defined('DB_COL_TOKEN') ? DB_COL_TOKEN : 'token';
			$col_created = defined('DB_COL_CREATED') ? DB_COL_CREATED : 'created';
			$col_lastused = defined('DB_COL_LASTUSED') ? DB_COL_LASTUSED : 'last_used';

			/* Get the token */
			$query = "SELECT * FROM $table_sessions WHERE $col_token = '$token' AND $col_created < NOW() AND $col_created + INTERVAL $token_lifetime SECOND > NOW()";
			$q = $this->_db->query($query);

			if(!$q || $q->num_rows === 0) return false;

			$result = $q->fetch_assoc();

			/* Token valid, update last used time */
			$query = "UPDATE $table_sessions SET $col_lastused = NOW() WHERE id = {$result[id]}";
			$this->_db->query($query);

			return $result;
		}

		protected function _setAllowedMethods($methods) {
			if(gettype($methods) === 'string') $methods = [ $methods ];

			sort($methods);
			$methods = array_unique($methods);
			$this->rMethods = $methods;
		}

		protected function _setDatabaseRequired($val) {
			$this->rDatabase = !!$val;
		}

		protected function _setUserRequired($val) {
			$this->rUser = !!$val;
		}

		private function _parseData() {
			$data = [];

			if($this->_method == "PUT" || $this->_method == "DELETE") parse_str(file_get_contents("php://input"), $data);
			if($this->_method == "POST") $data = $_POST;
			if($this->_method == "GET") $data = $_GET;

			if(!is_null($data["data"])) $data = $data["data"];
			unset($data['type']);

			return $data;
		}
	}

function array_toint($arr) {
	foreach($arr as $index => $el) {
		if(is_numeric($el)) $arr[$index] = intval($el);
	}

	return $arr;
}