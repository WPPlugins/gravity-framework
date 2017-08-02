<?php
global $gravityfields;
include_once( $gravityfields->themes->path( 'helpers/tabs-view', 'postmeta' ) );

?><div class="wrap gf-wrap gf-postmeta">
	<?php if($this->description): ?>
	<div class="head">
		<?php echo wpautop($this->description); ?>
	</div>
	<?php endif; ?>

	<?php
	if( count( $this->fields ) ):
		if( is_a( $this->fields[0], 'GF_Field' ) ) {
			echo '<div class="fields-group">';
		}

		foreach($this->fields as $i => $field) {
			if( is_a($field, 'GF_Field') ) {
				$field->display( 'postmeta' );
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
	endif;
	?>

	<?php $this->nonce(); ?>
</div>