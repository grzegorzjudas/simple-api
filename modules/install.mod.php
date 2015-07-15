<?php

	namespace Module\install;

	class Module extends \MBase implements \MInterface {
		/* protected $_method */
		/* protected $_params */
		/* protected $_data */

		public function init() {
			header('Location: ../install/index.php');
		}
	}