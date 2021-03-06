	<style type="text/css">
	</style>

	<script type="text/javascript">
		$(function() {
			reset();
			init();
			purchaseKeypress();
			purchaseClick();
		});

		var filterQuery = '<?= $filter; ?>';

		function changeFilter(f) {
			filterQuery = f;
		}

		function deletepurchase() {
			var purchaseId = $('.delete-purchase-button').attr('data-purchase-id');
			var purchaseUpdated = $('.delete-purchase-button').attr('data-purchase-updated');

			$('.ui.basic.modal.modal-warning-delete').modal('hide');
			$('.ui.text.loader').html('Connecting to Database...');
			$('.ui.dimmer.all-loader').dimmer('show');

			$.ajax({
				data :{
					updated: purchaseUpdated,
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
				url : '<?= base_url() ?>purchase/ajax_delete/'+ purchaseId +'/',
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

			window.location.href = '<?= base_url(); ?>purchase/view/'+ page +'/'+ filterQuery +'/'+ searchQuery +'/';
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
		}

		function purchaseClick() {
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
				var purchaseId = $(this).attr('data-purchase-id');
				var purchaseName = $(this).attr('data-purchase-name');
				var purchaseUpdated = $(this).attr('data-purchase-updated');

				$('.delete-purchase-title').html('Delete purchase ' + purchaseName);
				$('.delete-purchase-button').attr('data-purchase-id', purchaseId);
				$('.delete-purchase-button').attr('data-purchase-updated', purchaseUpdated);

				$('.ui.basic.modal.modal-warning-delete').modal('show');
			});
		}

		function purchaseKeypress() {
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
			<span class="delete-purchase-title">Delete purchase</span>
		</div>
		<div class="content text-center">
			<p>You're about to delete this purchase. You will not be able to undo this action. Are you sure?</p>
		</div>
		<div class="actions">
			<div class="ui red basic cancel inverted button">
				<i class="remove icon"></i>
				No
			</div>
			<div class="ui green ok inverted button delete-purchase-button" onclick="deletepurchase();">
				<i class="checkmark icon"></i>
				Yes
			</div>
		</div>
	</div>

	<div class="main-content">
		<div class="ui top attached menu table-menu">
			<div class="item item-add-button">
				Inventory - Purchase Lists
			</div>
			<div class="right menu">
				<? if (isset($acl['purchase']) && $acl['purchase']->add != ''): ?>
					<a class="item item-add-button" href="<?= base_url(); ?>purchase/add/">
						<i class="add circle icon"></i> Add Purchase
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
						<th class="td-icon">Action</th>
						<th>Number</th>
						<th>Vendor</th>
						<th>Date</th>
						<th>Location</th>
						<th>Total</th>
						<th>Payment Method</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>
					<? if (count($arr_purchase) <= 0): ?>
						<tr>
							<td colspan="8">No Result Founds</td>
						</tr>
					<? else: ?>
						<? foreach ($arr_purchase as $purchase): ?>
							<tr>
								<td class="td-icon">
									<? if (isset($acl['purchase']) && $acl['purchase']->edit > 0): ?>
										<a href="<?= base_url(); ?>purchase/edit/<?= $purchase->id; ?>/">
											<span class="table-icon" data-content="Edit purchase">
												<i class="edit icon"></i>
											</span>
										</a>
									<? endif; ?>

									<? if (isset($acl['purchase']) && $acl['purchase']->delete > 0): ?>
										<a class="open-modal-warning-delete" data-purchase-id="<?= $purchase->id; ?>" data-purchase-name="<?= $purchase->name; ?>" data-purchase-updated="<?= $purchase->updated; ?>">
											<span class="table-icon" data-content="Delete purchase">
												<i class="trash outline icon"></i>
											</span>
										</a>
									<? endif; ?>

									<? if (isset($acl['payment']) && $acl['payment']->list > 0): ?>
										<a href="<?= base_url(); ?>payment/view/purchase/<?= $purchase->id; ?>/">
											<span class="table-icon" data-content="Payment">
												<i class="credit card outline icon"></i>
											</span>
										</a>
									<? endif; ?>
								</td>
								<td><? if ($purchase->draft > 0): ?>[DRAFT]<? endif; ?> <?= $purchase->number; ?></td>
								<td><?= $purchase->vendor_name; ?></td>
								<td><?= $purchase->date; ?></td>
								<td><?= $purchase->location_name; ?></td>
								<td>Rp. <?= $purchase->total; ?></td>
								<td><?= $purchase->type; ?></td>
								<td><?= $purchase->status; ?></td>
							</tr>
						<? endforeach; ?>
					<? endif; ?>
				</tbody>
				<tfoot>
					<tr>
						<th colspan="8">
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