<table class="gf-wrap gf-form-table" id="<?php echo $this->id ?>">
<?php foreach($this->fields as $field): ?>
	<?php $field->display( 'term' ); ?>
<?php endforeach ?>
</table>