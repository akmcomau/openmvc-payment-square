<?php

namespace modules\payment_square;

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
		$table = $model->getModel('\\modules\\payment_square\\classes\\models\\Square');
		$table->createTable();
		$table->createIndexes();
		$table->createForeignKeys();
	}

	public function uninstall() {
		$model = new Model($this->config, $this->database);
		$table = $model->getModel('\\modules\\payment_square\\classes\\models\\Square');
		$table->dropTable();
	}

	public function enable() {
		$config = $this->config->getSiteConfig();
		$config['sites'][$this->config->getSiteDomain()]['checkout']['payment_methods']['square'] = [
			'name' => 'Square',
			'public' => '\modules\payment_square\controllers\PaymentSquare',
			'administrator' => '\modules\payment_square\controllers\administrator\PaymentSquare',
		];
		$this->config->setSiteConfig($config);
	}

	public function disable() {
		$config = $this->config->getSiteConfig();
		unset($config['sites'][$this->config->getSiteDomain()]['checkout']['payment_methods']['square']);
		$this->config->setSiteConfig($config);
	}
}
