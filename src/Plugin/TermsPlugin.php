<?php

namespace Metabolism\WordpressBundle\Plugin;

use Metabolism\WordpressBundle\Traits\SingletonTrait;

/**
 * Class TermsPlugin
 * @package Metabolism\WordpressBundle\Plugin
 */
class TermsPlugin{

	use SingletonTrait;

	/**
	 * @param $args
	 * @return mixed
	 */
	public function wp_terms_checklist_args($args )
	{
		$args['checked_ontop'] = false;
		return $args;
	}

	function adminFooter() {
		?>
		<script type="text/javascript">
			jQuery(function(){
				jQuery('[id$="-all"] > ul.categorychecklist').each(function() {
					var $list = jQuery(this);
					var $firstChecked = $list.find(':checkbox:checked').first();

					if ( !$firstChecked.length )
						return;

					var pos_first = $list.find(':checkbox').position().top;
					var pos_checked = $firstChecked.position().top;

					$list.closest('.tabs-panel').scrollTop(pos_checked - pos_first + 5);
				});
			});
		</script>
		<?php
	}


	/**
	 * TermsPlugin constructor.
	 * @param $config
	 */
	public function __construct($config)
	{
		// When viewing admin
		if( is_admin() )
		{
			add_action( 'admin_footer', [$this, 'adminFooter'] );
			add_filter( 'wp_terms_checklist_args', [$this, 'wp_terms_checklist_args'] );
		}
	}
}

