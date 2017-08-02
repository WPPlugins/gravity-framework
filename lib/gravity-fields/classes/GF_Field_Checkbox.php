<?php
GF_Field::add_field( 'checkbox',__( 'Checkbox', 'gf' ) );
class GF_Field_Checkbox extends GF_Field {
	public $multilingual_support = true;
	protected $text;

	public function set_text($text) {
		$this->text = $text;
		return $this;
	}

	public function display_input() {
		# Revert to "Yes" if no other text set
		if( ! $this->text ) {
			$this->text = __( 'Yes', 'gf' );
		}

		$checked = $this->value ? ' checked="checked"' : '';
		echo '<input type="checkbox" name="' . $this->input_id . '" id="' . $this->input_id . '" ' . $checked . ' />';
		echo '<label for="' . $this->input_id . '" class="text">' . $this->text . '</label>';
	}

	public function save($data) {
		if( $this->is_multilingual ) {
			$languages = GF_ML::get();
			$this->value = array();

			foreach($languages as $l) {
				$this->value[ $l['code'] ] = isset( $data[$this->id][ $l['code'] ] );
			}

			$this->value = GF_ML::join( $this->value );
		} else {
			$this->value = isset($data[$this->id]);
		}

		$this->datastore->save_value($this->id, $this->value);
	}

	/**
	 * Returns a description for the field, will be used in the settings
	 * 
	 * @return string The description
	 */
	static public function settings_description() {
		return __( 'Displays a single checkbox. Useful for toggling functionality.', 'gf' );
	}

	/**
	 * Adds additional fields to the settings pages
	 * 
	 * @return GF_Field[]
	 */
	static public function additional_settings() {
		return array(
			GF_Field::factory( 'text', 'text', __( 'Text', 'gf' ) )
				->set_description( __( 'This text could appear next to the checkbox itself, ex. &quot;Enable&quot;', 'gf' ) )
				->set_default_value( __( 'Yes', 'gf' ) )
		);
	}
}