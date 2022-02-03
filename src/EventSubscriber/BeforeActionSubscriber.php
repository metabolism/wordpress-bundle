<?php

namespace Metabolism\WordpressBundle\EventSubscriber;

use Psr\Container\ContainerInterface;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class BeforeActionSubscriber implements EventSubscriberInterface
{
	private $container;

	public function __construct(ContainerInterface $container)
	{
		$this->container = $container;
	}


	/**
	 * @return array
	 */
	public static function getSubscribedEvents()
	{
		return [KernelEvents::CONTROLLER => 'onKernelController'];
	}


    /**
     * @param ControllerEvent $event
     * @return void
     * @throws \Exception
     */
	public function onKernelController(ControllerEvent $event)
	{
        if ( !$event->isMasterRequest() )
			return;

        if( wp_maintenance_mode() )
            throw new \Exception('Service Unavailable', 503);
	}
}