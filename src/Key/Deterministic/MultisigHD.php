<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Key\Deterministic;

use BitWasp\Bitcoin\Address\Address;
use BitWasp\Bitcoin\Address\BaseAddressCreator;
use BitWasp\Bitcoin\Exceptions\InvalidDerivationException;
use BitWasp\Bitcoin\Key\KeyToScript\ScriptAndSignData;
use BitWasp\Bitcoin\Key\KeyToScript\ScriptDataFactory;
use BitWasp\Bitcoin\Script\ScriptType;

/**
 * Implements a multisignature HD node, which like HierarchicalKey
 * adapts the type of script (p2sh? p2wsh? nested?) with the ScriptDataFactory.
 * Older versions used to contain the absolute BIP32 path, which has been removed.

 * Older versions also used to sort the keys returned by getKeys, but now they are
 * returned in signer first order.
 *
 * The ScriptDataFactory must be configured for the desired m-on-n, and sorting parameters
 * as this is purely a concern for script creation.
 */
class MultisigHD
{
    /**
     * @var HierarchicalKey[]
     */
    private $keys;

    /**
     * @var ScriptDataFactory
     */
    private $scriptFactory;

    /**
     * @var ScriptAndSignData
     */
    private $scriptAndSignData;

    /**
     * MultisigHD constructor.
     * @param ScriptDataFactory $scriptDataFactory
     * @param HierarchicalKey ...$keys
     */
    public function __construct(ScriptDataFactory $scriptDataFactory, HierarchicalKey... $keys)
    {
        if (count($keys) < 1) {
            throw new \RuntimeException('Must have at least one HierarchicalKey for Multisig HD Script');
        }

        if (substr($scriptDataFactory->getScriptType(), 0 - strlen(ScriptType::MULTISIG)) !== ScriptType::MULTISIG) {
            throw new \RuntimeException("multi-signature script factory required: {$scriptDataFactory->getScriptType()} given");
        }

        $this->keys = $keys;
        $this->scriptFactory = $scriptDataFactory;

        // Immediately produce the script to check our inputs are correct
        $publicKeys = [];
        foreach ($this->keys as $key) {
            $publicKeys[] = $key->getPublicKey();
        }
        $this->scriptAndSignData = $this->scriptFactory->convertKey(...$publicKeys);
    }

    /**
     * Return the composite keys of this MultisigHD wallet entry.
     * Note: unlike previous versions, the cosigner indexes are preserved here.
     * To obtain the sorted keys, extract them from the script.
     *
     * @return HierarchicalKey[]
     */
    public function getKeys(): array
    {
        return $this->keys;
    }

    /**
     * @return ScriptDataFactory
     */
    public function getScriptDataFactory(): ScriptDataFactory
    {
        return $this->scriptFactory;
    }

    /**
     * @return ScriptAndSignData
     */
    public function getScriptAndSignData(): ScriptAndSignData
    {
        return $this->scriptAndSignData;
    }

    /**
     * @param BaseAddressCreator $addressCreator
     * @return Address
     */
    public function getAddress(BaseAddressCreator $addressCreator): Address
    {
        return $this->getScriptAndSignData()->getAddress($addressCreator);
    }

    /**
     * @param int $sequence
     * @return MultisigHD
     * @throws InvalidDerivationException
     */
    public function deriveChild(int $sequence): MultisigHD
    {
        $keys = [];
        foreach ($this->keys as $cosignerIdx => $key) {
            try {
                $keys[] = $key->deriveChild($sequence);
            } catch (InvalidDerivationException $e) {
                throw new InvalidDerivationException("Cosigner {$cosignerIdx} key derivation failed", 0, $e);
            }
        }

        return new self($this->scriptFactory, ...$keys);
    }

    /**
     * Decodes a BIP32 path into actual 32bit sequence numbers and derives the child key
     *
     * @param string $path
     * @return MultisigHD
     * @throws \Exception
     */
    public function derivePath(string $path): MultisigHD
    {
        $sequences = new HierarchicalKeySequence();
        $parts = $sequences->decodeRelative($path);
        $numParts = count($parts);

        $key = $this;
        for ($i = 0; $i < $numParts; $i++) {
            try {
                $key = $key->deriveChild((int) $parts[$i]);
            } catch (InvalidDerivationException $e) {
                if ($i === $numParts - 1) {
                    throw new InvalidDerivationException($e->getMessage());
                } else {
                    throw new InvalidDerivationException("Invalid derivation for non-terminal index: cannot use this path!");
                }
            }
        }

        return $key;
    }
}
