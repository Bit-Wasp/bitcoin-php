<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 25/02/15
 * Time: 14:14
 */

namespace Afk11\Serializer\Key\HierarchicalKey;

use Afk11\Bitcoin\Exceptions\ParserOutOfRange;
use Afk11\Bitcoin\Key\PrivateKeyFactory;
use Afk11\Bitcoin\Key\PublicKeyFactory;
use Afk11\Bitcoin\NetworkInterface;
use Afk11\Bitcoin\Parser;
use Afk11\Bitcoin\Key\HierarchicalKey;

class HexExtendedKeySerializer
{
    /**
     * @var NetworkInterface
     */
    public $network;

    /**
     * @param NetworkInterface $network
     */
    public function __construct(NetworkInterface $network)
    {
        try {
            $network->getHDPrivByte();
            $network->getHDPubByte();
        } catch (\Exception $e) {
            throw new \Exception('Network not configured for HD wallets');
        }

        $this->network = $network;
    }

    /**
     * @return NetworkInterface
     */
    public function getNetwork()
    {
        return $this->network;
    }

    /**
     * @param HierarchicalKey $key
     * @return string
     */
    public function serialize(HierarchicalKey $key)
    {
        $keydata = $key->getKeyData();

        $bytes = new Parser();
        $bytes
            ->writeBytes(4, $this->network->getHDPrivByte())
            ->writeInt(1, $key->getDepth())
            ->writeBytes(4, $key->getFingerprint())
            ->writeInt(4, $key->getSequence())
            ->writeBytes(32, $key->getChainCode()->serialize('hex'))
            ->writeBytes(33, $keydata);

        $hex = $bytes
            ->getBuffer()
            ->serialize('hex');

        return $hex;
    }

    /**
     * @param NetworkInterface $network
     * @param $hex
     * @return HierarchicalKey
     * @throws ParserOutOfRange
     * @throws \Exception
     */
    public function parse($network, $hex)
    {
        if (strlen($hex) !== 156) {
            throw new \Exception('Invalid extended key');
        }

        try {
            $parser = new Parser($hex);
            list($bytes, $depth, $parentFingerprint, $sequence, $chainCode, $keyData) =
                array(
                    $parser->readBytes(4)->serialize('hex'),
                    $parser->readBytes(1)->serialize('int'),
                    $parser->readBytes(4)->serialize('hex'),
                    $parser->readBytes(4)->serialize('int'),
                    $parser->readBytes(32),
                    $parser->readBytes(33)
                );
        } catch (ParserOutOfRange $e) {
            throw new ParserOutOfRange('Failed to extract extended key from parser');
        }

        // Key data from original extended key is saved for serializing later
        if ($network->getHDPrivByte() == $bytes) {
            $keyData = substr($keyData->serialize('hex'), 2);
            $key = PrivateKeyFactory::fromHex($keyData)->setCompressed(true);
        } else {
            $key = PublicKeyFactory::fromHex($keyData);
        }

        $hd = new HierarchicalKey($depth, $parentFingerprint, $sequence, $chainCode, $key);

        return $hd;
    }
}