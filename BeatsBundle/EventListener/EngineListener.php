<?php

namespace BeatsBundle\EventListener;

use BeatsBundle\Service\ContainerAware;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class EngineListener extends ContainerAware {

  public function onKernelRequest(GetResponseEvent $event) {
    if ($event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) {
      return;
    }
    $this->_chronos()->setupTimezone($event->getRequest());
  }

//  public function onKernelController(FilterControllerEvent $event) {
//    if ($event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) {
//      return;
//    }
//  }
//
//  public function onKernelResponse(FilterResponseEvent $event) {
//    if ($event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) {
//      return;
//    }
//  }

}
