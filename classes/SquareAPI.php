<?php

namespace modules\payment_square\classes;

use core\classes\exceptions\RedirectException;
use modules\checkout\classes\Cart;
use modules\checkout\classes\Order;
use core\classes\Encryption;
use core\classes\Logger;
use core\classes\Model;
use core\classes\URL;
use core\classes\Template;
use core\classes\Email;

class SquareAPI {
	protected $config;
	protected $module_config;
	protected $logger;
	protected $database;
	protected $language;
	protected $model;

	protected $application_id;
	protected $access_token;

	protected $card_token = NULL;

	protected $error_msg = NULL;

	protected $last_payment = NULL;

	public function __construct($config, $database, $language, Cart $cart) {
		$this->module_config = $config->moduleConfig('\modules\payment_square');
		$this->config        = $config;
		$this->database      = $database;
		$this->language      = $language;
		$this->model         = new Model($config, $database);
		$this->logger        = Logger::getLogger(get_class($this));

		$this->application_id = $this->module_config->application_id;
		$this->access_token   = $this->module_config->access_token;

		$this->cart = $cart;
	}

	public function getErrorMsg() {
		return $this->error_msg;
	}

	public function getLastPayment() {
		return $this->last_payment;
	}

	public function getLocationId() {
		\SquareConnect\Configuration::getDefaultConfiguration()->setAccessToken($this->access_token);
		$locationApi = new \SquareConnect\Api\LocationsApi();

		$result = $locationApi->listLocations();
		foreach ($result->getLocations() as $location) {
			return $location->getId();
		}

		return NULL;
	}

	public function chargeCard($customer, $nonce, $currency, $amount) {
		\SquareConnect\Configuration::getDefaultConfiguration()->setAccessToken($this->access_token);

		$location_id = $this->getLocationId();
		$transactions_api = new \SquareConnect\Api\TransactionsApi();

		$request_body = array (
			"card_nonce" => $nonce,

			# Monetary amounts are specified in the smallest unit of the applicable currency.
			# This amount is in cents. It's also hard-coded for $1.00, which isn't very useful.
			"amount_money" => array (
				"amount" => $amount,
				"currency" => $currency
			),

			# Every payment you process with the SDK must have a unique idempotency key.
			# If you're unsure whether a particular payment succeeded, you can reattempt
			# it with the same idempotency key without worrying about double charging
			# the buyer.
			"idempotency_key" => session_id().'-'.time()
		);

		try {
			$charge = $transactions_api->charge($location_id, $request_body);
		} catch (\SquareConnect\ApiException $e) {
			$this->logger->error('There was an error during payment: '.json_encode($e->getResponseBody()).' || '.json_encode($e->getResponseHeaders()));
			$this->error_msg = 'There was an error during payment: ';
			foreach ($e->getResponseBody()->errors as $error) {
				$this->error_msg .= $error->detail . ' ';
			}
		}

		// create the square transaction record
		$square = $this->model->getModel('\modules\payment_square\classes\models\Square');
		$square->square_reference = (isset($charge) && property_exists($charge, 'transaction')) ? $charge->getTransaction()->getId() : 'ERROR';
		$square->square_amount    = $this->cart->getCartSellTotal();
		$square->square_fee       = 0;
		$square->square_charge    = isset($charge) ? print_r($charge, TRUE) : '';
		$square->insert();

		$this->last_payment = $square;

		if (!isset($charge) || !property_exists($charge, 'transaction') || count($charge->getTransaction()->getTenders()) == 0) {
			$data = [
				'contents' => $this->cart->getContents(),
				'total' => $this->cart->getCartSellTotal(),
				'error_msg' => $this->error_msg,
			];

			$body = $this->getEmailTemplate($this->language, 'emails/internal_error.txt.php', $data, 'modules'.DS.'payment_square');
			$html = $this->getEmailTemplate($this->language, 'emails/internal_error.html.php', $data, 'modules'.DS.'payment_square');
			$email = new Email($this->config);
			$email->setToEmail($this->config->siteConfig()->email_addresses->orders);
			$email->setSubject($this->config->siteConfig()->name.': '.$this->language->get('internal_error_subject'));
			$email->setBodyTemplate($body);
			$email->setHtmlTemplate($html);
			$email->send();

			return FALSE;
		}

		$billing  = $this->model->getModel('\core\classes\models\Address', $this->cart->getBillingAddress());
		$shipping = $this->model->getModel('\core\classes\models\Address', $this->cart->getShippingAddress());

		$order = new Order($this->config, $this->database, $this->cart);
		$checkout = $order->purchase('square', $customer, $billing, $shipping);

		$status = $this->model->getModel('\modules\checkout\classes\models\CheckoutStatus');
		if ($checkout->shipping_address_id) {
			$checkout->status_id = $status->getStatusId('Processing');
		}
		else {
			$checkout->status_id = $status->getStatusId('Complete');
		}
		//$checkout->fees = $fee;
		//$checkout->receipt_note = $customer ? NULL : 'paypal_no_payer_info';
		//$checkout->update();
		$order->sendOrderEmails($checkout, $this->language);

		$square->checkout_id = $checkout->id;
		$square->update();

		return $checkout;
	}

	protected function getEmailTemplate($language, $filename, array $data = NULL, $path = NULL) {
		return new Template($this->config, $language, $filename, $data, $path);
	}
}
