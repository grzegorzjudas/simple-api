<?php

	Responders::register('application/json', function($data) {
		return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
	});