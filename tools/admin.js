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
	});


})(jQuery);
