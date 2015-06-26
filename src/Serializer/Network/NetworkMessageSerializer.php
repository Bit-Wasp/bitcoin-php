<?php

namespace BitWasp\Bitcoin\Serializer\Network;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Network\Messages\Block;
use BitWasp\Bitcoin\Network\Messages\FilterClear;
use BitWasp\Bitcoin\Network\Messages\GetAddr;
use BitWasp\Bitcoin\Network\Messages\MemPool;
use BitWasp\Bitcoin\Network\Messages\Tx;
use BitWasp\Bitcoin\Network\Messages\VerAck;
use BitWasp\Bitcoin\Network\NetworkMessage;
use BitWasp\Bitcoin\Serializer\Block\HexBlockHeaderSerializer;
use BitWasp\Bitcoin\Serializer\Block\HexBlockSerializer;
use BitWasp\Bitcoin\Serializer\Network\Message\AddrSerializer;
use BitWasp\Bitcoin\Serializer\Network\Message\AlertSerializer;
use BitWasp\Bitcoin\Serializer\Network\Message\FilterAddSerializer;
use BitWasp\Bitcoin\Serializer\Network\Message\FilterLoadSerializer;
use BitWasp\Bitcoin\Serializer\Network\Message\GetBlocksSerializer;
use BitWasp\Bitcoin\Serializer\Network\Message\GetDataSerializer;
use BitWasp\Bitcoin\Serializer\Network\Message\GetHeadersSerializer;
use BitWasp\Bitcoin\Serializer\Network\Message\HeadersSerializer;
use BitWasp\Bitcoin\Serializer\Network\Message\InvSerializer;
use BitWasp\Bitcoin\Serializer\Network\Message\MerkleBlockSerializer;
use BitWasp\Bitcoin\Serializer\Network\Message\NotFoundSerializer;
use BitWasp\Bitcoin\Serializer\Network\Message\PingSerializer;
use BitWasp\Bitcoin\Serializer\Network\Message\PongSerializer;
use BitWasp\Bitcoin\Serializer\Network\Message\RejectSerializer;
use BitWasp\Bitcoin\Serializer\Network\Message\VersionSerializer;
use BitWasp\Bitcoin\Serializer\Network\Structure\AlertDetailSerializer;
use BitWasp\Bitcoin\Serializer\Network\Structure\FilteredBlockSerializer;
use BitWasp\Bitcoin\Serializer\Network\Structure\InventoryVectorSerializer;
use BitWasp\Bitcoin\Serializer\Network\Structure\NetworkAddressSerializer;
use BitWasp\Bitcoin\Serializer\Network\Structure\NetworkAddressTimestampSerializer;
use BitWasp\Bitcoin\Serializer\Transaction\TransactionSerializer;
use BitWasp\Buffertools\Buffertools;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Buffertools\TemplateFactory;

class NetworkMessageSerializer
{
    /**
     * @var NetworkInterface
     */
    private $network;

    /**
     * @param NetworkInterface $network
     */
    public function __construct(NetworkInterface $network)
    {
        $this->network = $network;
    }

    /**
     * @return \BitWasp\Buffertools\Template
     */
    private function getHeaderTemplate()
    {
        return (new TemplateFactory())
            ->bytestringle(4)
            ->bytestring(12)
            ->uint32le()
            ->bytestring(4)
            ->getTemplate();
    }

    /**
     * @param Parser $parser
     * @return NetworkMessage
     * @throws \BitWasp\Buffertools\Exceptions\ParserOutOfRange
     * @throws \Exception
     */
    public function fromParser(Parser & $parser)
    {
        $math = Bitcoin::getMath();

        $parsed = $this->getHeaderTemplate()->parse($parser);

        /** @var Buffer $netBytes */
        $netBytes = $parsed[0];
        /** @var Buffer $command */
        $command = $parsed[1];
        /** @var int|string $payloadSize */
        $payloadSize = $parsed[2];
        /** @var Buffer $checksum */
        $checksum = $parsed[3];

        if ($netBytes->getHex() !== $this->network->getNetMagicBytes()) {
            throw new \RuntimeException('Invalid magic bytes for network');
        }

        $buffer = $payloadSize > 0
            ? $parser->readBytes($payloadSize)
            : new Buffer();

        // Compare payload checksum against header value
        if (Hash::sha256d($buffer)->slice(0, 4)->getBinary() !== $checksum->getBinary()) {
            throw new \RuntimeException('Invalid packet checksum');
        }

        $cmd = trim($command->getBinary());
        switch ($cmd) {
            case 'version':
                $serializer = new VersionSerializer(new NetworkAddressSerializer());
                $payload = $serializer->parse($buffer);
                break;
            case 'verack':
                $payload = new VerAck();
                break;
            case 'addr':
                $serializer = new AddrSerializer(new NetworkAddressTimestampSerializer());
                $payload = $serializer->parse($buffer);
                break;
            case 'inv':
                $serializer = new InvSerializer(new InventoryVectorSerializer());
                $payload = $serializer->parse($buffer);
                break;
            case 'getdata':
                $serializer = new GetDataSerializer(new InventoryVectorSerializer());
                $payload = $serializer->parse($buffer);
                break;
            case 'notfound':
                $serializer = new NotFoundSerializer(new InventoryVectorSerializer());
                $payload = $serializer->parse($buffer);
                break;
            case 'getblocks':
                $serializer = new GetBlocksSerializer();
                $payload = $serializer->parse($buffer);
                break;
            case 'getheaders':
                $serializer = new GetHeadersSerializer();
                $payload = $serializer->parse($buffer);
                break;
            case 'tx':
                $serializer = new TransactionSerializer();
                $payload = new Tx($serializer->parse($buffer));
                break;
            case 'block':
                $serializer = new HexBlockSerializer($math, new HexBlockHeaderSerializer(), new TransactionSerializer());
                $payload = new Block($serializer->parse($buffer));
                break;
            case 'headers':
                $serializer = new HeadersSerializer(new HexBlockHeaderSerializer());
                $payload = $serializer->parse($buffer);
                break;
            case 'getaddr':
                $payload = new GetAddr();
                break;
            case 'mempool':
                $payload = new MemPool();
                break;
            case 'filterload':
                $serializer = new FilterLoadSerializer(new BloomFilterSerializer());
                $payload = $serializer->parse($buffer);
                break;
            case 'filteradd':
                $serializer = new FilterAddSerializer();
                $payload = $serializer->parse($buffer);
                break;
            case 'filterclear':
                $payload = new FilterClear();
                break;
            case 'merkleblock':
                $serializer = new MerkleBlockSerializer(new FilteredBlockSerializer(new HexBlockHeaderSerializer(), new PartialMerkleTreeSerializer()));
                $payload = $serializer->parse($buffer);
                break;
            case 'ping':
                $serializer = new PingSerializer();
                $payload = $serializer->parse($buffer);
                break;
            case 'pong':
                $serializer = new PongSerializer();
                $payload = $serializer->parse($buffer);
                break;
            case 'reject':
                $serializer = new RejectSerializer();
                $payload = $serializer->parse($buffer);
                break;
            case 'alert':
                $serializer = new AlertSerializer(new AlertDetailSerializer());
                $payload = $serializer->parse($buffer);
                break;
            default:
                throw new \RuntimeException('Invalid command');
        }

        return new NetworkMessage(
            $this->network,
            $payload
        );
    }

    /**
     * @param NetworkMessage $object
     * @return Buffer
     */
    public function serialize(NetworkMessage $object)
    {
        $payload = $object->getPayload()->getBuffer();
        $command = str_pad(unpack("H*", $object->getCommand())[1], 24, '0', STR_PAD_RIGHT);
        $header = $this->getHeaderTemplate()->write([
            Buffer::hex($this->network->getNetMagicBytes()),
            Buffer::hex($command),
            $payload->getSize(),
            $object->getChecksum()
        ]);

        return Buffertools::concat($header, $payload);
    }

    /**
     * @param $data
     * @return NetworkMessage
     * @throws \Exception
     */
    public function parse($data)
    {
        return $this->fromParser(new Parser($data));
    }
}
