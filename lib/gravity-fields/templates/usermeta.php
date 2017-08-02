<h3><?php echo $this->get_title() ?></h3>

<table class="gf-wrap form-table gf-form-table" id="<?php echo esc_attr( $this->get_id() ) ?>">
	<?php foreach( $this->fields as $field ) {
		$field->display( 'term' );
	} ?>
</table>