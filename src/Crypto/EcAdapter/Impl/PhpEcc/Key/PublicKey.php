<?php

namespace BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Key;

use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Adapter\EcAdapter;
use BitWasp\Bitcoin\Crypto\EcAdapter\Impl\PhpEcc\Serializer\Key\PublicKeySerializer;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\Key;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PublicKeyInterface;
use BitWasp\Bitcoin\Crypto\EcAdapter\Signature\SignatureInterface;
use BitWasp\Buffertools\BufferInterface;
use Mdanter\Ecc\Primitives\PointInterface;

class PublicKey extends Key implements PublicKeyInterface, \Mdanter\Ecc\Crypto\Key\PublicKeyInterface
{
    /**
     * @var EcAdapter
     */
    private $ecAdapter;

    /**
     * @var PointInterface
     */
    private $point;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var bool
     */
    private $compressed;

    /**
     * PublicKey constructor.
     * @param EcAdapter $ecAdapter
     * @param PointInterface $point
     * @param bool $compressed
     * @param string $prefix
     */
    public function __construct(
        EcAdapter $ecAdapter,
        PointInterface $point,
        $compressed = false,
        $prefix = null
    ) {
        if (false === is_bool($compressed)) {
            throw new \InvalidArgumentException('PublicKey: Compressed must be a boolean');
        }
        $this->ecAdapter = $ecAdapter;
        $this->point = $point;
        $this->prefix = $prefix;
        $this->compressed = $compressed;
    }

    /**
     * @return \Mdanter\Ecc\Primitives\GeneratorPoint
     */
    public function getGenerator()
    {
        return $this->ecAdapter->getGenerator();
    }

    /**
     * @return \Mdanter\Ecc\Primitives\CurveFpInterface
     */
    public function getCurve()
    {
        return $this->ecAdapter->getGenerator()->getCurve();
    }

    /**
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * @return PointInterface
     */
    public function getPoint()
    {
        return $this->point;
    }

    /**
     * @param BufferInterface $msg32
     * @param SignatureInterface $signature
     * @return bool
     */
    public function verify(BufferInterface $msg32, SignatureInterface $signature)
    {
        return $this->ecAdapter->verify($msg32, $this, $signature);
    }

    /**
     * @param \GMP $tweak
     * @return PublicKeyInterface
     */
    public function tweakAdd(\GMP $tweak)
    {
        $offset = $this->ecAdapter->getGenerator()->mul($tweak);
        $newPoint = $this->point->add($offset);
        return $this->ecAdapter->getPublicKey($newPoint, $this->compressed);
    }

    /**
     * @param \GMP $tweak
     * @return PublicKeyInterface
     */
    public function tweakMul(\GMP $tweak)
    {
        $point = $this->point->mul($tweak);
        return $this->ecAdapter->getPublicKey($point, $this->compressed);
    }

    /**
     * @param BufferInterface $publicKey
     * @return bool
     */
    public static function isCompressedOrUncompressed(BufferInterface $publicKey)
    {
        $vchPubKey = $publicKey->getBinary();
        if ($publicKey->getSize() < self::LENGTH_COMPRESSED) {
            return false;
        }

        if ($vchPubKey[0] === self::KEY_UNCOMPRESSED) {
            if ($publicKey->getSize() !== self::LENGTH_UNCOMPRESSED) {
                // Invalid length for uncompressed key
                return false;
            }
        } elseif (in_array($vchPubKey[0], [
            self::KEY_COMPRESSED_EVEN,
            self::KEY_COMPRESSED_ODD
        ])) {
            if ($publicKey->getSize() !== self::LENGTH_COMPRESSED) {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    public function isCompressed()
    {
        return $this->compressed;
    }

    /**
     * @param PublicKey $other
     * @return bool
     */
    private function doEquals(PublicKey $other)
    {
        return $this->compressed === $other->compressed
            && $this->point->equals($other->point)
            && (($this->prefix === null || $other->prefix === null) || ($this->prefix === $other->prefix));
    }

    /**
     * @param PublicKeyInterface $other
     * @return bool
     */
    public function equals(PublicKeyInterface $other)
    {
        /** @var self $other */
        return $this->doEquals($other);
    }

    /**
     * @return BufferInterface
     */
    public function getBuffer()
    {
        return (new PublicKeySerializer($this->ecAdapter))->serialize($this);
    }
}
