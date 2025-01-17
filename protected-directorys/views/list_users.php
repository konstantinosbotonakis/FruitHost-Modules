<?php
	use fruithost\Auth;
	use fruithost\I18N;
?>
<table class="table table-borderless table-striped table-hover">
	<thead>
		<tr>
			<th scope="col" colspan="2"><?php I18N::__('Username'); ?></th>
			<th scope="col"><?php I18N::__('Password'); ?></th>
			<th scope="col"></th>
		</tr>
	</thead>
	<tbody>
		<?php
			foreach($this->users AS $user) {
				?>
					<tr>
						<td scope="row" width="1px">
							<div class="custom-control custom-checkbox">
								<input class="custom-control-input" type="checkbox" id="protected_directory_user_<?php print $user->id; ?>" name="protected_directory_user[]" value="<?php print $user->id; ?>" />
								<label class="custom-control-label" for="protected_directory_user_<?php print $user->id; ?>"></label>
							</div>
						</td>
						<td>
							<?php print $user->username; ?>
						</td>
						<td><?php print (empty($user->password) ? sprintf('<span class="text-warning">%s...</span>', I18N::get('Pending')) : sprintf('
							<div class="input-group input-group-sm w-50">
								<input type="password" autocomplete="false" class="form-control" value="%2$s" aria-label="%3$s" aria-describedby="password_viewer_%1$s" />
								<div class="input-group-append">
									<button type="button" class="input-group-text" id="password_viewer_%1$s" name="password">%4$s</button>
								</div>
							</div>', $user->id, $user->password, I18N::get('Password'), I18N::get('Show'))); ?></td>
						<td class="text-right">
							<button class="update btn btn-sm btn-info" type="button" data-toggle="modal" data-target="#change_protected_directory_user" name="change_protected_directory_user" id="change_directory_user_<?php print $user->id; ?>" value='<?php print json_encode([
								'id'			=> $user->id,
								'username'		=> $user->username,
								'path'			=> $this->directory->path,
								'directory'		=> $this->directory->id
							]); ?>'><?php I18N::__('Edit'); ?></button>
							<button class="delete btn btn-sm btn-danger" type="submit" name="action" value="delete" id="delete_<?php print $user->id; ?>"><?php I18N::__('Delete'); ?></button>
						</td>
					</tr>
				<?php
			}
		?>
	</tbody>
</table>
<script type="text/javascript">
	_watcher_ftp = setInterval(function() {
		if(typeof(jQuery) !== 'undefined') {
			clearInterval(_watcher_ftp);
			
			(function($) {
				$('button[name="action"].delete').on('click', function(event) {
					$(event.target).parent().parent().find('input[type="checkbox"][name="protected_directory_user[]"]').prop('checked', true);
				});
				
				$('button[name="password"]').on('click', function(event) {
					if($(this).text() === 'Show') {
						$(this).text('Hide');
						$(this).parent().parent().find('input[type="password"]').attr('type', 'text');
					} else {
						$(this).text('Show');
						$(this).parent().parent().find('input[type="text"]').attr('type', 'password');
					}
				});
				
				$('#change_protected_directory_user').on('show.bs.modal', function(event) {
					try {
						let data	= JSON.parse($(event.relatedTarget).val());
						let target	= $(event.target);
						
						$('input[name="protected_directory_user_id"]', target).val(data.id);
						$('input[name="protected_directory_id"]', target).val(data.directory);
						$('input[name="protected_directory_username"]', target).val(data.username);
						$('input[name="protected_directory_path"]', target).val(data.path);
					} catch(e) {
						console.log('malformed:', e);
					}
				});
			}(jQuery));
		}
	}, 500);
</script>