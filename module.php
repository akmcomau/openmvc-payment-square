<?php
$_MODULE = [
	"name" => "Payment - Square",
	"description" => "Support for Square payments within the checkout",
	"namespace" => "\\modules\\payment_square",
	"config_controller" => "administrator\\PaymentSquare",
	"controllers" => [
		"administrator\\PaymentSquare",
		"PaymentSquare"
	],
	"default_config" => [
		"currency" => "AUD",
		"application_id" => "",
		"access_token" => "",
	]
];
