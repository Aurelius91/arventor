	<style type="text/css">
	</style>

	<script type="text/javascript">
		$(function() {
			reset();
			init();
			userKeypress();
			userClick();
			userChange();
		});

		var filterQuery = '<?= $filter; ?>';

		function changeFilter(f) {
			filterQuery = f;
		}

		function deleteUser() {
			var userId = $('.delete-user-button').attr('data-user-id');
			var userUpdated = $('.delete-user-button').attr('data-user-updated');

			$('.ui.basic.modal.modal-warning-delete').modal('hide');
			$('.ui.text.loader').html('Connecting to Database...');
			$('.ui.dimmer.all-loader').dimmer('show');

			$.ajax({
				data :{
					updated: userUpdated,
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
				url : '<?= base_url() ?>user/ajax_delete/'+ userId +'/',
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

			window.location.href = '<?= base_url(); ?>user/view/'+ page +'/'+ filterQuery +'/'+ searchQuery +'/';
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

			<? foreach ($arr_user as $user): ?>
				<? if ($user->active > 0): ?>
					$('#checkbox-user-<?= $user->id; ?>').attr('checked', true);
				<? else: ?>
					$('#checkbox-user-<?= $user->id; ?>').attr('checked', false);
				<? endif; ?>
			<? endforeach; ?>
		}

		function userChange() {
			$('.user-checkbox').change(function() {
				var userId = $(this).attr('value');
				var checked = ($(this).is(':checked')) ? 1 : 0;

				$.ajax({
					data :{
						active: checked,
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
					url : '<?= base_url() ?>user/ajax_change_status/'+ userId +'/',
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

		function userClick() {
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
				var userId = $(this).attr('data-user-id');
				var userName = $(this).attr('data-user-name');
				var userUpdated = $(this).attr('data-user-updated');

				$('.delete-user-title').html('Delete User ' + userName);
				$('.delete-user-button').attr('data-user-id', userId);
				$('.delete-user-button').attr('data-user-updated', userUpdated);

				$('.ui.basic.modal.modal-warning-delete').modal('show');
			});
		}

		function userKeypress() {
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
			<span class="delete-user-title">Delete User</span>
		</div>
		<div class="content text-center">
			<p>You're about to delete this User. You will not be able to undo this action. Are you sure?</p>
		</div>
		<div class="actions">
			<div class="ui red basic cancel inverted button">
				<i class="remove icon"></i>
				No
			</div>
			<div class="ui green ok inverted button delete-user-button" onclick="deleteUser();">
				<i class="checkmark icon"></i>
				Yes
			</div>
		</div>
	</div>

	<div class="main-content">
		<div class="ui top attached menu table-menu">
			<div class="item item-add-button">
				Users List
			</div>
			<div class="right menu">
				<? if (isset($acl['user']) && $acl['user']->add != ''): ?>
					<a class="item item-add-button" href="<?= base_url(); ?>user/add/">
						<i class="add circle icon"></i> Add Users
					</a>
				<? endif; ?>
				<div class="item">
					<div class="ui dropdown dropdown-filter">
						<div class="text"><?= ucwords($filter); ?></div>
						<i class="dropdown icon"></i>
						<div class="menu">
							<div class="item" onclick="changeFilter('all');">All</div>
							<div class="item" onclick="changeFilter('name');">Name</div>
							<div class="item" onclick="changeFilter('phone');">Phone</div>
							<div class="item" onclick="changeFilter('email');">Email</div>
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
						<th>User</th>
						<th>Status</th>
						<th>Email</th>
						<th>Phone</th>
					</tr>
				</thead>
				<tbody>
					<? if (count($arr_user) <= 0): ?>
						<tr>
							<td colspan="6">No Result Founds</td>
						</tr>
					<? else: ?>
						<? foreach ($arr_user as $user): ?>
							<tr>
								<td style="width: 45px; text-align: center;">
									<div class="ui checkbox">
										<input id="checkbox-user-<?= $user->id; ?>" class="user-checkbox" name="checkbox-user-id" type="checkbox" value="<?= $user->id; ?>">
										<label></label>
									</div>
								</td>
								<td class="td-icon">
									<? if (isset($acl['user']) && $acl['user']->edit > 0): ?>
										<a href="<?= base_url(); ?>user/edit/<?= $user->id; ?>/">
											<span class="table-icon">
												<i class="edit icon"></i>
											</span>
										</a>
									<? endif; ?>

									<? if (isset($acl['user']) && $acl['user']->delete > 0): ?>
										<a class="open-modal-warning-delete" data-user-id="<?= $user->id; ?>" data-user-name="<?= $user->name; ?>" data-user-updated="<?= $user->updated; ?>">
											<span class="table-icon">
												<i class="trash outline icon"></i>
											</span>
										</a>
									<? endif; ?>
								</td>
								<td>
									<h4 class="ui image header">
										<img src="<?= base_url(); ?>/images/admin/users.png" class="ui mini circular image">
										<div class="content">
											<?= $user->name; ?>
											<div class="sub header"><?= $user->position; ?></div>
										</div>
									</h4>
								</td>
								<td><?= $user->status; ?></td>
								<td><?= $user->email; ?></td>
								<td><?= $user->phone; ?></td>
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