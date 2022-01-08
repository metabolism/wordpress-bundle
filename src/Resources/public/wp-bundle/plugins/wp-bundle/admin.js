;(function($) {

	function disableACFLayoutReorder(){
		$('.acf-flexible-content > .values').sortable( "disable" );
		$('.acf-flexible-content .ui-sortable-handle').removeAttr( "title" );
	}

	$(document).ready(function(){

		if( $('body').hasClass('no-acf_edit_layout') ){

			disableACFLayoutReorder();
			setInterval(disableACFLayoutReorder, 1000);
		}

		$('.postbox-container [data-wp-lists]').each(function(){

			if( $(this).find('.children').length )
				$(this).addClass('has-children');

			$(this).find('input[type="checkbox"]').click(function(){

				if( !$(this).is(':checked') )
					$(this).closest('li').find('input[type="checkbox"]').attr('checked', false)
				else
					$(this).parents('li').find('> label input[type="checkbox"]').attr('checked', true)
			})
		})

		$('.acf-label').each(function(){

			if( $(this).text().length < 2 )
				$(this).remove()
		})
	});


})(jQuery);
