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

		$('#wp-admin-bar-build a').click(function(e){

			e.preventDefault();
			$(this).addClass('loading');

			$.get( $(this).attr('href') ).then(function (){

				setTimeout(window.location.reload, 500);
			})
		})
	});


})(jQuery);
