	<style type="text/css">
	</style>

	<script type="text/javascript">
		$(function() {
			init();
			reset();
			change();
			inventoryKeypress();
			inventoryClick();
		});

		function change() {
			$('#inventory-location-search, #inventory-product-search').change(function() {
				window.location.href = '<?= base_url(); ?>inventory/view/1/'+ $('#inventory-location-search').val() +'/'+ $('#inventory-product-search').val() +'/';
			});
		}

		function filter(page) {
			window.location.href = '<?= base_url(); ?>inventory/view/'+ page +'/<?= $location_id; ?>/<?= $product_id; ?>/';
		}

		function init() {
			$('.dropdown-search, .dropdown-filter').dropdown({
				allowAdditions: true
			});

			$('.ui.search.dropdown.form-input').dropdown('clear');

			$('table').tablesort();
		}

		function reset() {
			$('#inventory-location-search').val("<?= $location_id; ?>");
			$('#inventory-location-search-container').dropdown('set selected', "<?= $location_id; ?>");

			$('#inventory-product-search').val("<?= $product_id; ?>");
			$('#inventory-product-search-container').dropdown('set selected', "<?= $product_id; ?>");

			$('#input-page').val("<?= $page; ?>");
		}

		function inventoryClick() {
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
		}

		function inventoryKeypress() {
			$('#input-page').keypress(function(e) {
				if (e.which == 13) {
					var page = $('#input-page').val();

					filter(page);
				}
			});
		}
	</script>

	<!-- Dashboard Here -->
	<div class="main-content">
		<div class="ui top attached menu table-menu">
			<div class="item">
				Inventory
			</div>
			<div class="right menu">
				<div class="ui right aligned inventory search item search-item-container">
					<div id="inventory-product-search-container" class="ui search selection dropdown form-input">
						<input id="inventory-product-search" type="hidden">
						<i class="dropdown icon"></i>
						<div class="default text">-- Select Product --</div>
						<div class="menu">
							<div class="item" data-value="0">All Product</div>
							<? foreach ($arr_product as $product): ?>
								<div class="item" data-value="<?= $product->id; ?>"><?= $product->name; ?></div>
							<? endforeach; ?>
						</div>
					</div>
				</div>

				<div class="ui right aligned inventory search item search-item-container">
					<div id="inventory-location-search-container" class="ui search selection dropdown form-input">
						<input id="inventory-location-search" type="hidden">
						<i class="dropdown icon"></i>
						<div class="default text">-- Select Location --</div>
						<div class="menu">
							<? if ($account->location_id <= 0): ?>
								<div class="item" data-value="0">All Location</div>
							<? endif; ?>

							<? foreach ($arr_location as $location): ?>
								<div class="item" data-value="<?= $location->id; ?>"><?= $location->name; ?></div>
							<? endforeach; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<div class="ui bottom attached segment table-segment">
			<table class="ui striped selectable sortable celled table">
				<thead>
					<tr>
						<th>Product Number</th>
						<th>Product Name</th>
						<th class="align-right">Quantity</th>
						<th>Location</th>
					</tr>
				</thead>
				<tbody>
					<? if (count($arr_inventory) <= 0): ?>
						<tr>
							<td colspan="6">No Result Founds</td>
						</tr>
					<? else: ?>
						<? foreach ($arr_inventory as $inventory): ?>
							<tr>
								<td><?= $inventory->product_number; ?></td>
								<td><?= $inventory->product_name; ?></td>
								<td class="align-right <? if ($inventory->quantity <= 0): ?>error<? endif; ?>"><?= $inventory->quantity_display; ?></td>
								<td><?= $inventory->location_name; ?></td>
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
									<input id="input-page" placeholder="" type="text" value="0">
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