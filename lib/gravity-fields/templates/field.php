<div<?php $this->field_atts() ?>>
	<div class="label">
		<label for="<?php echo $this->input_id ?>"><?php echo $this->title ?></label>

		<?php if($this->help_text): ?>
		<a href="#" class="help">&nbsp;<em><del></del><strong><?php echo esc_attr($this->help_text) ?></strong></em></a>
		<?php endif; ?>
	</div>

	<div class="field-wrap">
		<?php $this->base_display_input(); ?>

		<?php if($this->description): ?>
		<p class="description"><?php echo $this->description ?></p>
		<?php endif; ?>
	</div>
	<div class="cl">&nbsp;</div>

	<span class="border"></span>
</div>