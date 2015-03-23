<?php

namespace BitWasp\Bitcoin\Key;

use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Crypto\EcAdapter\EcAdapterInterface;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\ExtendedKeySerializer;
use BitWasp\Bitcoin\Serializer\Key\HierarchicalKey\HexExtendedKeySerializer;
use BitWasp\Bitcoin\Parser;
use BitWasp\Bitcoin\Crypto\Hash;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Exceptions\InvalidPrivateKey;

class HierarchicalKey
{
    /**
     * @var EcAdapterInterface
     */
    private $ecAdapter;

    /**
     * @var string
     */
    protected $bytes;

    /**
     * @var int
     */
    protected $depth;

    /**
     * @var string
     */
    protected $parentFingerprint;

    /**
     * @var int
     */
    protected $sequence;

    /**
     * @var int
     */
    protected $chainCode;

    /**
     * @var KeyInterface
     */
    protected $key;

    /**
     * @param EcAdapterInterface $ecAdapter
     * @param $depth
     * @param $parentFingerprint
     * @param $sequence
     * @param $chainCode
     * @param KeyInterface $key
     * @throws \Exception
     */
    public function __construct(EcAdapterInterface $ecAdapter, $depth, $parentFingerprint, $sequence, $chainCode, KeyInterface $key)
    {
        if (!$key->isCompressed()) {
            throw new \Exception('A HierarchicalKey must always be compressed');
        }

        $this->depth = $depth;
        $this->sequence = $sequence;
        $this->parentFingerprint = $parentFingerprint;
        $this->chainCode = $chainCode;
        $this->key = $key;
        $this->ecAdapter = $ecAdapter;
    }

    /**
     * @param $sequence
     * @return int|string
     */
    public function getHardenedSequence($sequence)
    {
        $math = $this->ecAdapter->getMath();
        $hardened = $math->hexDec('80000000');
        if ($math->cmp($sequence, $hardened) >= 0) {
            throw new \LogicException('Sequence is already for a hardened key');
        }

        return $math->add($hardened, $sequence);
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
        $fingerprint = $this->ecAdapter->getMath()->hexDec(substr($this->getPublicKey()->getPubKeyHash(), 0, 8));
        return $fingerprint;
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
     * @return Buffer
     * @throws \Exception
     */
    public function getBuffer()
    {
        if ($this->isPrivate()) {
            return $this->getPrivateKey()->getBuffer();
        } else {
            return $this->getPublicKey()->getBuffer();
        }
    }

    /**
     * @param NetworkInterface $network
     * @return string
     */
    public function toExtendedKey(NetworkInterface $network)
    {
        $extendedSerializer = new ExtendedKeySerializer($network, new HexExtendedKeySerializer($this->ecAdapter, $network));
        $extended = $extendedSerializer->serialize($this);
        return $extended;
    }

    /**
     * @param NetworkInterface $network
     * @return string
     */
    public function toExtendedPrivateKey(NetworkInterface $network)
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
    public function toExtendedPublicKey(NetworkInterface $network)
    {
        $clone = clone($this);
        return $clone->toPublic()->toExtendedKey($network);
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
        $math = $this->ecAdapter->getMath();
        return $math->cmp($this->getSequence(), $math->hexDec('80000000')) >= 0;
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
        $math = $this->ecAdapter->getMath();
        $chainHex = str_pad($math->decHex($this->getChainCode()), 64, '0', STR_PAD_LEFT);

        try {
            // can be easily wrapped in a loop that recurses until
            // the desired key is created, without the other stuff.
            $data = $this->getHmacSeed($sequence);
            $hash = Hash::hmac('sha512', $data->serialize(), pack("H*", $chainHex));

            list ($offset, $chainHex) = array(
                $math->hexDec(substr($hash, 0, 64)),
                substr($hash, 64, 64),
            );

            $key = $this->isPrivate()
                ? $this->ecAdapter->privateKeyAdd($this->getPrivateKey(), $offset)
                : $this->ecAdapter->publicKeyAdd($this->getPublicKey(), $offset);

        } catch (InvalidPrivateKey $e) {
            // Invalid keys should trigger recursion.. 1:1^128
            return $this->deriveChild(++$sequence);
        } catch (\Exception $e) {
            throw $e;
        }

        $key = new HierarchicalKey(
            $this->ecAdapter,
            $this->getDepth() + 1,
            $this->getChildFingerprint(),
            $sequence,
            $math->hexDec($chainHex),
            $key
        );

        return $key;
    }

    /**
     * Create a buffer containing data to be hashed hashed to yield the child offset
     *
     * @param Buffer $sequence
     * @return Buffer
     * @throws \Exception
     */
    public function getHmacSeed($sequence)
    {
        $math = $this->ecAdapter->getMath();
        $parser   = new Parser();
        $hardened = $math->cmp($sequence, $math->hexDec('80000000')) >= 0;

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
     * Decodes a BIP32 path into it's actual 32bit sequence numbers: ie, m/0/1'/2/3' -> m/0/2147483649/2/2147483651
     *
     * @param $path
     * @return string
     */
    public function decodePath($path)
    {
        $pathPieces = explode("/", $path);
        $newPath = array();

        foreach ($pathPieces as $c => $sequence) {
            $hardened = false;

            if (in_array(substr(strtolower($sequence), -1), array("h", "'")) === true) {
                $intEnd = strlen($sequence) - 1;
                $sequence = substr($sequence, 0, $intEnd);
                $hardened = true;
            }

            if ($hardened) {
                $sequence = $this->getHardenedSequence($sequence);
            }

            $newPath[] = $sequence;
        }

        $path = implode("/", $newPath);
        return $path;
    }
}
