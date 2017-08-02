<?php
/**
 * Output the menu icon for the Gravity Fields item in admin_head
 */
add_action( 'admin_head', 'gf_menu_icon_css' );
function gf_menu_icon_css() {
	?>
	<style type="text/css">
	#icon-edit.icon32-posts-gravityfields {
		background-image: url( <?php echo GF_URL ?>templates/css/images/gravity-fields-32.png );
		background-position: 0 0;
		background-size: 36px 34px;
	}

	@media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) { 

		#icon-edit.icon32-posts-gravityfields {
			background-image: url( <?php echo GF_URL ?>templates/css/images/gravity-fields-36@2x.png );
		}
	}
	</style>
	<?php
}