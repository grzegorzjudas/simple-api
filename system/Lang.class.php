<?php

	class Lang {
		private static $_locale = [];
		private static $_preferredLocale = null;

		public static function load($langs) {			
			if(in_array('*', $langs)) {
				if(is_null(Lang::$_preferredLocale) && defined('SYSTEM_LANGUAGE_DEFAULT')) {
					$langs[array_search('*', $langs)] = SYSTEM_LANGUAGE_DEFAULT;
				}
				else {
					array_splice($langs, array_search('*', $langs), 1);
				}
			}

			foreach ($langs as $lang) {
				$lang = str_replace('-', '_', $lang);

				if(!is_dir('locale/' . $lang . '/')) continue;

				Lang::$_preferredLocale = $lang;
				break;
			}


			if(!is_null(Lang::$_preferredLocale)) {
				$dir = opendir('locale/' . $lang . '/');

				while(($file = readdir($dir)) !== false) {
					if($file === '.' || $file === '..') continue;
					
					$content = file_get_contents('locale/' . $lang . '/' . $file);
					$content = json_decode($content, true);

					if(!!$content) {
						Lang::$_locale = array_merge(Lang::$_locale, $content);
					}
				}
			}
		}

		public static function get($label) {
			if(!is_null(Lang::$_locale[$label])) return Lang::$_locale[$label];
			else return '';
		}
	}