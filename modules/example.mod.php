<?php

	namespace Module\example;

	class Module extends \MBase implements \MInterface {
		/* protected $_params */
		/* protected $_method */

		public function init() {
			$this->_setAllowedMethods(['POST', 'PUT', 'DELETE']);

			if($this->_isMethodAllowed()) return \Response::success($this->_params);
			else return \Response::error(\Lang::get('module-invalid-method'), 'system-invalid-method', 405);
		}
	}