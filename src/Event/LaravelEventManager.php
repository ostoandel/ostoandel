<?php
namespace Ostoandel\Event;

use Illuminate\Support\Facades\Event;

\App::uses('CakeEventManager', 'Event');

class LaravelEventManager extends \CakeEventManager
{

    /**
     *
     * {@inheritDoc}
     * @see \CakeEventManager::prioritisedListeners()
     */
    public function prioritisedListeners($eventKey)
    {
        $listeners = parent::prioritisedListeners($eventKey);

        if (Event::hasListeners($eventKey)) {
            $listeners = [
                // Use an empty string as pesudo priority number so that Laravel's listeners have higher priority than Cake's alyways.
                '' => [
                    [
                        'callable' => [$this, 'dispatchToLaravel'],
                        'passParams' => false,
                    ],
                ],
            ] + $listeners;
        }

        return $listeners;
    }

    /**
     *
     * @param \CakeEvent $event
     */
    public function dispatchToLaravel($event)
    {
        $eventName = $event->name();

        $listeners = Event::getListeners($eventName);
        $responses = Event::dispatch($eventName, [ $event ]);

        if ($event->isStopped() || count($listeners) !== count($responses)) {
            return false;
        }

        foreach (array_reverse($responses) as $response) {
            if ($response !== null) {
                return $response;
            }
        }

        return null;
    }

}
