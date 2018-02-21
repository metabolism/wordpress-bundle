<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Metabolism\WordpressLoader\Model;


use Metabolism\WordpressLoader\Helper\ACFHelper as ACF;

/**
 * Class Post
 * @see \Timber\Term
 *
 * @package Metabolism\WordpressLoader\Model
 */
class TermModel extends \Timber\Term
{
	public $ID, $excerpt;

	/**
	 * Post constructor.
	 *
	 * @param null $id
	 */
	public function __construct($id = null) {

		parent::__construct( $id );

		if( is_array($id) ){

			if( empty($id) || isset($id['invalid_taxonomy']) )
				return false;

			$id = $id[0];
		}

		$this->ID = 'term_' . $id;
		$this->excerpt = strip_tags(term_description($id),'<b><i><strong><em><br>');

		$this->clean();
		$this->hydrateCustomFields();
	}


	/**
	 * Add ACF custom fields as members of the post
	 */
	protected function hydrateCustomFields()
	{
		$custom_fields = new ACF( $this->ID );

		foreach ($custom_fields->get() as $name => $value )
		{
			$this->$name = $value;
		}
	}


	/**
	 * Add ACF custom fields as members of the post
	 */
	protected function clean()
	{
		foreach ($this as $key=>$value){

			if( substr($key,0,1) == '_' and $key != '_content' and $key != '_prev' and $key != '_next')
			{
				unset($this->$key);
				$key = substr($key,1);
				unset($this->$key);
			}
		}

		unset($this->custom, $this->guid, $this->post_content_filtered, $this->to_ping, $this->pinged);
	}
}
