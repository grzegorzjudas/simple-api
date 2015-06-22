<?php

	require 'config/custom/custom.conf.php';
	require 'config/default.conf.php';
	require 'system/SimpleAPI.class.php';

	$api = new SimpleAPI();

	$response = $api->run();

	exit($response);