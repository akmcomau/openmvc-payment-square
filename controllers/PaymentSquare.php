<?php

namespace modules\payment_square\controllers;

use Exception;
use ErrorException;
use core\classes\exceptions\RedirectException;
use core\classes\renderable\Controller;
use core\classes\Config;
use core\classes\Database;
use core\classes\Email;
use core\classes\Response;
use core\classes\Template;
use core\classes\Language;
use core\classes\Request;
use core\classes\Encryption;
use core\classes\Model;
use core\classes\Pagination;
use core\classes\FormValidator;
use modules\checkout\classes\Cart;
use modules\checkout\classes\Order;
use modules\payment_square\classes\SquareAPI;

class PaymentSquare extends Controller {

	protected $permissions = [
	];

	public function getAllUrls($include_filter = NULL, $exclude_filter = NULL) {
		return [];
	}

	public function payment() {
		throw new RedirectException($this->url->getUrl('PaymentSquare', 'cc'));
	}

	public function cc() {
		$this->module_config = $this->config->moduleConfig('\modules\payment_square');
		$this->language->loadLanguageFile('checkout.php', 'modules'.DS.'checkout');
		$this->language->loadLanguageFile('administrator/orders.php', 'modules'.DS.'checkout');
		$cart = new Cart($this->config, $this->database, $this->request);
		$square = new SquareAPI($this->config, $this->database, $this->language, $cart);

		// get the currency
		$amount_total = money_format('%!^n', $cart->getGrandTotal());
		$currency = $this->module_config->currency;
		if (property_exists($this->config->siteConfig(), 'currency')) {
			$currency = $this->config->siteConfig()->currency;
		}

		// convert to the base currency
		$rate = 0;
		$currency_converted = FALSE;
		if (strtoupper($currency) != strtoupper($this->module_config->currency)) {
			$exchange_rate = $this->model->getModel('modules\exchange_rates\classes\models\ExchangeRate');
			$amount_total = $exchange_rate->reverse($this->config->siteConfig()->currency, $amount_total);
			$rate = $exchange_rate->reverse($this->config->siteConfig()->currency, 1);
			$currency_converted = TRUE;
		}

		// Do the payment
		if ($this->request->postParam('nonce')) {
			$customer = $this->model->getModel('\core\classes\models\Customer');
			$logged_in = $customer->get(['id' => $this->getAuthentication()->getCustomerID()]);

			$checkout = $square->chargeCard($customer, $this->request->postParam('nonce'), $this->module_config->currency, (int)(100*$amount_total));
			if ($checkout) {
				// create the event
				$this->request->addEvent('Stripe Payment', $square->getLastPayment()->id, $cart->getGrandTotal(), $currency);

				// clear the cart
				$cart->clear();

				// goto the receipt
				$enc_checkout_id = Encryption::obfuscate($checkout->id, $this->config->siteConfig()->secret);
				throw new RedirectException($this->url->getUrl('Checkout', 'receipt', [$enc_checkout_id]));
			}
		}

		$data = [
			'application_id' => $this->module_config->application_id,
			'location_id' => $square->getLocationId(),
			'contents' => $cart->getContents(),
			'total' => $cart->getCartSellTotal(),
			'error_msg' => $square->getErrorMsg(),
			'amount_total' => $amount_total,
			'currency' => $currency,
			'site_currency' => $this->module_config->currency,
			'currency_converted' => $currency_converted,
			'rate' => $rate,
		];
		$template = $this->getTemplate('pages/cc.php', $data, 'modules'.DS.'payment_square');
		$this->response->setContent($template->render());
	}
}
