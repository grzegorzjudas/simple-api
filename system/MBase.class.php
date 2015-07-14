<?php

	class MBase {
		/* Available for modules */
		protected $_params = [];
		protected $_route = [];
		protected $_method;
		protected $_data;
		protected $_db;

		/* For SimpleAPI system use */
		public $callFunction = 'init';
		
		/* Internal MBase variables */
		private $routes = [];

		/* Module requirements */
		private $rDatabase = false;
		private $rUser = false;
		private $rRoute = false;
		private $rFields = [];
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
				return Response::error('db-not-connected', 500);
			}

			/* Check whether user is signed in */
			if($this->rUser && !$this->_isUserSignedIn()) {
				return Response::error('user-not-signedin', 401);
			}

			/* Check if module allows HTTP method used */
			if(!$this->_isMethodAllowed()) {
				Headers::set('Allow', $this->_getAllowedMethods());

				return Response::error('module-invalid-method', 405);
			}

			/* Check if all fields are present */
			if(!$this->_requiredFieldsPresent()) {
				return Response::error('module-no-requirements');
			}

			/* Route whitelist enabled, and route did not match */
			if($this->rRoute && !$this->_isValidRoute()) {
				return Response::error('module-invalid-route', 404);
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
			/* Get token */
			$token = SEC_TOKEN_HEADER ? Headers::get('Token') : $_COOKIE['Token'];

			/* Cannot be signed in if no db connection or empty token */
			if(!$this->_isDatabaseConnected()) return false;
			if(is_null($token)) return false;

			return !!$this->_getUserSession($token);
		}

		protected function _isValidRoute() {
			if(!$this->rRoute) return true;

			$currentRoute = explode('/', $_SERVER['REQUEST_URI']);
			unset($currentRoute[array_search($this->_getModuleName(), $currentRoute)]);
			$currentRoute = implode('/', $currentRoute);
			if(substr($currentRoute, -1) !== '/') $currentRoute = $currentRoute . '/';

			foreach ($this->routes as $route) {
				$rMatch = explode('/', $route['route']);
				$rFind = explode('/', $currentRoute);
				$this->_route = [];

				if(count($rMatch) !== count($rFind)) continue;
				if(!in_array($this->_method, $route['methods'])) continue;

				foreach ($rMatch as $index => $param) {
					if(substr($param, 0, 1) !== ':') {
						if($param !== $rFind[$index]) break;
						else {
							if($index === count($rMatch)-1) {
								$this->callFunction = $route['f'];
								return true;
							}
							else continue;
						}
					}
					else {
						$this->_route[substr($param, 1)] = $rFind[$index];
						if($index === count($rMatch)-1) {
							$this->callFunction = $route['f'];
							return true;
						}
						else continue;
					}
				}
			}

			return in_array($currentRoute, $this->routes);
		}

		protected function _requiredFieldsPresent() {
			foreach($this->rFields as $fieldKey) {
				if(is_null($this->_data[$fieldKey])) return false;
			}

			return true;
		}

		protected function _getModuleName() {
			return explode('\\', get_class($this))[1];
		}

		protected function _getAllowedMethods() {
			return $this->rMethods;
		}

		protected function _getUserSession($token, $allowExpired = false) {
			/* Get the token */
			$query = "SELECT * FROM " . DB_TABLE_SESSIONS . " WHERE " . DB_COL_SESSIONS_TOKEN . " = '" . $token . "'";

			if(!$allowExpired) {
				$query .= " AND " . DB_COL_SESSIONS_CREATED . " < NOW() AND " . DB_COL_SESSIONS_CREATED . " + INTERVAL " . SEC_TOKEN_LIFETIME . " SECOND > NOW()";
			}
			$q = $this->_db->query($query);
			
			if(!$q || $q->num_rows === 0) return false;
			$result = $q->fetch_assoc();

			/* Token valid, update last used time */
			$query = "UPDATE $table_sessions SET $col_lastused = NOW() WHERE id = {$result[id]}";
			$this->_db->query($query);

			return $result;
		}

		protected function _getTemplate($name, $vars) {
			$tpl = file_get_contents('templates/' . $name . '.html');

			foreach ($vars as $key => $var) {
				$tpl = str_replace('{{' . $key . '}}', $var, $tpl);
			}

			return $tpl;
		}

		protected function _getMissingField() {
			foreach($this->rFields as $fieldKey) {
				if(is_null($this->_data[$fieldKey])) return $fieldKey;
			}

			return '';
		}

		protected function _setAllowedMethods($methods) {
			if(gettype($methods) === 'string') $methods = [ $methods ];

			sort($methods);
			$methods = array_unique($methods);
			$this->rMethods = $methods;
		}

		protected function _setStrictRouteMode($bool) {
			$this->rRoute = !!$bool;
		}

		protected function _setDatabaseRequired($bool) {
			$this->rDatabase = !!$bool;
		}

		protected function _setUserRequired($bool) {
			$this->rUser = !!$bool;
		}

		protected function _setFieldsRequired($arr) {
			$this->rFields = $arr;
		}

		protected function _addRoute($route, $methods = null, $f = 'init') {
			if(substr($route, 0, 1) !== '/') $route = '/' . $route;
			if(substr($route, -1, 1) !== '/') $route = $route . '/';

			if(is_null($methods) || $methods === '*') $methods = ['DELETE', 'GET', 'OPTIONS', 'POST', 'PUT'];
			if(gettype($methods) === 'string') $methods = [ $methods ];
			if(is_null($f)) $f = 'init';

			$this->routes[] = [
				'route' => $route,
				'methods' => $methods,
				'f' => $f
			];
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
	if(gettype($arr) !== 'array') return $arr;

	foreach($arr as $index => $el) {
		if(is_numeric($el)) $arr[$index] = intval($el);
	}

	return $arr;
}