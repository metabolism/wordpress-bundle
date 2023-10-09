<?php

namespace Metabolism\WordpressBundle\Helper;

use function Env\env;

class PathHelper {

    private static $wp_path;

    /**
     * GET WordPress root path
     *
     * @param $project_dir
     * @return string
     */
	public static function getWordpressRoot($project_dir): string
    {
        if( !is_null(self::$wp_path) )
            return self::$wp_path;

        $composer = $project_dir.'/composer.json';
        $wp_path = env('WP_PATH')?:'public/edition/';

        // get WordPress path from composer
        if( !is_dir($project_dir.'/'.$wp_path) && is_readable($composer) ){

            $composer = json_decode(file_get_contents($composer), true);
            $installer_paths= $composer['extra']['installer-paths']??[];

            foreach ($installer_paths as $installer_path=>$types){

                if( in_array("type:wordpress-core", $types) )
                    $wp_path = $installer_path;
            }
        }

        if( !is_dir($project_dir.'/'.$wp_path) )
            die('Unable to detect Wordpress root dir, please use WP_PATH env or keep composer.json file');

        self::$wp_path = $wp_path;

        return self::$wp_path;
    }
}
