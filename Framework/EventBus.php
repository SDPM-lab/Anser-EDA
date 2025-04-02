<?php
namespace App\Framework;

use App\Framework\MessageQueue\MessageBus;
use App\Framework\EventStore\EventStoreDB;

class EventBus
{
    private array $handlers = [];
    private MessageBus $messageBus;
    private EventStoreDB $eventStoreDB;
    
    public function __construct(MessageBus $messageBus, EventStoreDB $eventStoreDB)
    {
        $this->messageBus = $messageBus;
        $this->eventStoreDB = $eventStoreDB;
    }


    public function registerHandler(string $eventType, callable $handler)
    {
        if (!isset($this->handlers[$eventType])) {
            $this->handlers[$eventType] = [];
        }

        // ✅ 確保不會重複註冊相同的 handler
        foreach ($this->handlers[$eventType] as $existingHandler) {
            if ($existingHandler === $handler) {
                return;
            }
        }

        $this->handlers[$eventType][] = $handler;
    }

    public function dispatch(object $event)
    {
        $eventType = get_class($event);
        //echo "Dispatching Event: $eventType\n";

        if (!isset($this->handlers[$eventType])) {
            return;
        }

        foreach ($this->handlers[$eventType] as $handler) {
            //echo "Executing handler for: $eventType\n";
            call_user_func($handler, $event);
        }
    }

    public function publish(string $eventType, array $eventData,string $streamName = 'Order_Streams')
    {
        $this->eventStoreDB->appendEvent($streamName, [
            'eventId' => uniqid('event_', true),
            'eventType' => $eventType,
            'data' => $eventData,
            'metadata' => []
        ]);

        $this->messageBus->publishEvent($eventType, $eventData);
    }

}