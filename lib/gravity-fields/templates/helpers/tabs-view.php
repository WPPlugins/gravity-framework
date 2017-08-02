<?php
class GF_Tabs_View {
	public static function start($group, &$tabs, $align, $close_fields_group = true) {
		$links = array();
		foreach($tabs as $tab) {
			if( $tab['group'] != $group )
				continue;

			$links[] = GF_Tabs_View::get_link($tab);
		}

		if($close_fields_group):
		?>
		</div><!-- /.fields-group -->
		<?php endif; ?>
		
		<div class="tabs <?php echo $align ?>-tabs">
			<div class="tabs-bg"></div>
			
			<div class="tabs-nav-wrap">
				<div class="tabs-nav-inner">
					<ul class="tabs-nav">
						<?php echo implode("\n", $links); ?>
					</ul>
				</div>
			</div>

			<div class="tabs-cnt">
		<?php
	}

	static private function get_link($tab) {
		$icon  = $tab['icon'] ? '<img src="' . $tab['icon'] . '" />' : '';
		$class = $tab['icon'] ? ' class="with-icon"' : '';

		return '<li>
			<a href="#' . $tab['id'] . '"' . $class . '>'
				. $icon
				. $tab['title'] . '
				<span class="arrow"><span></span></span>
			</a>
		</li>';
	}

	public static function end($last) {
		?>
				<div class="cl"></div>
			</div>
			<div class="cl">&nbsp;</div>
		</div>
		<?php if(!$last): ?>
		<div class="fields-group">
		<?php endif; ?>
		<?php
	}

	public static function tab_start($tab) {
		?>
		<div class="tab" id="<?php echo $tab['id'] ?>">
		<?php
	}

	public static function tab_end() {
		?>
		</div>
		<?php
	}
}