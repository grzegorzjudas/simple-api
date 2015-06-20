<?php

	class MBase {
		protected $_params;

		public function __construct($params) {
			$this->_params = $params;
		}

		protected function _getModuleName() {
			return explode('\\', get_class($this))[1];
		}
	}