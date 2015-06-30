<?php

namespace BitWasp\Bitcoin\Network\P2P;

use BitWasp\Bitcoin\Block\BlockInterface;
use BitWasp\Bitcoin\Network\BloomFilter;
use BitWasp\Bitcoin\Network\Structure\FilteredBlock;
use BitWasp\Bitcoin\Network\MessageFactory;
use BitWasp\Bitcoin\Network\Messages\FilterAdd;
use BitWasp\Bitcoin\Network\Messages\FilterLoad;
use BitWasp\Bitcoin\Network\Messages\Ping;
use BitWasp\Bitcoin\Network\NetworkMessage;
use BitWasp\Bitcoin\Network\NetworkSerializable;
use BitWasp\Bitcoin\Network\Structure\AlertDetail;
use BitWasp\Bitcoin\Network\Structure\InventoryVector;
use BitWasp\Bitcoin\Network\Structure\NetworkAddressInterface;
use BitWasp\Bitcoin\Script\Interpreter\InterpreterInterface;
use BitWasp\Bitcoin\Signature\SignatureInterface;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Parser;
use Evenement\EventEmitter;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\SocketClient\Connector;
use React\Stream\Stream;

class Peer extends EventEmitter
{
    const USER_AGENT = "bitcoin-php/v0.1";
    const PROTOCOL_VERSION = "70000";

    /**
     * @var string
     */
    private $buffer = '';

    /**
     * @var NetworkAddressInterface
     */
    private $localAddr;

    /**
     * @var NetworkAddressInterface
     */
    private $remoteAddr;

    /**
     * @var BloomFilter
     */
    private $filter;

    /**
     * @var Connector
     */
    private $connector;

    /**
     * @var LoopInterface
     */
    private $loop;

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
    private $pingInterval = '600';

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

    private $shouldRelay = false;

    /**
     * @param NetworkAddressInterface $addr
     * @param NetworkAddressInterface $local
     * @param Connector $connector
     * @param MessageFactory $msgs
     * @param LoopInterface $loop
     */
    public function __construct(
        NetworkAddressInterface $addr,
        NetworkAddressInterface $local,
        Connector $connector,
        MessageFactory $msgs,
        LoopInterface $loop
    ) {
        $this->remoteAddr = $addr;
        $this->localAddr = $local;
        $this->connector = $connector;
        $this->msgs = $msgs;
        $this->loop = $loop;
        $this->lastPongTime = time();
    }

    /**
     * @return NetworkAddressInterface
     */
    public function getRemoteAddr()
    {
        return $this->remoteAddr;
    }

    /**
     * @return NetworkAddressInterface
     */
    public function getLocalAddr()
    {
        return $this->localAddr;
    }

    /**
     * @return bool
     */
    public function shouldRelay()
    {
        return $this->shouldRelay;
    }

    /**
     * @return bool
     */
    public function hasFilter()
    {
        return $this->filter !== null;
    }

    /**
     * @return BloomFilter
     * @throws \Exception
     */
    public function getFilter()
    {
        if (!$this->hasFilter()) {
            throw new \Exception('No filter set for peer');
        }

        return $this->filter;
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

    public function onData($data)
    {
        $this->buffer .= $data;
        $length = strlen($this->buffer);
        $parser = new Parser(new Buffer($this->buffer));
        try {
            while ($parser->getPosition() !== $length && $message = $this->msgs->parse($parser)) {
                $this->buffer = $parser->getBuffer()->slice($parser->getPosition())->getBinary();
                $this->emit('msg', [$this, $message]);
            }
        } catch (\Exception $e) {
            if ($data == "") {
                echo "EMPTY PACKET!";
            }
            echo ".";
        }
    }

    /**
     * @return \React\Promise\RejectedPromise|static
     */
    public function connect()
    {
        $deferred = new Deferred();

        $this->connector
            ->create($this->remoteAddr->getIp(), $this->remoteAddr->getPort())
            ->then(function (Stream $stream) use ($deferred) {
                $this->stream = $stream;

                $stream->on('data', [$this, 'onData']);

                $this->on('msg', function (Peer $peer, NetworkMessage $msg) {
                    echo " [ received " . $msg->getCommand() . " - " . $this->getRemoteAddr()->getIp() . "]\n";
                    $this->emit($msg->getCommand(), [$peer, $msg->getPayload()]);
                });

                $this->on('close', function () {
                    echo "Connection was closed\n";
                });

                $this->on('peerdisconnect', function (Peer $peer) {
                    echo 'peer disconnected';
                });

                $this->on('send', function (NetworkMessage $msg) {
                    echo " [ sending " . $msg->getCommand() . " - " . $this->getRemoteAddr()->getIp() . "]\n";
                });

                $this->on('version', function () {
                    $this->verack();
                });

                $this->on('verack', function () use ($deferred) {
                    if ($this->exchangedVersion == false) {
                        $this->exchangedVersion = true;
                        $this->loop->addPeriodicTimer($this->pingInterval, function (\React\EventLoop\Timer\Timer $timer) {
                            if (null === $this->stream) {
                                $timer->cancel();
                            }
                            $this->ping();
                            if ($this->lastPongTime > time() - ($this->pingInterval + $this->pingInterval * 0.20)) {
                                $this->missedPings++;
                            }
                            if ($this->missedPings > $this->maxMissedPings) {
                                $this->close();
                            }
                        });
                        $this->emit('ready', [$this]);
                        $deferred->resolve($this);
                    }
                });

                $this->on('ping', function (Peer $peer, Ping $ping) {
                    $this->pong($ping);
                });

                $this->on('filterload', function (Peer $peer, FilterLoad $filterLoad) {
                    $filter = $filterLoad->getFilter();

                    $this->filter = $filter;
                    $this->shouldRelay = true;
                });

                $this->on('filteradd', function (Peer $peer, FilterAdd $filterAdd) {
                    if (!$this->hasFilter()) {
                        // misbehaving
                        $this->close();
                    }

                    $data = $filterAdd->getData();
                    if ($data->getSize() > InterpreterInterface::MAX_SCRIPT_ELEMENT_SIZE) {
                        // misbehaving
                        $this->close();
                    }

                    $this->filter->insertData($data);
                });

                $this->on(
                    'filterclear',
                    function () {
                        $this->filter = null;
                        $this->shouldRelay = true;
                    }
                );

                $this->version();
            }, function ($error) use ($deferred) {
                $deferred->reject($error);
            });

        return $deferred->promise();
    }

    /**
     *
     */
    public function close()
    {
        $this->stream->end();
        $this->stream->close();
    }

    /**
     * @return \BitWasp\Bitcoin\Network\Messages\Version
     */
    public function version()
    {
        $this->send($this->msgs->version(
            self::PROTOCOL_VERSION,
            Buffer::hex('01', 16),
            time(),
            $this->remoteAddr,
            $this->localAddr,
            new Buffer(self::USER_AGENT),
            0,
            false
        ));
    }

    /**
     *
     */
    public function verack()
    {
        $this->send($this->msgs->verack());
    }

    /**
     * @param InventoryVector[] $vInv
     */
    public function inv(array $vInv)
    {
        $this->send($this->msgs->inv($vInv));
    }

    /**
     * @param InventoryVector[] $vInv
     */
    public function getdata(array $vInv)
    {
        $this->send($this->msgs->getdata($vInv));
    }

    /**
     * @param array $vInv
     */
    public function notfound(array $vInv)
    {
        $this->send($this->msgs->notfound($vInv));
    }

    /**
     *
     */
    public function getaddr()
    {
        $this->send($this->msgs->getaddr());
    }

    /**
     *
     */
    public function ping()
    {
        $this->send($this->msgs->ping());
    }

    /**
     * @param Ping $ping
     */
    public function pong(Ping $ping)
    {
        $this->send($this->msgs->pong($ping));
    }

    /**
     * @param TransactionInterface $tx
     */
    public function tx(TransactionInterface $tx)
    {
        $this->send($this->msgs->tx($tx));
    }

    /**
     * @param array $locatorHashes
     */
    public function getblocks(array $locatorHashes)
    {
        $this->send($this->msgs->getblocks(
            self::PROTOCOL_VERSION,
            $locatorHashes
        ));
    }

    /**
     * @param Buffer[] $locatorHashes
     * @return \BitWasp\Bitcoin\Network\Messages\GetHeaders
     */
    public function getheaders(array $locatorHashes)
    {
        $this->send($this->msgs->getheaders(
            self::PROTOCOL_VERSION,
            $locatorHashes
        ));
    }

    /**
     * @param BlockInterface $block
     */
    public function block(BlockInterface $block)
    {
        $this->send($this->msgs->block($block));
    }

    /**
     * @param array $vHeaders
     */
    public function headers(array $vHeaders)
    {
        $this->send($this->msgs->headers($vHeaders));
    }

    /**
     * @param AlertDetail $detail
     * @param SignatureInterface $signature
     */
    public function alert(AlertDetail $detail, SignatureInterface $signature)
    {
        $this->send($this->msgs->alert($detail, $signature));
    }

    /**
     * @param Buffer $data
     */
    public function filteradd(Buffer $data)
    {
        $this->send($this->msgs->filteradd($data));
    }

    /**
     * @param BloomFilter $filter
     */
    public function filterload(BloomFilter $filter)
    {
        $this->send($this->msgs->filterload($filter));
    }

    /**
     *
     */
    public function filterclear()
    {
        $this->send($this->msgs->filterclear());
    }

    /**
     * @param FilteredBlock $filtered
     */
    public function merkleblock(FilteredBlock $filtered)
    {
        $this->send($this->msgs->merkleblock($filtered));
    }

    /**
     *
     */
    public function mempool()
    {
        $this->send($this->msgs->mempool());
    }

    /**
     * Issue a Reject message, with a required $msg, $code, and $reason
     *
     * @param Buffer $msg
     * @param int $code
     * @param Buffer $reason
     * @param Buffer $data
     */
    public function reject(Buffer $msg, $code, Buffer $reason, Buffer $data = null)
    {
        $this->send($this->msgs->reject($msg, $code, $reason, $data));
    }
}
