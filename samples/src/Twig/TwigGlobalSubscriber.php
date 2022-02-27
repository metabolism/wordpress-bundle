<?php

namespace App\Twig;

use Metabolism\WordpressBundle\Repository\PostRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class TwigGlobalSubscriber implements EventSubscriberInterface {

    /**
     * @var \Twig\Environment
     */
    private $twig;
    private $postRepository;

    public function __construct( Environment $twig, PostRepository $postRepository) {
        $this->twig = $twig;
        $this->postRepository = $postRepository;
    }

    public function injectGlobalVariables( ControllerEvent $event ) {

        $subsidiaries = $this->postRepository->findBy(['post_type'=>'subsidiary'], null, -1);
        $this->twig->addGlobal( 'subsidiaries', $subsidiaries );
    }

    public static function getSubscribedEvents() {
        return [ KernelEvents::CONTROLLER =>  'injectGlobalVariables' ];
    }
}