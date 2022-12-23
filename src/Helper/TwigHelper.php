<?php

namespace Metabolism\WordpressBundle\Helper;

use App\Twig\AppExtension;
use Metabolism\WordpressBundle\Twig\WordpressTwigExtension;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class TwigHelper {

    private static $env;

	/**
	 * Todo: use real symfony twig env
	 *
	 * @return Environment
	 */
	public static function getEnvironment(): Environment
    {
		if( !is_null(self::$env) )
			return self::$env;

	    $loader = new FilesystemLoader(BASE_URI.'/templates');

	    $options = [];

	    if( WP_ENV != 'dev' && is_dir( BASE_URI.'/var/cache') )
		    $options['cache'] = BASE_URI.'/var/cache/twig';

	    $twig = new Environment($loader, $options);

	    if( class_exists('App\Twig\AppExtension'))
		    $twig->addExtension(new AppExtension());

	    if( class_exists('\Twig\Extra\Intl\IntlExtension'))
		    $twig->addExtension(new \Twig\Extra\Intl\IntlExtension());

	    $twig->addExtension(new WordpressTwigExtension());

		self::$env = $twig;

		return self::$env;
    }
}
