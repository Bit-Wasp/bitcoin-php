<?php

namespace BitWasp\Bitcoin\Key\Deterministic;

use BitWasp\Bitcoin\Address\BaseAddressCreator;
use BitWasp\Bitcoin\Key\KeyToScript\ScriptAndSignData;
use BitWasp\Bitcoin\Key\KeyToScript\ScriptDataFactoryInterface;

class HierarchicalKeyScriptDecorator
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
     * @var HierarchicalKey
     */
    private $hdKey;

    /**
     * HierarchicalKeyScriptDecorator constructor.
     * @param ScriptDataFactoryInterface $scriptFactory
     * @param HierarchicalKey $hdKey
     */
    public function __construct(ScriptDataFactoryInterface $scriptFactory, HierarchicalKey $hdKey)
    {
        $this->scriptFactory = $scriptFactory;
        $this->hdKey = $hdKey;
    }

    /**
     * @return HierarchicalKey
     */
    public function getHdKey()
    {
        return $this->hdKey;
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
            $this->scriptAndSignData = $this->scriptFactory->convertKey($this->hdKey->getPublicKey());
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
     * @return HierarchicalKeyScriptDecorator
     */
    public function withoutPrivateKey()
    {
        return new self($this->scriptFactory, $this->hdKey->withoutPrivateKey());
    }

    /**
     * Derive a child key
     *
     * @param int $sequence
     * @return HierarchicalKeyScriptDecorator
     * @throws \Exception
     */
    public function deriveChild($sequence)
    {
        return new self($this->scriptFactory, $this->hdKey->deriveChild($sequence));
    }

    /**
     * @param array|\stdClass|\Traversable $list
     * @return HierarchicalKeyScriptDecorator
     */
    public function deriveFromList(array $list)
    {
        return new self($this->scriptFactory, $this->hdKey->deriveFromList($list));
    }

    /**
     * Decodes a BIP32 path into actual 32bit sequence numbers and derives the child key
     *
     * @param string $path
     * @return HierarchicalKeyScriptDecorator
     * @throws \Exception
     */
    public function derivePath($path)
    {
        return new self($this->scriptFactory, $this->hdKey->derivePath($path));
    }
}
