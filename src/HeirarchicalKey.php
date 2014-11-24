<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 23/11/14
 * Time: 03:50
 */

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

    protected $privateKey = null;

    protected $publicKey;

    protected $chainCode;

    protected $depth;
    protected $sequence;
    protected $fingerprint;
    protected $network;

    public function __construct($base58, NetworkInterface $network, \Mdanter\Ecc\GeneratorPoint $generator = null)
    {
        try {
            $privByte = $network->getHDPrivByte();
            $pubByte  = $network->getHDPubByte();
            $this->network = $network;
        } catch (\Exception $e) {
            throw new \Exception('Network not configured for HD wallets');
        }

        $bytes = Base58::decode($base58);

        if (! strlen($bytes) == 164) {
            throw new \Exception('Invalid extended key');
        }

        if ($generator == null) {
            $generator = EccFactory::getSecgCurves()->generator256k1();
        }

        $parser = new Parser($bytes);

        list ($this->bytes, $this->depth, $this->fingerprint, $this->sequence, $this->chainCode) =
            array(
                $parser -> readBytesHex(4),
                $parser -> readBytesHex(1),
                $parser -> readBytesHex(4),
                $parser -> readBytesHex(4),
                $parser -> readBytesHex(32),
            );

        if ($this->network->getHDPrivByte() == $this->bytes) {
            $parser->readBytesHex(1); // Null byte for padding..
            $this->keyData = $parser->readBytesHex(32);
            $this->privateKey = new PrivateKey($this->keyData);

        } else {
            $this->keyData = $parser->readBytesHex(33);
            $this->publicKey = PublicKey::fromHex($this->keyData);
        }
    }

    public static function fromEntropy($hex, NetworkInterface $network)
    {
        $hash = Hash::hmac('sha512', pack("H*", $hex), "Bitcoin seed");
        list($hex, $chainCode) = chunk_split($hash, 64);

        $bytes = new Parser();
        $bytes  -> writeBytes(4, new Buffer($network->getHDPrivByte()))
            -> writeBytes(1, Buffer::hex('00'))
            -> writeBytes(4, '00000000')
            -> writeBytes(4, '00000000')
            -> writeBytes(32, $chainCode)
            -> writeBytes(33, '00' . $hex)
            -> getBuffer()
            -> serialize('hex');

        return new HeirarchicalKey($hex, $network);
    }

    public static function generateNew(NetworkInterface $network)
    {
        $generator = EccFactory::getSecgCurves()->generator256k1();

        $buffer = new Buffer(Random::bytes(32));
        while (Math::cmp($buffer->serialize('int'), $generator->getOrder()) >= 0) {
            $buffer = new Buffer(Random::bytes(32));
        }

        $hash = Hash::hmac('sha512', $buffer->serialize(), "Bitcoin seed");
        list($hex, $chainCode) = chunk_split($hash, 64);

        $bytes = new Parser();
        $bytes  -> writeBytes(4, new Buffer($network->getHDPrivByte()))
            -> writeBytes(1, Buffer::hex('00'))
            -> writeBytes(4, '00000000')
            -> writeBytes(4, '00000000')
            -> writeBytes(32, $chainCode)
            -> writeBytes(33, '00' . $hex)
            -> getBuffer()
            -> serialize('hex');

        return new HeirarchicalKey($hex, $network);
    }


    public function getDepth()
    {
        return $this->depth;
    }

    public function getFingerprint()
    {
        return $this->fingerprint;
    }

    public function getChainCode()
    {
        return $this->chainCode;
    }

    public function getPrivateKey()
    {
        if ($this->privateKey == null) {
            throw new \Exception('This is not a private key');
        }

        return $this->privateKey;
    }

    public function getPublicKey()
    {
        try {
            $public = $this->getPrivateKey()->getPublicKey();
        } catch (\Exception $e) {
            $public = $this->publicKey;
        }

        return $public;
    }

    public function isHardened()
    {

    }

    public function getExtendedPrivateKey()
    {

    }

    public function getExtendedPublicKey()
    {

    }

    public function getWif(NetworkInterface $network)
    {
        if ($this->privateKey == null) {
            throw new \Exception('This is not a private key');
        }
    }

    public function getPubKeyHash()
    {
        return $this->publicKey->getPubKeyHash();
    }

    public function isCompressed()
    {
        return true;
    }
}
