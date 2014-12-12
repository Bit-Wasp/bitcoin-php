<?php

namespace Bitcoin\Signature\K;

use Bitcoin\Util\Hash;
use Bitcoin\Util\Math;
use Bitcoin\Util\Buffer;
use Bitcoin\Key\PrivateKeyInterface;
use Mdanter\Ecc\GeneratorPoint;

/**
 * Class Deterministic
 * @package Bitcoin\SignatureK
 * @author Thomas Kerin
 */
class Deterministic implements KInterface
{
    /**
     * @var Buffer
     */
    protected $data;

    /**
     * @var string
     */
    protected $algorithm;

    /**
     * @var GeneratorPoint
     */
    protected $generator;

    /**
     * @var PrivateKeyInterface
     */
    protected $privateKey;

    /**
     * Used to hold the deterministic key data
     * @var string
     */
    protected $K;

    /**
     * Used to hold a deterministic salt
     * @var string
     */
    protected $V;

    /**
     * @param PrivateKeyInterface $privateKey
     * @param Buffer $data
     * @param string $algo
     * @param GeneratorPoint $generator
     */
    public function __construct(PrivateKeyInterface $privateKey, Buffer $data, $algo = 'sha256', GeneratorPoint $generator = null)
    {
        echo "Deterministic doesnt work right now\n";
        if ($generator == null) {
            $generator = \Mdanter\Ecc\EccFactory::getSecgCurves()->generator256k1();
        }

        $this->data         = $data;
        $this->algorithm    = $algo;
        $this->generator    = $generator;
        $this->privateKey   = $privateKey;

        // Step A: Process message data through the hash function.
        $this->h1       = Hash::$algo($data, true);
        $this->hlen     = strlen($this->h1);
        echo "HLEN: ".$this->hlen."\n";
        $this->vlen     = 8 * ceil($this->hlen / 8);
        echo "VLEN: ".$this->vlen."\n";
        $q              = Buffer::hex(Math::decHex($generator->getOrder()));
        $this->qlen     = $q->getSize();
        echo "QLEN: ".$this->qlen."\n";
        $this->rlen     = (8 * ceil($this->hlen / 8));
        echo "RLEN: ".$this->rlen."\n";
        // Step B: Set initial V
        $this->V    = str_pad('', $this->vlen, chr(0x0), STR_PAD_LEFT);

        // Step C: Set initial K
        $this->K    = str_pad('', $this->vlen, chr(0x0), STR_PAD_LEFT);
    }

    /**
     * @param $data
     * @return string
     */
    public function hash($data)
    {
        return Hash::hmac($this->algorithm, $this->K, $data, true);
    }

    /**
     * Return a K value deterministically derived from the private key
     *  - TODO
     */
    public function getK()
    {
        // Step D: Calculate HMAC with Key( V raw || 0x00 || PrivKey raw || Hash raw)
        $this->K = $this->hash(
            sprintf(
                "%s%s%s%s",
                $this->V,
                chr(0x00),
                $this->privateKey->serialize(),
                $this->h1
            )
        );
        echo "K: " . $this->K."\n";

        // Step E: V = HMAC with Key(V)
        $this->V = $this->hash($this->V);
        echo "V: " . $this->V."\n";
        // Step F:
        $this->K = $this->hash(
            sprintf(
                "%s%s%s%s",
                $this->V,
                chr(0x01),
                $this->privateKey->serialize(),
                $this->h1
            )
        );
        echo "K: " . $this->K."\n";
        // Step G: Set V: HMAC with Key (V)
        $this->V = $this->hash($this->V);

        // Step H: Apply algorithm until a value for K is found
        while (true) {
            echo "try now\n";
            // Step H1
            // Step H2

            $this->V = $this->hash($this->V);
            echo $this->V."\n";


            $secret  = Buffer::hex($this->V);
            $k       = $secret->serialize('int');

            if (Math::cmp(1, $k) <= 0
                and Math::cmp($k, $this->generator->getOrder()) < 0
            ) {
                var_dump($k);
                return $secret;
            }

            echo "DOOVER\n";
            $this->K = $this->hash($this->V . chr(0x00));
            echo "K: " . $this->K."\n";
            $this->V = $this->hash($this->V);
        }
    }
}
