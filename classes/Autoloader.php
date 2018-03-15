<?php

// Autoload PayPal classes
spl_autoload_register(function ($class) {
	$file = str_replace('SquareConnect\\', '', $class);
	$file = str_replace('\\', '/', $file);
	$root_path = __DIR__.'/../composer/vendor/square/connect/lib/';
	$filename = $root_path.$file.'.php';
	if (file_exists($filename)) {
		include($filename);
	}
});
