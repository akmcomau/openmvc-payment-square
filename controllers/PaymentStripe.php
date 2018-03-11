<?php

namespace modules\payment_stripe\controllers;

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
use modules\payment_stripe\classes\StripeAPI;

class PaymentStripe extends Controller {

	protected $permissions = [
	];

	public function getAllUrls($include_filter = NULL, $exclude_filter = NULL) {
		return [];
	}

	public function payment() {
		throw new RedirectException($this->url->getUrl('PaymentStripe', 'cc'));
	}

	public function cc() {
		$this->module_config = $this->config->moduleConfig('\modules\payment_stripe');
		$this->language->loadLanguageFile('checkout.php', 'modules'.DS.'checkout');
		$this->language->loadLanguageFile('administrator/orders.php', 'modules'.DS.'checkout');
		$cart = new Cart($this->config, $this->database, $this->request);
		$stripe = new StripeAPI($this->config, $this->database, $this->language, $cart);

		$form = $this->getCreditCardForm();

		if ($form->validate()) {
			$customer = $this->model->getModel('\sites\satvue\classes\models\Customer');
			$logged_in = $customer->get(['id' => $this->getAuthentication()->getCustomerID()]);

			$address = $this->model->getModel('\core\classes\models\Address', $cart->getBillingAddress());

			$card = [
				'number'          => $form->getValue('cc_number'),
				'exp_month'       => $form->getValue('cc_expiry_month'),
				'exp_year'        => $form->getValue('cc_expiry_year'),
				'cvc'             => $form->getValue('cc_cvc'),
				'address_city'    => $address->getCity()->name,
				'address_country' => $address->getCountry()->code,
				'address_line1'   => $address->line1,
				'address_line2'   => $address->line2,
				'address_state'   => $address->getState() ? $address->getState()->name : NULL,
				'address_zip'     => $address->postcode,
			];

			// get the currency
			$amount_total = money_format('%!^n', $cart->getGrandTotal());
			$currency = $this->module_config->currency;
			if (property_exists($this->config->siteConfig(), 'currency')) {
				$currency = $this->config->siteConfig()->currency;
				if ($this->config->siteConfig()->currency == 'JPY') {
					$amount_total = floor($amount_total);
				}
				else {
					$amount_total = 100 * $amount_total;
				}
			}

			$checkout = $stripe->chargeCard($logged_in, $card, strtolower($currency), $amount_total);
			if ($checkout) {
				// create the event
				$this->request->addEvent('Stripe Payment', $stripe->getLastPayment()->id, $cart->getGrandTotal(), $currency);

				// clear the cart
				$cart->clear();

				// goto the receipt
				$enc_checkout_id = Encryption::obfuscate($checkout->id, $this->config->siteConfig()->secret);
				throw new RedirectException($this->url->getUrl('Checkout', 'receipt', [$enc_checkout_id]));
			}
		}

		$data = [
			'form' => $form,
			'contents' => $cart->getContents(),
			'total' => $cart->getCartSellTotal(),
			'error_msg' => $stripe->getErrorMsg()
		];
		$template = $this->getTemplate('pages/cc.php', $data, 'modules'.DS.'payment_stripe');
		$this->response->setContent($template->render());
	}

	protected function getCreditCardForm() {
		$inputs = [
			'cc_number' => [
				'type' => 'string',
				'min_length' => 16,
				'max_length' => 24,
				'required' => TRUE,
				'message' => $this->language->get('error_cc_num')
			],
			'cc_expiry_month' => [
				'type' => 'integer',
				'min_value' => 1,
				'max_value' => 12,
				'required' => TRUE,
				'message' => $this->language->get('error_cc_expiry_month')
			],
			'cc_expiry_year' => [
				'type' => 'integer',
				'min_value' => date('Y'),
				'max_value' => date('Y') + 10,
				'required' => TRUE,
				'message' => $this->language->get('error_cc_expiry_year')
			],
			'cc_cvc' => [
				'type' => 'integer',
				'required' => TRUE,
				'message' => $this->language->get('error_cc_cvc')
			],
		];

		return new FormValidator($this->request, 'form-cc', $inputs);
	}
}
