<?php

namespace BitWasp\Bitcoin\Key\Deterministic;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Key\KeyInterface;
use BitWasp\Bitcoin\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Key\PublicKey;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\ExtendedKeySerializer;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\HexExtendedKeySerializer;
use BitWasp\Buffertools\Parser;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Network\NetworkInterface;

class HierarchicalKey
{
    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @var int|string
     */
    private $depth;

    /**
     * @var int|string
     */
    private $parentFingerprint;

    /**
     * @var int|string
     */
    private $sequence;

    /**
     * @var int|string
     */
    private $chainCode;

    /**
     * @var KeyInterface
     */
    private $key;

    /**
     * @param EcAdapterInterface $ecAdapter
     * @param integer|string $depth
     * @param integer|string $parentFingerprint
     * @param integer|string $sequence
     * @param integer|string $chainCode
     * @param KeyInterface $key
     * @throws \Exception
     */
    public function __construct(EcAdapterInterface $ecAdapter, $depth, $parentFingerprint, $sequence, $chainCode, KeyInterface $key)
    {
        if (!$key->isCompressed()) {
            throw new \Exception('A HierarchicalKey must always be compressed');
        }

        $this->ecAdapter = $ecAdapter;
        $this->depth = $depth;
        $this->sequence = $sequence;
        $this->parentFingerprint = $parentFingerprint;
        $this->chainCode = $chainCode;
        $this->key = $key;
    }

    /**
     * Return the depth of this key. This is limited to 256 sequential derivations.
     *
     * @return int
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * Get the sequence number for this address. Hardened keys are
     * created with sequence > 0x80000000. a sequence number lower
     * than this can be derived with the public key.
     *
     * @return int
     */
    public function getSequence()
    {
        return $this->sequence;
    }

    /**
     * Get the fingerprint of the parent key. For master keys, this is 00000000.
     *
     * @return string
     */
    public function getFingerprint()
    {
        if ($this->getDepth() == 0) {
            return 0;
        }

        return $this->parentFingerprint;
    }

    /**
     * Return the fingerprint to be used for child keys.
     * @return string
     */
    public function getChildFingerprint()
    {
        return $this->getPublicKey()->getPubKeyHash()->slice(0, 4)->getInt();
    }

    /**
     * Return the chain code - a deterministic 'salt' for HMAC-SHA512
     * in child derivations
     *
     * @return integer
     */
    public function getChainCode()
    {
        return $this->chainCode;
    }

    /**
     * @return PrivateKeyInterface
     */
    public function getPrivateKey()
    {
        if ($this->key->isPrivate()) {
            return $this->key;
        }

        throw new \RuntimeException('Unable to get private key, not known');
    }

    /**
     * Get the public key the private key or public key.
     *
     * @return PublicKey
     */
    public function getPublicKey()
    {
        if ($this->isPrivate()) {
            return $this->getPrivateKey()->getPublicKey();
        } else {
            return $this->key;
        }
    }

    /**
     * @return HierarchicalKey
     */
    public function toPublic()
    {
        if ($this->isPrivate()) {
            $this->key = $this->getPrivateKey()->getPublicKey();
        }

        return $this;
    }

    /**
     * Return whether this is a private key
     *
     * @return bool
     */
    public function isPrivate()
    {
        return $this->key->isPrivate();
    }

    /**
     * Return whether the key is hardened
     *
     * @return bool
     */
    public function isHardened()
    {
        // (sequence >> 31) == 1 ?
        return $this->ecAdapter->getMath()->getBinaryMath()->isNegative($this->sequence, 32);
    }

    /**
     * Create a buffer containing data to be hashed hashed to yield the child offset
     *
     * @param integer|string $sequence
     * @return Buffer
     * @throws \Exception
     */
    public function getHmacSeed($sequence)
    {
        $parser = new Parser();
        $hardened = $this->ecAdapter->getMath()->getBinaryMath()->isNegative($sequence, 32);

        if ($hardened) {
            if ($this->isPrivate() === false) {
                throw new \Exception("Can't derive a hardened key without the private key");
            }

            $parser
                ->writeBytes(1, '00')
                ->writeBytes(32, $this->getPrivateKey()->getBuffer());
        } else {
            $parser->writeBytes(33, $this->getPublicKey()->getBuffer());
        }

        return $parser
            ->writeInt(4, $sequence)
            ->getBuffer();
    }

    /**
     * Derive a child key
     *
     * @param $sequence
     * @return HierarchicalKey
     * @throws \Exception
     */
    public function deriveChild($sequence)
    {
        $chain = Buffer::hex($this->ecAdapter->getMath()->decHex($this->getChainCode()), 32);

        $hash = Hash::hmac('sha512', $this->getHmacSeed($sequence), $chain);
        $offset = $hash->slice(0, 32);
        $chain = $hash->slice(32);

        if (false === $this->ecAdapter->validatePrivateKey($offset)) {
            return $this->deriveChild($sequence + 1);
        }

        return new HierarchicalKey(
            $this->ecAdapter,
            $this->getDepth() + 1,
            $this->getChildFingerprint(),
            $sequence,
            $chain->getInt(),
            $this->isPrivate()
            ? $this->ecAdapter->privateKeyAdd($this->getPrivateKey(), $offset->getInt())
            : $this->ecAdapter->publicKeyAdd($this->getPublicKey(), $offset->getInt())
        );
    }

    /**
     * Decodes a BIP32 path into actual 32bit sequence numbers and derives the child key
     *
     * @param string $path
     * @return HierarchicalKey
     * @throws \Exception
     */
    public function derivePath($path)
    {
        $path = $this->decodePath($path);

        $key = $this;
        foreach (explode("/", $path) as $chunk) {
            $key = $key->deriveChild($chunk);
        }

        return $key;
    }

    /**
     * Decodes a BIP32 path into it's actual 32bit sequence numbers: ie, m/0/1'/2/3' -> m/0/2147483649/2/2147483651
     *
     * @param string $path
     * @return string
     */
    public function decodePath($path)
    {
        $pathPieces = explode("/", $path);
        if (strlen($path) == 0 || count($pathPieces) == 0) {
            throw new \InvalidArgumentException('Invalid path passed to decodePath()');
        }

        $newPath = array();

        $helper = new HierarchicalKeySequence($this->ecAdapter->getMath());
        foreach ($pathPieces as $c => $sequence) {
            $newPath[] = $helper->fromNode($sequence);
        }

        $path = implode("/", $newPath);
        return $path;
    }

    /**
     *
     * @param NetworkInterface $network
     * @return string
     */
    public function toExtendedKey(NetworkInterface $network = null)
    {
        $network = $network ?: Bitcoin::getNetwork();

        $extendedSerializer = new ExtendedKeySerializer(new HexExtendedKeySerializer($this->ecAdapter, $network));
        $extended = $extendedSerializer->serialize($this);
        return $extended;
    }

    /**
     * @param NetworkInterface $network
     * @return string
     */
    public function toExtendedPrivateKey(NetworkInterface $network = null)
    {
        if (!$this->isPrivate()) {
            throw new \LogicException('Cannot create extended private key from public');
        }

        return $this->toExtendedKey($network);
    }

    /**
     * @param NetworkInterface $network
     * @return string
     */
    public function toExtendedPublicKey(NetworkInterface $network = null)
    {
        $clone = clone($this);
        return $clone->toPublic()->toExtendedKey($network);
    }
}
