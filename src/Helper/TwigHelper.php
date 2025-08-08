<?php

namespace Metabolism\WordpressBundle\Helper;

use App\Twig\AppExtension;
use Metabolism\WordpressBundle\Twig\WordpressTwigExtension;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Symfony\Component\Asset\Package;
use Symfony\Component\Asset\Packages;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;

class TwigHelper {

    private static $env;

	/**
	 * Todo: use Micro Kernel with service autoload ( check perf )
	 *
	 * @return Environment
	 */
	public static function getEnvironment(): Environment
    {
		if( !is_null(self::$env) )
			return self::$env;

	    $loader = new FilesystemLoader(BASE_URI.'/templates');

	    $options = [];

	    if( !WP_DEBUG && is_dir( BASE_URI.'/var/cache') )
		    $options['cache'] = BASE_URI.'/var/cache/'.WP_ENV.'/twig';

	    $twig = new Environment($loader, $options);

        if( class_exists('Symfony\Bridge\Twig\Extension\AssetExtension')){

            $packages = new Packages(new Package(new EmptyVersionStrategy()));
            $twig->addExtension(new AssetExtension($packages));
        }

	    if( class_exists('App\Twig\AppExtension'))
		    $twig->addExtension(new AppExtension());

	    if( class_exists('\Twig\Extra\Intl\IntlExtension'))
		    $twig->addExtension(new \Twig\Extra\Intl\IntlExtension());

	    $twig->addExtension(new WordpressTwigExtension());

		self::$env = $twig;

		return self::$env;
    }
}
