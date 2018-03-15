<?php

namespace modules\payment_square\classes\models;

use core\classes\Model;
use core\classes\Encryption;

class Square extends Model {
	protected $table       = 'square';
	protected $primary_key = 'square_id';
	protected $columns     = [
		'square_id' => [
			'data_type'      => 'bigint',
			'auto_increment' => TRUE,
			'null_allowed'   => FALSE,
		],
		'square_created' => [
			'data_type'      => 'datetime',
			'null_allowed'   => FALSE,
		],
		'checkout_id' => [
			'data_type'      => 'bigint',
			'null_allowed'   => TRUE,
		],
		'square_reference' => [
			'data_type'      => 'text',
			'data_length'    => 32,
			'null_allowed'   => TRUE,
		],
		'square_amount' => [
			'data_type'      => 'numeric',
			'data_length'    => [6, 4],
			'null_allowed'   => FALSE,
		],
		'square_fee' => [
			'data_type'      => 'numeric',
			'data_length'    => [6, 4],
			'null_allowed'   => FALSE,
		],
		'square_charge' => [
			'data_type'      => 'text',
			'null_allowed'   => FALSE,
		],
	];

	protected $indexes = [
		'square_created',
		'checkout_id',
		'square_reference',
	];

	protected $foreign_keys = [
		'checkout_id'     => ['checkout',     'checkout_id'],
	];
}
