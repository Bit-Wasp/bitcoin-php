<?php

namespace BitWasp\Bitcoin\Network\P2P;


use BitWasp\Bitcoin\Network\MessageFactory;
use BitWasp\Bitcoin\Network\Structure\NetworkAddress;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Parser;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Socket\Server;
use React\SocketClient\Connector;
use React\Stream\Stream;

class Peer
{
    const USER_AGENT = "bitcoin-php/v0.1";
    const PROTOCOL_VERSION = "70000";

    /**
     * @var NetworkAddress
     */
    private $remoteAddr;
    private $localAddr;
    /**
     * @var Stream
     */
    private $socket;

    /**
     * @var MessageFactory
     */
    private $msgs;

    /**
     * @var bool
     */
    private $exchangedVersion = false;

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

    }

    public function ready()
    {
        return $this->exchangedVersion;
    }

    public function connect()
    {
        echo "connect()\n";
        $this->connector->create($this->remoteAddr->getIp(), $this->remoteAddr->getPort())->then(function (Stream $stream) {
            echo "connected\n";
            $response = new Deferred();
            $server = new Server($this->loop);
            echo "create Server\n";
            $server->on('connection', function (Stream $stream) use (&$response) {

                echo "server had connection!\n";
                $stream->on('data', function ($data) use (&$response) {
                    echo "incoming server response\n";
                    $response->resolve($data)->then(function ($data) {
                        $parser = new Parser(new Buffer($data));

                    });
                });
            });
            echo "server told to listen\n";
            $server->listen($this->localAddr->getIp(), $this->localAddr->getIp());
            $stream->write($this->version()->getNetworkMessage()->getBinary());
            return $response->promise();

        })->then(function ($data) {
            echo "omg received response\n";
            var_dump($data);
        });
    }

    /**
     *
     */
    public function version()
    {
        return $this->msgs->version(
            self::PROTOCOL_VERSION,
            Buffer::hex('00', 16),
            time(),
            $this->remoteAddr,
            $this->localAddr,
            new Buffer(self::USER_AGENT),
            0,
            false
        );
    }
}