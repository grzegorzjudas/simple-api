<?php

	require 'config/default.conf.php';
	require 'system/SimpleAPI.class.php';

	$api = new SimpleAPI();

	$response = $api->run();

	$response_format = $api->getPreferredResponseType();
	$response_code = $api->getResponseCode();

	header('Content-Type: ' . $response_format);
	http_response_code($response_code);

	exit($response);