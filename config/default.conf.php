<?php

	/* Default response type: used when no requested type found and the request allows any other MIME type */
	define("SYSTEM_RESPONSE_DEFAULT", "application/json");

	/* Default module: used when no module name provided (empty for error) */
	define("SYSTEM_MODULE_DEFAULT", "");

	/* Default language: used when no requested language found and the request allows any other language */
	define("SYSTEM_LANGUAGE_DEFAULT", "pl-PL");

	/* Default charset: used if no Accept-Charset header is provided */
	define("SYSTEM_CHARSET_DEFAULT", "utf-8");

	/* Enable or disable Cross Origin requests */
	define("SYSTEM_ALLOW_CROSSORIGIN", false); //unused

	/* Use HTTP Authentication with token passed in HTTP Header */
	define("SEC_TOKEN_HEADER", true);

	/* Token lifetime in seconds */
	define("SEC_TOKEN_LIFETIME", 86400);

	/* Minimum username length requirement */
	define("SEC_USER_MINLEN", 6);

	/* Minimum password length requirement */
	define("SEC_PWD_MINLEN", 6);

	/* Whether e-mail confirmation when registering is required */
	define("SEC_EMAIL_CONFIRM", false);

	/* Allow accounts to share e-mail address */
	define("SEC_EMAIL_UNIQUE", true);

	/* Data encryption (tokens, passswords, etc) */
	define("SEC_DATA_ENCRYPTION", "sha512");

	/* Database connection credentials */
	define("DB_CRED_HOST", "localhost");
	define("DB_CRED_USER", "root");
	define("DB_CRED_PWD", "root");
	define("DB_CRED_DBNAME", "");
	define("DB_CRED_PORT", 3306);

	/* Database features table names */
	define("DB_TABLE_USERS", "users");
	define("DB_TABLE_SESSIONS", "sessions");

	/* Database features column names */
	define("DB_COL_USER", "username");
	define("DB_COL_LOGIN", "login");
	define("DB_COL_PWD", "password");
	define("DB_COL_EMAIL", "email");
	define("DB_COL_TOKEN", "token");
	define("DB_COL_CREATED", "created");
	define("DB_COL_LASTUSED", "last_used");