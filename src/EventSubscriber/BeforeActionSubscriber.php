<?php

namespace Metabolism\WordpressBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class BeforeActionSubscriber implements EventSubscriberInterface
{
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
        if ( !$event->isMainRequest() )
			return;

        if( wp_is_maintenance_mode() || (function_exists('wp_maintenance_mode') && wp_maintenance_mode()) )
            throw new \Exception('Service Unavailable', 503);
	}
}
