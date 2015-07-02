<?php

	class InstallScript {
		public $data = [];
		public $indexes = [];
		public $relations = [];
		private $labels = [];
		// private $columnNameTaken;

		public function __construct() { }

		public function setTable($name) {
			if(!isset($this->data[$name])) {
				$this->data[$name] = [];
				$this->labels[$name] = [];
				$this->indexes[$name] = [];
				$this->relations[$name] = [];
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

		public function setRelation($source, $target, $onDelete = 'RESTRICT', $onUpdate = 'RESTRICT') {
			$sourceTable = explode('.', $source)[0];
			$sourceColumn = explode('.', $source)[1];
			$targetTable = explode('.', $target)[0];
			$targetColumn = explode('.', $target)[1];

			$this->relations[$sourceTable][$sourceColumn] = [
				'table' => $targetTable,
				'column' => $targetColumn,
				'ondelete' => $onDelete,
				'onupdate' => $onUpdate
			];
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