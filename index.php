<?php
	require 'system/SimpleAPI.class.php';

	$api = new SimpleAPI();

	$response = $api->run();

	exit($response);