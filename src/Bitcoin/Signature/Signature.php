<?php

namespace BitWasp\Bitcoin\Signature;

use BitWasp\Bitcoin\Buffer;
use BitWasp\Bitcoin\Exceptions\SignatureNotCanonical;
use BitWasp\Bitcoin\Serializable;

class Signature extends Serializable implements SignatureInterface
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
        $this
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
     * @param integer $hashtype
     * @return $this
     */
    private function setSighashType($hashtype)
    {
        $this->sighashType = $hashtype;
        return $this;
    }

    /**
     * @return \BitWasp\Bitcoin\Buffer
     */
    public function getBuffer()
    {
        $serializer = SignatureFactory::getSerializer();
        $buffer = $serializer->serialize($this);
        return $buffer;
    }

    /**
     * @param \BitWasp\Bitcoin\Buffer $sig
     * @return bool
     * @throws SignatureNotCanonical
     */
    public static function isDERSignature(Buffer $sig)
    {
        $checkVal = function ($fieldName, $start, $length, $binaryString) {
            if ($length == 0) {
                throw new SignatureNotCanonical('Signature ' . $fieldName . ' length is zero');
            }
            $typePrefix = ord(substr($binaryString, $start - 2, 1));
            if ($typePrefix !== 0x02) {
                throw new SignatureNotCanonical('Signature ' . $fieldName . ' value type mismatch');
            }
            $val = substr($binaryString, $start, $length);
            $vAnd = $val[0] & pack("H*", '80');
            if (ord($vAnd) === 128) {
                throw new SignatureNotCanonical('Signature ' . $fieldName . ' value is negative');
            }
            if ($length > 1 && ord($val[0]) == 0x00 && !ord(($val[1] & pack('H*', '80')))) {
                throw new SignatureNotCanonical('Signature ' . $fieldName . ' value excessively padded');
            }
        };

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

        $lenR = ord($bin[3]);
        $startR = 4;
        if (5 + $lenR >= $sig->getSize()) {
            throw new SignatureNotCanonical('Signature S length misplaced');
        }

        $lenS = ord($bin[5 + $lenR]);
        $startS = 4 + $lenR + 2;
        if (($lenR + $lenS + 7) !== $sig->getSize()) {
            throw new SignatureNotCanonical('Signature R+S length mismatch');
        }

        $checkVal('R', $startR, $lenR, $bin);
        $checkVal('S', $startS, $lenS, $bin);

        return true;

    }
}
