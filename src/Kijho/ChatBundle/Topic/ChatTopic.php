<?php

namespace Kijho\ChatBundle\Topic;

use Gos\Bundle\WebSocketBundle\Topic\TopicInterface;
use Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimer;
use Gos\Bundle\WebSocketBundle\Topic\TopicPeriodicTimerInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Wamp\Topic;
use Gos\Bundle\WebSocketBundle\Router\WampRequest;

class ChatTopic implements TopicInterface, TopicPeriodicTimerInterface {

    /**
     * @var TopicPeriodicTimer
     */
    protected $periodicTimer;

    /**
     * This will receive any Subscription requests for this topic.
     *
     * @param ConnectionInterface $connection
     * @param Topic $topic
     * @param WampRequest $request
     * @return void
     */
    public function onSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request) {
        //this will broadcast the message to ALL subscribers of this topic.
        $topic->broadcast(['msg' => $connection->nickname . " has joined " . $topic->getId()]);
        
        /** @var ConnectionPeriodicTimer $topicTimer */
        /*$topicTimer = $connection->PeriodicTimer;

        //Add periodic timer
        $topicTimer->addPeriodicTimer('hello', 2, function() use ($topic, $connection) {
            $connection->event($topic->getId(), ['msg' => 'you are active']);
        });

        //exist
        $topicTimer->isPeriodicTimerActive('hello'); //true or false*/
    }

    /**
     * This will receive any UnSubscription requests for this topic.
     *
     * @param ConnectionInterface $connection
     * @param Topic $topic
     * @param WampRequest $request
     * @return void
     */
    public function onUnSubscribe(ConnectionInterface $connection, Topic $topic, WampRequest $request) {
        //this will broadcast the message to ALL subscribers of this topic.
        $topic->broadcast(['msg' => $connection->nickname . " has left " . $topic->getId()]);
    }

    /**
     * This will receive any Publish requests for this topic.
     *
     * @param ConnectionInterface $connection
     * @param Topic $topic
     * @param WampRequest $request
     * @param $event
     * @param array $exclude
     * @param array $eligible
     * @return mixed|void
     */
    public function onPublish(ConnectionInterface $connection, Topic $topic, WampRequest $request, $event, array $exclude, array $eligible) {
        /*
          $topic->getId() will contain the FULL requested uri, so you can proceed based on that

          if ($topic->getId() === 'acme/channel/shout')
          //shout something to all subs.
         */

        //\Symfony\Component\VarDumper\VarDumper::dump($connection);die();
        
        
        $topic->broadcast([
            'msg' => $connection->nickname." says: ".$event,
        ]);
    }

    /**
     * Like RPC is will use to prefix the channel
     * @return string
     */
    public function getName() {
        return 'chat.topic';
    }

    /**
     * @param TopicPeriodicTimer $periodicTimer
     */
    public function setPeriodicTimer(TopicPeriodicTimer $periodicTimer) {
        $this->periodicTimer = $periodicTimer;
    }

    /**
     * @param Topic $topic
     *
     * @return array
     */
    public function registerPeriodicTimer(Topic $topic) {
        /*//add
        $this->periodicTimer->addPeriodicTimer($this, 'hello', 2, function() use ($topic) {
            $topic->broadcast('hello world');
        });

        //exist
        $this->periodicTimer->isPeriodicTimerActive($this, 'hello'); // true or false
        //remove
        $this->periodicTimer->cancelPeriodicTimer($this, 'hello');*/
    }
    
}
