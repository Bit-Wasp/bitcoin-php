<?php

namespace BitWasp\Bitcoin\PaymentProtocol;

use BitWasp\Bitcoin\PaymentProtocol\Protobufs\PaymentRequest as PaymentRequestBuf;
use BitWasp\Bitcoin\PaymentProtocol\Protobufs\X509Certificates as X509CertificatesBuf;

class PaymentRequestVerifier
{
    /**
     * @var PaymentRequestBuf
     */
    private $request;

    /**
     * @param PaymentRequestBuf $request
     */
    public function __construct(PaymentRequestBuf $request)
    {
        $this->request = $request;
    }

    /**
     * @param $certData
     * @return array
     */
    private function der2pem($certData)
    {
        $begin = '-----BEGIN CERTIFICATE-----';
        $end = '-----END CERTIFICATE-----';

        $d = $begin . "\n";
        $d .= chunk_split(base64_encode($certData));
        $d .= $end . "\n";
        return $d;
    }

    /**
     * @return bool
     */
    public function verifySignature()
    {
        if ($this->request->getPkiType() === 'none') {
            return true;
        }

        $algorithm = $this->request->getPkiType() === 'x509+sha256'
            ? OPENSSL_ALGO_SHA256
            : OPENSSL_ALGO_SHA1;

        $signature = $this->request->getSignature();

        $clone = clone $this->request;
        $clone->setSignature('');
        $data = $clone->serialize();

        // Parse the public key
        $certificates = new X509CertificatesBuf();
        $certificates->parse($clone->getPkiData());
        $certificate = $this->der2pem($certificates->getCertificate(0));
        $pubkeyid = openssl_pkey_get_public($certificate);

        return 1 === openssl_verify($data, $signature, $pubkeyid, $algorithm);
    }
}
