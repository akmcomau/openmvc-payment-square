<?php

namespace modules\payment_stripe\classes;

use core\classes\exceptions\RedirectException;
use modules\checkout\classes\Cart;
use modules\checkout\classes\Order;
use core\classes\Encryption;
use core\classes\Logger;
use core\classes\Model;
use core\classes\URL;
use core\classes\Template;
use core\classes\Email;

class StripeAPI {
	protected $config;
	protected $module_config;
	protected $logger;
	protected $database;
	protected $language;
	protected $model;

	protected $published_key;
	protected $secret_key;

	protected $card_token = NULL;

	protected $error_msg = NULL;

	public function __construct($config, $database, $language, Cart $cart) {
		$this->module_config = $config->moduleConfig('\modules\payment_stripe');
		$this->config        = $config;
		$this->database      = $database;
		$this->language      = $language;
		$this->model         = new Model($config, $database);
		$this->logger        = Logger::getLogger(get_class($this));

		$this->published_key = $this->module_config->published_key;
		$this->secret_key    = $this->module_config->secret_key;

		$this->cart = $cart;
	}

	public function getErrorMsg() {
		return $this->error_msg;
	}

	public function chargeCard($customer, $card, $currency, $amount) {
		\Stripe\Stripe::setApiKey($this->secret_key);

		try {
			$token = \Stripe\Token::create([ 'card' => $card ]);

			if (!isset($token['id'])) {
				throw new \Exception('There was an error with the card information.');
			}

			$charge = \Stripe\Charge::create([
				'amount' => $amount,
				'currency' => $currency,
				'source' => $token['id'],
				'description' => 'Purchase from '.$this->config->siteConfig()->name,
			]);
		}
		catch (\Exception $ex) {
			$this->error_msg = 'The payment was declined: '.$ex->getMessage();
			$this->logger->error('Payment has been declined: '.$ex->getMessage());
		}

		// create the stripe transaction record
		$stripe = $this->model->getModel('\modules\payment_stripe\classes\models\Stripe');
		$stripe->stripe_reference = (isset($charge) && property_exists($charge, 'id')) ? $charge->id : 'ERROR';
		$stripe->stripe_amount    = $this->cart->getCartSellTotal();
		$stripe->stripe_fee       = 0;
		$stripe->stripe_token     = isset($token) ? json_encode($token) : '';
		$stripe->stripe_charge    = isset($charge) ? json_encode($charge) : '';
		$stripe->insert();

		if (!isset($charge) || $charge->outcome->network_status != 'approved_by_network') {
			$data = [
				'contents' => $this->cart->getContents(),
				'total' => $this->cart->getCartSellTotal(),
				'error_msg' => $this->error_msg,
			];

			$body = $this->getEmailTemplate($this->language, 'emails/internal_error.txt.php', $data, 'modules'.DS.'payment_stripe');
			$html = $this->getEmailTemplate($this->language, 'emails/internal_error.html.php', $data, 'modules'.DS.'payment_stripe');
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
		$checkout = $order->purchase('stripe', $customer, $billing, $shipping);

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

		$stripe->checkout_id = $checkout->id;
		$stripe->update();

		return $checkout;
	}

	protected function getEmailTemplate($language, $filename, array $data = NULL, $path = NULL) {
		return new Template($this->config, $language, $filename, $data, $path);
	}
}
