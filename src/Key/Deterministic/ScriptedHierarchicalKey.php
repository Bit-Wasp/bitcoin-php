<?php

namespace BitWasp\Bitcoin\Key\Deterministic;

use BitWasp\Bitcoin\Address\BaseAddressCreator;
use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Crypto\EcAdapter\Adapter\EcAdapterInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\KeyInterface;
use BitWasp\Bitcoin\Key\KeyToScript\ScriptAndSignData;
use BitWasp\Bitcoin\Key\KeyToScript\ScriptDataFactoryInterface;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Serializer\Key\ScriptedHierarchicalKey\Base58ScriptedExtendedKeySerializer;
use BitWasp\Bitcoin\Serializer\Key\ScriptedHierarchicalKey\ExtendedKeyWithScriptSerializer;
use BitWasp\Buffertools\BufferInterface;

class ScriptedHierarchicalKey extends HierarchicalKey
{
    /**
     * @var ScriptDataFactoryInterface
     */
    private $scriptFactory;

    /**
     * @var ScriptAndSignData
     */
    private $scriptAndSignData;

    /**
     * ScriptedHierarchicalKey constructor.
     * @param EcAdapterInterface $ecAdapter
     * @param ScriptDataFactoryInterface $scriptFactory
     * @param $depth
     * @param $parentFingerprint
     * @param $sequence
     * @param BufferInterface $chainCode
     * @param KeyInterface $key
     * @throws \Exception
     */
    public function __construct(
        EcAdapterInterface $ecAdapter,
        ScriptDataFactoryInterface $scriptFactory,
        $depth,
        $parentFingerprint,
        $sequence,
        BufferInterface $chainCode,
        KeyInterface $key
    ) {
        $this->scriptFactory = $scriptFactory;
        parent::__construct($ecAdapter, $depth, $parentFingerprint, $sequence, $chainCode, $key);
    }

    /**
     * @param $nextDepth
     * @param $sequence
     * @param BufferInterface $chainCode
     * @param KeyInterface $key
     * @return ScriptedHierarchicalKey
     * @throws \Exception
     */
    protected function childKey($nextDepth, $sequence, BufferInterface $chainCode, KeyInterface $key)
    {
        return new ScriptedHierarchicalKey(
            $this->ecAdapter,
            $this->scriptFactory,
            $nextDepth,
            $this->getChildFingerprint(),
            $sequence,
            $chainCode,
            $key
        );
    }

    /**
     * Decodes a BIP32 path into actual 32bit sequence numbers and derives the child key
     *
     * @param string $path
     * @return ScriptedHierarchicalKey
     * @throws \Exception
     */
    public function derivePath($path)
    {
        $sequences = new HierarchicalKeySequence();
        return $this->deriveFromList($sequences->decodePath($path));
    }

    /**
     * @return ScriptDataFactoryInterface
     */
    public function getScriptDataFactory()
    {
        return $this->scriptFactory;
    }

    /**
     * @return \BitWasp\Bitcoin\Key\KeyToScript\ScriptAndSignData
     */
    public function getScriptAndSignData()
    {
        if (null === $this->scriptAndSignData) {
            $this->scriptAndSignData = $this->scriptFactory->convertKey($this->getPublicKey());
        }

        return $this->scriptAndSignData;
    }

    /**
     * @param BaseAddressCreator $addressCreator
     * @return \BitWasp\Bitcoin\Address\Address
     */
    public function getAddress(BaseAddressCreator $addressCreator)
    {
        $scriptAndSignData = $this->getScriptAndSignData();
        return $addressCreator->fromOutputScript($scriptAndSignData->getScriptPubKey());
    }

    /**
     * Serializes the instance according to whether it wraps a private or public key.
     * @param NetworkInterface $network
     * @return string
     */
    public function toExtendedKey(NetworkInterface $network = null)
    {
        $network = $network ?: Bitcoin::getNetwork();

        $extendedSerializer = new Base58ScriptedExtendedKeySerializer(new ExtendedKeyWithScriptSerializer($this->ecAdapter));
        $extended = $extendedSerializer->serialize($network, $this);
        return $extended;
    }
}
