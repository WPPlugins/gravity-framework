<div class="gf-repeater gf-layout" data-placeholder="<?php echo $this->i_placeholder ?>" data-cols="<?php echo $this->columns ?>">
	<!-- Prevent margin top from moving the whole field -->
	<div class="cl">&nbsp;</div>

	<div class="fields"<?php if(empty($this->fields)) echo ' style="display:none"' ?>>
		<?php foreach($this->fields as $i => $row): ?>
		<div class="gf-row gf-box" data-title="<?php echo $row['group_name'] ?>" data-min-width="<?php echo $row['min_width'] ?>" data-max-width="<?php echo $row['max_width'] ?>" data-width="<?php echo isset($row['width']) ? $row['width'] : $row['min_width'] ?>" data-gf-id="<?php echo esc_attr( $row['type'] ) ?>">
			<div class="inner">
				<div class="delete-row">
					<a href="#">Delete</a>
				</div>

				<div class="edit-row">
					<a href="#">Edit</a>
				</div>

				<div class="title"><?php echo $row['title'] ?></div>

				<div class="fields-wrap">
					<?php foreach($row['fields'] as $field) {
						$field->display();
					} ?>
				</div>

				<div class="resize-row">
					<a href="#" class="bigger">+</a>
					<a href="#" class="smaller">-</a>
				</div>
			</div>

			<input type="hidden" name="<?php echo $this->input_id . '[' . $i . '][__type]' ?>" value="<?php echo $row['type'] ?>" />
			<input type="hidden" name="<?php echo $this->input_id . '[' . $i . '][__columns]' ?>" value="<?php echo isset($row['width']) ? $row['width'] : $row['min_width'] ?>" class="count-holder" />
		</div>
		<?php endforeach; ?>
	</div>

	<div class="controls<?php if(empty($this->fields)) echo ' more-padding' ?>">
		<div class="add-btn">
			<a href="#" class="add">Add</a>
			<a href="#" class="choose">Choose</a>

			<ul>
				<?php foreach($this->field_groups as $group_key => $group){ 
					echo '<li><a href="#" data-key="' . $group_key . '"><strong>+</strong> Add new ' . $group['title'] . '</a></li>';
				} ?>
			</ul>
		</div>

		<div class="no-fields"<?php if(!empty($this->fields)) echo ' style="display:none"' ?>>
			<p><?php _e('Please click the &quot;Add Button&quot; to add data.') ?></p>
		</div>
		<div class="cl">&nbsp;</div>
	</div>

	<div class="prototypes">
		<?php foreach($this->field_groups as $group_key => $group): ?>
		<div class="gf-prototype" data-key="<?php echo $group_key ?>" data-title="<?php echo $group['group_name'] ?>" data-min-width="<?php echo $group['min_width'] ?>" data-max-width="<?php echo $group['max_width'] ?>" data-gf-id="<?php echo esc_attr( $group_key ) ?>">
			<div class="inner">
				<div class="delete-row">
					<a href="#">Delete</a>
				</div>

				<div class="edit-row">
					<a href="#">Edit</a>
				</div>

				<div class="title"><?php echo $group['group_name'] ?></div>

				<div class="fields-wrap">
					<?php foreach($group['fields'] as $field) {
						$this->display_prototype($field);
					} ?>
				</div>

				<div class="resize-row">
					<a href="#" class="bigger">+</a>
					<a href="#" class="smaller">-</a>
				</div>
			</div>

			<input type="hidden" name="<?php echo $this->input_id . '[' . $this->i_placeholder . '][__type]' ?>" value="<?php echo $group_key ?>" />
			<input type="hidden" name="<?php echo $this->input_id . '[' . $this->i_placeholder . '][__columns]' ?>" value="<?php echo $group['min_width'] ?>" />
		</div>
		<?php endforeach; ?>
	</div>
</div>