<?php
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
	<link rel="stylesheet" type="text/css" href="http://fonts.googleapis.com/css?family=<?php echo $font->family ?>:<?php echo implode(',', $font->variants) ?>">
	<style type="text/css" media="screen">
	* { padding:0; margin:0; }
	p { padding:3px 0; font-size:14px; line-height:20px; }
	</style>
</head>
<body>
	<div id="wrap" style="padding:10px;">
		<?php foreach($font->variants as $variant) {
			$v = str_replace('Normal', '400', $variant);
			$v = preg_replace('~Regular~i', '', $v);

			$fw = intval($v);
			$fs = preg_replace('~\d~i', '', $v);

			$style = "font-family:'$font->family';";
			if($fw) {
				$style .= 'font-weight:' . $fw . ';';
			}
			if($fs) {
				$style .= 'font-style:' . $fs;
			}

			$name = '400';
			if($fw) {
				$name = $fw;
			}
			if($fs) {
				$name .= ', ' . ucwords($fs);
			}

			echo '<p style="' . $style . '">' . $name . ': The quick brown fox jumps over the lazy dog</p>';
		} ?>
	</div>

<script type="text/javascript">
var $ = window.parent.jQuery;
$(window).load(function() {
	window.parent.GF.Field.GoogleFont.changeFrameHeight($(document.getElementById('wrap')).outerHeight());
});
</script>
</body>
</html>
<?php exit; ?>