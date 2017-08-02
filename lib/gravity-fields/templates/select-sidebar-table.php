<table class="wp-list-table widefat gf-sidebars" style="clear:none;">
	<thead>
		<th style="width:auto">&nbsp;</th>
		<th style="width:auto"><?php _e( 'Name', 'gf' ) ?></th>
		<th style="width:auto"><?php _e( 'Description', 'gf' ) ?></th>
		<th style="width:auto"><?php _e( 'Delete', 'gf' ) ?></th>
	</thead>

	<tbody>
		<?php foreach( $sidebars as $sidebar ) {
			$selected = $sidebar['name'] == $this->value ? ' checked="checked"' : '';
			echo '<tr>
				<td><input type="radio" name="' . esc_attr( $this->input_id) . '"' . $selected . ' value="' . esc_attr( $sidebar['name'] ) . '" /></td>
				<td><p>' . $sidebar['name'] . '</p></td>
				<td>' . wpautop($sidebar['description']) . '</td>
				<td class="center">–</td>
			</tr>';
		} ?>
		<?php foreach( $gf_sidebars as $sidebar ) {
			$selected = $sidebar['name'] == $this->value ? ' checked="checked"' : '';
			echo '<tr>
				<td><input type="radio" name="' . esc_attr( $this->input_id) . '"' . $selected . ' value="' . esc_attr( $sidebar['name'] ) . '" /></td>
				<td><p>' . $sidebar['name'] . '</p></td>
				<td>' . wpautop($sidebar['description']) . '</td>
				<td class="center"><a href="#" class="button-secondary delete">' . __( 'Delete', 'gf' ) . '</a></td>
			</tr>';
		} ?>

		<tr>
			<td>&nbsp;</td>
			<td><input type="text" class="name" placeholder="<?php echo esc_attr( __( 'New Sidebar', 'gf' ) ) ?>" /></td>
			<td><input type="text" class="description" placeholder="<?php echo esc_attr( __( 'Description', 'gf' ) ) ?>" /></td>
			<td class="center"><a href="#" class="button-primary add">
				<?php _e( 'Add', 'gf' ) ?></a>

				<!-- New Row Prototype -->
				<script type="text/html" class="new-template">
				<tr>
					<td><input type="radio" name="<?php echo esc_attr( $this->input_id) ?>" value="<# sidebar_name #>" /></td>
					<td><p><# sidebar_name #></p></td>
					<td><# sidebar_description #></td>
					<td class="center">
						<a href="#" class="button-secondary delete"><?php _e( 'Delete', 'gf' ) ?></a>
						<input type="hidden" name="new_<?php echo $this->input_id ?>[<# i #>][name]" value="<# sidebar_name #>" />
						<input type="hidden" name="new_<?php echo $this->input_id ?>[<# i #>][description]" value="<# sidebar_description #>" />
					</td>
				</tr>';
				</script>
			</td>
		</tr>
	</tbody>
</table>