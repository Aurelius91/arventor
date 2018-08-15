	<style type="text/css">
	</style>

	<script type="text/javascript">
		$(function() {
			reset();
			init();
			debitKeypress();
			debitClick();
			onChange();
		});

		var filterQuery = '<?= $filter; ?>';

		function changeFilter(f) {
			filterQuery = f;
		}

		function adddebit() {
			var debitName = $('#debit-name-add').val();
			var debitNumber = $('#debit-number-add').val();
			var debitDate = $('#debit-date-add').val();
			var debitAmount = $('#debit-amount-add').val();
			var debitType = $('#debit-type-add').val();
			var debitStatement = $('#debit-statement-add').val();
			var debitDescription = $('#debit-description-add').val();
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

			$('.add-debit-modal').modal('hide');
			$('.ui.text.loader').html('Connecting to Database...');
			$('.ui.dimmer.all-loader').dimmer('show');

			$.ajax({
				data :{
					name: debitName,
					number: debitNumber,
					date: debitDate,
					amount: debitAmount,
					type: debitType,
					statement_id: debitStatement,
					description: debitDescription,
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

						$('.add-debit-modal').modal({
							inverted: true,
						}).modal('show');
					}
				},
				type : 'POST',
				url : '<?= base_url() ?>debit/ajax_add/',
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

		function deletedebit() {
			var debitId = $('.delete-debit-button').attr('data-debit-id');
			var debitUpdated = $('.delete-debit-button').attr('data-debit-updated');

			$('.ui.basic.modal.modal-warning-delete').modal('hide');
			$('.ui.text.loader').html('Connecting to Database...');
			$('.ui.dimmer.all-loader').dimmer('show');

			$.ajax({
				data :{
					updated: debitUpdated,
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
				url : '<?= base_url() ?>debit/ajax_delete/'+ debitId +'/',
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

		function editdebit() {
			var debitName = $('#debit-name-edit').val();
			var debitNumber = $('#debit-number-edit').val();
			var debitDate = $('#debit-date-edit').val();
			var debitAmount = $('#debit-amount-edit').val();
			var debitType = $('#debit-type-edit').val();
			var debitStatement = $('#debit-statement-edit').val();
			var debitDescription = $('#debit-description-edit').val();

			var debitId = $('#debit-name-edit').data('debit_id');
			var debitUpdated = $('#debit-name-edit').data('updated');
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

			$('.edit-debit-modal').modal('hide');
			$('.ui.text.loader').html('Connecting to Database...');
			$('.ui.dimmer.all-loader').dimmer('show');

			$.ajax({
				data :{
					name: debitName,
					number: debitNumber,
					date: debitDate,
					amount: debitAmount,
					updated: debitUpdated,
					type: debitType,
					statement_id: debitStatement,
					description: debitDescription,
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

						$('.edit-debit-modal').modal({
							inverted: true,
						}).modal('show');
					}
				},
				type : 'POST',
				url : '<?= base_url() ?>debit/ajax_edit/'+ debitId +'/',
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

			window.location.href = '<?= base_url(); ?>debit/view/'+ page +'/'+ filterQuery +'/'+ searchQuery +'/';
		}

		function getdebit(debitId) {
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
						$('#debit-name-edit').val(data.debit.name);
						$('#debit-number-edit').val(data.debit.number);
						$('#debit-date-edit').val(data.debit.date_display);
						$('#debit-amount-edit').val(data.debit.amount_display);

						$('#debit-name-edit').val(data.debit.name);
						$('#debit-number-edit').val(data.debit.number);
						$('#debit-date-edit').val(data.debit.date_display);
						$('#debit-amount-edit').val(data.debit.amount_display);
						$('#debit-type-edit').val(data.debit.type);
						$('#debit-type-container-edit').dropdown('set selected', data.debit.type);
						$('#debit-statement-edit').val(data.debit.statement_id);
						$('#debit-statement-container-edit').dropdown('set selected', data.debit.statement_id);
						$('#debit-description-edit').val(data.debit.description);

						$('#debit-name-edit').data('debit_id', debitId);
						$('#debit-name-edit').data('updated', data.debit.updated);

						$('.edit-debit-modal').modal({
							inverted: false,
						}).modal('show');
					}
					else {
						$('.ui.dimmer.all-loader').dimmer('hide');
						$('.color-red.warning').html(data.message);

						$('.add-debit-modal').modal({
							inverted: true,
						}).modal('show');
					}
				},
				type : 'POST',
				url : '<?= base_url() ?>debit/ajax_get/'+ debitId +'/',
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

			$('#debit-date-add, #debit-date-edit').datepicker({
                dateFormat: 'yy-mm-dd',
                maxDate: 0
            });

            $('table').tablesort();
		}

		function onChange() {
			$('#debit-image-add').change(function() {
				var file_data = $('#debit-image-add').prop('files')[0];
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
							$('#debit-image-add').data('image_id', data.image_id);

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

			$('#debit-image-edit').change(function() {
				var file_data = $('#debit-image-edit').prop('files')[0];
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
							$('#debit-image-edit').data('image_id', data.image_id);

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

			$('#debit-name-add').val("");
			$('#debit-number-add').val("");
			$('#debit-date-add').val("<?= $date_display; ?>");
			$('#debit-amount-add').val("0");
			$('#debit-type-add').val("");
			$('#debit-type-container-add').dropdown('set selected', "");
			$('#debit-statement-add').val("<?= $arr_statement[0]->id; ?>");
			$('#debit-statement-container-add').dropdown('set selected', "<?= $arr_statement[0]->id; ?>");
			$('#debit-description-add').val("");
		}

		function debitClick() {
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
				$('#debit-name-add').val("");
				$('#debit-number-add').val("");
				$('#debit-date-add').val("<?= $date_display; ?>");
				$('#debit-amount-add').val("0");
				$('#debit-type-add').val("");
				$('#debit-type-container-add').dropdown('set selected', "");
				$('#debit-statement-add').val("<?= $arr_statement[0]->id; ?>");
				$('#debit-statement-container-add').dropdown('set selected', "<?= $arr_statement[0]->id; ?>");
				$('#debit-description-add').val("");

				$('.color-red.warning').html("");

				$('.add-debit-modal').modal({
					inverted: false,
				}).modal('show');
			});

			$('.open-modal-warning-delete').click(function() {
				var debitId = $(this).attr('data-debit-id');
				var debitName = $(this).attr('data-debit-name');
				var debitUpdated = $(this).attr('data-debit-updated');

				$('.delete-debit-title').html('Delete debit ' + debitName);
				$('.delete-debit-button').attr('data-debit-id', debitId);
				$('.delete-debit-button').attr('data-debit-updated', debitUpdated);

				$('.ui.basic.modal.modal-warning-delete').modal('show');
			});
		}

		function debitKeypress() {
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
			<span class="delete-debit-title">Delete Debit / Income</span>
		</div>
		<div class="content text-center">
			<p>You're about to delete this Debit / Income. You will not be able to undo this action. Are you sure?</p>
		</div>
		<div class="actions">
			<div class="ui red basic cancel inverted button">
				<i class="remove icon"></i>
				No
			</div>
			<div class="ui green ok inverted button delete-debit-button" onclick="deletedebit();">
				<i class="checkmark icon"></i>
				Yes
			</div>
		</div>
	</div>

	<div class="ui modal add-debit-modal">
		<i class="close icon"></i>
		<div class="header">Add Debit / Income</div>
		<div class="form-content content">
			<div class="form-add">
				<div class="form-content">
					<div class="ui form">
						<div class="two fields">
							<div class="field">
								<label>Transaction Number </label>
								<input id="debit-number-add" type="text" placeholder="AUTO..">
							</div>
							<div class="field">
								<label>From<span class="color-red warning"></span></label>
								<input id="debit-name-add" type="text" class="data-important-add" placeholder="Account Name..">
							</div>
						</div>
						<div class="two fields">
							<div class="field">
								<label>Account</label>
								<div id="debit-statement-container-add" class="ui search selection dropdown form-input">
									<input id="debit-statement-add" type="hidden" class="data-important">
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
								<label>Clasification</label>
								<div id="debit-type-container-add" class="ui search selection dropdown form-input">
									<input id="debit-type-add" type="hidden" class="data-important">
									<i class="dropdown icon"></i>
									<div class="default text">-- Select Clasification --</div>
									<div class="menu">
										<div class="item" data-value="Pendapatan Jasa Giro / Bunga">Pendapatan Jasa Giro / Bunga</div>
										<div class="item" data-value="Laba Atas Selisih Kurs">Laba Atas Selisih Kurs</div>
										<div class="item" data-value="Pendapatan Diluar Usaha Lainnya">Pendapatan Diluar Usaha Lainnya</div>
									</div>
								</div>
							</div>
						</div>
						<div class="two fields">
							<div class="field">
								<label>Date</label>
								<input id="debit-date-add" type="text" placeholder="Date..">
							</div>
							<div class="field">
								<label>First Amount </label>
								<input id="debit-amount-add" type="text" placeholder="First Amount..">
							</div>
						</div>
						<div class="field">
							<label>Description</label>
							<input id="debit-description-add" type="text" placeholder="Description..">
						</div>
					</div>
				</div>
			</div>
			<div class="actions text-right">
				<div class="ui deny button form-button">Exit</div>
				<div class="ui button form-button" onclick="adddebit();">Submit</div>
			</div>
		</div>
	</div>

	<div class="ui modal edit-debit-modal">
		<i class="close icon"></i>
		<div class="header">Edit Debit / Income</div>
		<div class="form-content content">
			<div class="form-add">
				<div class="form-content">
					<div class="ui form">
						<div class="two fields">
							<div class="field">
								<label>Transaction Number </label>
								<input id="debit-number-edit" type="text" placeholder="AUTO..">
							</div>
							<div class="field">
								<label>From<span class="color-red warning"></span></label>
								<input id="debit-name-edit" type="text" class="data-important-edit" placeholder="Account Name..">
							</div>
						</div>
						<div class="two fields">
							<div class="field">
								<label>Account</label>
								<div id="debit-statement-container-edit" class="ui search selection dropdown form-input">
									<input id="debit-statement-edit" type="hidden" class="data-important">
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
								<label>Clasification</label>
								<div id="debit-type-container-edit" class="ui search selection dropdown form-input">
									<input id="debit-type-edit" type="hidden" class="data-important">
									<i class="dropdown icon"></i>
									<div class="default text">-- Select Clasification --</div>
									<div class="menu">
										<div class="item" data-value="Pendapatan Jasa Giro / Bunga">Pendapatan Jasa Giro / Bunga</div>
										<div class="item" data-value="Laba Atas Selisih Kurs">Laba Atas Selisih Kurs</div>
										<div class="item" data-value="Pendapatan Diluar Usaha Lainnya">Pendapatan Diluar Usaha Lainnya</div>
									</div>
								</div>
							</div>
						</div>
						<div class="two fields">
							<div class="field">
								<label>Date</label>
								<input id="debit-date-edit" type="text" placeholder="Date..">
							</div>
							<div class="field">
								<label>First Amount </label>
								<input id="debit-amount-edit" type="text" placeholder="First Amount..">
							</div>
						</div>
						<div class="field">
							<label>Description</label>
							<input id="debit-description-edit" type="text" placeholder="Description..">
						</div>
					</div>
				</div>
			</div>
			<div class="actions text-right">
				<div class="ui deny button form-button">Exit</div>
				<div class="ui button form-button" onclick="editdebit();">Submit</div>
			</div>
		</div>
	</div>

	<div class="main-content">
		<div class="ui top attached menu table-menu">
			<div class="item">
				Transation - Debit / Income
			</div>
			<div class="right menu">
				<? if (isset($acl['debit']) && $acl['debit']->add > 0): ?>
					<a class="item item-add-button">
						<i class="add circle icon"></i> Add Debit / Income
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
				<div class="ui right aligned debit search item search-item-container">
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
						<th>From</th>
						<th>Clasification</th>
						<th>Account</th>
						<th>Amount</th>
						<th>Description</th>
					</tr>
				</thead>
				<tbody>
					<? if (count($arr_debit) <= 0): ?>
						<tr>
							<td colspan="7">No Result Founds</td>
						</tr>
					<? else: ?>
						<? foreach ($arr_debit as $debit): ?>
							<tr>
								<td class="td-icon">
									<? if (isset($acl['debit']) && $acl['debit']->edit > 0): ?>
										<a onclick="getdebit('<?= $debit->id; ?>');">
											<span class="table-icon">
												<i class="edit icon"></i>
											</span>
										</a>
									<? endif; ?>

									<? if (isset($acl['debit']) && $acl['debit']->delete > 0): ?>
										<a class="open-modal-warning-delete" data-debit-id="<?= $debit->id; ?>" data-debit-name="<?= $debit->name; ?>" data-debit-updated="<?= $debit->updated; ?>">
											<span class="table-icon">
												<i class="trash outline icon"></i>
											</span>
										</a>
									<? endif; ?>
								</td>
								<td><?= $debit->number; ?></td>
								<td><?= $debit->name; ?></td>
								<td><?= $debit->type; ?></td>
								<td><?= $debit->statement_name; ?></td>
								<td>Rp. <?= $debit->amount_display; ?></td>
								<td><?= $debit->description; ?></td>
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