<?php

namespace Bitcoin;

use Bitcoin\Util\Math;
use Bitcoin\Util\Hash;
use Bitcoin\Util\Buffer;
use Bitcoin\Util\Base58;
use Bitcoin\Util\Base58ChecksumFailure;
use Bitcoin\Util\Parser;
use Bitcoin\Util\Random;
use Mdanter\Ecc\EccFactory;

/**
 * Class HeirarchicalKey
 * @package Bitcoin
 */
class HeirarchicalKey implements PrivateKeyInterface, KeyInterface
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
    protected $fingerprint;

    /**
     * @var int
     */
    protected $sequence;

    /**
     * @var Buffer
     */
    protected $chainCode;

    /**
     * @var NetworkInterface
     */
    protected $network;

    /**
     * @param $base58
     * @param NetworkInterface $network
     * @param \Mdanter\Ecc\GeneratorPoint $generator
     * @throws \Exception
     */
    public function __construct($bytes, NetworkInterface $network, \Mdanter\Ecc\GeneratorPoint $generator = null)
    {
        try {
            $privByte = $network->getHDPrivByte();
            $pubByte  = $network->getHDPubByte();
            $this->network = $network;
        } catch (\Exception $e) {
            throw new \Exception('Network not configured for HD wallets');
        }

        if (!strlen($bytes) == 164) {
            throw new \Exception('Invalid extended key');
        }

        if ($generator == null) {
            $generator = EccFactory::getSecgCurves()->generator256k1();
        }

        $parser = new Parser($bytes);

        list($this->bytes, $this->depth, $this->fingerprint, $this->sequence, $this->chainCode) =
            array(
                $parser->readBytes(4)->serialize('hex'),
                $parser->readBytes(1)->serialize('int'),
                $parser->readBytes(4)->serialize('hex'),
                $parser->readBytes(4)->serialize('int'),
                $parser->readBytes(32),
            );

        if ($this->network->getHDPrivByte() == $this->bytes) {
            // Private key is prefixed with a null byte to maintain the length of the string..
            $parser->readBytes(1);
            $keyData = $parser->readBytes(32);
            $this->privateKey = new PrivateKey($keyData->serialize('hex'), true, $generator);
        } else {
            $keyData = $parser->readBytes(33);
            $this->publicKey = PublicKey::fromHex($keyData->serialize('hex'), $generator);
        }

        $this->generator = $generator;
    }

    /**
     * Import from a BIP32 extended key
     *
     * @param $base58
     * @param NetworkInterface $network
     * @param \Mdanter\Ecc\GeneratorPoint $generator
     * @return HeirarchicalKey
     */
    public static function fromBase58($base58, NetworkInterface $network, \Mdanter\Ecc\GeneratorPoint $generator)
    {
        if ($generator == null) {
            $generator = EccFactory::getSecgCurves()->generator256k1();
        }

        $bytes = Base58::decode($base58);

        return new HeirarchicalKey($bytes, $network, $generator);
    }

    /**
     * Generate a master key
     *
     * @param $hex
     * @param NetworkInterface $network
     * @param \Mdanter\Ecc\GeneratorPoint $generator
     * @return HeirarchicalKey
     * @throws \Exception
     */
    public static function fromEntropy($hex, NetworkInterface $network, \Mdanter\Ecc\GeneratorPoint $generator = null)
    {
        $hash = Hash::hmac('sha512', pack("H*", $hex), "Bitcoin seed");
        list($hex, $chainCode) = chunk_split($hash, 64);

        if (!PrivateKey::isValidKey($hex)) {
            throw new \Exception("Entropy produced an invalid key.. Odds of this happening are very low.");
        }

        $bytes = (new Parser)
            ->writeBytes(4, new Buffer($network->getHDPrivByte()))
            ->writeInt(1, '0')
            ->writeBytes(4, '00000000')
            ->writeBytes(4, '00000000')
            ->writeBytes(32, $chainCode)
            ->writeBytes(33, '00' . $hex)
            ->getBuffer()
            ->serialize('hex');

        if ($generator == null) {
            $generator = EccFactory::getSecgCurves()->generator256k1();
        }

        return new HeirarchicalKey($bytes, $network, $generator);
    }

    /**
     * @param NetworkInterface $network
     * @param \Mdanter\Ecc\GeneratorPoint $generator
     * @return HeirarchicalKey
     * @throws \Exception
     */
    public static function generateNew(NetworkInterface $network, \Mdanter\Ecc\GeneratorPoint $generator = null)
    {
        if ($generator == null) {
            $generator = EccFactory::getSecgCurves()->generator256k1();
        }

        $buffer = PrivateKey::generateKey();

        return self::fromEntropy($buffer->serialize('hex'), $network, $generator);
    }

    /**
     * @return \Mdanter\Ecc\GeneratorPoint
     */
    public function getGenerator()
    {
        return $this->generator;
    }

    /**
     * @return NetworkInterface
     */
    public function getNetwork()
    {
        return $this->network;
    }

    /**
     * @return int
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * @return string
     */
    public function getFingerprint()
    {
        $hash = Hash::sha256ripe160($this->getPublicKey()->serialize('hex'));
        $fingerprint = substr($hash, 0, 8);
        return $fingerprint;
    }

    /**
     * @return int
     */
    public function getSequence()
    {
        return $this->sequence;
    }

    /**
     * @return Buffer
     */
    public function getChainCode()
    {
        return $this->chainCode;
    }

    /**
     * @return bool
     */
    public function isHardened()
    {
        return Math::cmp($this->getSequence(), Math::hexDec('80000000')) >= 0;
    }

    /**
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
     * @return bool
     */
    public function isPrivate()
    {
        return $this->privateKey instanceOf PrivateKey;
    }

    public function getExtendedPrivateKey()
    {
        if (!$this->isPrivate()) {
            throw new \Exception('This is not a private key');
        }

        $bytes = (new Parser)
            ->writeBytes(4, Buffer::hex($this->getNetwork()->getHDPrivByte()))
            ->writeInt(1, $this->getDepth() + 1)
            ->writeBytes(4, $this->getFingerprint())
            ->writeInt(4, $this->getSequence())
            ->writeBytes(32, $this->getChainCode()->serialize('hex'))
            ->writeBytes(33, '00'.$this->getPrivateKey()->serialize('hex'))
            ->getBuffer()
            ->serialize('hex');

        $base58 = Base58::encodeCheck($bytes);

        return $base58;
    }

    public function getExtendedPublicKey()
    {
        $bytes = (new Parser)
            ->writeBytes(4, Buffer::hex($this->getNetwork()->getHDPubByte()))
            ->writeInt(1, $this->getDepth() + 1)
            ->writeBytes(4, $this->getFingerprint())
            ->writeInt(4, $this->getSequence())
            ->writeBytes(32, $this->getChainCode()->serialize('hex'))
            ->writeBytes(33, $this->getPublicKey()->serialize('hex'))
            ->getBuffer()
            ->serialize('hex');

        $base58 = Base58::encodeCheck($bytes);

        return $base58;
    }

    public function getWif(NetworkInterface $network)
    {
        if (!$this->isPrivate()) {
            throw new \Exception('This is not a private key');
        }

        return $this->privateKey->getWif($network);
    }

    public function getPubKeyHash()
    {
        return $this->publicKey->getPubKeyHash();
    }

    public function isCompressed()
    {
        return true;
    }

    public function getOffsetBuffer(Buffer $sequence)
    {

        $hardened  = Math::cmp($sequence->serialize('int'), Math::hexDec('80000000')) >= 0;

        if ($hardened) {
            if (! $this->isPrivate()) {
                throw new \Exception("Can't derive a hardened key without the private key");
            }

            $data = Buffer::hex(
                '00' .
                $this->getPrivateKey()->serialize('hex') .
                $sequence->serialize('hex')
            );
        } else {
            $data = Buffer::hex(
                $this->getPublicKey()->serialize('hex') .
                $sequence->serialize('hex')
            );
        }

        return $data;
    }

    public function deriveChild($sequence)
    {
        // Generate offset
        $sequence  = (new Parser())
            ->writeInt(4, $sequence)
            ->getBuffer();

        $data   = $this->getOffsetBuffer($sequence);
        $hash   = Hash::hmac('sha512', $data->serialize(), $this->getChainCode()->serialize());
        $parser = new Parser($hash);
        list($offset, $chainCode) =
            [
                $parser->readBytes(32),
                $parser->readBytes(32)
            ];

        // todo remove?
        if (PrivateKey::isValidKey($offset->serialize('hex')) == false) {
            // Do again, increasing the number by 1.
            $newSequence = (int)$sequence->serialize('int') + 1;
            return $this->deriveChild($newSequence);
        }

        if ($this->isPrivate()) {
            $private = new PrivateKey(
                str_pad(
                    Math::decHex(
                        Math::mod(
                            Math::add(
                                $offset->serialize('int'),
                                $this->getPrivateKey()->serialize('int')
                            ),
                            $this->getGenerator()->getOrder()
                        )
                    ),
                    64, '0', STR_PAD_LEFT)
            );
            $public = $private->getPublicKey();
        } else {
            $public = PublicKey::fromHex(
                $this
                    ->getGenerator()
                    ->mul(
                        $offset->serialize('int')
                    )   // Get EC point for this offset
                    ->add(
                        $this
                            ->getPublicKey()
                            ->getPoint()
                    )  // Add it to the public key
            );
        }

        $bytes = (new Parser)
            ->writeBytes(4, Buffer::hex($this->getNetwork()->getHDPrivByte()))
            ->writeInt(1, $this->getDepth() + 1)
            ->writeBytes(4, $this->getFingerprint())
            ->writeBytes(4, $sequence->serialize('hex'))
            ->writeBytes(32, $chainCode->serialize('hex'))
            ->writeBytes(33, substr($data, 0, 66))
            ->getBuffer()
            ->serialize('hex');

        return new HeirarchicalKey($bytes, $this->getNetwork(), $this->getGenerator());
    }

    public function recursiveDerive()
    {

    }
}
