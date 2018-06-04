<?php

namespace App\Components\QBus;

use RuntimeException;

class QBusUtil
{
    protected $cluster;
    protected $topic;
    protected $group;

    protected $producer;
    protected $consumer;
    protected $prepared = false;

    public function __construct($cluster, $topic, $group)
    {
        $this->cluster = $cluster;
        $this->topic = $topic;
        $this->group = $group;

        // XXX ugly file path...
        require_once('/home/t/php/kafka_client/lib/kafka_client.php');

        // 禁止非线上环境使用default相关的group
        if (!app()->environment('production') && stripos($group, 'default') !== false) {
            throw new RuntimeException('QBus group "'.$group.'" is forbidden on non-production env');
        }
    }

    /**
     * Push a raw payload onto the queue.
     *
     * @param  string  $payload
     * @param  string  $queue
     * @param  array   $options
     * @return mixed
     */
    public function pushRaw($payload, $queue = null, array $options = [])
    {
        $topic = is_null($queue) ? $this->topic : $queue;

        // Lazy load
        if (!isset($this->producer)) {
            $this->producer = \Kafka_Producer::getInstance($this->cluster);
        }

        /**
         * public function send($messages, $topic, $flag = Kafka_ConstDef::MESSAGE_RANDOM_SEND, $semanticKey = '')
         *
         * @param $messages String or Array  需要发送的消息
         * $param $topic    String 发送的topic
         * $param $flag     Int    发送的策略，MESSAGE_RANDOM_SEND 或者 MESSAGE_AFFINITY_SEND MESSAGE_SEMANTIC_SEND
         * $param $semanticKey String  如果flag为MESSAGE_SEMANTIC_SEND, 相同提供相同key的消息将会被同一个consumer来消费
         * @return true or false
         */
        return $this->producer->send($payload, $topic);
    }

    public function popRaw($queue = null)
    {
        $topic = is_null($queue) ? $this->topic : $queue;

        // Lazy load
        if (!isset($this->consumer)) {
            $this->consumer = new \Kafka_Consumer($this->cluster, $topic, 'yield', $this->group);
        }

        if (!$this->prepared) {
            $this->consumer->readPrepare();
        }

        // Fetch next message
        $payload = $this->consumer->readNext();

        return $payload;
    }

    public function serve($callback, $queue = null)
    {
        $topic = is_null($queue) ? $this->topic : $queue;

        $this->consumer = new \Kafka_Consumer($this->cluster, $topic, $callback, $this->group);

        $this->consumer->work();
    }
}
