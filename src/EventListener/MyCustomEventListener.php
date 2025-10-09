<?php 

namespace App\EventListener;

use App\Event\MyCustomEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MyCustomEventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            MyCustomEvent::class => 'onMyCustomEvent',
        ];
    }

    public function onMyCustomEvent(MyCustomEvent $event)
    {
        // Do something with the event data
        $data = $event->getData();
        dump("Received data: " . $data);
    }
}