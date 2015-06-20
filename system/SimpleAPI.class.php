<?php

	require 'system/Response.class.php';
	require 'system/Headers.class.php';
	require 'system/Lang.class.php';
	require 'system/Responders.class.php';
	require 'system/MBase.class.php';
	require 'system/MInterface.int.php';

	class SimpleAPI {
		private $_headers;
		private $_url;
		private $prefferedResponseType;
		private $responseCode = 200;

		public function __construct() {
			$this->_url = SimpleAPI::parseUrl();
			$this->_headers = new Headers();

			Responders::load();
			Lang::load($this->_headers->get('Accept-Language'));
		}

		public static function loadModule($name, $params = []) {
			$moduleName = 'Module\\' . $name . '\\Module';
			if(gettype($params) === 'string') $params = SimpleAPI::parseUrl($params);

			/* If module already loaded, prevent recursive calling */
			if(class_exists($moduleName)) {
				if(array_search($moduleName, array_column(debug_backtrace(), 'class')) !== false) {
					return Response::error(Lang::get('module-cannot-callback'), 'module-cannot-callback', 500);
				}
			}
			else {
				/* File not found */
				if(!file_exists('modules/' . $name . '.mod.php')) {
					return Response::error(Lang::get('module-not-found'), 'module-not-found', 404);
				}

				include 'modules/' . $name . '.mod.php';

				/* No appropriate module definition in the file */
				if(!class_exists($moduleName)) {
					return Response::error(Lang::get('module-no-module'), 'module-no-module', 500);
				}

				/* Invalid module, does not implement basic interface */
				if(!in_array('MInterface', class_implements($moduleName))) {
					return Response::error(Lang::get('module-invalid-declaration'), 'module-invalid-declaration', 500);
				}
			}

			$module = new $moduleName($params);
			$result = $module->init();

			if($result instanceof Response) return $result;
			else return Response::success($module->init());
		}

		public static function parseUrl($string = null) {
			if(is_null($string)) $string = $_SERVER['REQUEST_URI'];

			$url = explode('/', $string);
			if($url[0] === '') array_shift($url);
			if($url[count($url)-1] === '') array_pop($url);

			return $url;
		}

		public function run() {
			$moduleName = $this->_url[0];

			/* Fallback to default module, if set */
			if(is_null($moduleName) && defined('SYSTEM_MODULE_DEFAULT') && !empty(SYSTEM_MODULE_DEFAULT)) {
				$moduleName = SYSTEM_MODULE_DEFAULT;
			}

			if(!is_null($moduleName)) {
				$result = SimpleAPI::loadModule($moduleName, array_splice($this->_url, 1));
			}
			else {
				$result = Response::error(Lang::get('module-not-provided'), 'module-not-provided', 400);
			}			

			$response = [];
			$response['status'] = $result->getState();

			if($response['status'] === 'success') {
				$response['result'] = $result->getResult();
			}
			if($response['status'] === 'error') {
				$response['error'] = [
					'errno' => $result->getCode(),
					'message' => $result->getError()
				];
			}
			if(!is_null($result->getHttpStatus())) {
				$this->responseCode = $result->getHttpStatus();
			}

			return $this->createResponse($response);
		}

		public function createResponse($data) {
			$reqResponseTypes = $this->_headers->get('Accept');
			$response = '';

			foreach($reqResponseTypes as $responseType) {
				if($responseType === '*/*') continue;

				$responder = Responders::get($responseType);

				if($responder) {
					$response = $responder->__invoke($data);
					$this->prefferedResponseType = $responseType;

					break;
				}
			}

			if(empty($response)) {
				if(in_array('*/*', $reqResponseTypes) && defined('SYSTEM_RESPONSE_DEFAULT')) {
					$response = Responders::get(SYSTEM_RESPONSE_DEFAULT)->__invoke($data);
					$this->prefferedResponseType = SYSTEM_RESPONSE_DEFAULT;
				}
				else {
					$this->prefferedResponseType = 'none';
					return substr(print_r($data, true), 0, -1);
				}
			}

			return $response;
		}

		public function getPreferredResponseType() {
			return $this->prefferedResponseType;
		}

		public function getResponseCode() {
			return $this->responseCode;
		}
	}