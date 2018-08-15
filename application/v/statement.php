	<style type="text/css">
	</style>

	<script type="text/javascript">
		$(function() {
			reset();
			init();
			statementKeypress();
			statementClick();
			onChange();
		});

		var filterQuery = '<?= $filter; ?>';

		function changeFilter(f) {
			filterQuery = f;
		}

		function addStatement() {
			var statementName = $('#statement-name-add').val();
			var statementNumber = $('#statement-number-add').val();
			var statementDate = $('#statement-date-add').val();
			var statementAmount = $('#statement-amount-add').val();
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

			$('.add-statement-modal').modal('hide');
			$('.ui.text.loader').html('Connecting to Database...');
			$('.ui.dimmer.all-loader').dimmer('show');

			$.ajax({
				data :{
					name: statementName,
					number: statementNumber,
					date: statementDate,
					amount: statementAmount,
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

						$('.add-statement-modal').modal({
							inverted: true,
						}).modal('show');
					}
				},
				type : 'POST',
				url : '<?= base_url() ?>statement/ajax_add/',
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

		function deleteStatement() {
			var statementId = $('.delete-statement-button').attr('data-statement-id');
			var statementUpdated = $('.delete-statement-button').attr('data-statement-updated');

			$('.ui.basic.modal.modal-warning-delete').modal('hide');
			$('.ui.text.loader').html('Connecting to Database...');
			$('.ui.dimmer.all-loader').dimmer('show');

			$.ajax({
				data :{
					updated: statementUpdated,
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
				url : '<?= base_url() ?>statement/ajax_delete/'+ statementId +'/',
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

		function editStatement() {
			var statementName = $('#statement-name-edit').val();
			var statementNumber = $('#statement-number-edit').val();
			var statementDate = $('#statement-date-edit').val();
			var statementAmount = $('#statement-amount-edit').val();

			var statementId = $('#statement-name-edit').data('statement_id');
			var statementUpdated = $('#statement-name-edit').data('updated');
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

			$('.edit-statement-modal').modal('hide');
			$('.ui.text.loader').html('Connecting to Database...');
			$('.ui.dimmer.all-loader').dimmer('show');

			$.ajax({
				data :{
					name: statementName,
					number: statementNumber,
					date: statementDate,
					amount: statementAmount,
					updated: statementUpdated,
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

						$('.edit-statement-modal').modal({
							inverted: true,
						}).modal('show');
					}
				},
				type : 'POST',
				url : '<?= base_url() ?>statement/ajax_edit/'+ statementId +'/',
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

			window.location.href = '<?= base_url(); ?>statement/view/'+ page +'/'+ filterQuery +'/'+ searchQuery +'/';
		}

		function getStatement(statementId) {
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
						$('#statement-name-edit').val(data.statement.name);
						$('#statement-number-edit').val(data.statement.number);
						$('#statement-date-edit').val(data.statement.date_display);
						$('#statement-amount-edit').val(data.statement.amount_display);

						$('#statement-name-edit').data('statement_id', statementId);
						$('#statement-name-edit').data('updated', data.statement.updated);

						$('.edit-statement-modal').modal({
							inverted: false,
						}).modal('show');
					}
					else {
						$('.ui.dimmer.all-loader').dimmer('hide');
						$('.color-red.warning').html(data.message);

						$('.add-statement-modal').modal({
							inverted: true,
						}).modal('show');
					}
				},
				type : 'POST',
				url : '<?= base_url() ?>statement/ajax_get/'+ statementId +'/',
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

			$('#statement-date-add, #statement-date-edit').datepicker({
                dateFormat: 'yy-mm-dd',
                maxDate: 0
            });

            $('table').tablesort();
		}

		function onChange() {
			$('#statement-image-add').change(function() {
				var file_data = $('#statement-image-add').prop('files')[0];
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
							$('#statement-image-add').data('image_id', data.image_id);

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

			$('#statement-image-edit').change(function() {
				var file_data = $('#statement-image-edit').prop('files')[0];
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
							$('#statement-image-edit').data('image_id', data.image_id);

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

			$('#statement-name-add').val("");
			$('#statement-number-add').val("");
			$('#statement-date-add').val("<?= $date_display; ?>");
			$('#statement-amount-add').val("0");
		}

		function statementClick() {
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
				$('#statement-name-add').val("");
				$('#statement-number-add').val("");
				$('#statement-date-add').val("<?= $date_display; ?>");
				$('#statement-amount-add').val("0");

				$('.color-red.warning').html("");

				$('.add-statement-modal').modal({
					inverted: false,
				}).modal('show');
			});

			$('.open-modal-warning-delete').click(function() {
				var statementId = $(this).attr('data-statement-id');
				var statementName = $(this).attr('data-statement-name');
				var statementUpdated = $(this).attr('data-statement-updated');

				$('.delete-statement-title').html('Delete statement ' + statementName);
				$('.delete-statement-button').attr('data-statement-id', statementId);
				$('.delete-statement-button').attr('data-statement-updated', statementUpdated);

				$('.ui.basic.modal.modal-warning-delete').modal('show');
			});
		}

		function statementKeypress() {
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
			<span class="delete-statement-title">Delete Statement / Bank Account</span>
		</div>
		<div class="content text-center">
			<p>You're about to delete this Statement / Bank Account. You will not be able to undo this action. Are you sure?</p>
		</div>
		<div class="actions">
			<div class="ui red basic cancel inverted button">
				<i class="remove icon"></i>
				No
			</div>
			<div class="ui green ok inverted button delete-statement-button" onclick="deleteStatement();">
				<i class="checkmark icon"></i>
				Yes
			</div>
		</div>
	</div>

	<div class="ui modal add-statement-modal">
		<i class="close icon"></i>
		<div class="header">Add Statement / Bank Account</div>
		<div class="form-content content">
			<div class="form-add">
				<div class="form-content">
					<div class="ui form">
						<div class="two fields">
							<div class="field">
								<label>Account Name<span class="color-red warning"></span></label>
								<input id="statement-name-add" type="text" class="data-important-add" placeholder="Account Name..">
							</div>
							<div class="field">
								<label>Account Number </label>
								<input id="statement-number-add" type="text" placeholder="Account Number..">
							</div>
						</div>
						<div class="two fields">
							<div class="field">
								<label>Date</label>
								<input id="statement-date-add" type="text" placeholder="Date..">
							</div>
							<div class="field">
								<label>First Amount </label>
								<input id="statement-amount-add" type="text" placeholder="First Amount..">
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="actions text-right">
				<div class="ui deny button form-button">Exit</div>
				<div class="ui button form-button" onclick="addStatement();">Submit</div>
			</div>
		</div>
	</div>

	<div class="ui modal edit-statement-modal">
		<i class="close icon"></i>
		<div class="header">Edit Statement / Bank Account</div>
		<div class="form-content content">
			<div class="form-add">
				<div class="form-content">
					<div class="ui form">
						<div class="two fields">
							<div class="field">
								<label>Account Name<span class="color-red warning"></span></label>
								<input id="statement-name-edit" class="data-important-edit" placeholder="Account Name..">
							</div>
							<div class="field">
								<label>Account Number</label>
								<input id="statement-number-edit" placeholder="Account Number..">
							</div>
						</div>
						<div class="two fields">
							<div class="field">
								<label>Date</label>
								<input id="statement-date-edit" type="text" placeholder="Date..">
							</div>
							<div class="field">
								<label>First Amount </label>
								<input id="statement-amount-edit" type="text" placeholder="First Amount..">
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="actions text-right">
				<div class="ui deny button form-button">Exit</div>
				<div class="ui button form-button" onclick="editStatement();">Submit</div>
			</div>
		</div>
	</div>

	<div class="main-content">
		<div class="ui top attached menu table-menu">
			<div class="item">
				Transation - Statement / Bank Account
			</div>
			<div class="right menu">
				<? if (isset($acl['statement']) && $acl['statement']->add > 0): ?>
					<a class="item item-add-button">
						<i class="add circle icon"></i> Add Statement / Bank Account
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
				<div class="ui right aligned statement search item search-item-container">
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
						<th>Name</th>
						<th>Bank Account Number</th>
						<th>Amount</th>
					</tr>
				</thead>
				<tbody>
					<? if (count($arr_statement) <= 0): ?>
						<tr>
							<td colspan="4">No Result Founds</td>
						</tr>
					<? else: ?>
						<? foreach ($arr_statement as $statement): ?>
							<tr>
								<td class="td-icon">
									<? if (isset($acl['statement']) && $acl['statement']->edit > 0): ?>
										<a onclick="getStatement('<?= $statement->id; ?>');">
											<span class="table-icon">
												<i class="edit icon"></i>
											</span>
										</a>
									<? endif; ?>

									<? if (isset($acl['statement']) && $acl['statement']->delete > 0): ?>
										<a class="open-modal-warning-delete" data-statement-id="<?= $statement->id; ?>" data-statement-name="<?= $statement->name; ?>" data-statement-updated="<?= $statement->updated; ?>">
											<span class="table-icon">
												<i class="trash outline icon"></i>
											</span>
										</a>
									<? endif; ?>
								</td>
								<td><?= $statement->name; ?></td>
								<td><?= $statement->number; ?></td>
								<td>Rp. <?= $statement->final_amount_display; ?></td>
							</tr>
						<? endforeach; ?>
					<? endif; ?>
				</tbody>
				<tfoot>
					<tr>
						<th colspan="4">
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