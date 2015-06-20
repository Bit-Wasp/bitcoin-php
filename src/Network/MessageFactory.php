<?php

namespace BitWasp\Bitcoin\Network;

use BitWasp\Bitcoin\Block\BlockInterface;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Network\Messages\Addr;
use BitWasp\Bitcoin\Network\Messages\Alert;
use BitWasp\Bitcoin\Network\Messages\Block;
use BitWasp\Bitcoin\Network\Messages\GetAddr;
use BitWasp\Bitcoin\Network\Messages\GetData;
use BitWasp\Bitcoin\Network\Messages\Headers;
use BitWasp\Bitcoin\Network\Messages\Inv;
use BitWasp\Bitcoin\Network\Messages\MemPool;
use BitWasp\Bitcoin\Network\Messages\NotFound;
use BitWasp\Bitcoin\Network\Messages\Ping;
use BitWasp\Bitcoin\Network\Messages\Pong;
use BitWasp\Bitcoin\Network\Messages\Reject;
use BitWasp\Bitcoin\Network\Messages\Tx;
use BitWasp\Bitcoin\Network\Messages\VerAck;
use BitWasp\Bitcoin\Network\Messages\Version;
use BitWasp\Bitcoin\Network\Structure\AlertDetail;
use BitWasp\Bitcoin\Network\Structure\NetworkAddress;
use BitWasp\Bitcoin\Serializer\Network\NetworkMessageSerializer;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Parser;
use Mdanter\Ecc\Crypto\Signature\SignatureInterface;

class MessageFactory
{
    /**
     * @var NetworkInterface
     */
    private $network;

    /**
     * @var Random
     */
    private $random;

    /**
     * @param NetworkInterface $network
     * @param Random $random
     */
    public function __construct(NetworkInterface $network, Random $random)
    {
        $this->network = $network;
        $this->random = $random;
    }

    /**
     * @param int $version
     * @param Buffer $services
     * @param int $timestamp
     * @param NetworkAddress $addrRecv
     * @param NetworkAddress $addrFrom
     * @param Buffer $userAgent
     * @param int $startHeight
     * @param int $relay
     * @return Version
     */
    public function version(
        $version,
        Buffer $services,
        $timestamp,
        NetworkAddress $addrRecv,
        NetworkAddress $addrFrom,
        Buffer $userAgent,
        $startHeight,
        $relay
    ) {
        return new Version(
            $version,
            $services,
            $timestamp,
            $addrRecv,
            $addrFrom,
            $this->random->bytes(8)->getInt(),
            $userAgent,
            $startHeight,
            $relay
        );
    }

    /**
     * @return VerAck
     */
    public function verack()
    {
        return new VerAck();
    }

    /**
     * @param array $addrs
     * @return Addr
     */
    public function addr(array $addrs = array())
    {
        return new Addr($addrs);
    }

    /**
     * @param array $vectors
     * @return Inv
     */
    public function inv(array $vectors = array())
    {
        return new Inv($vectors);
    }

    /**
     * @param array $vectors
     * @return GetData
     */
    public function getdata(array $vectors = array())
    {
        return new GetData($vectors);
    }

    /**
     * @param array $vectors
     * @return NotFound
     */
    public function notfound(array $vectors = array())
    {
        return new NotFound($vectors);
    }

    public function getblocks()
    {

    }

    public function getheaders()
    {

    }

    /**
     * @param TransactionInterface $tx
     * @return Tx
     */
    public function tx(TransactionInterface $tx)
    {
        return new Tx($tx);
    }

    /**
     * @param BlockInterface $block
     * @return Block
     */
    public function block(BlockInterface $block)
    {
        return new Block($block);
    }

    /**
     * @param array $headers
     * @return Headers
     */
    public function headers(array $headers = array())
    {
        return new Headers($headers);
    }

    /**
     * @return GetAddr
     */
    public function getaddr()
    {
        return new GetAddr();
    }

    /**
     * @return MemPool
     */
    public function mempool()
    {
        return new MemPool();
    }

    /**
     * @return Ping
     * @throws \BitWasp\Bitcoin\Exceptions\RandomBytesFailure
     */
    public function ping()
    {
        return new Ping($this->random->bytes(8)->getInt());
    }

    /**
     * @param Ping $ping
     * @return Pong
     */
    public function pong(Ping $ping)
    {
        return new Pong($ping->getNonce());
    }

    /**
     * @param Buffer $message
     * @param $code
     * @param Buffer $reason
     * @param Buffer $data
     * @return Reject
     */
    public function reject(
        Buffer $message,
        $code,
        Buffer $reason,
        Buffer $data = null
    ) {
        return new Reject(
            $message,
            $code,
            $reason,
            $data
        );
    }

    /**
     * @param AlertDetail $detail
     * @param SignatureInterface $sig
     * @return Alert
     */
    public function alert(AlertDetail $detail, SignatureInterface $sig)
    {
        return new Alert(
            $detail,
            $sig
        );
    }

    /**
     * @param Parser $parser
     * @return NetworkMessage
     */
    public function parse(Parser & $parser)
    {
        return (new NetworkMessageSerializer($this->network))
            ->fromParser($parser);
    }
}
