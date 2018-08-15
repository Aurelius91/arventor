	<style type="text/css">
	</style>

	<script type="text/javascript">
		$(function() {
			click();
			init();
			reset();
			change();
			changeProduct();
			keypressProduct();
		});

		function addProduct() {
			var number = 0;
			$('.sale-add-product').remove();

			$.each($('.sale-product-list'), function(key, item) {
				number = $(item).attr('data-number');
			});

			var nextNumber = parseInt(number) + 1;

			var newItemList = '<tr id="sale-item-list-'+ nextNumber +'" class="sale-product-list" data-number="'+ nextNumber +'"><td class="td-icon"><span class="table-icon" data-content="Remove Item" onclick="removeItem('+ nextNumber +');"><i class="trash outline icon"></i></span></td><td><div id="sale-product-selection-'+ nextNumber +'" class="ui search selection dropdown form-input"><input id="sale-product-'+ nextNumber +'" class="sale-product-list-selection" data-number="'+ nextNumber +'" type="hidden" class="data-important"><i class="dropdown icon"></i><div class="default text">-- Select Product --</div><div class="menu"><? foreach ($arr_product as $product): ?><div class="item" data-value="<?= $product->id; ?>"><?= $product->type; ?> - <?= $product->name; ?></div><? endforeach; ?></div></div></td><td class="td-price-quantity" style="text-align: right;"><input id="sale-product-price-'+ nextNumber +'" type="text" class="sale-item-price" data-number="'+ nextNumber +'" placeholder="Price.." style="text-align: right;"></td><td class="td-price-quantity" style="text-align: right;"><input id="sale-product-quantity-'+ nextNumber +'" type="text" class="sale-item-quantity" data-number="'+ nextNumber +'" placeholder="Quantity.." style="text-align: right;"></td><td class="td-price-quantity" style="text-align: right;"><span id="sale-product-price-total-'+ nextNumber +'" data-number="'+ nextNumber +'" data-total="0">Rp 0</span></td></tr><tr><td class="sale-add-product" style="cursor: pointer;" colspan="5" onclick="addProduct();"><span><i class="plus circle icon"></i></span> Add Product</td></tr>';

			$('#sale-item-list').append(newItemList);
			$('#sale-product-price-'+ nextNumber).val("0");
			$('#sale-product-quantity-'+ nextNumber).val("1");
			$('#sale-product-selection-'+ nextNumber).dropdown('clear');

			changeProduct();
			keypressProduct();
		}

		function back() {
			window.location.href = '<?= base_url(); ?>sale/view/1/';
		}

		function calculateQuantity() {
			var subtotal = 0;

			$.each($('.sale-product-list'), function(key, item) {
				var number = $(item).attr('data-number');
				var qty = ($('#sale-product-quantity-'+ number).val() != '') ? $('#sale-product-quantity-'+ number).val() : 0;
				var price = ($('#sale-product-price-'+ number).val() != '') ? $('#sale-product-price-'+ number).val() : 0;

				var total = parseInt(qty) * parseInt(price);
				var totalDisplay = $.number(total, 0, ',', '.');

				$('#sale-product-price-total-'+ number).html('Rp '+ totalDisplay);
				$('#sale-product-price-total-'+ number).attr('data-total', total);

				subtotal += parseInt(total);
			});

			calculateTotal(subtotal);
		}

		function calculateTotal(subtotal) {
			var subtotalDisplay = $.number(subtotal, 0, ',', '.');
			$('#sale-subtotal').attr('data-subtotal', subtotal);
			$('#sale-subtotal').html('Rp '+ subtotalDisplay);

			var discount = ($('#sale-discount').val() != '') ? $('#sale-discount').val() : 0;
			var tax = ($('#sale-tax').val() != '') ? $('#sale-tax').val() : 0;
			var shipping = ($('#sale-shipping').val() != '') ? $('#sale-shipping').val() : 0;

			var subtotalDiscount = (parseInt(discount) / 100) * subtotal;
			var subtotalDiscountDisplay = $.number(subtotalDiscount, 0, ',', '.');
			$('#sale-discount-display').html('Rp '+ subtotalDiscountDisplay);

			var subtotalDiscountTax = (parseInt(tax) / 100) * (subtotal - subtotalDiscount);
			var subtotalDiscountTaxDisplay = $.number(subtotalDiscountTax, 0, ',', '.');
			$('#sale-tax-display').html('Rp '+ subtotalDiscountTaxDisplay);

			var shippingDisplay = $.number(shipping, 0, ',', '.');
			$('#sale-shipping-display').html('Rp '+ shippingDisplay);

			var grandTotal = parseInt(subtotal) - parseInt(subtotalDiscount) + parseInt(subtotalDiscountTax) + parseInt(shipping);
			var grandTotalDisplay = $.number(grandTotal, 0, ',', '.');
			$('#sale-total').html('Rp '+ grandTotalDisplay);
			$('#sale-total').attr('data-total', grandTotal);
		}

		function change() {
			$('#sale-method').change(function() {
				var saleDate = $('#sale-date').val();

				if ($(this).val() == 'Cash') {
					$('#sale-deadline').val(saleDate);
					$('#deadline-term').html('0');
				}
				else {
					var defaultTerm = parseInt("<?= $setting->setting__webshop_default_credit_term ?>");
					$('#sale-deadline').html("<?= $setting->setting__webshop_default_credit_term ?>");

					var dates = saleDate;
					var dates1 = dates.split("-");
					var newDate = dates1[1] + "/" + dates1[2] + "/" + dates1[0];

					var dateTerm = new Date(newDate);
					dateTerm.setDate(dateTerm.getDate() + defaultTerm);

					var day = dateTerm.getDate();
				    var month = dateTerm.getMonth() + 1;
				    var year = dateTerm.getFullYear();

				    month = month.toString();
  					month = (month.length < 2) ? ("0" + month) : month;

  					day = day.toString();
  					day = (day.length < 2) ? ("0" + day) : day;

				    var endDate = year + '-' + month + '-' + day;

					$('#sale-deadline').val(endDate);
					$('#deadline-term').html('14');
				}
			});

			$('#sale-date').change(function() {
				if ($('#sale-method').val() == 'Cash') {
					$('#sale-deadline').val($('#sale-date').val());
				}
				else {
					var saleDate = $('#sale-date').val();
					var defaultTerm = parseInt("<?= $setting->setting__webshop_default_credit_term ?>");

					var dates = saleDate;
					var dates1 = dates.split("-");
					var newDate = dates1[1] + "/" + dates1[2] + "/" + dates1[0];

					var dateTerm = new Date(newDate);
					dateTerm.setDate(dateTerm.getDate() + defaultTerm);

					var day = dateTerm.getDate();
				    var month = dateTerm.getMonth() + 1;
				    var year = dateTerm.getFullYear();

				    month = month.toString();
  					month = (month.length < 2) ? ("0" + month) : month;

  					day = day.toString();
  					day = (day.length < 2) ? ("0" + day) : day;

				    var endDate = year + '-' + month + '-' + day;

					$('#sale-deadline').val(endDate);
				}
			});
		}

		function changeProduct() {
			$('.sale-product-list-selection').change(function() {
				var number = $(this).attr('data-number');
				var productId = $(this).val();

				if (productId > 0) {
					$.ajax({
						data :{
							"<?= $csrf['name'] ?>": "<?= $csrf['hash'] ?>"
						},
						dataType: 'JSON',
						error: function() {
							alert('Server Error.');
						},
						success: function(data){
							if (data.status == 'success') {
								$('#sale-product-price-'+ number).val(data.product.price_display);

								calculateQuantity();
							}
							else {
								alert(data.message);
							}
						},
						type : 'POST',
						url : '<?= base_url() ?>sale/ajax_get_product/'+ productId +'/',
					});
				}
			});
		}

		function click() {
			$('#form-back').click(function() {
				back();
			});

			$('#form-submit').click(function() {
				submit(0);
			});

			$('#form-submit-draft').click(function() {
				submit(1);
			});

			$('.form-input').click(function() {
				$(this).removeClass('input-error');
			});

			$('.shipping-address-button').click(function() {
				$('.ui.modal.shipping-address-modal').modal({
					inverted: false,
				}).modal('show');
			});
		}

		function init() {
			$('.ui.search.dropdown.form-input').dropdown('clear');

			$('#sale-date').datepicker({
                dateFormat: 'yy-mm-dd',
                maxDate: 0
            });
		}

		function keypressProduct() {
			$('.sale-item-price, .sale-item-quantity, #sale-discount, #sale-tax, #sale-shipping').keyup(function (e) {
				calculateQuantity();
			});
		}

		function removeItem(number) {
			$('#sale-item-list-'+ number).remove();
			calculateQuantity();
		}

		function reset() {
			$('#sale-number').val("");
			$('#sale-date').val("<?= $date_display; ?>");
			$('.sale-item-quantity').val('1');
			$('.sale-item-price').val('0');
			$('#sale-discount').val('0');
			$('#sale-shipping').val('0');
			$('#sale-tax').val('<?= $setting->setting__webshop_default_tax; ?>');

			$('#sale-deadline').val("<?= $date_display; ?>");

			<? if ($account->location_id > 0): ?>
				$('#sale-location').val("<?= $account->location_id; ?>");
				$('#sale-location-selection').dropdown('set selected', "<?= $account->location_id; ?>");
			<? else: ?>
				$('#sale-location').val("<?= $arr_location[0]->id; ?>");
				$('#sale-location-selection').dropdown('set selected', "<?= $arr_location[0]->id; ?>");
			<? endif; ?>

			$('#sale-customer').val("<?= $setting->setting__webshop_default_customer_id; ?>");
			$('#sale-customer-selection').dropdown('set selected', "<?= $setting->setting__webshop_default_customer_id; ?>");

			$('#sale-method').val("Cash");
			$('#sale-method-selection').dropdown('set selected', "Cash");

			$('#sale-statement').val("<?= $arr_statement[0]->id; ?>");
			$('#sale-statement-selection').dropdown('set selected', "<?= $arr_statement[0]->id; ?>");
		}

		function submit(draft) {
			var saleNumber = $('#sale-number').val();
			var saleDate = $('#sale-date').val();
			var saleTerm = $('#deadline-term').html();
			var saleLocation = $('#sale-location').val();
			var saleCustomer = $('#sale-customer').val();
			var saleStatus = $('#sale-method').val();
			var salestatement = $('#sale-statement').val();
			var saleSubtotal = $('#sale-subtotal').attr('data-subtotal');
			var saleDiscount = $('#sale-discount').val();
			var saleTax = $('#sale-tax').val();
			var saleShipping = $('#sale-shipping').val();
			var saleTotal = $('#sale-total').attr('data-total');
			var found = 0;

			if (found > 0) {
				return;
			}

			$.each($('.data-important'), function(key, data) {
				if ($(data).val() == '') {
					found += 1;

					$(data).addClass('input-error');
				}
			});

			/* get all sale product list */
			var arrsaleItem = [];
			var saleItem = {};

			$.each($('.sale-product-list'), function(key, item) {
				var number = $(item).attr('data-number');

				if ($('#sale-product-'+ number).val() > 0 || $('#sale-product-'+ number).val() != '') {
					saleItem = {};
					saleItem.customer_id  = saleCustomer;
					saleItem.location_id = saleLocation;
					saleItem.product_id = $('#sale-product-'+ number).val();
					saleItem.quantity = $('#sale-product-quantity-'+ number).val();
					saleItem.price = $('#sale-product-price-'+ number).val();

					arrsaleItem.push(saleItem);
				}
			});

			if (arrsaleItem.length <= 0) {
				found += 1;

				$('.ui.dimmer.all-loader').dimmer('hide');
				$('.ui.basic.modal.all-error').modal('show');
				$('.all-error-text').html('Item cannot be empty.');
			}

			if (found > 0) {
				return;
			}

			$('.ui.text.loader').html('Connecting to Database...');
			$('.ui.dimmer.all-loader').dimmer('show');

			$.ajax({
				data :{
					number: saleNumber,
					date: saleDate,
					term: saleTerm,
					location_id: saleLocation,
					customer_id: saleCustomer,
					statement_id: salestatement,
					type: saleStatus,
					sale_item_sale_item: JSON.stringify(arrsaleItem),
					subtotal: saleSubtotal,
					discount: saleDiscount,
					tax: saleTax,
					shipping: saleShipping,
					total: saleTotal,
					draft: draft,
					"<?= $csrf['name'] ?>": "<?= $csrf['hash'] ?>"
				},
				dataType: 'JSON',
				error: function() {
					$('.ui.dimmer.all-loader').dimmer('hide');
					$('.ui.basic.modal.all-error').modal('show');
					$('.all-error-text').html('Server Error.');
				},
				success: function(data){
					if (data.status == 'success') {
						$('.ui.text.loader').html('Redirecting...');

						back();
					}
					else {
						$('.ui.dimmer.all-loader').dimmer('hide');
						$('.ui.basic.modal.all-error').modal('show');
						$('.all-error-text').html(data.message);
					}
				},
				type : 'POST',
				url : '<?= base_url() ?>sale/ajax_add/',
				xhr: function() {
					var percentage = 0;
					var xhr = new window.XMLHttpRequest();

					xhr.upload.addEventListener('progress', function(evt) {
						$('.ui.text.loader').html('Checking Data..');
					}, false);

					xhr.addEventListener('progress', function(evt) {
						$('.ui.text.loader').html('Updating Database...');
					}, false);

					return xhr;
				},
			});
		}
	</script>

	<!-- Dashboard Here -->
	<div class="main-content">
		<div class="ui stackable one column centered grid">
			<div class="column">
				<div class="ui attached message setting-header">
					<div class="header">Add Sale</div>
				</div>
				<div class="form-content">
					<div class="ui form">
						<div class="field">
							<div class="four fields">
								<div class="field">
									<label>Sale Number</label>
									<input id="sale-number" class="form-input" placeholder="AUTO.." type="text">
								</div>
								<div class="field">
									<label>Date</label>
									<input id="sale-date" class="form-input" placeholder="Date.." type="text">
								</div>
								<div class="field">
									<label>Payment Method</label>
									<div id="sale-method-selection" class="ui search selection dropdown form-input">
										<input id="sale-method" type="hidden" class="data-important">
										<i class="dropdown icon"></i>
										<div class="default text">-- Select Status --</div>
										<div class="menu">
											<div class="item" data-value="Cash">Cash</div>
											<div class="item" data-value="Credit">Credit</div>
										</div>
									</div>
								</div>
								<div class="field">
									<label>Deadline (<span id="deadline-term">0</span> Days)</label>
									<input id="sale-deadline" class="form-input" placeholder="Deadline.." type="text" disabled>
								</div>
							</div>

							<div class="three fields">
								<div class="field">
									<label>Location</label>
									<div id="sale-location-selection" class="ui search selection <? if ($account->location_id > 0): ?>disabled<? endif; ?> dropdown form-input">
										<input id="sale-location" type="hidden" class="data-important">
										<i class="dropdown icon"></i>
										<div class="default text">-- Select Location --</div>
										<div class="menu">
											<? foreach ($arr_location as $location): ?>
												<div class="item" data-value="<?= $location->id; ?>"><?= $location->name; ?></div>
											<? endforeach; ?>
										</div>
									</div>
								</div>
								<div class="field">
									<label>Customer</label>
									<div id="sale-customer-selection" class="ui search selection dropdown form-input">
										<input id="sale-customer" type="hidden" class="data-important">
										<i class="dropdown icon"></i>
										<div class="default text">-- Select Customer --</div>
										<div class="menu">
											<? foreach ($arr_customer as $customer): ?>
												<div class="item" data-value="<?= $customer->id; ?>"><?= $customer->name; ?></div>
											<? endforeach; ?>
										</div>
									</div>
								</div>
								<div class="field">
									<label>Account</label>
									<div id="sale-statement-selection" class="ui search selection dropdown form-input">
										<input id="sale-statement" type="hidden" class="data-important">
										<i class="dropdown icon"></i>
										<div class="default text">-- Select Account --</div>
										<div class="menu">
											<? foreach ($arr_statement as $statement): ?>
												<div class="item" data-value="<?= $statement->id; ?>"><?= $statement->name; ?></div>
											<? endforeach; ?>
										</div>
									</div>
								</div>
							</div>

							<div class="field">
								<table class="ui striped selectable celled table" style="border: 1px solid rgba(34, 36, 38, 0.15); border-radius: 0;">
									<thead>
										<tr>
											<th class="td-icon">Action</th>
											<th>Product</th>
											<th style="text-align: right;">Price</th>
											<th style="text-align: right;">Quantity</th>
											<th style="text-align: right;">Total</th>
										</tr>
									</thead>
									<tbody id="sale-item-list">
										<tr id="sale-item-list-1" class="sale-product-list" data-number="1">
											<td class="td-icon">
												<span class="table-icon" data-content="Remove Item" onclick="removeItem(1);">
													<i class="trash outline icon"></i>
												</span>
											</td>
											<td>
												<div id="sale-product-selection-1" class="ui search selection dropdown form-input">
													<input id="sale-product-1" class="sale-product-list-selection" data-number="1" type="hidden" class="data-important">
													<i class="dropdown icon"></i>
													<div class="default text">-- Select Product --</div>
													<div class="menu">
														<? foreach ($arr_product as $product): ?>
															<div class="item" data-value="<?= $product->id; ?>"><?= $product->type; ?> - <?= $product->name; ?></div>
														<? endforeach; ?>
													</div>
												</div>
											</td>
											<td class="td-price-quantity" style="text-align: right;">
												<input id="sale-product-price-1" type="text" class="sale-item-price" data-number="1" placeholder="Price.." style="text-align: right;">
											</td>
											<td class="td-price-quantity" style="text-align: right;">
												<input id="sale-product-quantity-1" type="text" class="sale-item-quantity" data-number="1" placeholder="Quantity.." style="text-align: right;">
											</td>
											<td class="td-price-quantity" style="text-align: right;">
												<span id="sale-product-price-total-1" data-number="1" data-total="0">Rp 0</span>
											</td>
										</tr>
										<tr>
											<td class="sale-add-product" style="cursor: pointer;" colspan="5" onclick="addProduct();">
												<span>
													<i class="plus circle icon"></i>
												</span> Add Product
											</td>
										</tr>
									</tbody>
									<tfoot>
										<tr>
											<td colspan="3" style="text-align: right;">Subtotal</td>
											<td colspan="2" id="sale-subtotal" style="text-align: right;" data-subtotal="0">Rp 0</td>
										</tr>
										<tr>
											<td colspan="3" style="text-align: right;">Discount (%)</td>
											<td style="text-align: right;">
												<input id="sale-discount" type="text" data-number="1" placeholder="Discount.." style="text-align: right;">
											</td>
											<td id="sale-discount-display" style="text-align: right;">Rp 0</td>
										</tr>
										<tr>
											<td colspan="3" style="text-align: right;">PPN (%)</td>
											<td style="text-align: right;">
												<input id="sale-tax" type="text" data-number="1" placeholder="PPN.." style="text-align: right;">
											</td>
											<td id="sale-tax-display" style="text-align: right;">Rp 0</td>
										</tr>
										<tr>
											<td colspan="3" style="text-align: right;">Shipping</td>
											<td style="text-align: right;">
												<input id="sale-shipping" type="text" data-number="1" placeholder="Shipping.." style="text-align: right;">
											</td>
											<td id="sale-shipping-display" style="text-align: right;">Rp 0</td>
										</tr>
										<tr>
											<td colspan="3" style="text-align: right;">Grand Total</td>
											<td colspan="2" id="sale-total" style="text-align: right;" data-total="0">Rp 0</td>
										</tr>
									</tfoot>
								</table>
							</div>
						</div>
					</div>
				</div>
				<div class="ui bottom attached message text-right setting-header">
					<div class="ui buttons">
						<button id="form-back" class="ui button form-button">Back</button>
						<button id="form-submit-draft" class="ui button form-button">Save as Draft</button>
						<button id="form-submit" class="ui button form-button">Save</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</body>
</html>