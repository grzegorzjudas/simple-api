<?php

define("DB_TABLE_USERS", "users"); 
define("DB_COL_USERS_ID", "id");
define("DB_COL_USERS_LOGIN", "login");
define("DB_COL_USERS_USERNAME", "username");
define("DB_COL_USERS_PASSWORD", "password");
define("DB_COL_USERS_EMAIL", "email");
define("DB_COL_USERS_ACCESS_LEVEL", "access_level");
define("DB_COL_USERS_CREATED", "created");
define("DB_COL_USERS_ACTIVATED", "activated");

define("DB_TABLE_SESSIONS", "sessions"); 
define("DB_COL_SESSIONS_ID", "id");
define("DB_COL_SESSIONS_USERS_ID", "users_id");
define("DB_COL_SESSIONS_TOKEN", "token");
define("DB_COL_SESSIONS_CREATED", "created");
define("DB_COL_SESSIONS_LAST_USED", "last_used");
define("DB_COL_SESSIONS_IP", "ip");

