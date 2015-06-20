<?php

	Responders::register('text/html', function($data) {
		return print_r($data, true);
	});