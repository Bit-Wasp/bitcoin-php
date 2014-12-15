<?php

namespace Bitcoin\Signature;

use Bitcoin\Util\Math;
use Bitcoin\Util\Buffer;
use Bitcoin\Util\Parser;
use Bitcoin\Exceptions\ParserOutOfRange;
use Bitcoin\Exceptions\SignatureNotCanonical;

/**
 * Class Signature
 * @package Bitcoin\Signature
 * @author Thomas Kerin
 */
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
     * @param $hex
     * @return Signature
     */
    public static function fromHex($hex)
    {
        $parser    = new Parser($hex);
        $signature = new Signature(0, 0);
        $signature = $signature->fromParser($parser);
        return $signature;
    }

    /**
     * @param Parser $parser
     * @return Signature
     * @throws ParserOutOfRange
     */
    public function fromParser(Parser &$parser)
    {
        try {
            $parser->readBytes(1);
            $outer    = $parser->getVarString();
            $this->setSighashType($parser->readBytes(1)->serialize('int'));

            $parse    = new Parser($outer);
            $prefix   = $parse->readBytes(1);
            $this->setR($parse->getVarString()->serialize('int'));

            $prefix   = $parse->readBytes(1);
            $this->setS($parse->getVarString()->serialize('int'));
        } catch (ParserOutOfRange $e) {
            throw new ParserOutOfRange('Failed to extract full signature from parser');
        }

        return $this;
    }

    /**
     * @param null $type
     * @return string
     */
    public function serialize($type = null)
    {
        // Ensure that the R and S hex's are of even length
        $rHex = Math::decHex($this->getR());
        $rHex = ((strlen($rHex) % 2 == '0') ? '' : '0') . $rHex;
        $sHex = Math::decHex($this->getS());
        $sHex = ((strlen($sHex) % 2 == '0') ? '' : '0') . $sHex;

        $rBin = pack('H*', $rHex);
        $sBin = pack('H*', $sHex);
        // Pad R and S if their highest bit is flipped, ie,
        // they are negative.
        $rt = pack('H*', substr($rHex, 0, 2)) & pack('H*', '80');
        if (ord($rt) == 128) {
            $rHex = '00' . $rHex;
        }

        $st = pack('H*', substr($sHex, 0, 2)) & pack('H*', '80');
        if (ord($st) == 128) {
            $sHex = '00' . $sHex;
        }

        //
        $rBuf  = Buffer::hex($rHex);
        $sBuf  = Buffer::hex($sHex);

        $inner = new Parser();
        $inner
            ->writeBytes(1, '02')
            ->writeWithLength($rBuf)
            ->writeBytes(1, '02')
            ->writeWithLength($sBuf);

        $outer = new Parser();
        $outer
            ->writeBytes(1, '30')
            ->writeWithLength($inner->getBuffer())
            ->writeInt(1, $this->getSighashType(), true);

        $serialized = $outer
            ->getBuffer()
            ->serialize($type);

        return $serialized;
    }

    /**
     * @param Buffer $sig
     * @return bool
     * @throws SignatureNotCanonical
     */
    public static function isCanonical(Buffer $sig)
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
