<div class="gf-field gf-separator">
	<h3><?php echo $this->title; ?></h3>

	<?php if($this->description){
		echo wpautop($this->description);
	} ?>
</div>