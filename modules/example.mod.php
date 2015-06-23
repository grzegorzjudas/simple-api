<?php

	namespace Module\example;

	use \Response;
	use \Lang;
	use \Headers;
	use \SimpleAPI;

	class Module extends \MBase implements \MInterface {
		/* protected $_method */
		/* protected $_params */
		/* protected $_data */

		public function setRequirements() {
			$this->_setDatabaseRequired(true);
			$this->_setUserRequired(true);
			$this->_setAllowedMethods(['GET', 'PUT', 'DELETE']);
		}

		public function init() {
			return Response::success($this->_params);
		}
	}