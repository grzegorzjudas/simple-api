<?php

	namespace Module\example;

	class Module extends \MBase implements \MInterface {
		/* protected $_params */
		/* protected $_method */

		public function init() {
			return \Response::success($this->_params);
		}
	}