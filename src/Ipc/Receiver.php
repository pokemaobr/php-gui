<?php

namespace Gui\Ipc;

use Gui\Application;

class Receiver
{
    public $application;
    public $messageCallbacks = [];

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * When the result of a message arrives, we call a callback
     * @param int $id       Message ID
     * @param function $callback Callback function
     */
    public function addMessageCallback($id, $callback)
    {
        $this->messageCallbacks[$id] = $callback;
    }

    /**
     * Fire a event on a object
     * @param  int $id Object ID
     * @param  String $eventName Event Name
     */
    public function callObjectEventListener($id, $eventName)
    {
        $object = $this->application->getObject($id);

        if ($object) {
            $object->fire($eventName);
        }
    }

    /**
     * Result received, time to call the message callback
     * @param  int $id Message ID
     * @param  String|Array|Object|int $result Command Result
     */
    public function callMessageCallback($id, $result)
    {
        if (array_key_exists($id, $this->messageCallbacks)) {
            $this->messageCallbacks[$id]($result);
            unset($this->messageCallbacks[$id]);
        }
    }

    /**
     * Process Stdout handler
     * @param  String $data Data received
     */
    public function onData($data)
    {
        echo 'Received: ' . $data . PHP_EOL;

        if (strlen($data) === 0) {
            return;
        }

        if ($data[0] === '{' && $data[strlen($data) - 1] === '}') {
            $message = json_decode($data);

            // Can be a command or a result
            if ($message && property_exists($message, 'id')) {
                if (property_exists($message, 'result')) {
                    $this->callMessageCallback($message->id, $message->result);
                } else {
                    // @TODO: Command implementation
                }
            }

            // This is a notification/event!
            if ($message && ! property_exists($message, 'id')) {
                if ($message->method == 'callObjectEventListener') {
                    // @TODO: Check if params contains all the items
                    $this->callObjectEventListener($message->params[0], $message->params[1]);
                }
            }
        }
    }
}