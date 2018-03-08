<div class="<?php echo $page_class; ?>">
	<div class="row">
		<h1 style="color: darkred; text-align: center;"><?php echo nl2br($error_msg); ?></h1>
		<form id="form-cc" action="<?php echo $this->url->getUrl('PaymentStripe', 'cc'); ?>" method="post">
			<div class="col-md-5">
				<h4><?php echo $text_cc_header; ?></h4>
				<hr class="separator-2column" />
				<div class="row">
					<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_card_number; ?></div>
					<div class="col-md-9 col-sm-9 ">
						<input type="text" name="cc_number" class="form-control" value="<?php echo htmlspecialchars($form->getValue('cc_number')); ?>" maxlength="20" />
						<?php echo $form->getHtmlErrorDiv('cc_number'); ?>
					</div>
				</div>
				<hr class="separator-2column" />
				<div class="row">
					<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_card_expiry; ?></div>
					<div class="col-md-9 col-sm-9 ">
						<select name="cc_expiry_month" class="form-control" style="width: 75px; display: inline-block;" value="<?php echo htmlspecialchars($form->getValue('cc_expiry_month')); ?>">
							<option value=""></option>
							<?php for ($i=1; $i<=12; $i++) { ?>
								<option value="<?php printf("%02d", $i); ?>" <?php if ($form->getValue('cc_expiry_month') == $i) echo 'selected="selected"'; ?>><?php printf("%02d", $i); ?></option>
							<?php } ?>
						</select>
						<select name="cc_expiry_year" class="form-control" style="width: 100px; display: inline-block;">
							<option value=""></option>
							<?php for ($i=date('Y'); $i<=(date('Y')+10); $i++) { ?>
								<option value="<?php echo $i; ?>" <?php if ($form->getValue('cc_expiry_year') == $i) echo 'selected="selected"'; ?>><?php printf("%02d", $i); ?></option>
							<?php } ?>
						</select>
						<?php echo $form->getHtmlErrorDiv('cc_expiry_month'); ?>
						<?php echo $form->getHtmlErrorDiv('cc_expiry_year'); ?>
					</div>
				</div>
				<hr class="separator-2column" />
				<div class="row">
					<div class="col-md-3 col-sm-3 title-2column"><?php echo $text_card_cvc; ?></div>
					<div class="col-md-9 col-sm-9 ">
						<input type="text" name="cc_cvc" class="form-control" value="<?php echo htmlspecialchars($form->getValue('cc_cvc')); ?>" style="width: 100px;" maxlength="4" />
						<?php echo $form->getHtmlErrorDiv('cc_cvc'); ?>
					</div>
				</div>
				<div class="align-center">
					<br /><br />
					<button type="submit" name="form-cc-submit" class="btn btn-lg btn-primary"><?php echo $text_pay; ?></button>
				</div>
			</div>
		</form>
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
<script type="text/javascript">
	<?php echo $form->getJavascriptValidation(); ?>
	<?php /*echo $message_js;*/ ?>
</script>

