<?php

	class MBase {
		protected $_params = [];
		protected $_method;
		protected $_data;
		protected $_db;

		private $rDatabase = false;
		private $rUser = false;
		private $rMethods = ['DELETE', 'GET', 'OPTIONS', 'POST', 'PUT'];

		public function __construct($params) {
			$this->_params = $params;
			$this->_method = $_SERVER['REQUEST_METHOD'];
			$this->_data = $this->_parseData();

			if(DB::isConnected()) $this->_db = DB::getConnection();
		}

		public function _isMethodAllowed() {
			return in_array($this->_getUsedMethod(), $this->rMethods);
		}

		protected function _isDatabaseConnected() {
			return DB::isConnected();
		}

		protected function _isUserSignedIn() {
			if(is_null(Headers::get('Token'))) return false;
			if(!$this->_isDatabaseConnected()) return false;

			return true;
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

		public function _getModuleName() {
			return explode('\\', get_class($this))[1];
		}

		public function _getAllowedMethods() {
			return $this->rMethods;
		}

		public function _getUsedMethod() {
			return $this->_method;
		}

		protected function _setDatabaseRequired($val) {
			$this->rDatabase = !!$val;
		}

		protected function _setUserRequired($val) {
			$this->rUser = !!$val;
		}

		protected function _setAllowedMethods($methods) {
			if(gettype($methods) === 'string') $methods = [ $methods ];

			sort($methods);
			$methods = array_unique($methods);
			$this->rMethods = $methods;
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