<?php

namespace BitWasp\Bitcoin;

use BitWasp\Bitcoin\Exceptions\Bech32Exception;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Script\WitnessProgram;
use BitWasp\Buffertools\Buffer;

class SegwitBech32
{
    /**
     * Takes the $hrp and $witnessProgram and produces a native segwit
     * address.
     *
     * @param WitnessProgram $witnessProgram
     * @param NetworkInterface $network
     * @return string
     */
    public static function encode(WitnessProgram $witnessProgram, NetworkInterface $network = null)
    {
        // do this first, why bother encoding if the network doesn't support it..
        $network = $network ?: Bitcoin::getNetwork();
        $hrp = $network->getSegwitBech32Prefix();

        $programChars = array_values(unpack('C*', $witnessProgram->getProgram()->getBinary()));
        $programBits = Bech32::convertBits($programChars, count($programChars), 8, 5, true);
        $encodeData = array_merge([$witnessProgram->getVersion()], $programBits);

        return Bech32::encode($hrp, $encodeData);
    }

    /**
     * Decodes the provided $bech32 string, validating against
     * the chosen prefix.
     *
     * @param string $bech32
     * @param NetworkInterface $network
     * @return WitnessProgram
     * @throws Bech32Exception
     */
    public static function decode($bech32, NetworkInterface $network = null)
    {
        $network = $network ?: Bitcoin::getNetwork();
        $hrp = $network->getSegwitBech32Prefix();

        list ($hrpGot, $data) = Bech32::decode($bech32);
        if ($hrpGot !== $hrp) {
            throw new Bech32Exception('Invalid prefix for address');
        }

        $decoded = Bech32::convertBits(array_slice($data, 1), count($data) - 1, 5, 8, false);
        $decodeLen = count($decoded);
        if ($decodeLen < 2 || $decodeLen > 40) {
            throw new Bech32Exception('Invalid segwit address');
        }

        if ($data[0] > 16) {
            throw new Bech32Exception('Invalid witness program version');
        }

        $bytes = '';
        foreach ($decoded as $char) {
            $bytes .= chr($char);
        }
        $decoded = new Buffer($bytes);

        if (0 === $data[0]) {
            return WitnessProgram::v0($decoded);
        }

        return new WitnessProgram($data[0], $decoded);
    }
}
