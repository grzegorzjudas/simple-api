<?php

	class Headers {
		private $data;
		private $negotiable = ['Accept', 'Accept-Language'];
		private $listable = ['Accept-Encoding'];

		public function __construct() {
			$this->data = $this->parseHeaders(getallheaders());

			/* DEVELOPMENT */
			array_unshift($this->data['Accept'], 'application/json');

			// print_r($this->data);
		}

		public function get($name) {
			return $this->data[$name];
		}

		private function parseHeaders($headers) {
			if(gettype($headers) !== 'array') return [];

			foreach($headers as $headerName => $header) {
				if(in_array($headerName, $this->negotiable)) {
					$headers[$headerName] = $this->negotiate($header);
				}
				if(in_array($headerName, $this->listable)) {
					$headers[$headerName] = $this->listElements($header);
				}
			}

			$headers['Cookie'] = $_COOKIE;
			
			return $headers;
		}

		private function listElements($header) {
			return explode(', ', $header);
		}

		private function negotiate($header) {
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