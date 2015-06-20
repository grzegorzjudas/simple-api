<?php

	class MBase {
		protected $_params = [];
		protected $_method;
		protected $_data;
		protected $_allowedMethods = ['DELETE', 'GET', 'OPTIONS', 'POST', 'PUT'];

		public function __construct($params) {
			$this->_params = $params;
			$this->_method = $_SERVER['REQUEST_METHOD'];
			$this->_data = $this->_parseData();
		}

		public function _isMethodAllowed() {
			return in_array($this->_getUsedMethod(), $this->_allowedMethods);
		}

		public function _getModuleName() {
			return explode('\\', get_class($this))[1];
		}

		public function _getAllowedMethods() {
			return $this->_allowedMethods;
		}

		public function _getUsedMethod() {
			return $this->_method;
		}

		protected function _setAllowedMethods($methods) {
			if(gettype($methods) === 'string') $methods = [ $methods ];

			sort($methods);
			$methods = array_unique($methods);
			$this->_allowedMethods = $methods;
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