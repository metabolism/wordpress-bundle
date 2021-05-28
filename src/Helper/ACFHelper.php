<?php


namespace Metabolism\WordpressBundle\Helper;

use Metabolism\WordpressBundle\Entity\Entity;

use Metabolism\WordpressBundle\Factory\Factory,
	Metabolism\WordpressBundle\Factory\PostFactory,
	Metabolism\WordpressBundle\Factory\TaxonomyFactory;

class ACFHelper
{
	private $raw_objects;
	private $objects;
	private $id;
	private $loaded=false;
	private $use_entity=false;

	protected static $MAX_DEPTH = 2;
	protected static $DEPTH = 0;

	public static function get($id){

	    $fields = new self($id);
	    return $fields->loadFromCache();
    }

	/**
	 * ACFHelper constructor.
	 * @param $id
	 * @param string $type
	 */
	public function __construct( $id, $type='objects' )
	{
        global $_config;

        if( !$_config )
            return;

        $this->use_entity = $_config->get('acf.settings.use_entity', true);

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
	public function loadFromCache($force=false)
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
	 * @param bool $object
	 * @return array|bool|Entity|mixed|\WP_Error
	 */
	public function load($type, $id, $object=false)
	{
		$value = false;

		if( $type == 'term' ){

            if(is_array($id) )
                $id = $id['term_id']??false;
            elseif( is_object($id) )
                $id = $id->term_id??false;
        }
        else{

            if(is_array($id) )
                $id = $id['id']??$id['ID']??false;
            elseif( is_object($id) )
                $id = $id->id??$id->ID??false;
        }

        if( !$id )
            return null;

		switch ($type)
		{
			case 'image':
				$value = Factory::create($id, 'image', false, $object);
				break;

			case 'file':
				$value = Factory::create($id, 'file', false, $object);
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

				case 'clone':

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

				case 'latest_posts':

					$objects[$object['name']] = [];

					if( isset($object['value']) && is_iterable($object['value']) ){
						foreach($object['value'] as $post)
							$objects[$object['name']][] = $this->load('post', $post->ID);
					}

					break;

				case 'image':

					if( empty($object['value']) )
						break;

					if ($object['return_format'] == 'entity' || (!$this->use_entity && $object['return_format'] == 'array'))
						$objects[$object['name']] = $this->load('image', $object['value'], $object);
					else
						$objects[$object['name']] = $object['value'];

					break;

				case 'gallery':

					if( empty($object['value']) )
						break;

					if( is_array($object['value']) ){

						$objects[$object['name']] = [];

						if( isset($object['value']) && is_iterable($object['value']) ){

							foreach ($object['value'] as $value){

								if ($object['return_format'] == 'entity' || (!$this->use_entity && $object['return_format'] == 'array'))
									$objects[$object['name']][] = $this->load('image', $value, $object);
								else
									$objects[$object['name']][] = $value;
							}
						}
					}

					break;

				case 'file':

					if( empty($object['value']) )
						break;

					if ($object['return_format'] == 'entity' || (!$this->use_entity && $object['return_format'] == 'array'))
						$objects[$object['name']] = $this->load('file', $object['value'], $object);
					else
						$objects[$object['name']] = $object['value'];

					break;

				case 'relationship':

					if( isset($object['value']) && is_iterable($object['value']) ){

                        $relationship = [];

                        foreach ($object['value'] as $value) {

                            if ($object['return_format'] == 'entity' || (!$this->use_entity && $object['return_format'] == 'object'))
                                $item = $this->load('post', $value);
							else
                                $item = $value;

							if( $item )
                                $relationship[] = $item;
                        }

                        if( !empty($relationship) )
                            $objects[$object['name']] = $relationship;
					}
					break;

				case 'post_object':

					if( empty($object['value']) )
						break;

					if ($object['return_format'] == 'link' )
						$objects[$object['name']] = get_permalink($object['value']);
					elseif ($object['return_format'] == 'entity' || (!$this->use_entity && $object['return_format'] == 'object'))
						$objects[$object['name']] = $this->load('post', $object['value']);
					else
						$objects[$object['name']] = $object['value'];

					break;

				case 'user':

					if( empty($object['value']) )
						break;

					$objects[$object['name']] = $this->load('user', $object['value']);
					break;

				case 'flexible_content':

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

				case 'repeater':

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

				case 'taxonomy':

					$objects[$object['name']] = [];

					if( isset($object['value']) && is_iterable($object['value']) ){

						foreach ($object['value'] as $value) {

                            if( $value ){

                                if($object['return_format'] == 'link' )
                                    $objects[$object['name']][] = get_term_link($value);
                                elseif($object['return_format'] == 'entity' || (!$this->use_entity && $object['return_format'] == 'object') )
                                    $objects[$object['name']][] = $this->load('term', $value);
                                else
                                    $objects[$object['name']][] = $value;
                            }
						}
					}
					else{

                        if( $object['value'] ){

                            $value = $object['value'];

                            if($object['return_format'] == 'link' )
                                $objects[$object['name']] = get_term_link($value);
                            elseif($object['return_format'] == 'entity' || (!$this->use_entity && $object['return_format'] == 'object') )
                                $objects[$object['name']] = $this->load('term', $value);
                            else
                                $objects[$object['name']] = $value;
                        }
					}

					break;

				case 'select':

					if( !$object['multiple'] && is_array($object['value']) &&($object['value']) )
						$objects[$object['name']] = $object['value'][0];
					else
						$objects[$object['name']] = $object['value'];

					break;

				case 'group':

					$layout = $this->layoutAsKeyValue($object['sub_fields']);
					$value = $this->bindLayoutFields($object['value'], $layout);

					$objects[$object['name']] = $this->clean($value);

					break;

				case 'url':

					if( is_int($object['value']) )
						$objects[$object['name']] = get_permalink($object['value']);
					else
						$objects[$object['name']] = $object['value'];

					break;

				case 'wysiwyg':
					$objects[$object['name']] = do_shortcode($object['value']);
				    break;

				default:

					$objects[$object['name']] = $object['value'];
					break;
			}
		}

		return $objects;
	}
}
