<?php

namespace Metabolism\WordpressLoader\Model;

use Metabolism\WordpressLoader\Traits\SingletonTrait;

class TermsModel{

	use SingletonTrait;

	public function wp_terms_checklist_args( $args )
	{
		add_action( 'admin_footer', [$this, 'adminFooter'] );

		$args['checked_ontop'] = false;

		return $args;
	}


	function adminFooter() {
		?>
		<script type="text/javascript">
			jQuery(function(){
				jQuery('[id$="-all"] > ul.categorychecklist').each(function() {
					var $list = jQuery(this);
					console.log($list)
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


	public static function sortHierarchically($raw_cats){

		$cats = [];

		if( is_object($raw_cats) )
			$raw_cats = (Array)$raw_cats;

		self::sort($raw_cats, $cats);

		return $cats;
	}


	public static function sort(&$cats, &$into, $parentId = 0)
	{
		foreach ($cats as $i => $cat)
		{
			if ($cat->parent == $parentId)
			{
				$into[$cat->term_id] = $cat;
				unset($cats[$i]);
			}
		}

		foreach ($into as $topCat)
		{
			$topCat->children = array();
			self::sort($cats, $topCat->children, $topCat->term_id);

			if( empty($topCat->children) )
				unset($topCat->children);
		}
	}
}
