<?php

	namespace Module\login;

	class Module extends \MBase implements \MInterface {
		/* protected $_method */
		/* protected $_params */
		/* protected $_data */

		public function setRequirements() {
			$this->_setDatabaseRequired(true);
			$this->_setUserRequired(true);
			$this->_setAllowedMethods(['GET']);
		}

		public function init() {
			/* Proxy module for GET user/ */
			return \SimpleAPI::loadModule('user', [], 'GET');
		}
	}