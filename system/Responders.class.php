<?php

	class Responders {
		private static $responders = [];

		public static function register($names, $function) {
			if(gettype($names) !== 'array') $names = [ $names ];

			foreach($names as $name) {
				Responders::$responders[$name] = $function;
			}
		}

		public static function get($name) {
			if(!isset(Responders::$responders[$name])) return false;

			return Responders::$responders[$name];
		}

		public static function load() {
			$dir = opendir('system/responders/');

			while(($file = readdir($dir)) !== false) {
				if($file === '.' || $file === '..') continue;
				
				include 'system/responders/' . $file;
			}
		}
	}