<?php
/**
 * Created by PhpStorm.
 * User: thomas
 * Date: 12/12/14
 * Time: 15:13
 */

namespace Bitcoin\Util;

use Bitcoin\Key\PrivateKeyInterface;
use Bitcoin\Util\Buffer;

class HMACDRBG
{
    private $algorithm;
    private $privKey;
    private $order;
    private $orderBitLength;
    private $K;
    private $V;
    private $reseedCounter;

    public function __construct($algo, PrivateKeyInterface $privKey, $entropy = null)
    {
        if (!in_array($algo, hash_algos())) {
            throw new \Exception('HMAC_DRGB: Hashing algorithm not found');
        }

        $this->algorithm = $algo;
        $this->privKey   = $privKey;

        $this->generator = $privKey->getGenerator();
        $this->order = $this->generator->getOrder();

        $order = Buffer::hex(Math::decHex($this->order));
        $this->orderBitLength = $order->getSize() * 8;
        $this->rlen           = 8*ceil($this->orderBitLength / 8);

        $this->initialize($entropy);
    }

    public function initialize($entropy = null, $personalization_string = null)
    {
        echo "initialize\n";
        echo "E: $entropy\nP: ".$personalization_string."\n";
        $hlen = strlen(hash($this->getHashAlgorithm(), 1, true));
        $vlen = 8 * ceil($hlen / 8);

        $this->hlen = $hlen;
        $this->V    = str_pad('', $vlen, chr(0x01), STR_PAD_LEFT);
        $this->K    = str_pad('', $vlen, chr(0x00), STR_PAD_LEFT);
        $seed       = $entropy . $personalization_string;
        echo "s: $seed\n";
        $this->update($seed);
        return $this;
    }

    /**
     * @param $data
     * @return string
     */
    public function hash($data)
    {
        echo "hash\n";
        $hash = Hash::hmac($this->algorithm, $this->K, $data, true);
        echo bin2hex($hash)."\n";
        return $hash;
    }

    public function update($data = null)
    {
        echo "\n\nDo Update with data '$data' " . bin2hex($data) . "\n\n";
        $K = sprintf(
            "%s%s%s",
            $this->V,
            chr(0x00),
            $data ?: ''
        );
        echo "updated k - sha256(" . bin2hex($K).")\n\n";
        $this->K = $this->hash(
            $K
        );

        $this->V = $this->hash($this->V);
        echo "K now " . bin2hex($this->K)."\n\n";
        echo "V now " . bin2hex($this->V)."\n\n";
        if (!is_null($data)) {
            $this->K = $this->hash(
                sprintf(
                    "%s%s%s",
                    $this->V,
                    chr(0x01),
                    $data
                )
            );

            $this->V = $this->hash($this->V);
        }
    }

    public function getHashAlgorithm()
    {
        return $this->algorithm;
    }

    public function compute($numBytes)
    {
        echo "Compute!\n";
        $temp = "";

        while (strlen($temp) <  $numBytes) {
            $this->V = $this->hash($this->V);
            $temp .= $this->V;
        }

        $secret = new Buffer($temp);
        $k = $secret->serialize('int');

        if (Math::cmp(1, $k) <= 0
            and Math::cmp($k, $this->generator->getOrder()) < 0
        ) {
            echo "found\n";
            var_dump($k);
            return $secret;
        }

        echo "DOOVER\n";
        $this->K = $this->hash($this->V . chr(0x00));
        echo "K: " . $this->K."\n";
        $this->V = $this->hash($this->V);

        $this->update(null);
        $this->reseedCounter++;

        $buffer = new Buffer($temp);
        echo $buffer."\n";
        return $buffer;
    }
}
