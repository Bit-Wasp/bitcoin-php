<?php

namespace BitWasp\Bitcoin\Network\P2P;

use BitWasp\Bitcoin\Block\BlockHeaderInterface;
use BitWasp\Bitcoin\Block\BlockInterface;
use BitWasp\Bitcoin\Flags;
use BitWasp\Bitcoin\Network\BloomFilter;
use BitWasp\Bitcoin\Network\Structure\FilteredBlock;
use BitWasp\Bitcoin\Network\MessageFactory;
use BitWasp\Bitcoin\Network\Messages\FilterAdd;
use BitWasp\Bitcoin\Network\Messages\FilterClear;
use BitWasp\Bitcoin\Network\Messages\FilterLoad;
use BitWasp\Bitcoin\Network\Messages\Ping;
use BitWasp\Bitcoin\Network\NetworkMessage;
use BitWasp\Bitcoin\Network\NetworkSerializable;
use BitWasp\Bitcoin\Network\PartialMerkleTree;
use BitWasp\Bitcoin\Network\Structure\AlertDetail;
use BitWasp\Bitcoin\Network\Structure\NetworkAddress;
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
use BitWasp\Bitcoin\Network\Structure\InventoryVector;

class Peer extends EventEmitter
{
    const USER_AGENT = "bitcoin-php/v0.1";
    const PROTOCOL_VERSION = "70000";

    /**
     * @var string
     */
    private $buffer = '';

    /**
     * @var NetworkAddress
     */
    private $localAddr;

    /**
     * @var NetworkAddress
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
     * @param NetworkAddress $addr
     * @param NetworkAddress $local
     * @param Connector $connector
     * @param MessageFactory $msgs
     * @param LoopInterface $loop
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

    /**
     * @return \React\Promise\RejectedPromise|static
     */
    public function connect()
    {
        $deferred = new Deferred();

        $this->connector
            ->create($this->remoteAddr->getIp(), $this->remoteAddr->getPort())
            ->then(function (Stream $stream) use ($deferred) {
                echo "connection connected\n";
                $this->stream = $stream;

                $stream->on('data', function ($data) use ($stream) {
                    $this->buffer .= $data;
                    $length = strlen($this->buffer);
                    $parser = new Parser(new Buffer($this->buffer));
                    try {
                        while ($parser->getPosition() !== $length && $message = $this->msgs->parse($parser)) {
                            $this->buffer = $parser->getBuffer()->slice($parser->getPosition())->getBinary();
                            $this->emit('msg', [$this, $message]);
                        }
                    } catch (\Exception $e) {
                        echo "..";
                    }
                });

                $this->on('msg', function (Peer $peer, NetworkMessage $msg) {
                    echo " [ received " . $msg->getCommand() . " ]\n";
                    $this->emit($msg->getCommand(), [$peer, $msg->getPayload()]);
                });

                $this->on('peerdisconnect', function (Peer $peer) {
                    echo 'peer disconnected';
                });

                $this->on('send', function (NetworkMessage $msg) {
                    echo " [ sending " . $msg->getCommand() . " ]\n";
                });

                $peer = $this;
                $this->loop->addPeriodicTimer($this->pingInterval, function () use ($peer) {
                    $this->ping();
                    if ($this->lastPongTime > time() - ($this->pingInterval + $this->pingInterval * 0.20)) {
                        $this->missedPings++;
                    }
                    if ($this->missedPings > $this->maxMissedPings) {
                        $this->stream->close();
                    }
                });

                $this->on('version', function () {
                    $this->verack();
                });

                $this->on('verack', function () use ($deferred, $stream) {
                    $this->exchangedVersion = true;
                    $this->emit('ready', [$this]);
                    $deferred->resolve($this);
                });

                $this->on('ping', function (Peer $peer, Ping $ping) {
                    $peer->pong($ping);
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

                $this->on('filterclear', function () {
                    $this->filter = null;
                    $this->shouldRelay = true;
                });

                $this->version();
            });

        return $deferred->promise();
    }

    /**
     *
     */
    public function close()
    {
        $this->stream->close();
    }

    /**
     * @return MessageFactory
     */
    public function msgs()
    {
        return $this->msgs;
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
        $this->send($this->msgs()->verack());
    }

    /**
     * @param InventoryVector[] $vInv
     */
    public function inv(array $vInv)
    {
        $this->send($this->msgs()->inv($vInv));
    }

    /**
     * @param InventoryVector[] $vInv
     */
    public function getdata(array $vInv)
    {
        $this->send($this->msgs()->getdata($vInv));
    }

    /**
     * @param array $vInv
     */
    public function notfound(array $vInv)
    {
        $this->send($this->msgs()->notfound($vInv));
    }

    /**
     *
     */
    public function getaddr()
    {
        $this->send($this->msgs()->getaddr());
    }

    /**
     *
     */
    public function ping()
    {
        $this->send($this->msgs()->ping());
    }

    /**
     * @param Ping $ping
     */
    public function pong(Ping $ping)
    {
        $this->send($this->msgs()->pong($ping));
    }

    /**
     * @param TransactionInterface $tx
     */
    public function tx(TransactionInterface $tx)
    {
        $this->send($this->msgs()->tx($tx));
    }

    /**
     * @param array $locatorHashes
     */
    public function getblocks(array $locatorHashes)
    {
        $this->send($this->msgs()->getblocks(
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
        $this->send($this->msgs()->getheaders(
            self::PROTOCOL_VERSION,
            $locatorHashes
        ));
    }

    /**
     * @param BlockInterface $block
     */
    public function block(BlockInterface $block)
    {
        $this->send($this->msgs()->block($block));
    }

    /**
     * @param array $vHeaders
     */
    public function headers(array $vHeaders)
    {
        $this->send($this->msgs()->headers($vHeaders));
    }

    /**
     * @param AlertDetail $detail
     * @param SignatureInterface $signature
     */
    public function alert(AlertDetail $detail, SignatureInterface $signature)
    {
        $this->send($this->msgs()->alert($detail, $signature));
    }

    /**
     * @param Buffer $data
     */
    public function filteradd(Buffer $data)
    {
        $this->send($this->msgs()->filteradd($data));
    }

    /**
     * @param BloomFilter $filter
     */
    public function filterload(BloomFilter $filter)
    {
        $this->send($this->msgs()->filterload($filter));
    }

    /**
     *
     */
    public function filterclear()
    {
        $this->send($this->msgs()->filterclear());
    }

    /**
     * @param FilteredBlock $filtered
     */
    public function merkleblock(FilteredBlock $filtered)
    {
        $this->send($this->msgs()->merkleblock($filtered));
    }

    /**
     *
     */
    public function mempool()
    {
        $this->send($this->msgs()->mempool());
    }

    /**
     * Issue a Reject message, with a required $msg, $code, and $reason
     *
     * @param Buffer $msg
     * @param $code
     * @param Buffer $reason
     * @param Buffer $data
     */
    public function reject(Buffer $msg, $code, Buffer $reason, Buffer $data = null)
    {
        $this->send($this->msgs()->reject($msg, $code, $reason, $data));
    }
}
