	<style type="text/css">
	</style>

	<script type="text/javascript">
		$(function() {
			reset();
			init();
			transferKeypress();
			transferClick();
			onChange();
		});

		var filterQuery = '<?= $filter; ?>';

		function changeFilter(f) {
			filterQuery = f;
		}

		function addTransfer() {
			var transferNumber = $('#transfer-number-add').val();
			var transferDate = $('#transfer-date-add').val();
			var transferAmount = $('#transfer-amount-add').val();
			var transferStatement = $('#transfer-statement-add').val();
			var transferStatementTo = $('#transfer-statement-to-add').val();
			var transferDescription = $('#transfer-description-add').val();
			var found = 0;

			$.each($('.data-important-add'), function(key, data) {console.log($(this));
				if ($(data).val() == '') {
					found += 1;

					$('.color-red.warning').html('This Field Must Be Filled');
				}
			});

			if (found > 0) {
				return;
			}

			$('.add-transfer-modal').modal('hide');
			$('.ui.text.loader').html('Connecting to Database...');
			$('.ui.dimmer.all-loader').dimmer('show');

			$.ajax({
				data :{
					number: transferNumber,
					date: transferDate,
					amount: transferAmount,
					statement_id: transferStatement,
					statement_to_id: transferStatementTo,
					description: transferDescription,
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
						$('.color-red.warning').html(data.message);

						$('.add-transfer-modal').modal({
							inverted: true,
						}).modal('show');
					}
				},
				type : 'POST',
				url : '<?= base_url() ?>transfer/ajax_add/',
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

		function deleteTransfer() {
			var transferId = $('.delete-transfer-button').attr('data-transfer-id');
			var transferUpdated = $('.delete-transfer-button').attr('data-transfer-updated');

			$('.ui.basic.modal.modal-warning-delete').modal('hide');
			$('.ui.text.loader').html('Connecting to Database...');
			$('.ui.dimmer.all-loader').dimmer('show');

			$.ajax({
				data :{
					updated: transferUpdated,
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
				url : '<?= base_url() ?>transfer/ajax_delete/'+ transferId +'/',
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

		function editTransfer() {
			var transferNumber = $('#transfer-number-edit').val();
			var transferDate = $('#transfer-date-edit').val();
			var transferAmount = $('#transfer-amount-edit').val();
			var transferStatement = $('#transfer-statement-edit').val();
			var transferStatementTo = $('#transfer-statement-to-edit').val();
			var transferDescription = $('#transfer-description-edit').val();

			var transferId = $('#transfer-number-edit').data('transfer_id');
			var transferUpdated = $('#transfer-number-edit').data('updated');
			var found = 0;

			$.each($('.data-important-edit'), function(key, data) {
				if ($(data).val() == '') {
					found += 1;

					$('.color-red.warning').html('This Field Must Be Filled');
				}
			});

			if (found > 0) {
				return;
			}

			$('.edit-transfer-modal').modal('hide');
			$('.ui.text.loader').html('Connecting to Database...');
			$('.ui.dimmer.all-loader').dimmer('show');

			$.ajax({
				data :{
					number: transferNumber,
					date: transferDate,
					amount: transferAmount,
					statement_id: transferStatement,
					statement_to_id: transferStatementTo,
					description: transferDescription,
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
						$('.color-red.warning').html(data.message);

						$('.edit-transfer-modal').modal({
							inverted: true,
						}).modal('show');
					}
				},
				type : 'POST',
				url : '<?= base_url() ?>transfer/ajax_edit/'+ transferId +'/',
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

		function filter(page) {
			var searchQuery = ($('.input-search').val() == '') ? '' : $.base64('encode', $('.input-search').val());

			window.location.href = '<?= base_url(); ?>transfer/view/'+ page +'/'+ filterQuery +'/'+ searchQuery +'/';
		}

		function getTransfer(transferId) {
			$('.ui.text.loader').html('Connecting to Database...');

			$.ajax({
				data :{
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
						$('#transfer-number-edit').val(data.transfer.number);
						$('#transfer-date-edit').val(data.transfer.date_display);
						$('#transfer-amount-edit').val(data.transfer.amount_display);
						$('#transfer-statement-edit').val(data.transfer.statement_id);
						$('#transfer-statement-container-edit').dropdown('set selected', data.transfer.statement_id);
						$('#transfer-statement-to-edit').val(data.transfer.statement_to_id);
						$('#transfer-statement-to-container-edit').dropdown('set selected', data.transfer.statement_to_id);
						$('#transfer-description-edit').val(data.transfer.description);

						$('#transfer-number-edit').data('transfer_id', transferId);
						$('#transfer-number-edit').data('updated', data.transfer.updated);

						$('.edit-transfer-modal').modal({
							inverted: false,
						}).modal('show');
					}
					else {
						$('.ui.dimmer.all-loader').dimmer('hide');
						$('.color-red.warning').html(data.message);

						$('.add-transfer-modal').modal({
							inverted: true,
						}).modal('show');
					}
				},
				type : 'POST',
				url : '<?= base_url() ?>transfer/ajax_get/'+ transferId +'/',
				xhr: function() {
					var percentage = 0;
					var xhr = new window.XMLHttpRequest();

					xhr.upload.addEventListener('progress', function(evt) {
						$('.ui.text.loader').html('Checking Data..');
					}, false);

					xhr.addEventListener('progress', function(evt) {
						$('.ui.text.loader').html('Retrieving Data...');
					}, false);

					return xhr;
				},
			});
		}

		function init() {
			$('.dropdown-search, .dropdown-filter').dropdown({
				allowAdditions: true
			});

			$('.ui.search.dropdown.form-input').dropdown('clear');

			$('#transfer-date-add, #transfer-date-edit').datepicker({
                dateFormat: 'yy-mm-dd',
                maxDate: 0
            });

            $('table').tablesort();
		}

		function onChange() {
			$('#transfer-image-add').change(function() {
				var file_data = $('#transfer-image-add').prop('files')[0];
				var form_data = new FormData();
				form_data.append('file', file_data);
				form_data.append("<?= $csrf['name'] ?>", "<?= $csrf['hash'] ?>");

				$.ajax({
					cache: false,
					contentType: false,
					data: form_data,
					dataType: 'JSON',
					error: function() {
						alert('Server Error.');
					},
					processData: false,
					type: 'post',
					success: function(data) {
						if (data.status == 'success') {
							$('#transfer-image-add').data('image_id', data.image_id);

							alert('Image Uploaded');
						}
						else {
							alert(data.message);
						}
					},
					url: '<?= base_url(); ?>image/ajax_upload_all/',
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
					}
				});
			});

			$('#transfer-image-edit').change(function() {
				var file_data = $('#transfer-image-edit').prop('files')[0];
				var form_data = new FormData();
				form_data.append('file', file_data);
				form_data.append("<?= $csrf['name'] ?>", "<?= $csrf['hash'] ?>");

				$.ajax({
					cache: false,
					contentType: false,
					data: form_data,
					dataType: 'JSON',
					error: function() {
						alert('Server Error.');
					},
					processData: false,
					type: 'post',
					success: function(data) {
						if (data.status == 'success') {
							$('#transfer-image-edit').data('image_id', data.image_id);

							alert('Image Uploaded');
						}
						else {
							alert(data.message);
						}
					},
					url: '<?= base_url(); ?>image/ajax_upload_all/',
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
					}
				});
			});
		}

		function reset() {
			$('.input-search').val("<?= $query; ?>");
			$('#input-page').val("<?= $page; ?>");

			$('#transfer-name-add').val("");
			$('#transfer-number-add').val("");
			$('#transfer-date-add').val("<?= $date_display; ?>");
			$('#transfer-amount-add').val("0");
			$('#transfer-type-add').val("");
			$('#transfer-type-container-add').dropdown('set selected', "");
			$('#transfer-statement-add').val("<?= $arr_statement[0]->id; ?>");
			$('#transfer-statement-container-add').dropdown('set selected', "<?= $arr_statement[0]->id; ?>");
			$('#transfer-description-add').val("");
		}

		function transferClick() {
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

			$('.item-add-button').click(function() {
				$('#transfer-number-add').val("");
				$('#transfer-date-add').val("<?= $date_display; ?>");
				$('#transfer-amount-add').val("0");
				$('#transfer-statement-add').val("<?= $arr_statement[0]->id; ?>");
				$('#transfer-statement-container-add').dropdown('set selected', "<?= $arr_statement[0]->id; ?>");
				$('#transfer-statement-to-add').val("<?= $arr_statement[0]->id; ?>");
				$('#transfer-statement-to-container-add').dropdown('set selected', "");
				$('#transfer-description-add').val("");

				$('.color-red.warning').html("");

				$('.add-transfer-modal').modal({
					inverted: false,
				}).modal('show');
			});

			$('.open-modal-warning-delete').click(function() {
				var transferId = $(this).attr('data-transfer-id');
				var transferName = $(this).attr('data-transfer-name');
				var transferUpdated = $(this).attr('data-transfer-updated');

				$('.delete-transfer-title').html('Delete transfer ' + transferName);
				$('.delete-transfer-button').attr('data-transfer-id', transferId);
				$('.delete-transfer-button').attr('data-transfer-updated', transferUpdated);

				$('.ui.basic.modal.modal-warning-delete').modal('show');
			});
		}

		function transferKeypress() {
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
			<span class="delete-transfer-title">Delete Transfer</span>
		</div>
		<div class="content text-center">
			<p>You're about to delete this Transfer. You will not be able to undo this action. Are you sure?</p>
		</div>
		<div class="actions">
			<div class="ui red basic cancel inverted button">
				<i class="remove icon"></i>
				No
			</div>
			<div class="ui green ok inverted button delete-transfer-button" onclick="deleteTransfer();">
				<i class="checkmark icon"></i>
				Yes
			</div>
		</div>
	</div>

	<div class="ui modal add-transfer-modal">
		<i class="close icon"></i>
		<div class="header">Add Transfer</div>
		<div class="form-content content">
			<div class="form-add">
				<div class="form-content">
					<div class="ui form">
						<div class="three fields">
							<div class="field">
								<label>Transaction Number </label>
								<input id="transfer-number-add" type="text" placeholder="AUTO..">
							</div>
							<div class="field">
								<label>Account From</label>
								<div id="transfer-statement-container-add" class="ui search selection dropdown form-input">
									<input id="transfer-statement-add" type="hidden" class="data-important">
									<i class="dropdown icon"></i>
									<div class="default text">-- Select Account --</div>
									<div class="menu">
										<? foreach ($arr_statement as $statement): ?>
											<div class="item" data-value="<?= $statement->id; ?>"><?= $statement->name; ?></div>
										<? endforeach; ?>
									</div>
								</div>
							</div>
							<div class="field">
								<label>Account To</label>
								<div id="transfer-statement-to-container-add" class="ui search selection dropdown form-input">
									<input id="transfer-statement-to-add" type="hidden" class="data-important">
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
						<div class="two fields">
							<div class="field">
								<label>Date</label>
								<input id="transfer-date-add" type="text" placeholder="Date..">
							</div>
							<div class="field">
								<label>Amount </label>
								<input id="transfer-amount-add" type="text" placeholder="Amount..">
							</div>
						</div>
						<div class="field">
							<label>Description</label>
							<input id="transfer-description-add" type="text" placeholder="Description..">
						</div>
					</div>
				</div>
			</div>
			<div class="actions text-right">
				<div class="ui deny button form-button">Exit</div>
				<div class="ui button form-button" onclick="addTransfer();">Submit</div>
			</div>
		</div>
	</div>

	<div class="ui modal edit-transfer-modal">
		<i class="close icon"></i>
		<div class="header">Edit Transfer</div>
		<div class="form-content content">
			<div class="form-add">
				<div class="form-content">
					<div class="ui form">
						<div class="three fields">
							<div class="field">
								<label>Transaction Number </label>
								<input id="transfer-number-edit" type="text" placeholder="AUTO..">
							</div>
							<div class="field">
								<label>Account From</label>
								<div id="transfer-statement-container-edit" class="ui search selection dropdown form-input">
									<input id="transfer-statement-edit" type="hidden" class="data-important">
									<i class="dropdown icon"></i>
									<div class="default text">-- Select Account --</div>
									<div class="menu">
										<? foreach ($arr_statement as $statement): ?>
											<div class="item" data-value="<?= $statement->id; ?>"><?= $statement->name; ?></div>
										<? endforeach; ?>
									</div>
								</div>
							</div>
							<div class="field">
								<label>Account To</label>
								<div id="transfer-statement-to-container-edit" class="ui search selection dropdown form-input">
									<input id="transfer-statement-to-edit" type="hidden" class="data-important">
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
						<div class="two fields">
							<div class="field">
								<label>Date</label>
								<input id="transfer-date-edit" type="text" placeholder="Date..">
							</div>
							<div class="field">
								<label>Amount </label>
								<input id="transfer-amount-edit" type="text" placeholder="Amount..">
							</div>
						</div>
						<div class="field">
							<label>Description</label>
							<input id="transfer-description-edit" type="text" placeholder="Description..">
						</div>
					</div>
				</div>
			</div>
			<div class="actions text-right">
				<div class="ui deny button form-button">Exit</div>
				<div class="ui button form-button" onclick="editTransfer();">Submit</div>
			</div>
		</div>
	</div>

	<div class="main-content">
		<div class="ui top attached menu table-menu">
			<div class="item">
				Transation - Transfer
			</div>
			<div class="right menu">
				<? if (isset($acl['transfer']) && $acl['transfer']->add > 0): ?>
					<a class="item item-add-button">
						<i class="add circle icon"></i> Add Transfer
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
				<div class="ui right aligned transfer search item search-item-container">
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
						<th>Transaction Number</th>
						<th>Account</th>
						<th>From</th>
						<th>Amount</th>
						<th>Description</th>
					</tr>
				</thead>
				<tbody>
					<? if (count($arr_transfer) <= 0): ?>
						<tr>
							<td colspan="7">No Result Founds</td>
						</tr>
					<? else: ?>
						<? foreach ($arr_transfer as $transfer): ?>
							<tr>
								<td class="td-icon">
									<? if (isset($acl['transfer']) && $acl['transfer']->edit > 0): ?>
										<a onclick="getTransfer('<?= $transfer->id; ?>');">
											<span class="table-icon">
												<i class="edit icon"></i>
											</span>
										</a>
									<? endif; ?>

									<? if (isset($acl['transfer']) && $acl['transfer']->delete > 0): ?>
										<a class="open-modal-warning-delete" data-transfer-id="<?= $transfer->id; ?>" data-transfer-name="<?= $transfer->name; ?>" data-transfer-updated="<?= $transfer->updated; ?>">
											<span class="table-icon">
												<i class="trash outline icon"></i>
											</span>
										</a>
									<? endif; ?>
								</td>
								<td><?= $transfer->number; ?></td>
								<td><?= $transfer->statement_name; ?></td>
								<td><?= $transfer->statement_to_name; ?></td>
								<td>Rp. <?= $transfer->amount_display; ?></td>
								<td><?= $transfer->description; ?></td>
							</tr>
						<? endforeach; ?>
					<? endif; ?>
				</tbody>
				<tfoot>
					<tr>
						<th colspan="7">
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