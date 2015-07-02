<?php

	require '../config/custom/custom.conf.php';
	require '../config/default.conf.php';
	require '../system/DB.class.php';
	require '../system/MBase.class.php';
	require '../system/MInterface.int.php';
	require '../system/InstallScript.class.php';

	class Installer {
		public function __construct() {
			DB::load();

			$this->_db = DB::getConnection();
			$this->fetchModuleRequirements();
			$this->_db->close();
		}

		private function fetchModuleRequirements() {
			$dir = opendir('../modules/');

			while(($file = readdir($dir)) !== false) {
				if($file === '.' || $file === '..') continue;
				if(substr($file, -8) !== '.mod.php') continue;

				$moduleName = 'Module\\' . substr($file, 0, -8) . '\\Module';

				include '../modules/' . $file;

				if(!class_exists($moduleName)) continue;
				if(!in_array('MInterface', class_implements($moduleName))) continue;
				if(!method_exists($moduleName, 'install')) continue;

				$script = (new $moduleName([]))->install();
				if(!($script instanceof InstallScript)) continue;

				$cfg = $script->generateConfigFile(substr($file, 0, -8));
				$this->DBinstall($script);
			}
		}

		private function DBinstall($script) {
			foreach($script->data as $table => $columns) {
				/* Check table existence */
				$query = "SELECT * FROM information_schema.tables WHERE table_schema = '" . DB_CRED_DBNAME . "' AND table_name = '" . $table . "'";
				$q = $this->_db->query($query);

				if($q->num_rows > 0) {
					foreach ($columns as $column => $params) {
						/* Check column existance */
						$query = "SELECT * FROM information_schema.columns WHERE 
							table_schema = '" . DB_CRED_DBNAME . "' 
							AND table_name = '" . $table . "' 
							AND column_name = '" . $column . "'";

						$q = $this->_db->query($query);


						if($q->num_rows === 0) {
							$query = "ALTER TABLE " . $table . " ADD " . $column . " " . $params;
							$q = $this->_db->query($query);
						}
					}
				}
				else {
					$query = "CREATE TABLE " . $table . " (" . PHP_EOL;

					foreach($columns as $column => $params) {
						$query .= $column . " " . $params . ", ";
					}

					$query = substr($query, 0, -2) . ")";
					// echo $query;
					$q = $this->_db->query($query);	

					// print_r($q);
				}
			}

		}
	}

	$inst = new Installer();