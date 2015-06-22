<?php

	class DB {
		private static $_connection;

		public static function load() {
			if(defined('DB_CRED_HOST') && defined('DB_CRED_USER') && defined('DB_CRED_PWD') && defined('DB_CRED_DBNAME')) {
				if(!defined('DB_CRED_PORT')) $port = 3306;
				else $port = DB_CRED_PORT;

				DB::$_connection = @new MySQLi(DB_CRED_HOST, DB_CRED_USER, DB_CRED_PWD, DB_CRED_DBNAME, $port);
			}
		}

		public static function isConnected() {
			return @DB::$_connection->errno === 0;
		}

		public static function getConnection() {
			return DB::$_connection;
		}

		public static function close() {
			@DB::$_connection->close();
		}
	}