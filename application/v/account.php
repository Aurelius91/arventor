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
			window.location.href = '<?= base_url(); ?>';
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

			$('.user-access-button').click(function() {
				$('.ui.modal.user-access-modal').modal({
					inverted: true,
				}).modal('show');
			});
		}

		function init() {
			tinymce.init({
				selector: 'textarea#user-address',
				height: 300,
				width: '100%',
				plugins: ["advlist autolink lists link charmap preview", "searchreplace visualblocks code", "table contextmenu paste"],
				toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent",
				paste_as_text: true
			});
		}

		function reset() {
			$('#user-name').val("<?= $user->name; ?>");
			$('#user-position').val("<?= $user->position; ?>");
			$('#user-address').val("<?= $user->address; ?>");
			$('#user-phone').val("<?= $user->phone; ?>");
			$('#user-email').val("<?= $user->email; ?>");
			$('#user-username').val("<?= $user->username; ?>");
		}

		function submit() {
			var userName = $('#user-name').val();
			var userPosition = $('#user-position').val();
			var userAddress = tinyMCE.get('user-address').getContent();
			var userPhone = $('#user-phone').val();
			var userEmail = $('#user-email').val();
			var userUsername = $('#user-username').val();
			var userPassword = $('#user-password').val();
			var found = 0;

			$.each($('.data-important'), function(key, data) {
				if ($(data).val() == '') {
					found += 1;

					$(data).addClass('input-error');
				}
			});

			/* Get User Access Value */
			if (found > 0) {
				return;
			}

			$('.ui.text.loader').html('Connecting to Database...');
			$('.ui.dimmer.all-loader').dimmer('show');

			$.ajax({
				data :{
					name: userName,
					position: userPosition,
					address: userAddress,
					phone: userPhone,
					email: userEmail,
					username: userUsername,
					password: userPassword,
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
				url : '<?= base_url() ?>user/ajax_edit/<?= $user->id; ?>/',
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

	<div class="main-content">
		<div class="ui stackable one column centered grid">
			<div class="column">
				<div class="ui attached message setting-header">
					<div class="header header-user-access">My Account</div>
				</div>
				<div class="form-content">
					<div class="ui form">
						<h4 class="ui dividing header">Personal Details</h4>
						<div class="field">
							<div class="two fields">
								<div class="field">
									<label>Name <span class="color-red">*</span></label>
									<input id="user-name" class="form-input data-important" placeholder="Name.." type="text">
								</div>
								<div class="field">
									<label>Position</label>
									<input id="user-position" class="form-input" placeholder="Position.." type="text" disabled>
								</div>
							</div>
							<div class="field">
								<label>Address</label>
								<textarea id="user-address" placeholder="Address.."></textarea>
							</div>
							<div class="two fields">
								<div class="field">
									<label>Phone</label>
									<input id="user-phone" class="form-input" placeholder="Phone.." type="text">
								</div>
								<div class="field">
									<label>Email</label>
									<input id="user-email" class="form-input" placeholder="Email.." type="text">
								</div>
							</div>
						</div>

						<h4 class="ui dividing header">Account Details</h4>
						<div class="field">
							<div class="two fields">
								<div class="field">
									<label>Username</label>
									<input id="user-username" class="form-input" placeholder="Username.." type="text">
								</div>
								<div class="field">
									<label>Password</label>
									<input id="user-password" class="form-input" placeholder="Password.." type="password">
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