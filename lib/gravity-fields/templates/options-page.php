<?php
global $gravityfields;
include_once( $gravityfields->themes->path( 'helpers/tabs-view' ) );

?><div class="wrap gf-wrap gf-options" id="<?php echo $this->id ?>">
	<div class="head">
		<div id="icon-<?php echo $this->icon_id ?>" class="icon32"></div>
		<h2><?php echo $this->title; ?></h2>

		<?php if($this->description) {
			echo wpautop($this->description);
		} ?>
	</div>

	<?php if(count($this->fields)): ?>
	<div class="error error-msg">
		<span><?php echo apply_filters('gf_validation_error', __('There are errors in your data. Please review the highlighted fields below!', 'gf')) ?></span>
	</div>

	<form action="" method="POST" enctype="multipart/form-data">
		<?php
		if( is_a( $this->fields[0], 'GF_Field' ) ) {
			echo '<div class="fields-group">';
		}

		foreach($this->fields as $i => $field) {
				if( is_a($field, 'GF_Field') ) {
					$field->display( 'options' );
				} elseif($field['item'] == 'tabs_start') {
					GF_Tabs_View::start($field['group'], $this->tabs, $this->tabs_align, $i>0);
				} elseif($field['item'] == 'tab_start') {
					GF_Tabs_View::tab_start($field);
				} elseif($field['item'] == 'tab_end') {
					GF_Tabs_View::tab_end();
				} elseif( $field['item'] == 'tabs_end' ) {
					GF_Tabs_View::end( true );
				}
			}

		if( is_a( $field, 'GF_Field' ) ) {
			echo '</div>';
		}
		?>

		<div class="gf-submit-btns">
			<input type="submit" value="<?php _e('Save', 'gf') ?>" class="button-primary" />
		</div>

		<div class="ajax-loader"></div>

		<?php $this->nonce(); ?>
	</form>
	<?php endif; ?>
</div>