	<style type="text/css">
	</style>

	<script type="text/javascript">
		$(function() {
			click();
			init();
			reset();
			change();
		});

		function back() {
			window.location.href = '<?= base_url(); ?>product/view/1/';
		}

		function change() {
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
		}

		function init() {
			tinymce.init({
				selector: 'textarea#product-address',
				height: 300,
				width: '100%',
				plugins: ["advlist autolink lists link charmap preview", "searchreplace visualblocks code", "table contextmenu paste"],
				toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent",
				paste_as_text: true
			});

			$('.ui.search.dropdown.form-input').dropdown('clear');
		}

		function reset() {
			$('#product-number').val("");
			$('#product-barcode').val("");
			$('#product-name').val("");
			$('#product-category').val("");
			$('#product-brand').val("");
			$('#product-price').val("0");
			$('#product-weight').val("1");

			$('#product-type').val("Product");
			$('#product-type-container').dropdown('set selected', "Product");

			$('#product-status').val("Active");
			$('#product-status-container').dropdown('set selected', "Active");
		}

		function submit() {
			var productNumber = $('#product-number').val();
			var productBarcode = $('#product-barcode').val();
			var productName = $('#product-name').val();
			var productType = $('#product-type').val();
			var productCategory = $('#product-category').val();
			var productBrand = $('#product-brand').val();
			var productPrice = $('#product-price').val();
			var productWeight = $('#product-weight').val();
			var productStatus = $('#product-status').val();

			var found = 0;

			$.each($('.data-important'), function(key, data) {
				if ($(data).val() == '' || $(data).val() <= 0) {
					found += 1;

					$(data).addClass('input-error');
				}
			});

			if (found > 0) {
				return;
			}

			$('.ui.text.loader').html('Connecting to Database...');
			$('.ui.dimmer.all-loader').dimmer('show');

			$.ajax({
				data :{
					number: productNumber,
					barcode: productBarcode,
					name: productName,
					type: productType,
					category_id: productCategory,
					brand_id: productBrand,
					price: productPrice,
					weight: productWeight,
					status: productStatus,
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
				url : '<?= base_url() ?>product/ajax_add/',
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
					<div class="header">Add New Product</div>
				</div>
				<div class="form-content">
					<div class="ui form">
						<h4 class="ui dividing header">Product - Details</h4>
						<div class="field">
							<div class="three fields">
								<div class="field">
									<label>Number (SKU)</label>
									<input id="product-number" class="form-input" placeholder="AUTO" type="text">
								</div>
								<div class="field">
									<label>Barcode</label>
									<input id="product-barcode" class="form-input" placeholder="AUTO" type="text">
								</div>
								<div class="field">
									<label>Name <span class="color-red">*</span></label>
									<input id="product-name" class="form-input data-important" placeholder="Name.." type="text">
								</div>
							</div>
							<div class="four fields">
								<div class="field">
									<label>Status</label>
									<div id="product-status-container" class="ui search selection dropdown form-input">
										<input id="product-status" type="hidden">
										<i class="dropdown icon"></i>
										<div class="default text">-- Select Status --</div>
										<div class="menu">
											<div class="item" data-value="Active">Active</div>
											<div class="item" data-value="Void">Void</div>
										</div>
									</div>
								</div>
								<div class="field">
									<label>Category</label>
									<div id="product-category-container" class="ui search selection dropdown form-input">
										<input id="product-category" type="hidden">
										<i class="dropdown icon"></i>
										<div class="default text">-- Select Category --</div>
										<div class="menu">
											<? foreach ($arr_category as $category): ?>
												<div class="item" data-value="<?= $category->id; ?>"><?= $category->name; ?></div>
											<? endforeach; ?>
										</div>
									</div>
								</div>
								<div class="field">
									<label>Brand</label>
									<div id="product-brand-container" class="ui search selection dropdown form-input">
										<input id="product-brand" type="hidden">
										<i class="dropdown icon"></i>
										<div class="default text">-- Select Brand --</div>
										<div class="menu">
											<? foreach ($arr_brand as $brand): ?>
												<div class="item" data-value="<?= $brand->id; ?>"><?= $brand->name; ?></div>
											<? endforeach; ?>
										</div>
									</div>
								</div>
								<div class="field">
									<label>Type</label>
									<div id="product-type-container" class="ui search selection dropdown form-input">
										<input id="product-type" type="hidden">
										<i class="dropdown icon"></i>
										<div class="default text">-- Select Type --</div>
										<div class="menu">
											<div class="item" data-value="Product">Product</div>
											<div class="item" data-value="Service">Service</div>
										</div>
									</div>
								</div>
							</div>
						</div>

						<h4 class="ui dividing header">Product - Additional Detail</h4>
						<div class="field">
							<div class="two fields">
								<div class="field">
									<label>Price</label>
									<input id="product-price" class="form-input" placeholder="Price.." type="text">
								</div>
								<div class="field">
									<label>Weight (KG)</label>
									<input id="product-weight" class="form-input" placeholder="Weight.." type="text">
								</div>
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