<?php

namespace Metabolism\WordpressBundle\Twig;

use Metabolism\WordpressBundle\Entity\Blog;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;
use function Env\env;

class TwigGlobalSubscriber implements EventSubscriberInterface {

	/**
	 * @var Environment
	 */
	private $twig;

	public function __construct( Environment $twig ) {

		$this->twig = $twig;
	}

	public function injectGlobalVariables() {

		$blog = Blog::getInstance();
		$this->twig->addGlobal('blog', $blog);

		// retro-compatibility
		if( env('MIGRATE_FROM_V1') )
			$blog->setGlobals($this->twig);
	}

	/**
	 * @return array
	 */
	public static function getSubscribedEvents(): array{

		return [ KernelEvents::CONTROLLER =>  'injectGlobalVariables' ];
	}

	public function onKernelRequest(){}
}
