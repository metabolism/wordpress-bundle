<?php

namespace Metabolism\WordpressBundle\Plugin;

/**
 * Class TermsPlugin
 * @package Metabolism\WordpressBundle\Plugin
 */
class TermsPlugin{

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
	 * @param $raw_cats
	 * @return array
	 */
	public static function sortHierarchically($raw_cats){

		$cats = [];

		if( is_object($raw_cats) )
			$raw_cats = (Array)$raw_cats;

		self::sort($raw_cats, $cats);

		return $cats;
	}


	/**
	 * @param $cats
	 * @param $into
	 * @param int $parentId
	 */
	public static function sort(&$cats, &$into, $parentId = 0)
	{
		foreach ($cats as $i => $cat)
		{
			if (!is_wp_error($cat) && $cat->parent == $parentId)
			{
				$into[$cat->ID] = $cat;
				unset($cats[$i]);
			}
		}

		foreach ($into as $topCat)
		{
			$topCat->children = array();
			self::sort($cats, $topCat->children, $topCat->ID);

			if( empty($topCat->children) )
				unset($topCat->children);
		}
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

