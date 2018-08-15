	<style type="text/css">
	</style>

	<script type="text/javascript">
		$(function() {
			reset();
			init();
			productKeypress();
			productClick();
			productChange();
		});

		var filterQuery = '<?= $filter; ?>';

		function changeFilter(f) {
			filterQuery = f;
		}

		function deleteProduct() {
			var productId = $('.delete-product-button').attr('data-product-id');
			var productUpdated = $('.delete-product-button').attr('data-product-updated');

			$('.ui.basic.modal.modal-warning-delete').modal('hide');
			$('.ui.text.loader').html('Connecting to Database...');
			$('.ui.dimmer.all-loader').dimmer('show');

			$.ajax({
				data :{
					updated: productUpdated,
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

						window.location.reload();
					}
					else {
						$('.ui.dimmer.all-loader').dimmer('hide');
						$('.ui.basic.modal.all-error').modal('show');
						$('.all-error-text').html(data.message);
					}
				},
				type : 'POST',
				url : '<?= base_url() ?>product/ajax_delete/'+ productId +'/',
				xhr: function() {
					var percentage = 0;
					var xhr = new window.XMLHttpRequest();

					xhr.upload.addEventListener('progress', function(evt) {
						$('.ui.text.loader').html('Validating Data..');
					}, false);

					xhr.addEventListener('progress', function(evt) {
						$('.ui.text.loader').html('Delete Data from Database...');
					}, false);

					return xhr;
				},
			});
		}

		function filter(page) {
			var searchQuery = ($('.input-search').val() == '') ? '' : $.base64('encode', $('.input-search').val());

			window.location.href = '<?= base_url(); ?>product/view/'+ page +'/'+ filterQuery +'/'+ searchQuery +'/';
		}

		function init() {
			$('.dropdown-search, .dropdown-filter').dropdown({
				allowAdditions: true
			});

			$('table').tablesort();
		}

		function reset() {
			$('.input-search').val("<?= $query; ?>");
			$('#input-page').val("<?= $page; ?>");

			<? foreach ($arr_product as $product): ?>
				<? if ($product->status == 'Active'): ?>
					$('#checkbox-product-<?= $product->id; ?>').attr('checked', true);
				<? else: ?>
					$('#checkbox-product-<?= $product->id; ?>').attr('checked', false);
				<? endif; ?>
			<? endforeach; ?>
		}

		function productChange() {
			$('.product-checkbox').change(function() {
				var productId = $(this).attr('value');
				var checked = ($(this).is(':checked')) ? 'Active' : 'Void';

				$.ajax({
					data :{
						status: checked,
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

							window.location.reload();
						}
						else {
							$('.ui.dimmer.all-loader').dimmer('hide');
							$('.ui.basic.modal.all-error').modal('show');
							$('.all-error-text').html(data.message);
						}
					},
					type : 'POST',
					url : '<?= base_url() ?>product/ajax_change_status/'+ productId +'/',
					xhr: function() {
						var percentage = 0;
						var xhr = new window.XMLHttpRequest();

						xhr.upload.addEventListener('progress', function(evt) {
							$('.ui.text.loader').html('Validating Data..');
						}, false);

						xhr.addEventListener('progress', function(evt) {
							$('.ui.text.loader').html('Delete Data from Database...');
						}, false);

						return xhr;
					},
				});
			});
		}

		function productClick() {
			$('.button-prev').click(function() {
				var page = parseInt('<?= $page; ?>');

				page = page - 1 ;

				if (page <= 0) {
					return;
				}

				filter(page);
			});

			$('.button-next').click(function() {
				var page = parseInt('<?= $page; ?>');
				var maxPage = parseInt('<?= $count_page; ?>');

				page = page + 1 ;

				if (page > maxPage) {
					return;
				}

				filter(page);
			});

			$('.open-modal-warning-delete').click(function() {
				var productId = $(this).attr('data-product-id');
				var productName = $(this).attr('data-product-name');
				var productUpdated = $(this).attr('data-product-updated');

				$('.delete-product-title').html('Delete product ' + productName);
				$('.delete-product-button').attr('data-product-id', productId);
				$('.delete-product-button').attr('data-product-updated', productUpdated);

				$('.ui.basic.modal.modal-warning-delete').modal('show');
			});
		}

		function productKeypress() {
			$('.input-search').keypress(function(e) {
				if (e.which == 13) {
					var page = 1;

					filter(page);
				}
			});

			$('#input-page').keypress(function(e) {
				if (e.which == 13) {
					var page = $('#input-page').val();

					filter(page);
				}
			});
		}
	</script>

	<!-- Dashboard Here -->
	<div class="ui basic modal modal-warning-delete">
		<div class="ui icon header">
			<i class="trash outline icon delete-icon"></i>
			<span class="delete-product-title">Delete Product</span>
		</div>
		<div class="content text-center">
			<p>You're about to delete this product. You will not be able to undo this action. Are you sure?</p>
		</div>
		<div class="actions">
			<div class="ui red basic cancel inverted button">
				<i class="remove icon"></i>
				No
			</div>
			<div class="ui green ok inverted button delete-product-button" onclick="deleteProduct();">
				<i class="checkmark icon"></i>
				Yes
			</div>
		</div>
	</div>

	<div class="main-content">
		<div class="ui top attached menu table-menu">
			<div class="item item-add-button">
				Data - Product Lists
			</div>
			<div class="right menu">
				<? if (isset($acl['product']) && $acl['product']->add != ''): ?>
					<a class="item item-add-button" href="<?= base_url(); ?>product/add/">
						<i class="add circle icon"></i> Add Product
					</a>
				<? endif; ?>
				<div class="item">
					<div class="ui dropdown dropdown-filter">
						<div class="text"><?= ucwords($filter); ?></div>
						<i class="dropdown icon"></i>
						<div class="menu">
							<div class="item" onclick="changeFilter('all');">All</div>
						</div>
					</div>
				</div>
				<div class="ui right aligned category search item search-item-container">
					<div class="ui transparent icon input">
						<input class="input-search" placeholder="Search..." type="text">
						<i class="search link icon"></i>
					</div>
					<div class="results"></div>
				</div>
			</div>
		</div>
		<div class="ui bottom attached segment table-segment">
			<table class="ui striped selectable sortable celled table">
				<thead>
					<tr>
						<th style="width: 60px;"></th>
						<th class="td-icon">Action</th>
						<th>Number</th>
						<th>Type</th>
						<th>Name</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>
					<? if (count($arr_product) <= 0): ?>
						<tr>
							<td colspan="6">No Result Founds</td>
						</tr>
					<? else: ?>
						<? foreach ($arr_product as $product): ?>
							<tr>
								<td style="width: 45px; text-align: center;">
									<div class="ui checkbox">
										<input id="checkbox-product-<?= $product->id; ?>" class="product-checkbox" name="checkbox-product-id" type="checkbox" value="<?= $product->id; ?>">
										<label></label>
									</div>
								</td>
								<td class="td-icon">
									<? if (isset($acl['product']) && $acl['product']->edit > 0): ?>
										<a href="<?= base_url(); ?>product/edit/<?= $product->id; ?>/">
											<span class="table-icon" data-content="Edit product">
												<i class="edit icon"></i>
											</span>
										</a>
									<? endif; ?>

									<? if (isset($acl['product']) && $acl['product']->delete > 0): ?>
										<a class="open-modal-warning-delete" data-product-id="<?= $product->id; ?>" data-product-name="<?= $product->name; ?>" data-product-updated="<?= $product->updated; ?>">
											<span class="table-icon" data-content="Delete product">
												<i class="trash outline icon"></i>
											</span>
										</a>
									<? endif; ?>
								</td>
								<td><?= $product->number; ?></td>
								<td><?= $product->type; ?></td>
								<td><?= $product->name; ?></td>
								<td><?= $product->status; ?></td>
							</tr>
						<? endforeach; ?>
					<? endif; ?>
				</tbody>
				<tfoot>
					<tr>
						<th colspan="6">
							<button class="ui button button-prev">Prev</button>
							<span>
								<div class="ui input input-page">
									<input id="input-page" placeholder="" type="text">
								</div> / <?= $count_page; ?>
							</span>
							<button class="ui button button-next">Next</button>
						</th>
					</tr>
				</tfoot>
			</table>
		</div>
	</div>
</body>
</html>