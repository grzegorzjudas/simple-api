<?php

	class Headers {
		private static $data;
		const negotiable = ['Accept', 'Accept-Language', 'Accept-Charset'];
		const listable = ['Accept-Encoding'];

		public static function load() {
			Headers::$data = Headers::parseHeaders(getallheaders());

			array_unshift(Headers::$data['Accept'], 'application/json');
		}

		public static function get($name) {
			return Headers::$data[$name];
		}

		public static function set($name, $value) {
			if(gettype($value) === 'array') $value = implode(', ', $value);

			header($name . ': ' . $value);
		}

		private static function parseHeaders($headers) {
			if(gettype($headers) !== 'array') return [];

			foreach($headers as $headerName => $header) {
				if(in_array($headerName, Headers::negotiable)) {
					$headers[$headerName] = Headers::negotiate($header);
				}
				if(in_array($headerName, Headers::listable)) {
					$headers[$headerName] = Headers::listElements($header);
				}
			}

			$headers['Cookie'] = $_COOKIE;
			
			return $headers;
		}

		private static function listElements($header) {
			return explode(', ', $header);
		}

		private static function negotiate($header) {
			$req = [];

			foreach(explode(',', $header) as $index => $param) {
				list($pName, $pWeight) = explode(';', $param);

				if(is_null($pWeight)) $pWeight = 1;
				else $pWeight = explode('=', $pWeight)[1];

				$req[] = ['name' => $pName, 'q' => $pWeight];
			}
			usort($req, function($a, $b) {
				return $a['q'] < $b['q'];
			});

			$resp = [];
			foreach($req as $r) {
				$resp[] = $r['name'];
			}

			return $resp;
		}
	}