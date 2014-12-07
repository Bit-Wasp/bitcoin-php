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
        $this->vlen     = 8 * ceil($this->hlen / 8);

        $q              = Buffer::hex(Math::decHex($generator->getOrder()));
        $this->qlen     = $q->getSize();
        $this->rlen     = (8 * ceil($this->hlen / 8));

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

        // Step E: V = HMAC with Key(V)
        $this->V = $this->hash($this->V);

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

        // Step G: Set V: HMAC with Key (V)
        $this->V = $this->hash($this->V);

        // Step H: Apply algorithm until a value for K is found
        while (true) {
            // Step H1
            $t = '';

            // Step H2

            while (strlen($t) < $this->rlen) {
                $this->V = $this->hash($this->V);
                $t      .= $this->V;

            }

            $secret = new Buffer($t);
            $k      = $secret->serialize('int');

            if (Math::cmp(1, $this->generator->getOrder()) >= 0
                and Math::cmp($k, $this->generator->getOrder()) < 0
            ) {
                return $secret;
            }

            $this->K = $this->hash($this->V . chr(0x00));
            $this->V = $this->hash($this->V);
        }
    }
}
