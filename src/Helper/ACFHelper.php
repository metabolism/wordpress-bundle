<?php


namespace Metabolism\WordpressBundle\Helper;

use Metabolism\WordpressBundle\Entity\Entity;

use Metabolism\WordpressBundle\Factory\Factory,
	Metabolism\WordpressBundle\Factory\PostFactory,
	Metabolism\WordpressBundle\Factory\TaxonomyFactory;

class ACF
{
	private $raw_objects, $objects, $id, $loaded=false;

	protected static $MAX_DEPTH = 1;
	protected static $DEPTH = 0;

	/**
	 * ACF constructor.
	 * @param $id
	 */
	public function __construct( $id, $type='objects' )
	{
		$this->id = $id;

		self::$DEPTH++;

		if( $cached = wp_cache_get( $id.'::'.self::$DEPTH, 'acf_helper' ) ){
			$this->objects = $cached;
		}
		else{

			if( self::$DEPTH > self::$MAX_DEPTH ) {
				$this->objects = [];
			}
			else {
				$this->loaded = true;
				$this->objects = $this->load($type, $id);

				wp_cache_set( $id.'::'.self::$DEPTH, $this->objects, 'acf_helper' );
			}
		}

		self::$DEPTH--;
	}


	/**
	 * @return bool|int
	 */
	public function loaded()
	{
		return $this->loaded;
	}


	/**
	 * @param $value
	 */
	public static function setMaxDepth($value )
	{
		self::$MAX_DEPTH = $value;
	}


	/**
	 * @param bool $force
	 * @return array|bool|Entity|mixed|\WP_Error
	 */
	public function get($force=false)
	{
		if( !$this->loaded() && $force ){

			$this->loaded  = true;
			$this->objects = $this->load('objects', $this->id);
			wp_cache_set( $this->id.'::'.self::$DEPTH, $this->objects, 'acf_helper' );
		}

		return $this->objects;
	}


	/**
	 * @param $raw_layouts
	 * @return array
	 */
	public function layoutsAsKeyValue($raw_layouts)
	{
		$layouts = [];

		if( !$raw_layouts || !is_iterable($raw_layouts) )
			return $layouts;

		foreach ($raw_layouts as $layout){

			$layouts[$layout['name']] = [];

			if( isset($layout['sub_fields']) && is_iterable($layout['sub_fields']) ) {

				$subfields = $layout['sub_fields'];
				foreach ($subfields as $subfield){
					$layouts[$layout['name']][$subfield['name']] = $subfield;
				}
			}
		}

		return $layouts;
	}


	/**
	 * @param $fields
	 * @param $layouts
	 * @return array|bool
	 */
	public function bindLayoutsFields($fields, $layouts){

		$data = [];
		$type = $fields['acf_fc_layout'];

		if( !isset($layouts[$type]) )
			return false;

		$layout = $layouts[$type];

		unset($fields['acf_fc_layout']);

		if( !$fields || !is_iterable($fields) )
			return $data;

		foreach ($fields as $name=>$value){

			if( isset($layout[$name]) )
				$data[$name] = $layout[$name];

			$data[$name]['value'] = $value;
		}

		return $data;
	}


	/**
	 * @param $raw_layout
	 * @return array
	 */
	public function layoutAsKeyValue($raw_layout )
	{
		$data = [];

		if( !$raw_layout || !is_iterable($raw_layout) )
			return $data;

		foreach ($raw_layout as $value)
			$data[$value['name']] = $value;

		return $data;
	}


	/**
	 * @param $fields
	 * @param $layout
	 * @return array
	 */
	public function bindLayoutFields($fields, $layout){

		$data = [];

		if( !$fields || !is_iterable($fields) )
			return $data;

		foreach ($fields as $name=>$value){

			if( isset($layout[$name]) )
				$data[$name] = $layout[$name];

			$data[$name]['value'] = $value;
		}

		return $data;
	}

	/**
	 * @param $type
	 * @param $id
	 * @return array|bool|Entity|mixed|\WP_Error
	 */
	public function load($type, $id, $object=false)
	{
		$value = false;

		switch ($type)
		{
			case 'image':
                $value = Factory::create($id, 'image', false, $object);
				break;

			case 'file':
				$value = wp_get_attachment_url( $id );
				break;

			case 'post':
				$value = PostFactory::create( $id );
				break;

			case 'user':
				$value = Factory::create($id, 'user');
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


	/**
	 * @param $raw_objects
	 * @return array
	 */
	public function clean($raw_objects)
	{
		$objects = [];

		if( !$raw_objects || !is_iterable($raw_objects) )
			return $objects;

		// Start analyzing

		foreach ($raw_objects as $object) {

			if(!isset($object['type'], $object['name']) || empty($object['name']))
				continue;

			if(isset($object['public']) && !$object['public'])
				continue;

			switch ($object['type']) {

				case 'clone';

					if( $object['display'] == 'group' && isset($object['sub_fields']) && is_iterable($object['sub_fields']) ){

						foreach ($object['sub_fields'] as &$sub_field)
						{
							if( isset($object['value'][$sub_field['name']])){

								$sub_field['value'] = $object['value'][$sub_field['name']];
								$sub_field['name'] = $sub_field['_name'];
							}
						}

						$objects[$object['name']] = $this->clean($object['sub_fields']);
					}

					break;

				case 'latest_posts';

					$objects[$object['name']] = [];

					if( isset($object['value']) && is_iterable($object['value']) ){
					foreach($object['value'] as $post)
						$objects[$object['name']][] = $this->load('post', $post->ID);
					}

					break;

				case 'image';

					if( empty($object['value']) )
						break;

					if ($object['return_format'] == 'id' || is_int($object['value']) )
						$objects[$object['name']] = $this->load('image', $object['value'], $object);
					elseif ($object['return_format'] == 'array')
						$objects[$object['name']] = $this->load('image', $object['value']['id'], $object);
					else
						$objects[$object['name']] = $object['value'];

					break;

				case 'gallery';

					if( empty($object['value']) )
						break;

					if( is_array($object['value']) ){

						$objects[$object['name']] = [];

						if( isset($object['value']) && is_iterable($object['value']) ){

						foreach ($object['value'] as $value)
							$objects[$object['name']][] = $this->load('image', $value['id'], $object);
					}
					}

					break;

				case 'file';

					if( empty($object['value']) )
						break;

					if ($object['return_format'] == 'id'){

						$objects[$object['name']] = $this->load('file', $object['value']);
					}
					else {

						$object_value = $object['value'];
						$remove = ['id', 'link', 'name', 'status', 'uploaded_to', 'menu_order', 'icon', 'author'];

						foreach($remove as $prop){
							if( isset($object_value[$prop]) )
								unset($object_value[$prop]);
						}

						$objects[$object['name']] = $object_value;
					}

					break;

				case 'relationship';

					$objects[$object['name']] = [];

					if( isset($object['value']) && is_iterable($object['value']) ){

						foreach ($object['value'] as $value) {

							if ($object['return_format'] == 'id' || is_int($value) )
								$element = $value;
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

					if ($object['return_format'] == 'id' || is_int($object['value']) )
						$objects[$object['name']] = $object['value'];
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

					if( isset($object['value']) && is_iterable($object['value']) ){

						$layouts = $this->layoutsAsKeyValue($object['layouts']);

						foreach ($object['value'] as $value) {
							$type = $value['acf_fc_layout'];
							$value = $this->bindLayoutsFields($value, $layouts);
							$data = $this->clean($value);

							$objects[$object['name']][] = ['type'=>$type, 'data'=>$data];
						}
					}

					break;

				case 'repeater';

					$objects[$object['name']] = [];

					if( isset($object['value']) && is_iterable($object['value']) )
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

					if( isset($object['value']) && is_iterable($object['value']) ){

						foreach ($object['value'] as $value) {

							if ($object['return_format'] == 'id'){
								if( $value )
									$objects[$object['name']][] = $value;
							}
							elseif (is_object($value) && $object['return_format'] == 'object'){
								if( $value->term_id )
									$objects[$object['name']][] = $this->load('term', $value->term_id);
							}
						}
					}
					else{

						if ($object['return_format'] == 'id'){
							if( $object['value'] )
								$objects[$object['name']] = $object['value'];
						}
						elseif (is_object($object['value']) && $object['return_format'] == 'object'){

							if( $object['value']->term_id )
								$objects[$object['name']] = $this->load('term', $object['value']->term_id);
						}
					}

					break;

				case 'select';

					if( !$object['multiple'] && is_array($object['value']) &&($object['value']) )
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
