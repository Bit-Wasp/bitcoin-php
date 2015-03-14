<?php

namespace Afk11\Bitcoin\Signature;

use \Afk11\Bitcoin\Buffer;
use \Afk11\Bitcoin\Exceptions\SignatureNotCanonical;

class Signature implements SignatureInterface
{
    /**
     * @var int
     */
    protected $r;

    /**
     * @var int
     */
    protected $s;

    /**
     * @var int
     */
    protected $sighashType;

    /**
     * @param $r
     * @param $s
     * @param int $sighashType
     */
    public function __construct($r, $s, $sighashType = SignatureHashInterface::SIGHASH_ALL)
    {
        return $this
            ->setR($r)
            ->setS($s)
            ->setSighashType($sighashType);
    }

    /**
     * @inheritdoc
     */
    public function getR()
    {
        return $this->r;
    }

    /**
     * @param $r
     * @return $this
     */
    private function setR($r)
    {
        $this->r = $r;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getS()
    {
        return $this->s;
    }

    /**
     * @param $s
     * @return $this
     */
    private function setS($s)
    {
        $this->s = $s;
        return $this;
    }

    /**
     * Return the SIGHASH type for this signature
     *
     * @return int
     */
    public function getSighashType()
    {
        return $this->sighashType;
    }

    /**
     * @param $hashtype
     * @return $this
     */
    private function setSighashType($hashtype)
    {
        $this->sighashType = $hashtype;
        return $this;
    }

    /**
     * @return \Afk11\Bitcoin\Buffer
     */
    public function getBuffer()
    {
        $serializer = SignatureFactory::getSerializer();
        $buffer = $serializer->serialize($this);
        return $buffer;
    }

    /**
     * @param \Afk11\Bitcoin\Buffer $sig
     * @return bool
     * @throws SignatureNotCanonical
     */
    public static function isDERSignature(Buffer $sig)
    {
        $bin = $sig->serialize();

        if ($sig->getSize() < 9) {
            throw new SignatureNotCanonical('Signature too short');
        }

        if ($sig->getSize() > 73) {
            throw new SignatureNotCanonical('Signature too long');
        }

        if (ord($bin[0]) !== 0x30) {
            throw new SignatureNotCanonical('Signature has wrong type');
        }

        if (ord($bin[1]) !== $sig->getSize() - 3) {
            throw new SignatureNotCanonical('Signature has wrong length marker');
        }

        $lenR   = ord($bin[3]);
        $r      = substr($bin, 4, $lenR);
        if (5 + $lenR >= $sig->getSize()) {
            throw new SignatureNotCanonical('Signature S length misplaced');
        }

        $lenS   = ord($bin[5 + $lenR]);
        $startS = 4 + $lenR + 2;
        $s      = substr($bin, $startS, $lenS);
        if (($lenR + $lenS + 7) !== $sig->getSize()) {
            throw new SignatureNotCanonical('Signature R+S length mismatch');
        }

        if (ord(substr($bin, 2, 1)) !== 0x02) {
            throw new SignatureNotCanonical('Signature R value type mismatch');
        }

        if ($lenR == 0) {
            throw new SignatureNotCanonical('Signature R length is zero');
        }

        $rAnd   = $r[0] & pack('H*', '80');
        if (ord($rAnd) == 128) {
            throw new SignatureNotCanonical('Signature R value is negative');
        }

        if ($lenR > 1 && ord($r[0]) == 0x00 && !ord(($r[1] & pack('H*', '80')))) {
            throw new SignatureNotCanonical('Signature R value excessively padded');
        }

        if (ord(substr($bin, $startS - 2, 1)) !== 0x02) {
            throw new SignatureNotCanonical('Signature S value type mismatch');
        }

        if ($lenS == 0) {
            throw new SignatureNotCanonical('Signature S length is zero');
        }

        $sAnd   = $s[0] & pack('H*', '80');
        if (ord($sAnd) == 128) {
            throw new SignatureNotCanonical('Signature S value negative');
        }

        if ($lenS > 1 && ord($s[0]) == 0x00 && !ord(($s[1] & pack("H*", '80'))) == 0x80) {
            throw new SignatureNotCanonical('Signature S value excessively padded');
        }

        return true;

    }
}
