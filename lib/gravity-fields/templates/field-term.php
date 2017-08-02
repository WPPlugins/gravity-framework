<tr  <?php $this->field_atts( 'row' ) ?>>
	<th valign="top">
		<label for="<?php echo esc_attr($this->get_input_id()) ?>"><?php echo $this->get_title() ?></label>
		
		<?php if($this->help_text): ?>
		<a href="#" class="help">&nbsp;<em><del></del><strong><?php echo esc_attr($this->help_text) ?></strong></em></a>
		<?php endif; ?>
	</th>
	<td>
		<div class="field-wrap">
			<?php $this->base_display_input(); ?>

			<?php if($this->description): ?>
			<p class="description"><?php echo $this->description ?></p>
			<?php endif; ?>
		</div>
	</td>
</tr>