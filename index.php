<?php

	require 'config/default.conf.php';
	require 'system/SimpleAPI.class.php';

	$api = new SimpleAPI();

	$response = $api->run();

	/* Set the Content-Type of a document */
	header('Content-Type: ' . $api->getPreferredResponseType());

	/* Set HTTP status code */
	http_response_code($api->getResponseCode());

	exit($response);