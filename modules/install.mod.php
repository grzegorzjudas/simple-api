<?php

	namespace Module\install;

	use \Response;
	use \Lang;
	use \Headers;
	use \SimpleAPI;

	class Module extends \MBase implements \MInterface {
		/* protected $_method */
		/* protected $_params */
		/* protected $_data */

		public function init() {
			header('Location: ../install/index.php');
		}
	}