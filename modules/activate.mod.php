<?php

	namespace Module\activate;

	class Module extends \MBase implements \MInterface {
		/* protected $_method */
		/* protected $_params */
		/* protected $_data */

		public function setRequirements() {
			$this->_setDatabaseRequired(true);
			$this->_setAllowedMethods(['GET']);
		}

		public function init() {
			return \SimpleAPI::loadModule('user')->activate($this->_params[0]);
		}
	}