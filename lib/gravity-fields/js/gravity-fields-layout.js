(function($) {
	GF_Field_Layout  = function( item ){
		this.element  = $(item);
		this.columns  = parseInt(this.element.find('.gf-layout:eq(0)').attr('data-cols'));

		this.construct(item);
	};

	GF_Field_Layout.prototype = new GF_Field_Repeater();
	GF_Field_Layout.prototype.initRepeaterChild = function() {
		var layout = this;

		this.fields.children().each(function() {
			var row  = $(this);
			var btns = row.find('.resize-row:eq(0) a');
			var min  = parseInt(row.attr('data-min-width'));
			var max  = parseInt(row.attr('data-max-width'));
			var w    = row.attr('data-width') ? parseInt(row.attr('data-width')) : min;

			btns.unbind('click').bind('click', function() {
				var newVal = $(this).is('.bigger') ? w+1 : w-1;

				if(newVal<min)
					newVal = min;

				if(newVal>max)
					newVal = max;

				w = newVal;

				row.animate({
					width: Math.floor( (w/layout.columns) * 100 ) + '%' 
				});

				row.children('.count-holder').val(w);

				return false;
			});
		});

		this.initDelete();
	}

	GF_Field_Layout.prototype.initGroup = function($group) {
		var layout = this;
		var groupW = parseInt( $group.attr('data-width') ? $group.attr('data-width') : $group.attr('data-min-width') );
		var $toggle = $group.find('.edit-row a:eq(0)');

		$group.css({
			width: Math.floor( (groupW/this.columns) * 100 ) + '%'
		});

		$toggle.click(function() {
			$(this).closest('.gf-box').siblings().find('.edit-row a.editing').each(function(){
				layout.setTitle($(this));
				$(this).removeClass('editing').parent().siblings('.fields-wrap').slideUp();
			});

			var $fields = $(this).toggleClass('editing').parent().siblings('.fields-wrap');

			$(this).closest('.gf-box').addClass('current-box').siblings().removeClass('current-box');

			$fields.slideToggle();
			layout.setTitle($(this));

			return false;
		});

		layout.setTitle($toggle);
	}

	GF_Field_Layout.prototype.setTitle = function($element){
		var $fields = $element.parent().siblings('.fields-wrap'),
			$title  = $fields.siblings('.title'),
			$src    = $fields.find('input[type="text"], select, textarea');

		if($src.size()) {
			$indicator = $('<span class="subtext" />');
			
			if($src.val()) {
				$indicator.text(': ' + ($src.is('select') ? $src.find('[selected=selected]').text() : $src.val()));
			}

			$title.find('.subtext').remove();
			$title.append($indicator);
		}
	}
})(jQuery);