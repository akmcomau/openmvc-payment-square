<?php

namespace modules\payment_stripe\classes\models;

use core\classes\Model;
use core\classes\Encryption;

class Stripe extends Model {
	protected $table       = 'stripe';
	protected $primary_key = 'stripe_id';
	protected $columns     = [
		'stripe_id' => [
			'data_type'      => 'bigint',
			'auto_increment' => TRUE,
			'null_allowed'   => FALSE,
		],
		'stripe_created' => [
			'data_type'      => 'datetime',
			'null_allowed'   => FALSE,
		],
		'checkout_id' => [
			'data_type'      => 'bigint',
			'null_allowed'   => TRUE,
		],
		'stripe_reference' => [
			'data_type'      => 'text',
			'data_length'    => 32,
			'null_allowed'   => TRUE,
		],
		'stripe_amount' => [
			'data_type'      => 'numeric',
			'data_length'    => [6, 4],
			'null_allowed'   => FALSE,
		],
		'stripe_fee' => [
			'data_type'      => 'numeric',
			'data_length'    => [6, 4],
			'null_allowed'   => FALSE,
		],
		'stripe_token' => [
			'data_type'      => 'text',
			'null_allowed'   => FALSE,
		],
		'stripe_charge' => [
			'data_type'      => 'text',
			'null_allowed'   => FALSE,
		],
	];

	protected $indexes = [
		'stripe_created',
		'checkout_id',
		'stripe_reference',
	];

	protected $foreign_keys = [
		'checkout_id'     => ['checkout',     'checkout_id'],
	];
}
