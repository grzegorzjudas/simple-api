<?php

	class Response {
		private $state = '';
		private $data = false;
		private $errCode = 0;
		private $err = '';
		private $httpStatus = null;
		const STATE_ERROR = 'error';
		const STATE_SUCCESS = 'success';

		public static function error($message, $code, $httpStatus = null) {
			$response = new Response();

			$response->setState(Response::STATE_ERROR);
			$response->setError($code, $message);
			if(!is_null($httpStatus)) $response->setHttpStatus($httpStatus);
			else $response->setHttpStatus('Bad Request');

			return $response;
		}

		public static function success($data) {
			$response = new Response();

			$response->setState(Response::STATE_SUCCESS);
			$response->setData($data);

			return $response;
		}

		public static function translateHttpCode($id) {
			$codes = [
				'OK' => 200,
				'Created' => 201,
				'Accepted' => 202,
				'No Content' => 204,
				'Not Modified' => 304,
				'Bad Request' => 400,
				'Unauthorized' => 401,
				'Payment Required' => 402,
				'Forbidden' => 403,
				'Not Found' => 404,
				'Method Not Allowed' => 405,
				'Not Acceptable' => 406,
				'Internal Server Error' => 500,
				'Not Implemented' => 501,
				'Bad Gateway' => 502
			];

			if(gettype($id) === 'string') $status = in_array($id, array_keys($codes)) ? $codes[$id] : 200;
			else $status = in_array($id, $codes) ? array_search($id, $codes) : 'OK';

			return $status;
		}

		public function getState() {
			return $this->state;
		}

		public function getResult() {
			return $this->data;
		}

		public function getCode() {
			return $this->errCode;
		}

		public function getError() {
			return $this->err;
		}

		public function getHttpStatus() {
			return $this->httpStatus;
		}

		private function setState($state) {
			$this->state = $state;
		}

		private function setData($data) {
			$this->data = $data;
		}

		private function setError($code, $msg) {
			$this->errCode = $code;
			$this->err = $msg;
		}

		private function setHttpStatus($httpStatus) {
			if(gettype($httpStatus) === 'string') {
				$this->httpStatus = Response::translateHttpCode($httpStatus);
			}
			else $this->httpStatus = $httpStatus;
		}
	}