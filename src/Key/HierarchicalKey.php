<?php

namespace Afk11\Bitcoin\Key;

use \Afk11\Bitcoin\Bitcoin;
use \Afk11\Bitcoin\Exceptions\ParserOutOfRange;
use \Afk11\Bitcoin\Buffer;
use Afk11\Bitcoin\Serializer\Key\HierarchicalKey\ExtendedKeySerializer;
use Afk11\Bitcoin\Serializer\Key\HierarchicalKey\HexExtendedKeySerializer;
use \Afk11\Bitcoin\Math\Math;
use \Afk11\Bitcoin\Parser;
use \Afk11\Bitcoin\Crypto\Hash;
use \Afk11\Bitcoin\NetworkInterface;
use \Afk11\Bitcoin\Exceptions\InvalidPrivateKey;
use Mdanter\Ecc\GeneratorPoint;
use Mdanter\Ecc\MathAdapterInterface;

class HierarchicalKey implements PrivateKeyInterface, PublicKeyInterface
{
    /**
     * @var PrivateKey
     */
    protected $privateKey;

    /**
     * @var PublicKey
     */
    protected $publicKey;

    /**
     * @var \Mdanter\Ecc\GeneratorPoint
     */
    protected $generator;

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
     * @var Buffer
     */
    protected $chainCode;

    /**
     * @var Buffer
     */
    protected $keyData;

    /**
     * @var NetworkInterface
     */
    protected $network;

    /**
     * @var MathAdapterInterface
     */
    protected $math;

    /**
     * @var bool
     */
    protected $private = false;

    /**
     * @param $bytes
     * @param NetworkInterface $network
     * @throws ParserOutOfRange
     * @throws \Exception
     */
    //public function __construct($bytes, NetworkInterface $network)
    public function __construct(Math $math, GeneratorPoint $generator, $depth, $parentFingerprint, $sequence, Buffer $chainCode, KeyInterface $key)
    {
        $this->math = $math;
        $this->generator = $generator;
        $this->depth = $depth;
        $this->parentFingerprint = $parentFingerprint;
        $this->sequence = $sequence;
        $this->chainCode = $chainCode;

        if ($key->isPrivate()) {
            echo "create priv";
            $this->privateKey = $key;
            $this->private = true;
            $keyData = '00' . $this->getPrivateKey()->toHex();
        } else {
            echo "create pub";
            $this->publicKey = $key;
            $keyData = $this->getPublicKey()->toHex();
        }

        $this->keyData = Buffer::hex($keyData);
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
            return '00000000';
        }

        return $this->parentFingerprint;
    }

    /**
     * Return the fingerprint to be used for child keys.
     * @return string
     */
    public function getChildFingerprint()
    {
        $hash        = $this->getPublicKey()->getPubKeyHash();
        $fingerprint = substr($hash, 0, 8);
        return $fingerprint;
    }

    /**
     * Return the chain code - a deterministic 'salt' for HMAC-SHA512
     * in child derivations
     *
     * @return Buffer
     */
    public function getChainCode()
    {
        return $this->chainCode;
    }

    /**
     * Return the network bytes for the current extended key.
     *
     * @return string
     */
    public function getBytes()
    {
        return $this->bytes;
    }

    /**
     * Return the 'key data' portion of the current extended key. This is 33 bytes,
     * and private keys are prefixed with 1 null byte.
     *
     * @return string
     */
    public function getKeyData()
    {
        return $this->keyData;
    }

    /**
     * Get the generator point for this curve
     *
     * @return \Mdanter\Ecc\GeneratorPoint
     */
    public function getGenerator()
    {
        return $this->generator;
    }

    /**
     * Return the current private key
     *
     * @return PrivateKey
     * @throws \Exception
     */
    public function getPrivateKey()
    {
        if (!$this->isPrivate()) {
            throw new \Exception('This is not a private key');
        }

        return $this->privateKey;
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function getSecretMultiplier()
    {
        return $this->getPrivateKey()->getSecretMultiplier();
    }

    /**
     * Get the public key the private key or public key.
     *
     * @return PublicKey
     */
    public function getPublicKey()
    {
        try {
            $public = $this->getPrivateKey()->getPublicKey();
        } catch (\Exception $e) {
            $public = $this->publicKey;
        }

        return $public;
    }

    /**
     * @return HierarchicalKey
     */
    public function derivePublic()
    {
        if ($this->isPrivate()) {
            $this->private = false;
            $this->privateKey = null;
        }

        return $this;
    }

    /**
     * @return \Mdanter\Ecc\PointInterface
     */
    public function getPoint()
    {
        return $this->getPublicKey()->getPoint();
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function toHex()
    {
        if ($this->isPrivate()) {
            return $this->getPrivateKey()->toHex();
        } else {
            return $this->getPublicKey()->toHex();
        }
    }

    /**
     * @inheritdoc
     */
    public function getPubKeyHash()
    {
        return $this->getPublicKey()->getPubKeyHash();
    }

    /**
     * @param NetworkInterface $network
     * @return string
     * @throws \Exception
     */
    public function toWif(NetworkInterface $network)
    {
        return $this->getPrivateKey()->toWif($network);
    }

    /**
     * @param NetworkInterface $network
     * @return string
     */
    public function toExtendedKey(NetworkInterface $network)
    {
        $extendedSerializer = new ExtendedKeySerializer($network, new HexExtendedKeySerializer($this->math, $this->generator, $network));
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
        return $clone->derivePublic()->toExtendedKey($network);
    }

    /**
     * Return whether the wif/address are compressed. For HD wallets
     * this is always true
     *
     * @return bool
     */
    public function isCompressed()
    {
        return true;
    }

    /**
     * Return whether this is a private key
     *
     * @return bool
     */
    public function isPrivate()
    {
        return $this->private == true;
    }

    /**
     * Return whether the key is hardened
     *
     * @return bool
     */
    public function isHardened()
    {
        return $this->math->cmp($this->getSequence(), $this->math->hexDec('80000000')) >= 0;
    }








    /**
     * Serialize a key into 'hex', or a byte string by default
     *
     * @param null $type
     * @return string
     */
    public function serialize($type = null)
    {
        $bytes = new Parser();
        $bytes = $bytes
            ->writeBytes(4, $this->getBytes())
            ->writeInt(1, $this->getDepth())
            ->writeBytes(4, $this->getFingerprint())
            ->writeInt(4, $this->getSequence())
            ->writeBytes(32, $this->getChainCode())
            ->writeBytes(33, $this->getKeyData())
            ->getBuffer()
            ->serialize($type);

        return $bytes;
    }

    /**
     * Derive a child key
     *
     * @param $sequence
     * @return HierarchicalKey
     * @throws \Exception
     */
    public function deriveChild($sequence, NetworkInterface $network = null)
    {
        $network = $network ?: Bitcoin::getNetwork();

        // Generate offset
        $chainCode = $this->getChainCode();

        try {
            // can be easily wrapped in a loop that recurses until
            // the desired key is created, without the other stuff.
            $parser = new Parser();
            $sequence = $parser
                ->writeInt(4, $sequence)
                ->getBuffer();

            $data      = $this->getOffsetBuffer($sequence);
            $hash      = Hash::hmac('sha512', $data->serialize(), $chainCode->serialize());
            list($offsetBuf, $chainCode) = array(
                Buffer::hex(substr($hash, 0, 64)),
                Buffer::hex(substr($hash, 64, 64)),
            );
            $key       = $this->getKeyFromOffset($offsetBuf);

        } catch (InvalidPrivateKey $e) {
            // Invalid keys should trigger recursion.. 1:1^128
            $newSequence = (int)$sequence->serialize('int') + 1;
            return $this->deriveChild($newSequence);
        } catch (\Exception $e) {
            throw $e;
        }

        $key =  new HierarchicalKey(
            $this->math,
            $this->generator,
            $this->getDepth()+1,
            $this->getChildFingerprint(),
            $sequence->serialize('hex'),
            $chainCode,
            $key
        );

        return $key;
    }

    /**
     * Create a buffer containing data to be hashed hashed to yield the child offset
     *
     * @param Buffer $sequence
     * @return \Afk11\Bitcoin\Buffer
     * @throws \Exception
     */
    public function getOffsetBuffer(Buffer $sequence)
    {
        $parser   = new Parser();
        $hardened = $this->math->cmp($sequence->serialize('int'), $this->math->hexDec('80000000')) >= 0;

        if ($hardened) {
            if ($this->isPrivate() === false) {
                throw new \Exception("Can't derive a hardened key without the private key");
            }

            $parser
                ->writeBytes(1, '00')
                ->writeBytes(32, $this->getPrivateKey()->serialize('hex'));

        } else {
            $parser->writeBytes(33, $this->getPublicKey()->serialize('hex'));
        }

        return $parser
            ->writeBytes(4, $sequence->serialize('hex'))
            ->getBuffer();
    }

    /**
     * Create a key when given an offset buffer. This returns either a public
     * or private key depending on the current key type.
     *
     * @param $offset
     * @return PrivateKey|PublicKey
     * @throws \Exception
     */
    public function getKeyFromOffset($offset)
    {
        $key = $this->isPrivate()
            ? new PrivateKey(
                $this->math,
                $this->generator,
                $this->math->mod(
                    $this->math->add(
                        $this->math->hexDec($offset->serialize('hex')),
                        $this->getPrivateKey()->serialize('int')
                    ),
                    $this->getGenerator()->getOrder()
                )
            )

                    : new PublicKey(
                        $this // Get the EC point for this offset
                    ->getGenerator()
                    ->mul(
                        $offset->serialize('int')
                        )
                        // Add it to the public key
                        ->add(
                            $this->getPublicKey()->getPoint()
                        ),
                        true
                    );

        return $key;
    }

    /**
     * Decodes a BIP32 path: ie, m/0/1'/2/3' -> m/0/2147483649/2/2147483651
     *
     * @param $path
     * @return string
     */
    public function decodePath($path)
    {
        $array = explode("/", $path);
        foreach ($array as $c => &$int) {
            if ($c == 0) {
                continue;
            }

            $hardened = false;

            if (in_array(substr(strtolower($int), -1), array("h", "'")) === true) {
                $intEnd = strlen($int) - 1;
                $int = substr($int, 0, $intEnd);
                $hardened = true;
            }

            if ($hardened) {
                $int = ((int)$this->math->hexDec('80000000')) + ((int)$int);
            } else {
                $int = (int)$int;
            }
        }

        $path = implode("/", $array);

        return $path;
    }
}
