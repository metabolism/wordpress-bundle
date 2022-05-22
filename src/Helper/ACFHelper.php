<?php


namespace Metabolism\WordpressBundle\Helper;

use Metabolism\WordpressBundle\Entity\Entity;

use Metabolism\WordpressBundle\Factory\Factory,
	Metabolism\WordpressBundle\Factory\PostFactory,
	Metabolism\WordpressBundle\Factory\TermFactory;

class ACFHelper
{
	private $objects;
	private $id;

    public static $use_entity;

    /**
     * ACFHelper constructor.
     * @param $id
     * @param bool $type
     * @param bool $load_value
     */
	public function __construct( $id=false, $type=false, $load_value=false )
	{
		global $_config;

		if( !class_exists('ACF') || !$id )
			return;

        if( is_null(self::$use_entity) && $_config )
            self::$use_entity = $_config->get('acf.settings.use_entity', false);

		if( !in_array($type, ['post', 'blog']) && $type)
			$id = $type.'_'.$id;

		$this->id = $id;

        if( $load_value )
            $this->getFieldObjects( true );
	}


    /**
     * Magic method to check properties
     *
     * @param $id
     * @return bool
     */
	public function __isset($id) {

		return $this->has($id);
	}


    /**
     * Magic method to load properties
     *
     * @param $id
     * @return null|string|array|object
     */
	public function __get($id) {

		return $this->getValue($id);
	}


    /**
     * Magic method to load properties
     *
     * @param $id
     * @return null|string|array|object
     */
	public function __call($id, $args) {

		return $this->getValue($id);
	}


	/**
	 * @param $id
	 * @return bool
	 */
	public function has($id){

        $this->getFieldObjects();

        return isset($this->objects[$id]);
	}


	/**
	 * @deprecated
	 */
	public function get_value($id){

		return $this->getValue($id);
	}

	/**
	 * @param $id
	 * @return null|string|array|object
	 */
	public function getValue($id){

		if( !$this->has($id) )
			return null;

        $field = $this->objects[$id];

        if( isset($field['value']) )
            return $field['value'];

        $field['value'] = acf_get_value( $this->id, $field );
        $field['value'] = acf_format_value( $field['value'], $this->id, $field );

        $data = $this->format([$field]);

        $this->objects[$id]['value'] = $data[$id]??null;

		return $this->objects[$id]['value'];
	}

	/**
	 * @return array
	 */
	public static function get($id){

		$self = new self($id, false, true);

		return $self->format($self->objects);
	}


	/**
	 * @return bool
	 */
	public function loaded()
	{
		return !is_null($this->objects);
	}


	/**
	 * @return void
	 */
	public function getFieldObjects($load_value=false)
	{
        if( $this->loaded() )
            return;

        if( $cached = wp_cache_get( $this->id, 'acf_helper' ) ){

            $this->objects = $cached;
        }
        else{

            $this->objects = get_field_objects($this->id, $load_value, $load_value);
            wp_cache_set( $this->id, $this->objects, 'acf_helper' );
        }
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
				$value = Factory::create($id, 'image');
				break;

			case 'file':
				$value = Factory::create($id, 'file');
				break;

			case 'post':
				$value = PostFactory::create( $id );
				break;

			case 'user':
				$value = Factory::create($id, 'user');
				break;

			case 'term':
				$value = TermFactory::create( $id );
				break;
		}

		return $value;
	}


	/**
	 * @param $raw_objects
	 * @return array
	 */
	public function format($raw_objects)
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

						$objects[$object['name']] = $this->format($object['sub_fields']);
					}

					break;

				case 'latest_posts':
				case 'children':

					$objects[$object['name']] = [];

					if( isset($object['value']) && is_iterable($object['value']) ){
						foreach($object['value'] as $post)
							$objects[$object['name']][] = $this->load('post', $post->ID);
					}

					break;

				case 'image':

					if( empty($object['value']) )
						break;

					if ($object['return_format'] == 'entity' || (!self::$use_entity && $object['return_format'] == 'array'))
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

								if ($object['return_format'] == 'entity' || (!self::$use_entity && $object['return_format'] == 'array'))
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

					if ($object['return_format'] == 'entity' || (!self::$use_entity && $object['return_format'] == 'array'))
						$objects[$object['name']] = $this->load('file', $object['value'], $object);
					else
						$objects[$object['name']] = $object['value'];

					break;

				case 'relationship':

					if( isset($object['value']) && is_iterable($object['value']) ){

						$relationship = [];

						foreach ($object['value'] as $value) {

							if ($object['return_format'] == 'entity' || (!self::$use_entity && $object['return_format'] == 'object'))
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
					elseif ($object['return_format'] == 'entity' || (!self::$use_entity && $object['return_format'] == 'object'))
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
							$data = $this->format($value);

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
							$objects[$object['name']][] = $this->format($value);
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
								elseif($object['return_format'] == 'entity' || (!self::$use_entity && $object['return_format'] == 'object') )
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
							elseif($object['return_format'] == 'entity' || (!self::$use_entity && $object['return_format'] == 'object') )
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

					$objects[$object['name']] = $this->format($value);

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
