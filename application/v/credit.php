	<style type="text/css">
	</style>

	<script type="text/javascript">
		$(function() {
			reset();
			init();
			creditKeypress();
			creditClick();
			onChange();
		});

		var filterQuery = '<?= $filter; ?>';

		function changeFilter(f) {
			filterQuery = f;
		}

		function addCredit() {
			var creditName = $('#credit-name-add').val();
			var creditNumber = $('#credit-number-add').val();
			var creditDate = $('#credit-date-add').val();
			var creditAmount = $('#credit-amount-add').val();
			var creditType = $('#credit-type-add').val();
			var creditStatement = $('#credit-statement-add').val();
			var creditDescription = $('#credit-description-add').val();
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

			$('.add-credit-modal').modal('hide');
			$('.ui.text.loader').html('Connecting to Database...');
			$('.ui.dimmer.all-loader').dimmer('show');

			$.ajax({
				data :{
					name: creditName,
					number: creditNumber,
					date: creditDate,
					amount: creditAmount,
					type: creditType,
					statement_id: creditStatement,
					description: creditDescription,
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

						$('.add-credit-modal').modal({
							inverted: true,
						}).modal('show');
					}
				},
				type : 'POST',
				url : '<?= base_url() ?>credit/ajax_add/',
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

		function deleteCredit() {
			var creditId = $('.delete-credit-button').attr('data-credit-id');
			var creditUpdated = $('.delete-credit-button').attr('data-credit-updated');

			$('.ui.basic.modal.modal-warning-delete').modal('hide');
			$('.ui.text.loader').html('Connecting to Database...');
			$('.ui.dimmer.all-loader').dimmer('show');

			$.ajax({
				data :{
					updated: creditUpdated,
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
				url : '<?= base_url() ?>credit/ajax_delete/'+ creditId +'/',
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

		function editCredit() {
			var creditName = $('#credit-name-edit').val();
			var creditNumber = $('#credit-number-edit').val();
			var creditDate = $('#credit-date-edit').val();
			var creditAmount = $('#credit-amount-edit').val();
			var creditType = $('#credit-type-edit').val();
			var creditStatement = $('#credit-statement-edit').val();
			var creditDescription = $('#credit-description-edit').val();

			var creditId = $('#credit-name-edit').data('credit_id');
			var creditUpdated = $('#credit-name-edit').data('updated');
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

			$('.edit-credit-modal').modal('hide');
			$('.ui.text.loader').html('Connecting to Database...');
			$('.ui.dimmer.all-loader').dimmer('show');

			$.ajax({
				data :{
					name: creditName,
					number: creditNumber,
					date: creditDate,
					amount: creditAmount,
					updated: creditUpdated,
					type: creditType,
					statement_id: creditStatement,
					description: creditDescription,
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

						$('.edit-credit-modal').modal({
							inverted: true,
						}).modal('show');
					}
				},
				type : 'POST',
				url : '<?= base_url() ?>credit/ajax_edit/'+ creditId +'/',
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

			window.location.href = '<?= base_url(); ?>credit/view/'+ page +'/'+ filterQuery +'/'+ searchQuery +'/';
		}

		function getCredit(creditId) {
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
						$('#credit-name-edit').val(data.credit.name);
						$('#credit-number-edit').val(data.credit.number);
						$('#credit-date-edit').val(data.credit.date_display);
						$('#credit-amount-edit').val(data.credit.amount_display);

						$('#credit-name-edit').val(data.credit.name);
						$('#credit-number-edit').val(data.credit.number);
						$('#credit-date-edit').val(data.credit.date_display);
						$('#credit-amount-edit').val(data.credit.amount_display);
						$('#credit-type-edit').val(data.credit.type);
						$('#credit-type-container-edit').dropdown('set selected', data.credit.type);
						$('#credit-statement-edit').val(data.credit.statement_id);
						$('#credit-statement-container-edit').dropdown('set selected', data.credit.statement_id);
						$('#credit-description-edit').val(data.credit.description);

						$('#credit-name-edit').data('credit_id', creditId);
						$('#credit-name-edit').data('updated', data.credit.updated);

						$('.edit-credit-modal').modal({
							inverted: false,
						}).modal('show');
					}
					else {
						$('.ui.dimmer.all-loader').dimmer('hide');
						$('.color-red.warning').html(data.message);

						$('.add-credit-modal').modal({
							inverted: true,
						}).modal('show');
					}
				},
				type : 'POST',
				url : '<?= base_url() ?>credit/ajax_get/'+ creditId +'/',
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

			$('#credit-date-add, #credit-date-edit').datepicker({
                dateFormat: 'yy-mm-dd',
                maxDate: 0
            });

            $('table').tablesort();
		}

		function onChange() {
			$('#credit-image-add').change(function() {
				var file_data = $('#credit-image-add').prop('files')[0];
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
							$('#credit-image-add').data('image_id', data.image_id);

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

			$('#credit-image-edit').change(function() {
				var file_data = $('#credit-image-edit').prop('files')[0];
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
							$('#credit-image-edit').data('image_id', data.image_id);

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

			$('#credit-name-add').val("");
			$('#credit-number-add').val("");
			$('#credit-date-add').val("<?= $date_display; ?>");
			$('#credit-amount-add').val("0");
			$('#credit-type-add').val("");
			$('#credit-type-container-add').dropdown('set selected', "");
			$('#credit-statement-add').val("<?= $arr_statement[0]->id; ?>");
			$('#credit-statement-container-add').dropdown('set selected', "<?= $arr_statement[0]->id; ?>");
			$('#credit-description-add').val("");
		}

		function creditClick() {
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
				$('#credit-name-add').val("");
				$('#credit-number-add').val("");
				$('#credit-date-add').val("<?= $date_display; ?>");
				$('#credit-amount-add').val("0");
				$('#credit-type-add').val("");
				$('#credit-type-container-add').dropdown('set selected', "");
				$('#credit-statement-add').val("<?= $arr_statement[0]->id; ?>");
				$('#credit-statement-container-add').dropdown('set selected', "<?= $arr_statement[0]->id; ?>");
				$('#credit-description-add').val("");

				$('.color-red.warning').html("");

				$('.add-credit-modal').modal({
					inverted: false,
				}).modal('show');
			});

			$('.open-modal-warning-delete').click(function() {
				var creditId = $(this).attr('data-credit-id');
				var creditName = $(this).attr('data-credit-name');
				var creditUpdated = $(this).attr('data-credit-updated');

				$('.delete-credit-title').html('Delete credit ' + creditName);
				$('.delete-credit-button').attr('data-credit-id', creditId);
				$('.delete-credit-button').attr('data-credit-updated', creditUpdated);

				$('.ui.basic.modal.modal-warning-delete').modal('show');
			});
		}

		function creditKeypress() {
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
			<span class="delete-credit-title">Delete Credit / Spending</span>
		</div>
		<div class="content text-center">
			<p>You're about to delete this Credit / Spending. You will not be able to undo this action. Are you sure?</p>
		</div>
		<div class="actions">
			<div class="ui red basic cancel inverted button">
				<i class="remove icon"></i>
				No
			</div>
			<div class="ui green ok inverted button delete-credit-button" onclick="deleteCredit();">
				<i class="checkmark icon"></i>
				Yes
			</div>
		</div>
	</div>

	<div class="ui modal add-credit-modal">
		<i class="close icon"></i>
		<div class="header">Add Credit / Spending</div>
		<div class="form-content content">
			<div class="form-add">
				<div class="form-content">
					<div class="ui form">
						<div class="two fields">
							<div class="field">
								<label>Transaction Number </label>
								<input id="credit-number-add" type="text" placeholder="AUTO..">
							</div>
							<div class="field">
								<label>Receiver<span class="color-red warning"></span></label>
								<input id="credit-name-add" type="text" class="data-important-add" placeholder="Account Name..">
							</div>
						</div>
						<div class="two fields">
							<div class="field">
								<label>Account</label>
								<div id="credit-statement-container-add" class="ui search selection dropdown form-input">
									<input id="credit-statement-add" type="hidden" class="data-important">
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
								<div id="credit-type-container-add" class="ui search selection dropdown form-input">
									<input id="credit-type-add" type="hidden" class="data-important">
									<i class="dropdown icon"></i>
									<div class="default text">-- Select Clasification --</div>
									<div class="menu">
										<div class="item" data-value="Biaya Gaji">Biaya Gaji</div>
										<div class="item" data-value="Biaya PPH 21">Biaya PPH 21</div>
										<div class="item" data-value="Biaya BPJS Ketenagakerjaan">Biaya BPJS Ketenagakerjaan</div>
										<div class="item" data-value="Biaya BPJS Kesehatan">Biaya BPJS Kesehatan</div>
										<div class="item" data-value="Biaya THR">Biaya THR</div>
										<div class="item" data-value="Biaya Insentif dan Bonus">Biaya Insentif dan Bonus</div>
										<div class="item" data-value="Biaya Tunjangan">Biaya Tunjangan</div>
										<div class="item" data-value="Biaya Makan">Biaya Makan</div>
										<div class="item" data-value="Biaya Medis">Biaya Medis</div>
										<div class="item" data-value="Biaya Perjalanan Dinas">Biaya Perjalanan Dinas</div>
										<div class="item" data-value="Biaya Transportasi, BBM, Tol & Parkir">Biaya Transportasi, BBM, Tol & Parkir</div>
										<div class="item" data-value="Biaya Listrik">Biaya Listrik</div>
										<div class="item" data-value="Biaya Gas">Biaya Gas</div>
										<div class="item" data-value="Biaya Air">Biaya Air</div>
										<div class="item" data-value="Biaya Telepon, Fax & Internet">Biaya Telepon, Fax & Internet</div>
										<div class="item" data-value="Biaya Keamanan">Biaya Keamanan</div>
										<div class="item" data-value="Biaya Kebersihan">Biaya Kebersihan</div>
										<div class="item" data-value="Biaya Materai">Biaya Materai</div>
										<div class="item" data-value="Biaya ATK & Fotocopy">Biaya ATK & Fotocopy</div>
										<div class="item" data-value="Biaya Perlengkapan">Biaya Perlengkapan</div>
										<div class="item" data-value="Biaya Pengiriman">Biaya Pengiriman</div>
										<div class="item" data-value="Biaya Bongkar / Muat">Biaya Bongkar / Muat</div>
										<div class="item" data-value="Biaya POS, Paket, Kurir">Biaya POS, Paket, Kurir</div>
										<div class="item" data-value="Biaya Servis & Pemeliharaan">Biaya Servis & Pemeliharaan</div>
										<div class="item" data-value="Biaya Sewa Kendaraan">Biaya Sewa Kendaraan</div>
										<div class="item" data-value="Biaya Entertainment & Representasi">Biaya Entertainment & Representasi</div>
										<div class="item" data-value="Biaya Rekruitment & Pelatihan">Biaya Rekruitment & Pelatihan</div>
										<div class="item" data-value="Biaya Promosi & Iklan">Biaya Promosi & Iklan</div>
										<div class="item" data-value="Biaya Asuransi Kendaraan">Biaya Asuransi Kendaraan</div>
										<div class="item" data-value="Biaya Sewa Kantor">Biaya Sewa Kantor</div>
										<div class="item" data-value="Biaya Lisensi / Izin">Biaya Lisensi / Izin</div>
										<div class="item" data-value="Biaya Legal">Biaya Legal</div>
										<div class="item" data-value="Biaya Donasi">Biaya Donasi</div>
										<div class="item" data-value="Biaya Piutang Tak Tertagih">Biaya Piutang Tak Tertagih</div>
										<div class="item" data-value="Biaya Operasional Lainnya">Biaya Operasional Lainnya</div>
										<div class="item" data-value="Biaya Penyusutan Bangunan Kantor">Biaya Penyusutan Bangunan Kantor</div>
										<div class="item" data-value="Biaya Penyusutan Kendaraan">Biaya Penyusutan Kendaraan</div>
										<div class="item" data-value="Biaya Penyusutan Peralatan Kantor">Biaya Penyusutan Peralatan Kantor</div>
										<div class="item" data-value="Biaya Biaya Amortisasi dan Biaya Pra Operasional">Biaya Biaya Amortisasi dan Biaya Pra Operasional</div>
										<div class="item" data-value="Biaya Biaya Provisi / Admin Bank">Biaya Biaya Provisi / Admin Bank</div>
										<div class="item" data-value="Biaya Bunga Bank">Biaya Bunga Bank</div>
										<div class="item" data-value="Rugi atas Selisih Kurs">Rugi atas Selisih Kurs</div>
										<div class="item" data-value="Biaya Denda Pajak">Biaya Denda Pajak</div>
										<div class="item" data-value="Biaya Pajak Penghasilan Perusahaan">Biaya Pajak Penghasilan Perusahaan</div>
										<div class="item" data-value="Biaya Komisi">Biaya Komisi</div>
										<div class="item" data-value="Biaya Diluar Usaha Lainnya">Biaya Diluar Usaha Lainnya</div>
									</div>
								</div>
							</div>
						</div>
						<div class="two fields">
							<div class="field">
								<label>Date</label>
								<input id="credit-date-add" type="text" placeholder="Date..">
							</div>
							<div class="field">
								<label>First Amount </label>
								<input id="credit-amount-add" type="text" placeholder="First Amount..">
							</div>
						</div>
						<div class="field">
							<label>Description</label>
							<input id="credit-description-add" type="text" placeholder="Description..">
						</div>
					</div>
				</div>
			</div>
			<div class="actions text-right">
				<div class="ui deny button form-button">Exit</div>
				<div class="ui button form-button" onclick="addCredit();">Submit</div>
			</div>
		</div>
	</div>

	<div class="ui modal edit-credit-modal">
		<i class="close icon"></i>
		<div class="header">Edit Credit / Spending</div>
		<div class="form-content content">
			<div class="form-add">
				<div class="form-content">
					<div class="ui form">
						<div class="two fields">
							<div class="field">
								<label>Transaction Number </label>
								<input id="credit-number-edit" type="text" placeholder="AUTO..">
							</div>
							<div class="field">
								<label>Receiver<span class="color-red warning"></span></label>
								<input id="credit-name-edit" type="text" class="data-important-edit" placeholder="Account Name..">
							</div>
						</div>
						<div class="two fields">
							<div class="field">
								<label>Account</label>
								<div id="credit-statement-container-edit" class="ui search selection dropdown form-input">
									<input id="credit-statement-edit" type="hidden" class="data-important">
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
								<div id="credit-type-container-edit" class="ui search selection dropdown form-input">
									<input id="credit-type-edit" type="hidden" class="data-important">
									<i class="dropdown icon"></i>
									<div class="default text">-- Select Clasification --</div>
									<div class="menu">
										<div class="item" data-value="Biaya Gaji">Biaya Gaji</div>
										<div class="item" data-value="Biaya PPH 21">Biaya PPH 21</div>
										<div class="item" data-value="Biaya BPJS Ketenagakerjaan">Biaya BPJS Ketenagakerjaan</div>
										<div class="item" data-value="Biaya BPJS Kesehatan">Biaya BPJS Kesehatan</div>
										<div class="item" data-value="Biaya THR">Biaya THR</div>
										<div class="item" data-value="Biaya Insentif dan Bonus">Biaya Insentif dan Bonus</div>
										<div class="item" data-value="Biaya Tunjangan">Biaya Tunjangan</div>
										<div class="item" data-value="Biaya Makan">Biaya Makan</div>
										<div class="item" data-value="Biaya Medis">Biaya Medis</div>
										<div class="item" data-value="Biaya Perjalanan Dinas">Biaya Perjalanan Dinas</div>
										<div class="item" data-value="Biaya Transportasi, BBM, Tol & Parkir">Biaya Transportasi, BBM, Tol & Parkir</div>
										<div class="item" data-value="Biaya Listrik">Biaya Listrik</div>
										<div class="item" data-value="Biaya Gas">Biaya Gas</div>
										<div class="item" data-value="Biaya Air">Biaya Air</div>
										<div class="item" data-value="Biaya Telepon, Fax & Internet">Biaya Telepon, Fax & Internet</div>
										<div class="item" data-value="Biaya Keamanan">Biaya Keamanan</div>
										<div class="item" data-value="Biaya Kebersihan">Biaya Kebersihan</div>
										<div class="item" data-value="Biaya Materai">Biaya Materai</div>
										<div class="item" data-value="Biaya ATK & Fotocopy">Biaya ATK & Fotocopy</div>
										<div class="item" data-value="Biaya Perlengkapan">Biaya Perlengkapan</div>
										<div class="item" data-value="Biaya Pengiriman">Biaya Pengiriman</div>
										<div class="item" data-value="Biaya Bongkar / Muat">Biaya Bongkar / Muat</div>
										<div class="item" data-value="Biaya POS, Paket, Kurir">Biaya POS, Paket, Kurir</div>
										<div class="item" data-value="Biaya Servis & Pemeliharaan">Biaya Servis & Pemeliharaan</div>
										<div class="item" data-value="Biaya Sewa Kendaraan">Biaya Sewa Kendaraan</div>
										<div class="item" data-value="Biaya Entertainment & Representasi">Biaya Entertainment & Representasi</div>
										<div class="item" data-value="Biaya Rekruitment & Pelatihan">Biaya Rekruitment & Pelatihan</div>
										<div class="item" data-value="Biaya Promosi & Iklan">Biaya Promosi & Iklan</div>
										<div class="item" data-value="Biaya Asuransi Kendaraan">Biaya Asuransi Kendaraan</div>
										<div class="item" data-value="Biaya Sewa Kantor">Biaya Sewa Kantor</div>
										<div class="item" data-value="Biaya Lisensi / Izin">Biaya Lisensi / Izin</div>
										<div class="item" data-value="Biaya Legal">Biaya Legal</div>
										<div class="item" data-value="Biaya Donasi">Biaya Donasi</div>
										<div class="item" data-value="Biaya Piutang Tak Tertagih">Biaya Piutang Tak Tertagih</div>
										<div class="item" data-value="Biaya Operasional Lainnya">Biaya Operasional Lainnya</div>
										<div class="item" data-value="Biaya Penyusutan Bangunan Kantor">Biaya Penyusutan Bangunan Kantor</div>
										<div class="item" data-value="Biaya Penyusutan Kendaraan">Biaya Penyusutan Kendaraan</div>
										<div class="item" data-value="Biaya Penyusutan Peralatan Kantor">Biaya Penyusutan Peralatan Kantor</div>
										<div class="item" data-value="Biaya Biaya Amortisasi dan Biaya Pra Operasional">Biaya Biaya Amortisasi dan Biaya Pra Operasional</div>
										<div class="item" data-value="Biaya Biaya Provisi / Admin Bank">Biaya Biaya Provisi / Admin Bank</div>
										<div class="item" data-value="Biaya Bunga Bank">Biaya Bunga Bank</div>
										<div class="item" data-value="Rugi atas Selisih Kurs">Rugi atas Selisih Kurs</div>
										<div class="item" data-value="Biaya Denda Pajak">Biaya Denda Pajak</div>
										<div class="item" data-value="Biaya Pajak Penghasilan Perusahaan">Biaya Pajak Penghasilan Perusahaan</div>
										<div class="item" data-value="Biaya Komisi">Biaya Komisi</div>
										<div class="item" data-value="Biaya Diluar Usaha Lainnya">Biaya Diluar Usaha Lainnya</div>
									</div>
								</div>
							</div>
						</div>
						<div class="two fields">
							<div class="field">
								<label>Date</label>
								<input id="credit-date-edit" type="text" placeholder="Date..">
							</div>
							<div class="field">
								<label>First Amount </label>
								<input id="credit-amount-edit" type="text" placeholder="First Amount..">
							</div>
						</div>
						<div class="field">
							<label>Description</label>
							<input id="credit-description-edit" type="text" placeholder="Description..">
						</div>
					</div>
				</div>
			</div>
			<div class="actions text-right">
				<div class="ui deny button form-button">Exit</div>
				<div class="ui button form-button" onclick="editCredit();">Submit</div>
			</div>
		</div>
	</div>

	<div class="main-content">
		<div class="ui top attached menu table-menu">
			<div class="item">
				Transation - Credit / Spending
			</div>
			<div class="right menu">
				<? if (isset($acl['credit']) && $acl['credit']->add > 0): ?>
					<a class="item item-add-button">
						<i class="add circle icon"></i> Add Credit / Spending
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
				<div class="ui right aligned credit search item search-item-container">
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
						<th>Receiver</th>
						<th>Clasification</th>
						<th>Account</th>
						<th>Amount</th>
						<th>Description</th>
					</tr>
				</thead>
				<tbody>
					<? if (count($arr_credit) <= 0): ?>
						<tr>
							<td colspan="7">No Result Founds</td>
						</tr>
					<? else: ?>
						<? foreach ($arr_credit as $credit): ?>
							<tr>
								<td class="td-icon">
									<? if (isset($acl['credit']) && $acl['credit']->edit > 0): ?>
										<a onclick="getCredit('<?= $credit->id; ?>');">
											<span class="table-icon">
												<i class="edit icon"></i>
											</span>
										</a>
									<? endif; ?>

									<? if (isset($acl['credit']) && $acl['credit']->delete > 0): ?>
										<a class="open-modal-warning-delete" data-credit-id="<?= $credit->id; ?>" data-credit-name="<?= $credit->name; ?>" data-credit-updated="<?= $credit->updated; ?>">
											<span class="table-icon">
												<i class="trash outline icon"></i>
											</span>
										</a>
									<? endif; ?>
								</td>
								<td><?= $credit->number; ?></td>
								<td><?= $credit->name; ?></td>
								<td><?= $credit->type; ?></td>
								<td><?= $credit->statement_name; ?></td>
								<td>Rp. <?= $credit->amount_display; ?></td>
								<td><?= $credit->description; ?></td>
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