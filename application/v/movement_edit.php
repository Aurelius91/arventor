	<style type="text/css">
	</style>

	<script type="text/javascript">
		$(function() {
			click();
			init();
			reset();
		});

		function addProduct() {
			var number = 0;
			$('.movement-add-product').remove();

			$.each($('.movement-product-list'), function(key, item) {
				number = $(item).attr('data-number');
			});

			var nextNumber = parseInt(number) + 1;

			var newItemList = '<tr id="movement-item-list-'+ nextNumber +'" class="movement-product-list" data-number="'+ nextNumber +'"><td class="td-icon"><span class="table-icon" data-content="Remove Item" onclick="removeItem('+ nextNumber +');"><i class="trash outline icon"></i></span></td><td><div id="movement-product-selection-'+ nextNumber +'" class="ui search selection dropdown form-input"><input id="movement-product-'+ nextNumber +'" class="movement-product-list-selection" data-number="'+ nextNumber +'" type="hidden" class="data-important"><i class="dropdown icon"></i><div class="default text">-- Select Product --</div><div class="menu"><? foreach ($arr_product as $product): ?><div class="item" data-value="<?= $product->id; ?>"><?= $product->name; ?></div><? endforeach; ?></div></div></td><td style="text-align: right;"><input id="movement-product-quantity-'+ nextNumber +'" type="text" class="movement-item-quantity" data-number="'+ nextNumber +'" placeholder="Quantity.."></td></tr><tr><td class="movement-add-product" style="cursor: pointer;" colspan="5" onclick="addProduct();"><span><i class="plus circle icon"></i></span> Add Product</td></tr>';

			$('#movement-item-list').append(newItemList);
			$('#movement-product-quantity-'+ nextNumber).val("0");
			$('#movement-product-selection-'+ nextNumber).dropdown('clear');

			changeProduct();
			keyUpmovement();
		}

		function back() {
			window.location.href = '<?= base_url(); ?>movement/view/1/';
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

			$('#movement-date').datepicker({
                dateFormat: 'yy-mm-dd',
                maxDate: 0
            });
		}

		function removeItem(number) {
			$('#movement-item-list-'+ number).remove();
		}

		function reset() {
			$('#movement-number').val("<?= $movement->number; ?>");
			$('#movement-date').val("<?= $movement->date_display; ?>");
			$('.movement-item-quantity').val('0');

			<? if ($account->location_id > 0): ?>
				$('#movement-location').val("<?= $account->location_id; ?>");
				$('#movement-location-selection').dropdown('set selected', "<?= $account->location_id; ?>");
			<? else: ?>
				$('#movement-location').val("<?= $arr_location[0]->id; ?>");
				$('#movement-location-selection').dropdown('set selected', "<?= $arr_location[0]->id; ?>");
			<? endif; ?>

			$('#movement-location-to').val("<?= $movement->location_to_id; ?>");
			$('#movement-location-to-selection').dropdown('set selected', "<?= $movement->location_to_id; ?>");

			<? foreach ($movement->arr_movement_item as $key => $movement_item): ?>
				$('#movement-product-selection-<?= $key + 1; ?>').dropdown('set selected', "<?= $movement_item->product_id; ?>");
				$('#movement-product-quantity-<?= $key + 1; ?>').val("<?= $movement_item->quantity_display; ?>");
			<? endforeach; ?>
		}

		function submit() {
			var movementNumber = $('#movement-number').val();
			var movementDate = $('#movement-date').val();
			var movementLocation = $('#movement-location').val();
			var movementLocationTo = $('#movement-location-to').val();
			var found = 0;

			if (movementLocationTo == '' || movementLocationTo <= 0) {
				$('.ui.dimmer.all-loader').dimmer('hide');
				$('.ui.basic.modal.all-error').modal('show');
				$('.all-error-text').html('Location to cannot be empty.');

				found += 1;
			}

			if (movementLocationTo == movementLocation) {
				$('.ui.dimmer.all-loader').dimmer('hide');
				$('.ui.basic.modal.all-error').modal('show');
				$('.all-error-text').html('You cannot send items to the same location.');

				found += 1;
			}

			if (found > 0) {
				return;
			}

			$.each($('.data-important'), function(key, data) {
				if ($(data).val() == '') {
					found += 1;

					$(data).addClass('input-error');
				}
			});

			/* get all movement product list */
			var arrMovementItem = [];
			var movementItem = {};

			$.each($('.movement-product-list'), function(key, item) {
				var number = $(item).attr('data-number');

				if ($('#movement-product-'+ number).val() > 0 || $('#movement-product-'+ number).val() != '') {
					movementItem = {};
					movementItem.location_id = movementLocation;
					movementItem.location_to_id = movementLocationTo;
					movementItem.product_id = $('#movement-product-'+ number).val();
					movementItem.quantity = $('#movement-product-quantity-'+ number).val();

					arrMovementItem.push(movementItem);
				}
			});

			if (arrMovementItem.length <= 0) {
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
					number: movementNumber,
					date: movementDate,
					location_id: movementLocation,
					location_to_id: movementLocationTo,
					movement_item_movement_item: JSON.stringify(arrMovementItem),
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
				url : '<?= base_url() ?>movement/ajax_edit/<?= $movement->id; ?>/',
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
					<div class="header">Edit Movement</div>
				</div>
				<div class="form-content">
					<div class="ui form">
						<div class="field">
							<div class="four fields">
								<div class="field">
									<label>Movement Number</label>
									<input id="movement-number" class="form-input" placeholder="AUTO.." type="text">
								</div>
								<div class="field">
									<label>Date</label>
									<input id="movement-date" class="form-input" placeholder="Date.." type="text">
								</div>
								<div class="field">
									<label>Location From</label>
									<div id="movement-location-selection" class="ui search selection <? if ($account->location_id > 0): ?>disabled<? endif; ?> dropdown form-input">
										<input id="movement-location" type="hidden" class="data-important">
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
									<label>Location To</label>
									<div id="movement-location-to-selection" class="ui search selection dropdown form-input">
										<input id="movement-location-to" type="hidden" class="data-important">
										<i class="dropdown icon"></i>
										<div class="default text">-- Select Location --</div>
										<div class="menu">
											<? foreach ($arr_location as $location): ?>
												<? if ($account->location_id > 0 && $account->location_id == $location->id): ?>
													<? continue; ?>
												<? endif; ?>

												<div class="item" data-value="<?= $location->id; ?>"><?= $location->name; ?></div>
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
											<th>Quantity</th>
										</tr>
									</thead>
									<tbody id="movement-item-list">
										<? foreach ($movement->arr_movement_item as $key => $movement_item): ?>
											<tr id="movement-item-list-<?= $key + 1; ?>" class="movement-product-list" data-number="<?= $key + 1; ?>">
												<td class="td-icon">
													<span class="table-icon" data-content="Remove Item" onclick="removeItem('<?= $key + 1; ?>');">
														<i class="trash outline icon"></i>
													</span>
												</td>
												<td>
													<div id="movement-product-selection-<?= $key + 1; ?>" class="ui search selection dropdown form-input">
														<input id="movement-product-<?= $key + 1; ?>" class="movement-product-list-selection" data-number="<?= $key + 1; ?>" type="hidden" class="data-important">
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
													<input id="movement-product-quantity-<?= $key + 1; ?>" type="text" class="movement-item-quantity" data-number="<?= $key + 1; ?>" placeholder="Quantity..">
												</td>
											</tr>
										<? endforeach; ?>
										<tr>
											<td class="movement-add-product" style="cursor: pointer;" colspan="5" onclick="addProduct();">
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