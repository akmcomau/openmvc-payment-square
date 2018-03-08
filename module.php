<?php
$_MODULE = [
	"name" => "Payment - Stripe",
	"description" => "Support for Stripe payments within the checkout",
	"namespace" => "\\modules\\payment_stripe",
	"config_controller" => "administrator\\PaymentStripe",
	"controllers" => [
		"administrator\\PaymentStripe",
		"PaymentStripe"
	],
	"default_config" => [
		"currency" => "AUD",
		"published_key" => "",
		"secret_key" => "",
	]
];
