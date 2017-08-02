<?php
GF_Field::add_field( 'radio',__( 'Radio', 'gf' ) );
class GF_Field_Radio extends GF_Field_Select {
	public function display_input() {
		if(!$this->check_options( __('This radio has no options.', 'gf') )) {
			return;
		}

		# Prepare checked value - 1st one
		if( !$this->value && key($this->options) ) {
			$this->value = key($this->options);
		}

		?>
		<fieldset>
			<?php foreach($this->options as $key => $value):
				$checked = $key == $this->value ? ' checked="checked"' : '';
				?>
				<label>
					<input type="radio" value="<?php echo esc_attr($key) ?>" name="<?php echo $this->input_id ?>" <?php echo $checked ?>/>
					<span><?php echo $value ?></span>
					<br />
				</label>
			<?php endforeach; ?>
		</fieldset>
		<?php
	}

	/**
	 * Returns a description for the field, will be used in the settings
	 * 
	 * @return string The description
	 */
	static public function settings_description() {
		return __( 'Displays single-choise select by using radio inputs.', 'gf' );
	}
}