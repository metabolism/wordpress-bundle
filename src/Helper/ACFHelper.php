<?php

/*
 * Route middleware to easily implement multi-langue
 * todo: check to find a better way...
 */
namespace Metabolism\WordpressBundle\Helper;

use Metabolism\WordpressBundle\Entity\Post,
	Metabolism\WordpressBundle\Entity\Term,
	Metabolism\WordpressBundle\Entity\User,
	Metabolism\WordpressBundle\Entity\Image,
	Metabolism\WordpressBundle\Entity\Product;

use Metabolism\WordpressBundle\Factory\PostFactory,
	Metabolism\WordpressBundle\Factory\TaxonomyFactory;

class ACF
{
	private $raw_objects, $objects;

	protected static $MAX_DEPTH = 2;
	protected static $DEPTH = 0;

	public function __construct( $post_id )
	{
		if( self::$DEPTH > self::$MAX_DEPTH )
		{
			$this->objects = [];
		}
		else
		{
			++self::$DEPTH;
			$this->objects = $this->load('objects', $post_id);
			--self::$DEPTH;
		}
	}


	public static function setMaxDepth( $value )
	{
		self::$MAX_DEPTH = $value;
	}


	public function get()
	{
		return $this->objects;
	}


	public function layoutsAsKeyValue($raw_layouts)
	{
		$layouts = [];

		foreach ($raw_layouts as $layout){

			$layouts[$layout['name']] = [];

			if( isset($layout['sub_fields']) )
			{
				$subfields = $layout['sub_fields'];
				foreach ($subfields as $subfield){
					$layouts[$layout['name']][$subfield['name']] = $subfield;
				}
			}
		}

		return $layouts;
	}


	public function bindLayoutsFields($fields, $layouts){

		$data = [];
		$type = $fields['acf_fc_layout'];

		if( !isset($layouts[$type]) )
			return false;

		$layout = $layouts[$type];

		unset($fields['acf_fc_layout']);

		foreach ($fields as $name=>$value){

			if( isset($layout[$name]) )
				$data[$name] = $layout[$name];

			$data[$name]['value'] = $value;
		}

		return $data;
	}


	public function layoutAsKeyValue( $raw_layout )
	{
		$data = [];

		foreach ($raw_layout as $value)
			$data[$value['name']] = $value;

		return $data;
	}


	public function bindLayoutFields($fields, $layout){

		$data = [];

		foreach ($fields as $name=>$value){

			if( isset($layout[$name]) )
				$data[$name] = $layout[$name];

			$data[$name]['value'] = $value;
		}

		return $data;
	}

	public function load($type, $id)
	{
		$value = false;

		switch ($type)
		{
			case 'image':
				$value = PostFactory::create($id, 'image');
				break;

			case 'file':
				$value = wp_get_attachment_url( $id );
				break;

			case 'post':
				$post_status = get_post_status( $id );
				$value = ( $post_status && $post_status !== 'publish' ) ? false : PostFactory::create( $id );
				break;

			case 'user':
				$value = new User( $id );
				break;

			case 'term':
				$value = TaxonomyFactory::create( $id );
				break;

			case 'objects':

				if( function_exists('get_field_objects') )
					$this->raw_objects = get_field_objects($id);
				else
					$this->raw_objects = [];

				$value = $this->clean( $this->raw_objects);

				break;
		}
		
		return $value;
	}


	public function clean($raw_objects)
	{
		$objects = [];

		if( !$raw_objects or !is_array($raw_objects) )
			return $objects;

		// Start analyzing

		foreach ($raw_objects as $object) {

			if(!isset($object['type']))
				continue;

			switch ($object['type']) {

				case 'clone';

					foreach ($object['sub_fields'] as &$sub_field)
					{
						if( isset($object['value'][$sub_field['name']]))
							$sub_field['value'] = $object['value'][$sub_field['name']];
					}

					$objects[$object['name']] = $this->clean($object['sub_fields']);

					break;

				case 'latest_posts';

					$objects[$object['name']] = [];

					foreach($object['value'] as $post)
						$objects[$object['name']][] = $this->load('post', $post->ID);

					break;

				case 'image';

					if( empty($object['value']) )
						break;

					if ($object['return_format'] == 'id' or is_int($object['value']) )
						$objects[$object['name']] = $this->load('image', $object['value']);
					elseif ($object['return_format'] == 'array')
						$objects[$object['name']] = $this->load('image', $object['value']['id']);
					else
						$objects[$object['name']] = $object['value'];

					break;

				case 'gallery';

					if( empty($object['value']) )
						break;

					if( is_array($object['value']) ){

						$objects[$object['name']] = [];

						foreach ($object['value'] as $value)
							$objects[$object['name']][] = $this->load('image', $value['id']);
					}

					break;

				case 'file';

					if( empty($object['value']) )
						break;

					if ($object['return_format'] == 'id')
						$objects[$object['name']] = $this->load('file', $object['value']);
					elseif ($object['return_format'] == 'array')
						$objects[$object['name']] = $object['value']['url'];
					else
						$objects[$object['name']] = $object['value'];

					break;

				case 'relationship';

					$objects[$object['name']] = [];

					if( is_array($object['value']) ){

						foreach ($object['value'] as $value) {

							if ($object['return_format'] == 'id' or is_int($value) )
								$element = $this->load('post', $value);
							elseif ($object['return_format'] == 'object')
								$element = $this->load('post', $value->ID);
							else
								$element = $object['value'];

							if( $element )
								$objects[$object['name']][] = $element;
						}
					}
					break;

				case 'post_object';

					if( empty($object['value']) )
						break;

					if ($object['return_format'] == 'id' or is_int($object['value']) )
						$objects[$object['name']] = $this->load('post', $object['value']);
					elseif ($object['return_format'] == 'object')
						$objects[$object['name']] = $this->load('post', $object['value']->ID);
					else
						$objects[$object['name']] = $object['value'];

					break;

				case 'user';

					if( empty($object['value']) )
						break;

					$objects[$object['name']] = $this->load('user', $object['value']['ID']);
					break;

				case 'flexible_content';

					$objects[$object['name']] = [];

					if( is_array($object['value']) ){

						$layouts = $this->layoutsAsKeyValue($object['layouts']);

						foreach ($object['value'] as $value) {
							$type = $value['acf_fc_layout'];
							$value = $this->bindLayoutsFields($value, $layouts);
							$data = $this->clean($value);

							if( is_array($value) and count($value) == 1 and is_string(key($value)) )
								$data = reset($data);

							$objects[$object['name']][] = ['@type'=>$type, 'data'=>$data];
						}
					}

					break;

				case 'repeater';

					$objects[$object['name']] = [];

					if( is_array($object['value']) )
					{
						$layout = $this->layoutAsKeyValue($object['sub_fields']);

						foreach ($object['value'] as $value)
						{
							$value = $this->bindLayoutFields($value, $layout);
							$objects[$object['name']][] = $this->clean($value);
						}
					}

					break;

				case 'taxonomy';

					$objects[$object['name']] = [];

					if( is_array($object['value']) ){

						foreach ($object['value'] as $value) {

							$id = false;

							if ($object['return_format'] == 'id')
								$id = $value;
							elseif (is_object($value) && $object['return_format'] == 'object')
								$id = $value->term_id;

							if( $id )
								$objects[$object['name']][] = $this->load('term', $id);
						}
					}
					else{

						$id = false;

						if ($object['return_format'] == 'id')
							$id = $object['value'];
						elseif (is_object($object['value']) && $object['return_format'] == 'object')
							$id = $object['value']->term_id;

						if( $id )
							$objects[$object['name']] = $this->load('term', $id);
					}

					break;

				case 'select';

					if( !$object['multiple'] and is_array($object['value']) and count($object['value']) )
						$objects[$object['name']] = $object['value'][0];
					else
						$objects[$object['name']] = $object['value'];

					break;

				case 'group';

					$layout = $this->layoutAsKeyValue($object['sub_fields']);
					$value = $this->bindLayoutFields($object['value'], $layout);

					$objects[$object['name']] = $this->clean($value);

					break;

				default:

					$objects[$object['name']] = $object['value'];
					break;
			}
		}

		return $objects;
	}
}
