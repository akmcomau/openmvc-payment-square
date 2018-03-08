<?php

namespace modules\payment_stripe;

use ErrorException;
use core\classes\Config;
use core\classes\Database;
use core\classes\Language;
use core\classes\Model;
use core\classes\Menu;

class Installer {
	protected $config;
	protected $database;

	public function __construct(Config $config, Database $database) {
		$this->config = $config;
		$this->database = $database;
	}

	public function install() {
		$model = new Model($this->config, $this->database);
		$table = $model->getModel('\\modules\\payment_stripe\\classes\\models\\Stripe');
		$table->createTable();
		$table->createIndexes();
		$table->createForeignKeys();
	}

	public function uninstall() {
		$model = new Model($this->config, $this->database);
		$table = $model->getModel('\\modules\\payment_stripe\\classes\\models\\Stripe');
		$table->dropTable();
	}

	public function enable() {
		$config = $this->config->getSiteConfig();
		$config['sites'][$this->config->getSiteDomain()]['checkout']['payment_methods']['stripe'] = [
			'name' => 'Stripe',
			'public' => '\modules\payment_stripe\controllers\PaymentStripe',
			'administrator' => '\modules\payment_stripe\controllers\administrator\PaymentStripe',
		];
		$this->config->setSiteConfig($config);
	}

	public function disable() {
		$config = $this->config->getSiteConfig();
		unset($config['sites'][$this->config->getSiteDomain()]['checkout']['payment_methods']['stripe']);
		$this->config->setSiteConfig($config);
	}
}
