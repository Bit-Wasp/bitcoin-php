<?php

namespace BitWasp\Bitcoin\Network;

use BitWasp\Bitcoin\Block\BlockInterface;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Network\Messages\Addr;
use BitWasp\Bitcoin\Network\Messages\Alert;
use BitWasp\Bitcoin\Network\Messages\Block;
use BitWasp\Bitcoin\Network\Messages\FilterAdd;
use BitWasp\Bitcoin\Network\Messages\FilterClear;
use BitWasp\Bitcoin\Network\Messages\FilterLoad;
use BitWasp\Bitcoin\Network\Messages\GetAddr;
use BitWasp\Bitcoin\Network\Messages\GetBlocks;
use BitWasp\Bitcoin\Network\Messages\GetData;
use BitWasp\Bitcoin\Network\Messages\GetHeaders;
use BitWasp\Bitcoin\Network\Messages\Headers;
use BitWasp\Bitcoin\Network\Messages\Inv;
use BitWasp\Bitcoin\Network\Messages\MemPool;
use BitWasp\Bitcoin\Network\Messages\MerkleBlock;
use BitWasp\Bitcoin\Network\Messages\NotFound;
use BitWasp\Bitcoin\Network\Messages\Ping;
use BitWasp\Bitcoin\Network\Messages\Pong;
use BitWasp\Bitcoin\Network\Messages\Reject;
use BitWasp\Bitcoin\Network\Messages\Tx;
use BitWasp\Bitcoin\Network\Messages\VerAck;
use BitWasp\Bitcoin\Network\Messages\Version;
use BitWasp\Bitcoin\Network\Structure\AlertDetail;
use BitWasp\Bitcoin\Network\Structure\FilteredBlock;
use BitWasp\Bitcoin\Network\Structure\NetworkAddress;
use BitWasp\Bitcoin\Network\Structure\InventoryVector;
use BitWasp\Bitcoin\Network\Structure\NetworkAddressInterface;
use BitWasp\Bitcoin\Serializer\Network\NetworkMessageSerializer;
use BitWasp\Bitcoin\Signature\SignatureInterface;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\Parser;

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
     * @param NetworkAddressInterface $addrRecv
     * @param NetworkAddressInterface $addrFrom
     * @param Buffer $userAgent
     * @param int $startHeight
     * @param bool $relay
     * @return Version
     */
    public function version(
        $version,
        Buffer $services,
        $timestamp,
        NetworkAddressInterface $addrRecv,
        NetworkAddressInterface $addrFrom,
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
     * @param InventoryVector[] $addrs
     * @return Addr
     */
    public function addr(array $addrs)
    {
        return new Addr($addrs);
    }

    /**
     * @param InventoryVector[] $vectors
     * @return Inv
     */
    public function inv(array $vectors)
    {
        return new Inv($vectors);
    }

    /**
     * @param InventoryVector[] $vectors
     * @return GetData
     */
    public function getdata(array $vectors)
    {
        return new GetData($vectors);
    }

    /**
     * @param InventoryVector[] $vectors
     * @return NotFound
     */
    public function notfound(array $vectors)
    {
        return new NotFound($vectors);
    }

    /**
     * @param int $version
     * @param Buffer[] $hashes
     * @param Buffer|null $hashStop
     * @return GetBlocks
     */
    public function getblocks($version, array $hashes, Buffer $hashStop = null)
    {
        return new GetBlocks($version, $hashes, $hashStop);
    }

    /**
     * @param int $version
     * @param Buffer[] $hashes
     * @param Buffer|null $hashStop
     * @return GetHeaders
     */
    public function getheaders($version, array $hashes, Buffer $hashStop = null)
    {
        return new GetHeaders($version, $hashes, $hashStop);
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
     * @param \BitWasp\Bitcoin\Block\BlockHeaderInterface[] $headers
     * @return Headers
     */
    public function headers(array $headers)
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
     * @param Buffer $data
     * @return FilterAdd
     */
    public function filteradd(Buffer $data)
    {
        return new FilterAdd($data);
    }

    /**
     * @param BloomFilter $filter
     * @return FilterLoad
     */
    public function filterload(BloomFilter $filter)
    {
        return new FilterLoad($filter);
    }

    /**
     * @return FilterClear
     */
    public function filterclear()
    {
        return new FilterClear();
    }

    /**
     * @param FilteredBlock $filtered
     * @return MerkleBlock
     */
    public function merkleblock(FilteredBlock $filtered)
    {
        return new MerkleBlock($filtered);
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
     * @param int $code
     * @param Buffer $reason
     * @param Buffer|null $data
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
            $data ?: new Buffer()
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
        return (new NetworkMessageSerializer($this->network))->fromParser($parser);
    }
}
