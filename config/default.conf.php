<?php

	/* Default response type: used when no requested type found and the request allows any other MIME type */
	define("SYSTEM_RESPONSE_DEFAULT", "application/json");

	/* Default module: used when no module name provided (empty for error) */
	define("SYSTEM_MODULE_DEFAULT", "");

	/* Default language: used when no requested language found and the request allows any other language */
	define("SYSTEM_LANGUAGE_DEFAULT", "pl-PL");

	/* Enable or disable Cross Origin requests */
	define("SYSTEM_ALLOW_CROSSORIGIN", false); //unused