<?php

namespace Bitcoin\Key;

use Bitcoin\Bitcoin;
use Bitcoin\Exceptions\ParserOutOfRange;
use Bitcoin\Base58;
use Bitcoin\Buffer;
use Bitcoin\Util\Math;
use Bitcoin\Parser;
use Bitcoin\Crypto\Hash;
use Bitcoin\NetworkInterface;
use Bitcoin\Signature\K\KInterface;
use Bitcoin\Exceptions\InvalidPrivateKey;
use Mdanter\Ecc\EccFactory;
use Mdanter\Ecc\GeneratorPoint;
use Mdanter\Ecc\MathAdapterInterface;

/**
 * Class HierarchicalKey
 * @package Bitcoin
 */
class HierarchicalKey implements PrivateKeyInterface, KeyInterface
{
    /**
     * @var PrivateKey
     */
    protected $privateKey = null;

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
     * @var \Bitcoin\Buffer
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
     * @param $bytes
     * @param NetworkInterface $network
     * @param \Mdanter\Ecc\GeneratorPoint $generator
     * @throws \Exception
     */
    public function __construct($bytes, NetworkInterface $network)
    {
        try {
            $network->getHDPrivByte();
            $network->getHDPubByte();
            $this->network = $network;
        } catch (\Exception $e) {
            throw new \Exception('Network not configured for HD wallets');
        }

        if (strlen($bytes) !== 156) {
            throw new \Exception('Invalid extended key');
        }

        try {
            $parser = new Parser($bytes);
            list($this->bytes, $this->depth, $this->parentFingerprint, $this->sequence, $this->chainCode) =
                array(
                    $parser->readBytes(4)->serialize('hex'),
                    $parser->readBytes(1)->serialize('int'),
                    $parser->readBytes(4)->serialize('hex'),
                    $parser->readBytes(4)->serialize('int'),
                    $parser->readBytes(32)
                );
        } catch (ParserOutOfRange $e) {
            throw new ParserOutOfRange('Failed to extract extended key from parser');
        }

        $this->math = Bitcoin::getMath();
        $this->generator  = Bitcoin::getGenerator();

        // Key data from original extended key is saved for serializing later
        if ($this->network->getHDPrivByte() == $this->bytes) {
            $this->keyData     = $parser->readBytes(33);
            $private           = substr($this->keyData->serialize('hex'), 2);
            $this->privateKey  = new PrivateKey($this->math, $this->generator, $private, true, $this->generator);
        } else {
            $this->keyData     = $parser->readBytes(33);
            $this->publicKey   = PublicKey::fromHex($this->keyData->serialize('hex'), $this->generator);
        }
    }

    /**
     * Import from a BIP32 extended key
     *
     * @param $base58
     * @param NetworkInterface $network
     * @param \Mdanter\Ecc\GeneratorPoint $generator
     * @return HierarchicalKey
     */
    public static function fromBase58($base58, NetworkInterface $network)
    {
        try {
            $bytes = Base58::decodeCheck($base58);
        } catch (\Exception $e) {
            throw new \Exception('Failed to decode HierarchicalKey');
        }

        return new HierarchicalKey($bytes, $network);
    }

    /**
     * @param NetworkInterface $network
     * @param \Mdanter\Ecc\GeneratorPoint $generator
     * @return HierarchicalKey
     * @throws \Exception
     */
    public static function generateNew(NetworkInterface $network)
    {
        $buffer  = PrivateKey::generateKey();
        $private = self::fromEntropy($buffer->serialize('hex'), $network);
        return $private;
    }

    /**
     * Generate a master key from entropy
     *
     * @param $random
     * @param NetworkInterface $network
     * @param \Mdanter\Ecc\GeneratorPoint $generator
     * @return HierarchicalKey
     * @throws InvalidPrivateKey
     */
    public static function fromEntropy(
        $random,
        NetworkInterface $network
    ) {
        $hash      = Hash::hmac('sha512', pack("H*", $random), "Bitcoin seed");
        $private   = substr($hash, 0, 64);
        $chainCode = substr($hash, 64, 64);

        if (PrivateKey::isValidKey($private) === false) {
            throw new InvalidPrivateKey("Entropy produced an invalid key.. Odds of this happening are very low.");
        }

        $bytes = new Parser();
        $bytes = $bytes->writeBytes(4, $network->getHDPrivByte())
            ->writeInt(1, '0')
            ->writeBytes(4, Buffer::hex('00000000'))
            ->writeBytes(4, '00000000')
            ->writeBytes(32, $chainCode)
            ->writeBytes(33, '00' . $private)
            ->getBuffer()
            ->serialize('hex');

        return new HierarchicalKey($bytes, $network);
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
     * Return the network object
     *
     * @return NetworkInterface
     */
    public function getNetwork()
    {
        return $this->network;
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
     * @inheritdoc
     */
    public function getPubKeyHash()
    {
        return $this->getPublicKey()->getPubKeyHash();
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
        return $this->privateKey instanceof PrivateKey;
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
     * Return a WIF private key if set
     *
     * @param NetworkInterface $network
     * @return mixed|string
     * @throws \Exception
     */
    public function getWif(NetworkInterface $network = null)
    {
        return $this->getPrivateKey()->getWif($network);
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
     * Return an extended private key in base58.
     *
     * @return string
     * @throws \Exception
     */
    public function getExtendedPrivateKey()
    {
        if (!$this->isPrivate()) {
            throw new \Exception('This is not a private key');
        }

        $bytes = new Parser();
        $bytes = $bytes
            ->writeBytes(4, $this->getNetwork()->getHDPrivByte())
            ->writeInt(1, $this->getDepth())
            ->writeBytes(4, $this->getFingerprint())
            ->writeInt(4, $this->getSequence())
            ->writeBytes(32, $this->getChainCode()->serialize('hex'))
            ->writeBytes(1, '00')
            ->writeBytes(32, $this->getPrivateKey()->serialize('hex'))
            ->getBuffer()
            ->serialize('hex');

        $base58 = Base58::encodeCheck($bytes);

        return $base58;
    }

    /**
     * Return a base58 encoded extended public key
     *
     * @return string
     */
    public function getExtendedPublicKey()
    {
        $bytes = new Parser();
        $bytes = $bytes
            ->writeBytes(4, Buffer::hex($this->getNetwork()->getHDPubByte()))
            ->writeInt(1, $this->getDepth())
            ->writeBytes(4, $this->getFingerprint())
            ->writeInt(4, $this->getSequence())
            ->writeBytes(32, $this->getChainCode()->serialize('hex'))
            ->writeBytes(33, $this->getPublicKey()->serialize('hex'))
            ->getBuffer()
            ->serialize('hex');

        $base58 = Base58::encodeCheck($bytes);

        return $base58;
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
    public function deriveChild($sequence)
    {
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

        $bytes = new Parser();
        $bytes = $bytes
            ->writeBytes(4, Buffer::hex($this->getNetwork()->getHDPrivByte()))
            ->writeInt(1, ((int)$this->getDepth() + 1))
            ->writeBytes(4, $this->getChildFingerprint())
            ->writeBytes(4, $sequence->serialize('hex'))
            ->writeBytes(32, $chainCode->serialize('hex'))
            ->writeBytes(
                33,
                (   $key->isPrivate()
                        ? '00' . $key->serialize('hex')
                        : $key->serialize('hex')   )
            )
            ->getBuffer()
            ->serialize('hex');

        return new HierarchicalKey($bytes, $this->getNetwork(), $this->getGenerator());
    }

    /**
     * Create a buffer containing data to be hashed hashed to yield the child offset
     *
     * @param Buffer $sequence
     * @return \Bitcoin\Buffer
     * @throws \Exception
     */
    public function getOffsetBuffer(Buffer $sequence)
    {
        $parser   = new Parser();
        $hardened = $this->math->cmp($sequence->serialize('int'), $this->math->hexDec('80000000')) >= 0;

        if ($hardened) {
            if ($this->isPrivate() == false) {
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
        if ($this->isPrivate()) {
            // offset + privKey % n
            $key = new PrivateKey($this->math, $this->generator, str_pad(
                $this->math->decHex(
                    $this->math->mod(
                        $this->math->add(
                            $this->math->hexDec($offset->serialize('hex')),
                            $this->getPrivateKey()->serialize('int')
                        ),
                        $this->getGenerator()->getOrder()
                    )
                ),
                64,
                '0',
                STR_PAD_LEFT
            ));

        } else {
            // (offset*G) + (K)
            $key = new PublicKey(
                $this// Get the EC point for this offset
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
        }

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
