<?php

namespace BitWasp\Bitcoin\Serializer\Key\HierarchicalKey;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Key\Deterministic\HierarchicalKey;
use BitWasp\Bitcoin\Key\PrivateKeyFactory;
use BitWasp\Bitcoin\Key\PublicKeyFactory;
use BitWasp\Bitcoin\Network\Network;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Buffertools\Buffer;

class BasicKeyAdapter
{
    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * BasicBip32Key constructor.
     * @param EcAdapterInterface|null $ecAdapter
     */
    public function __construct(EcAdapterInterface $ecAdapter = null)
    {
        if (null === $ecAdapter) {
            $ecAdapter = Bitcoin::getEcAdapter();
        }

        $this->ecAdapter = $ecAdapter;
    }

    /**
     * @param Network $network
     * @param RawKeyParams $params
     * @return HierarchicalKey
     * @throws \BitWasp\Bitcoin\Exceptions\MissingBip32Prefix
     * @throws \Exception
     */
    public function getKey(Network $network, RawKeyParams $params)
    {
        if ($params->getPrefix() === $network->getHDPubByte()) {
            $key = PublicKeyFactory::fromHex($params->getKeyData(), $this->ecAdapter);
        } else if ($params->getPrefix() === $network->getHDPrivByte()) {
            $key = PrivateKeyFactory::fromHex($params->getKeyData()->slice(1), true, $this->ecAdapter);
        } else {
            throw new \InvalidArgumentException('HD key magic bytes do not match network magic bytes');
        }

        return new HierarchicalKey(
            $this->ecAdapter,
            $params->getDepth(),
            $params->getFingerprint(),
            $params->getSequence(),
            $params->getChainCode(),
            $key
        );
    }

    /**
     * @param NetworkInterface $network
     * @param HierarchicalKey $key
     * @return RawKeyParams
     * @throws \Exception
     */
    public function getParams(NetworkInterface $network, HierarchicalKey $key)
    {
        if ($key->isPrivate()) {
            $prefix = $network->getHDPrivByte();
            $keyData = new Buffer("\x00" . $key->getPrivateKey()->getBinary());
        } else {
            $prefix = $network->getHDPubByte();
            $keyData = $key->getPublicKey()->getBuffer();
        }

        return new RawKeyParams(
            $prefix,
            $key->getDepth(),
            $key->getFingerprint(),
            $key->getSequence(),
            $key->getChainCode(),
            $keyData
        );
    }
}
