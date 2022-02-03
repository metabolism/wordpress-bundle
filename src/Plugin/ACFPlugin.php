<?php

namespace Metabolism\WordpressBundle\Plugin;

use Metabolism\WordpressBundle\Helper\ACFHelper;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Loader\FilesystemLoader;

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

        $loader = new FilesystemLoader(BASE_URI.'/templates/components');

        $twig = new Environment($loader, [
            // 'cache' => BASE_URI.'/var/cache/dev/twig'
        ]);

        $name = $layout['name'];
        $data = array_values($data)[0];

        $template = $twig->load($name.'.html.twig');

        return $template->render(['data'=>$data]);
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
