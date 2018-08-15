	<style type="text/css">
	</style>

	<script type="text/javascript">
		$(function() {
			click();
			init();
			reset();
			change();
			changeProduct();
			keyUpreceive();
		});

		function addProduct() {
			var number = 0;
			$('.receive-add-product').remove();

			$.each($('.receive-product-list'), function(key, item) {
				number = $(item).attr('data-number');
			});

			var nextNumber = parseInt(number) + 1;

			var newItemList = '<tr id="receive-item-list-'+ nextNumber +'" class="receive-product-list" data-number="'+ nextNumber +'"><td class="td-icon"><span class="table-icon" data-content="Remove Item" onclick="removeItem('+ nextNumber +');"><i class="trash outline icon"></i></span></td><td><div id="receive-product-selection-'+ nextNumber +'" class="ui search selection dropdown form-input"><input id="receive-product-'+ nextNumber +'" class="receive-product-list-selection" data-number="'+ nextNumber +'" type="hidden" class="data-important"><i class="dropdown icon"></i><div class="default text">-- Select Product --</div><div class="menu"><? foreach ($arr_product as $product): ?><div class="item" data-value="<?= $product->id; ?>"><?= $product->name; ?></div><? endforeach; ?></div></div></td><td style="text-align: right;"><input id="receive-product-quantity-'+ nextNumber +'" type="text" class="receive-item-quantity" data-number="'+ nextNumber +'" placeholder="Quantity.."></td></tr><tr><td class="receive-add-product" style="cursor: pointer;" colspan="3" onclick="addProduct();"><span><i class="plus circle icon"></i></span> Add Product</td></tr>';

			$('#receive-item-list').append(newItemList);
			$('#receive-product-quantity-'+ nextNumber).val("0");
			$('#receive-product-selection-'+ nextNumber).dropdown('clear');

			changeProduct();
			keyUpreceive();
		}

		function back() {
			window.location.href = '<?= base_url(); ?>receive/view/1/';
		}

		function calculateQuantity(number) {
			var inventoryQuantity = $('#data-inventory-quantity-'+ number).html();
			var inputQuantity = ($('#receive-product-quantity-'+ number).val() != '') ? $('#receive-product-quantity-'+ number).val() : 0;

			var fixedQuantity = parseInt(inventoryQuantity) - parseInt(inputQuantity);

			$('#data-inventory-quantity-fix-'+ number).html(fixedQuantity);
		}

		function change() {
			$('#receive-status').change(function() {
				if ($('#receive-status').val() == 'Shipped') {
					$('#receive-shipping-receipt').prop('disabled', false);
				}
				else {
					$('#receive-shipping-receipt').prop('disabled', true);
				}
			});

			$('#receive-location').change(function() {
				$('#receive-item-list').empty();

				var resetItemList = '<tr id="receive-item-list-1" class="receive-product-list" data-number="1"><td class="td-icon"><span class="table-icon" data-content="Remove Item" onclick="removeItem(1);"><i class="trash outline icon"></i></span></td><td><div id="receive-product-selection-1" class="ui search selection dropdown form-input"><input id="receive-product-1" class="receive-product-list-selection" data-number="1" type="hidden" class="data-important"><i class="dropdown icon"></i><div class="default text">-- Select Product --</div><div class="menu"><? foreach ($arr_product as $product): ?><div class="item" data-value="<?= $product->id; ?>"><?= $product->name; ?></div><? endforeach; ?></div></div></td><td style="text-align: right;"><span id="data-inventory-quantity-1">0</span></td><td style="text-align: right;"><input id="receive-product-quantity-1" type="text" class="receive-item-quantity" data-number="1" placeholder="Quantity.."></td><td style="text-align: right;"><span id="data-inventory-quantity-fix-1">0</span></td></tr><tr><td class="receive-add-product" style="cursor: pointer;" colspan="5" onclick="addProduct();"><span><i class="plus circle icon"></i></span> Add Product</td></tr>';

				$('#receive-item-list').append(resetItemList);
				$('#receive-product-quantity-1').val("0");
				$('#receive-product-selection-1').dropdown('clear');

				changeProduct();
				keyUpreceive();
			});
		}

		function changeProduct() {
			$('.receive-product-list-selection').change(function() {
				var number = $(this).attr('data-number');
				var productId = $(this).val();
				var locationId = $('#receive-location').val();

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
								$('#data-inventory-quantity-'+ number).html(data.inventory.quantity_display);

								calculateQuantity(number);
							}
							else {
								alert(data.message);
							}
						},
						type : 'POST',
						url : '<?= base_url() ?>inventory/ajax_get/'+ productId +'/'+ locationId +'/',
					});
				}
			});
		}

		function click() {
			$('#form-back').click(function() {
				back();
			});

			$('#form-submit').click(function() {
				submit();
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

			$('#receive-date').datepicker({
                dateFormat: 'yy-mm-dd',
                maxDate: 0
            });
		}

		function keyUpreceive() {
			$('.receive-item-quantity').keyup(function (e) {
				var number = $(this).attr('data-number');

				calculateQuantity(number);
			});
		}

		function removeItem(number) {
			$('#receive-item-list-'+ number).remove();
		}

		function reset() {
			$('#receive-number').val("<?= $receive->number; ?>");
			$('#receive-date').val("<?= $receive->date_display; ?>");

			$('#receive-location').val("<?= $receive->location_id; ?>");
			$('#receive-location-selection').dropdown('set selected', "<?= $receive->location_id; ?>");

			<? foreach ($receive->arr_receive_item as $key => $receive_item): ?>
				$('#receive-product-selection-<?= $key + 1; ?>').dropdown('set selected', "<?= $receive_item->product_id; ?>");
				$('#receive-product-quantity-<?= $key + 1; ?>').val('<?= $receive_item->quantity_display; ?>');
			<? endforeach; ?>
		}

		function submit() {
			var receiveNumber = $('#receive-number').val();
			var receiveDate = $('#receive-date').val();
			var receiveLocation = $('#receive-location').val();
			var found = 0;

			$.each($('.data-important'), function(key, data) {
				if ($(data).val() == '') {
					found += 1;

					$(data).addClass('input-error');
				}
			});

			/* get all receive product list */
			var arrreceiveItem = [];
			var receiveItem = {};

			$.each($('.receive-product-list'), function(key, item) {
				var number = $(item).attr('data-number');

				if ($('#receive-product-'+ number).val() > 0 || $('#receive-product-'+ number).val() != '') {
					receiveItem = {};
					receiveItem.location_id = receiveLocation;
					receiveItem.product_id = $('#receive-product-'+ number).val();
					receiveItem.quantity = $('#receive-product-quantity-'+ number).val();

					if ($('#receive-product-quantity-'+ number).val() == 0 || $('#receive-product-quantity-'+ number).val() == '') {
						found += 1;

						$('.ui.dimmer.all-loader').dimmer('hide');
						$('.ui.basic.modal.all-error').modal('show');
						$('.all-error-text').html('Quantity must not be empty.');
					}

					arrreceiveItem.push(receiveItem);
				}
			});

			if (arrreceiveItem.length <= 0) {
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
					number: receiveNumber,
					date: receiveDate,
					location_id: receiveLocation,
					receive_item_receive_item: JSON.stringify(arrreceiveItem),
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
				url : '<?= base_url() ?>receive/ajax_edit/<?= $receive->id; ?>/',
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
					<div class="header">Edit receive</div>
				</div>
				<div class="form-content">
					<div class="ui form">
						<div class="field">
							<div class="three fields">
								<div class="field">
									<label>receive Number</label>
									<input id="receive-number" class="form-input" placeholder="AUTO.." type="text">
								</div>
								<div class="field">
									<label>Date</label>
									<input id="receive-date" class="form-input" placeholder="Date.." type="text">
								</div>
								<div class="field">
									<label>Location</label>
									<div id="receive-location-selection" class="ui search selection dropdown form-input">
										<input id="receive-location" type="hidden" class="data-important">
										<i class="dropdown icon"></i>
										<div class="default text">-- Select Location --</div>
										<div class="menu">
											<div class="item" data-value="<?= $receive->location_id; ?>"><?= $receive->location_name; ?></div>
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
											<th>Quantity</th>
										</tr>
									</thead>
									<tbody id="receive-item-list">
										<? foreach ($receive->arr_receive_item as $key => $receive_item): ?>
											<tr id="receive-item-list-<?= $key + 1; ?>" class="receive-product-list" data-number="<?= $key + 1; ?>">
												<td class="td-icon">
													<span class="table-icon" data-content="Remove Item" onclick="removeItem('<?= $key + 1; ?>');">
														<i class="trash outline icon"></i>
													</span>
												</td>
												<td>
													<div id="receive-product-selection-<?= $key + 1; ?>" class="ui search selection dropdown form-input">
														<input id="receive-product-<?= $key + 1; ?>" class="receive-product-list-selection" data-number="<?= $key + 1; ?>" type="hidden" class="data-important">
														<i class="dropdown icon"></i>
														<div class="default text">-- Select Product --</div>
														<div class="menu">
															<? foreach ($arr_product as $product): ?>
																<div class="item" data-value="<?= $product->id; ?>"><?= $product->name; ?></div>
															<? endforeach; ?>
														</div>
													</div>
												</td>
												<td style="text-align: right;">
													<input id="receive-product-quantity-<?= $key + 1; ?>" type="text" class="receive-item-quantity" data-number="<?= $key + 1; ?>" placeholder="Quantity..">
												</td>
											</tr>
										<? endforeach; ?>
										<tr>
											<td class="receive-add-product" style="cursor: pointer;" colspan="3" onclick="addProduct();">
												<span>
													<i class="plus circle icon"></i>
												</span> Add Product
											</td>
										</tr>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
				<div class="ui bottom attached message text-right setting-header">
					<div class="ui buttons">
						<button id="form-back" class="ui left attached button form-button">Back</button>
						<button id="form-submit" class="ui right attached button form-button">Save</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</body>
</html>