<?php

	namespace Module\example;

	use \Response;
	use \Lang;
	use \Headers;
	use \SimpleAPI;
	use \InstallScript;

	class Module extends \MBase implements \MInterface {
		/* protected $_method */
		/* protected $_params */
		/* protected $_data */

		public function install() {
			$inst = new InstallScript();

			$inst->setTable('example1');
			$inst->setColumn('example1', 'id', 'INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY', 'EXAMPLE1_ID');
			$inst->setColumn('example1', 'name', 'VARCHAR(128) NOT NULL', 'EXAMPLE1_NAME');
			$inst->setColumn('example1', 'value', 'VARCHAR(128) NOT NULL', 'EXAMPLE1_VALUE');

			$inst->setTable('example2');
			$inst->setColumn('example2', 'id', 'INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY', 'EXAMPLE2_ID');
			$inst->setColumn('example2', 'example1_id', 'INT(11) UNSIGNED', 'EXAMPLE2_EXAMPLE1_ID');
			$inst->setColumn('example2', 'description', 'VARCHAR(128) NOT NULL', 'EXAMPLE2_DESCRIPTION');
			$inst->setIndex('example2', 'example1_id');
			$inst->setRelation('example2.example1_id', 'example1.id');

			return $inst;
		}

		public function setRequirements() {
			$this->_setDatabaseRequired(true);
			$this->_setUserRequired(true);
			$this->_setAllowedMethods(['GET', 'PUT', 'DELETE']);
		}

		public function init() {
			return Response::success($this->_params);
		}
	}