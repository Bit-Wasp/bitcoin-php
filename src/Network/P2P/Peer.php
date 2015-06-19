<?php

namespace BitWasp\Bitcoin\Network\P2P;

use BitWasp\Bitcoin\Network\MessageFactory;
use BitWasp\Bitcoin\Network\Messages\VerAck;
use BitWasp\Bitcoin\Network\NetworkMessage;
use BitWasp\Bitcoin\Network\NetworkSerializable;
use BitWasp\Bitcoin\Network\Structure\NetworkAddress;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Parser;
use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;
use React\SocketClient\Connector;
use React\Stream\Stream;

class Peer extends EventEmitter
{
    const USER_AGENT = "bitcoin-php/v0.1";
    const PROTOCOL_VERSION = "70000";

    /**
     * @var NetworkAddress
     */
    private $remoteAddr;

    /**
     * @var NetworkAddress
     */
    private $localAddr;

    /**
     * @var MessageFactory
     */
    private $msgs;

    /**
     * @var Stream
     */
    private $stream;

    /**
     * @var bool
     */
    private $exchangedVersion = false;

    /**
     * @var int
     */
    private $pingInterval = 60;

    /**
     * @var int
     */
    private $maxMissedPings = 5;

    /**
     * @var int
     */
    private $missedPings = 0;

    /**
     * @var int
     */
    private $lastPongTime;

    /**
     * @param NetworkAddress $addr
     * @param NetworkAddress $local
     * @param Connector $connector
     * @param MessageFactory $msgs
     */
    public function __construct(NetworkAddress $addr, NetworkAddress $local, Connector $connector, MessageFactory $msgs, LoopInterface $loop)
    {
        $this->remoteAddr = $addr;
        $this->localAddr = $local;
        $this->connector = $connector;
        $this->msgs = $msgs;
        $this->loop = $loop;
        $this->lastPongTime = time();
    }

    /**
     * @return bool
     */
    public function ready()
    {
        return $this->exchangedVersion;
    }

    /**
     * @param NetworkSerializable $msg
     */
    public function send(NetworkSerializable $msg)
    {
        $net = $msg->getNetworkMessage();
        $this->stream->write($net->getBinary());
        $this->emit('send', [$net]);
    }

    /**
     * @param Stream $stream
     */
    private function initConnection(Stream $stream)
    {
        $this->stream = $stream;
        $this->send($this->version());

        $this->on('msg', function (NetworkMessage $msg) {
            $this->emit($msg->getCommand(), [$msg->getPayload()]);
        });

        $this->on('verack', function (VerAck $verAck) {
            $this->exchangedVersion = true;
            $this->emit('ready', [$this]);
        });

        $this->on('pong', function () {
            $this->lastPongTime = time();
        });

        $peer = $this;

        $this->loop->addPeriodicTimer($this->pingInterval, function () use ($peer) {
            $peer->send($this->msgs->ping());
            if ($this->lastPongTime > time() - ($this->pingInterval + $this->pingInterval * 0.20)) {
                $this->missedPings++;
            }
            if ($this->missedPings > 10) {
                $this->stream->close();
            }
        });
    }

    /**
     * @return \React\Promise\RejectedPromise|static
     */
    public function connect()
    {
        return $this->connector
            ->create($this->remoteAddr->getIp(), $this->remoteAddr->getPort())
            ->then(function (Stream $stream) {
                $this->initConnection($stream);
                $stream->on('data', function ($data) {
                    $message = $this->msgs->parse(new Parser(new Buffer($data)));
                    $this->emit('msg', [$message]);
                });
            });
    }

    /**
     * @return \BitWasp\Bitcoin\Network\Messages\Version
     */
    public function version()
    {
        return $this->msgs->version(
            self::PROTOCOL_VERSION,
            Buffer::hex('01', 16),
            time(),
            $this->remoteAddr,
            $this->localAddr,
            new Buffer(self::USER_AGENT),
            0,
            false
        );
    }
}
