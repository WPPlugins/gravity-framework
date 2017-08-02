<div class="gf-wrap gf-widget" id="<?php echo $this->gf_id ?>">
	<div class="gf-widget-error" style="display:none">
		<div>
			<span style="padding:5px; display:block;"><?php _e( 'There are errors in your data. Please review the highlighted fields below!', 'gf' ) ?></span>
		</div>
	</div>

	<?php foreach( $this->fields as $field ) {
		$field->display( 'widget' );
	} ?>
</div>