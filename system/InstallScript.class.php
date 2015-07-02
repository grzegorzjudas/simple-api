<?php

	class InstallScript {
		public $data = [];
		public $indexes = [];
		private $labels = [];
		// private $columnNameTaken;

		public function __construct() { }

		public function setTable($name) {
			if(!isset($this->data[$name])) {
				$this->data[$name] = [];
				$this->labels[$name] = [];
				$this->indexes[$name] = [];
			}
		}

		public function setColumn($table, $name, $params, $label = null) {
			if(is_null($label)) $label = strtoupper($name);
			$label = 'DB_COL_' . $label;

			$this->data[$table][$name] = $params;
			$this->labels[$table][$name] = $label;
		}

		public function setIndex($table, $column) {
			$this->indexes[$table][] = $column;
		}

		public function generateConfigFile($moduleName) {
			$cfg = '<?php' . PHP_EOL . PHP_EOL;

			foreach($this->data as $table => $columns) {
				$cfg .= 'define("DB_TABLE_' . strtoupper($table) . '", "' . $table . '"); ' . PHP_EOL;

				foreach($columns as $column => $params) {
					$cfg .= 'define("' . $this->labels[$table][$column] . '", "' . $column . '");' . PHP_EOL;
				}

				$cfg .= PHP_EOL;
			}

			$filename = '../config/' . $moduleName . '.conf.php';
			file_put_contents($filename, $cfg);

			return $cfg;
		}
	}