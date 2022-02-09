<?php

namespace Metabolism\WordpressBundle\Plugin;

use Metabolism\WordpressBundle\Helper\ACFHelper;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;
use function Env\env;

/**
 * Class Metabolism\WordpressBundle Framework
 */
class ACFPlugin {

    public static $folder = '/config/packages/acf';

    /**
     * Render preview layout
     * @param $layout
     * @param $post_id
     * @param $options
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function layoutPreview($layout, $post_id, $options){

        $acfHelper = new ACFHelper();
        $data = $acfHelper->format([$layout]);

        $components_path = env('COMPONENTS_PATH')?:'/templates/components';

        $loader = new FilesystemLoader(BASE_URI.$components_path);

        $options = [];

        if( WP_ENV != 'dev' && is_dir( BASE_URI.'/var/cache') )
            $options['cache'] = BASE_URI.'/var/cache/components';

        $twig = new Environment($loader, $options);

        $name = $layout['name'];
        $data = array_values($data)[0];

        $template = $twig->load($name.'.html.twig');

        return $template->render(['data'=>$data, 'is_component_preview'=>true]);
    }


    /**
     * Add entity return format
     * @param $field
     * @return array
     */
    public function validateField($field){

        if( $field['name'] === 'return_format'){

            if( isset($field['choices']['object'] ) )
                $field['choices']['link'] = __('Link');

            $field['choices']['entity'] = __('Entity');
            $field['default_value'] = 'entity';
        }

        return $field;
    }


    /**
     * ACFPlugin constructor.
     */
    public function __construct()
    {
        add_filter('acf/settings/save_json', function(){ return BASE_URI.$this::$folder; });
        add_filter('acf/settings/load_json', function(){ return [BASE_URI.$this::$folder]; });

        add_filter('acf/flexible/layout_preview', [$this, 'layoutPreview'], 10, 3);
        add_filter('acf/validate_field', [$this, 'validateField']);
    }
}
