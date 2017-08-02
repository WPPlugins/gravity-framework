<div class="gf-repeater<?php if( is_a( $this->datastore, 'GF_Field_Repeater' ) ) echo ' gf-notable' ?>">
	<!-- Prevent margin top from moving the whole field -->
	<div class="cl">&nbsp;</div>

	<div class="fields">
		<?php foreach($this->fields as $i => $row): ?>
		<div class="postbox metabox-holder gf-row closed" data-gf-id="<?php echo esc_attr( $row['type'] ) ?>" data-title-field="<?php echo esc_attr( $row['title_field' ] ) ?>">
			<div class="btn handlediv" title="<?php _e( 'Click to toggle' ) ?>"><br></div>
			<div class="btn delete-row" title="<?php _e( 'Delete', 'gf' ) ?>"><br></div>

			<h3 class="hndle"><?php echo $row['title'] ?><em>: <span class="group-title"></span></em></h3>

			<div class="gf-inside">
				<?php foreach($row['fields'] as $field) {
					$field->display( 'repeater' );
				} ?>

				<input type="hidden" name="<?php echo $this->input_id . '[' . $i . '][__type]' ?>" value="<?php echo $row['type'] ?>" />
			</div>
		</div>
		<?php endforeach; ?>

		<div class="placeholder"<?php if(!empty($this->fields)) echo ' style="display:none"' ?>>
			<p><?php if( count( $this->field_groups ) > 1 ) {
				_e( 'Drag an item here to add data', 'gf' );
			} else {
				_e( 'Please click the &quot;Add&quot; Button to add data.', 'gf' );
			}?></p>
		</div>
	</div>

	<?php if( count( $this->field_groups ) == 1 ): ?>
	<div class="controls">
		<a href="#" class="button-primary add"><?php _e( 'Add', 'gf' ) ?></a>
	</div>
	<?php else: ?>
	<h4><?php _e( 'Add', 'gf' ) ?>: <span><?php _e( 'Drag & Drop into the area above', 'gf' ) ?></span></h4>
	<?php endif; ?>

	<div class="prototypes"<?php if( count( $this->field_groups ) < 2 ) echo ' style="display:none"' ?>>
		<?php foreach($this->field_groups as $group_key => $group): ?>
		<div class="metabox-wrap">
			<div class="postbox metabox-holder gf-prototype gf-row closed" data-key="<?php echo $group_key ?>" data-gf-id="<?php echo esc_attr( $group_key ) ?>" data-title-field="<?php echo esc_attr( $group['title_field' ] ) ?>">
				<div class="btn add-row" title="<?php _e( 'Click to add', 'gf' ) ?>"><br></div>
				<div class="btn handlediv" title="<?php _e( 'Click to toggle' ) ?>"><br></div>
				<div class="btn delete-row" title="<?php _e( 'Delete', 'gf' ) ?>"><br></div>
				<h3 class="hndle"><?php echo $group['title'] ?><em>: <span class="group-title"></span></em></h3>

				<div class="gf-inside">
					<?php foreach($group['fields'] as $field) {
						$this->display_prototype($field);
					} ?>
				</div>

				<input type="hidden" name="<?php echo $this->input_id . '[' . $this->i_placeholder . '][__type]' ?>" value="<?php echo $group_key ?>" />
			</div>
			<?php echo wpautop( $group['description'] ) ?>
		</div>
		<?php endforeach; ?>
	</div>
</div>