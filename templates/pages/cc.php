<script type="text/javascript">
var applicationId = "<?php echo $application_id; ?>";
var locationId = "<?php echo $location_id; ?>";
</script>
<script type="text/javascript" src="https://js.squareup.com/v2/paymentform"></script>
<script type="text/javascript" src="/modules/payment_square/assets/js/sqpaymentform.js"></script>
<link rel="stylesheet" type="text/css" href="/modules/payment_square/assets/css/sqpaymentform.css">

<div class="<?php echo $page_class; ?>">
	<div class="row">
		<h1 style="color: darkred; text-align: center;"><?php echo nl2br($error_msg); ?></h1>
			<div class="col-md-5">
				<h4><?php echo $text_cc_header; ?></h4>
				<div id="cc-error" class="form-error"></div>
				<hr class="separator-2column" />
				<div class="row">
					<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_card_number; ?></div>
					<div class="col-md-9 col-sm-9 ">
						<div id="sq-card-number"></div>
					</div>
				</div>
				<hr class="separator-2column" />
				<div class="row">
					<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_card_cvc; ?></div>
					<div class="col-md-9 col-sm-9 ">
						<div id="sq-cvv"></div>
					</div>
				</div>
				<hr class="separator-2column" />
				<div class="row">
					<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_card_expiry; ?></div>
					<div class="col-md-9 col-sm-9 ">
						<div id="sq-expiration-date"></div>
					</div>
				</div>
				<hr class="separator-2column" />
				<div class="row">
					<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_postcode; ?></div>
					<div class="col-md-9 col-sm-9 ">
						<div id="sq-postal-code"></div>
					</div>
				</div>
				<?php if ($currency_converted) { ?>
					<div style="text-align: center; font-weight: bold; font-size: 14px; color: darkred;">
						You will be billed <?php echo $site_currency; ?> $<?php echo money_format('%!^n', $amount_total); ?><br />
						(1 <?php echo $currency; ?> = <?php echo number_format($rate, 4); ?> <?php echo $site_currency; ?>)
					</div>
				<?php } ?>
				<div class="align-center">
					<br /><br />
					<button id="sq-creditcard" name="form-cc-submit" class="btn btn-lg btn-primary" onclick="requestCardNonce(event)"><?php echo $text_pay; ?></button>
				</div>
			</div>
		<div class="col-md-7">
			<h4><?php echo $text_cart; ?></h4>
			<table class="table">
				<tr>
					<th><?php echo $text_name; ?></th>
					<th class="hidden-xs"><?php echo $text_price; ?></th>
					<th><?php echo $text_quantity; ?></th>
					<th><?php echo $text_total; ?></th>
				</tr>
				<?php foreach ($contents as $item) { ?>
					<tr>
						<td><?php echo $item->getName(); ?></td>
						<td class="hidden-xs"><?php echo money_format('%n', $item->getSellPrice()); ?></td>
						<td>
							<?php echo $item->getQuantity(); ?>
						</td>
						<td><?php echo money_format('%n', $item->getSellTotal()); ?></td>
					</tr>
				<?php } ?>
				<tr>
					<th class="visible-xs"></th>
					<th class="hidden-xs" colspan="2"></th>
					<th><?php echo $text_total; ?></th>
					<th><?php echo money_format('%n', $total); ?></th>
				</tr>
			</table>
		</div>
	</div>
</div>
<form id="nonce-form" action="<?php echo $this->url->getUrl('PaymentSquare', 'cc'); ?>" method="post">
	<input type="hidden" id="card-nonce" name="nonce" value="" />
</form>
<script type="text/javascript">
</script>

