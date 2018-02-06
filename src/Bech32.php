<?php

namespace BitWasp\Bitcoin;

use \BitWasp\Bech32\Exception\Bech32Exception;

class Bech32
{
    /**
     * @var string
     */
    protected static $charset = 'qpzry9x8gf2tvdw0s3jn54khce6mua7l';

    /**
     * @var array
     */
    protected static $charsetKey = [
        -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
        -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
        -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
        15, -1, 10, 17, 21, 20, 26, 30,  7,  5, -1, -1, -1, -1, -1, -1,
        -1, 29, -1, 24, 13, 25,  9,  8, 23, -1, 18, 22, 31, 27, 19, -1,
        1,  0,  3, 16, 11, 28, 12, 14,  6,  4,  2, -1, -1, -1, -1, -1,
        -1, 29, -1, 24, 13, 25,  9,  8, 23, -1, 18, 22, 31, 27, 19, -1,
        1,  0,  3, 16, 11, 28, 12, 14,  6,  4,  2, -1, -1, -1, -1, -1
    ];

    /**
     * @var array
     */
    protected static $generator = [0x3b6a57b2, 0x26508e6d, 0x1ea119fa, 0x3d4233dd, 0x2a1462b3];

    /**
     * @param int[] $values
     * @param int $numValues
     * @return int
     */
    public static function polyMod(array $values, $numValues)
    {
        $chk = 1;
        for ($i = 0; $i < $numValues; $i++) {
            $top = $chk >> 25;
            $chk = ($chk & 0x1ffffff) << 5 ^ $values[$i];

            for ($j = 0; $j < 5; $j++) {
                $value = (($top >> $j) & 1) ? self::$generator[$j] : 0;
                $chk ^= $value;
            }
        }

        return $chk;
    }

    /**
     * Expands the human readable part into a character array for checksumming.
     * @param string $hrp
     * @param int $hrpLen
     * @return array
     */
    public static function hrpExpand($hrp, $hrpLen)
    {
        $expand1 = [];
        $expand2 = [];
        for ($i = 0; $i < $hrpLen; $i++) {
            $o = ord($hrp[$i]);
            $expand1[] = $o >> 5;
            $expand2[] = $o & 31;
        }

        return array_merge($expand1, [0], $expand2);
    }

    /**
     * Converts words of $fromBits bits to $toBits bits in size.
     *
     * @param int[] $data - character array of data to convert
     * @param int $inLen - number of elements in array
     * @param int $fromBits - word (bit count) size of provided data
     * @param int $toBits - requested word size (bit count)
     * @param bool $pad - whether to pad (only when encoding)
     * @return array
     * @throws Bech32Exception
     */
    public static function convertBits(array $data, $inLen, $fromBits, $toBits, $pad = true)
    {
        $acc = 0;
        $bits = 0;
        $ret = [];
        $maxv = (1 << $toBits) - 1;
        $maxacc = (1 << ($fromBits + $toBits - 1)) - 1;

        for ($i = 0; $i < $inLen; $i++) {
            $value = $data[$i];
            if ($value < 0 || $value >> $fromBits) {
                throw new Bech32Exception('Invalid value for convert bits');
            }

            $acc = (($acc << $fromBits) | $value) & $maxacc;
            $bits += $fromBits;

            while ($bits >= $toBits) {
                $bits -= $toBits;
                $ret[] = (($acc >> $bits) & $maxv);
            }
        }

        if ($pad) {
            if ($bits) {
                $ret[] = ($acc << $toBits - $bits) & $maxv;
            }
        } else if ($bits >= $fromBits || ((($acc << ($toBits - $bits))) & $maxv)) {
            throw new \BitWasp\Bech32\Exception\Bech32Exception('Invalid data');
        }

        return $ret;
    }

    /**
     * @param string $hrp
     * @param int[] $convertedDataChars
     * @return array
     */
    public static function createChecksum($hrp, array $convertedDataChars)
    {
        $values = array_merge(self::hrpExpand($hrp, strlen($hrp)), $convertedDataChars);
        $polyMod = self::polyMod(array_merge($values, [0, 0, 0, 0, 0, 0]), count($values) + 6) ^ 1;
        $results = [];
        for ($i = 0; $i < 6; $i++) {
            $results[$i] = ($polyMod >> 5 * (5 - $i)) & 31;
        }

        return $results;
    }

    /**
     * Verifies the checksum given $hrp and $convertedDataChars.
     *
     * @param string $hrp
     * @param int[] $convertedDataChars
     * @return bool
     */
    public static function verifyChecksum($hrp, array $convertedDataChars)
    {
        $expandHrp = self::hrpExpand($hrp, strlen($hrp));
        $r = array_merge($expandHrp, $convertedDataChars);
        $poly = self::polyMod($r, count($r));
        return $poly === 1;
    }

    /**
     * @param string $hrp
     * @param array $combinedDataChars
     * @return string
     */
    public static function encode($hrp, array $combinedDataChars)
    {
        $checksum = self::createChecksum($hrp, $combinedDataChars);
        $characters = array_merge($combinedDataChars, $checksum);

        $encoded = [];
        for ($i = 0, $n = count($characters); $i < $n; $i++) {
            $encoded[$i] = self::$charset[$characters[$i]];
        }

        return "{$hrp}1" . implode('', $encoded);
    }

    /**
     * Validates a bech32 string and returns [$hrp, $dataChars] if
     * the conversion was successful. An exception is thrown on invalid
     * data.
     *
     * @param string $sBech - the bech32 encoded string
     * @return array - returns [$hrp, $dataChars]
     * @throws Bech32Exception
     */
    public static function decode($sBech)
    {
        return \BitWasp\Bech32\decode($sBech);
    }
}
