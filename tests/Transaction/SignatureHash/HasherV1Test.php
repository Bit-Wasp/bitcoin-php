<?php

namespace BitWasp\Bitcoin\Tests\Transaction\SignatureHash;

use BitWasp\Bitcoin\Amount;
use BitWasp\Bitcoin\Transaction\SignatureHash\V1Hasher;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\SignatureHash\SigHash;
use BitWasp\Bitcoin\Tests\AbstractTestCase;

class HasherV1Test extends AbstractTestCase
{
    /**
     * @dataProvider getBip143TestVectors
     * @param $unsignedTx
     * @param $inputToSign
     * @param $outputValueBtc
     * @param $hashScriptCode
     * @param $sigHashType
     * @param $expectedHash
     */
    public function testCase($unsignedTx, $inputToSign, $outputValueBtc, $hashScriptCode, $sigHashType, $expectedHash)
    {
        $amount = new Amount();
        $tx = TransactionFactory::fromHex($unsignedTx);

        $valueSatoshis = $amount->toSatoshis($outputValueBtc);
        $hasher = new V1Hasher($tx, $valueSatoshis);
        $effScript = ScriptFactory::fromHex($hashScriptCode);
        $hash = $hasher->calculate($effScript, $inputToSign, $sigHashType);

        $this->assertEquals($expectedHash, $hash->getHex());
    }

    public function getBip143TestVectors()
    {
        return [
            [
                'unsignedTx' => '0100000002fff7f7881a8099afa6940d42d1e7f6362bec38171ea3edf433541db4e4ad969f0000000000eeffffffef51e1b804cc89d182d279655c3aa89e815b1b309fe287d9b2b55d57b90ec68a0100000000ffffffff02202cb206000000001976a9148280b37df378db99f66f85c95a783a76ac7a6d5988ac9093510d000000001976a9143bde42dbee7e4dbe6a21b2d50ce2f0167faa815988ac11000000',
                'inputToSign' => 1,
                'outputValueBtc' => '6',
                'hashScriptCode' => '76a9141d0f172a0ecb48aee1be1f2687d2963ae33f71a188ac',
                'sigHashType' => SigHash::ALL,
                'expectedHash' => 'c37af31116d1b27caf68aae9e3ac82f1477929014d5b917657d0eb49478cb670'
            ],
            [
                'unsignedTx' => '0100000001db6b1b20aa0fd7b23880be2ecbd4a98130974cf4748fb66092ac4d3ceb1a54770100000000feffffff02b8b4eb0b000000001976a914a457b684d7f0d539a46a45bbc043f35b59d0d96388ac0008af2f000000001976a914fd270b1ee6abcaea97fea7ad0402e8bd8ad6d77c88ac92040000',
                'inputToSign' => 0,
                'outputValueBtc' => '10',
                'hashScriptCode' => '76a91479091972186c449eb1ded22b78e40d009bdf008988ac',
                'sigHashType' => SigHash::ALL,
                'expectedHash' => '64f3b0f4dd2bb3aa1ce8566d220cc74dda9df97d8490cc81d89d735c92e59fb6'
            ],
            // Skipped the OP_CODESEPARATOR examples
            [
                'unsignedTx' => '010000000136641869ca081e70f394c6948e8af409e18b619df2ed74aa106c1ca29787b96e0100000000ffffffff0200e9a435000000001976a914389ffce9cd9ae88dcc0631e88a821ffdbe9bfe2688acc0832f05000000001976a9147480a33f950689af511e6e84c138dbbd3c3ee41588ac00000000',
                'inputToSign' => 0,
                'outputValueBtc' => '9.87654321',
                'hashScriptCode' => '56210307b8ae49ac90a048e9b53357a2354b3334e9c8bee813ecb98e99a7e07e8c3ba32103b28f0c28bfab54554ae8c658ac5c3e0ce6e79ad336331f78c428dd43eea8449b21034b8113d703413d57761b8b9781957b8c0ac1dfe69f492580ca4195f50376ba4a21033400f6afecb833092a9a21cfdf1ed1376e58c5d1f47de74683123987e967a8f42103a6d48b1131e94ba04d9737d61acdaa1322008af9602b3b14862c07a1789aac162102d8b661b0b3302ee2f162b09e07a55ad5dfbe673a9f01d9f0c19617681024306b56ae',
                'sigHashType' => SigHash::ALL,
                'expectedHash' => '185c0be5263dce5b4bb50a047973c1b6272bfbd0103a89444597dc40b248ee7c'
            ],
            [
                'unsignedTx' => '010000000136641869ca081e70f394c6948e8af409e18b619df2ed74aa106c1ca29787b96e0100000000ffffffff0200e9a435000000001976a914389ffce9cd9ae88dcc0631e88a821ffdbe9bfe2688acc0832f05000000001976a9147480a33f950689af511e6e84c138dbbd3c3ee41588ac00000000',
                'inputToSign' => 0,
                'outputValueBtc' => '9.87654321',
                'hashScriptCode' => '56210307b8ae49ac90a048e9b53357a2354b3334e9c8bee813ecb98e99a7e07e8c3ba32103b28f0c28bfab54554ae8c658ac5c3e0ce6e79ad336331f78c428dd43eea8449b21034b8113d703413d57761b8b9781957b8c0ac1dfe69f492580ca4195f50376ba4a21033400f6afecb833092a9a21cfdf1ed1376e58c5d1f47de74683123987e967a8f42103a6d48b1131e94ba04d9737d61acdaa1322008af9602b3b14862c07a1789aac162102d8b661b0b3302ee2f162b09e07a55ad5dfbe673a9f01d9f0c19617681024306b56ae',
                'sigHashType' => SigHash::NONE,
                'expectedHash' => 'e9733bc60ea13c95c6527066bb975a2ff29a925e80aa14c213f686cbae5d2f36'
            ],
            [
                'unsignedTx' => '010000000136641869ca081e70f394c6948e8af409e18b619df2ed74aa106c1ca29787b96e0100000000ffffffff0200e9a435000000001976a914389ffce9cd9ae88dcc0631e88a821ffdbe9bfe2688acc0832f05000000001976a9147480a33f950689af511e6e84c138dbbd3c3ee41588ac00000000',
                'inputToSign' => 0,
                'outputValueBtc' => '9.87654321',
                'hashScriptCode' => '56210307b8ae49ac90a048e9b53357a2354b3334e9c8bee813ecb98e99a7e07e8c3ba32103b28f0c28bfab54554ae8c658ac5c3e0ce6e79ad336331f78c428dd43eea8449b21034b8113d703413d57761b8b9781957b8c0ac1dfe69f492580ca4195f50376ba4a21033400f6afecb833092a9a21cfdf1ed1376e58c5d1f47de74683123987e967a8f42103a6d48b1131e94ba04d9737d61acdaa1322008af9602b3b14862c07a1789aac162102d8b661b0b3302ee2f162b09e07a55ad5dfbe673a9f01d9f0c19617681024306b56ae',
                'sigHashType' => SigHash::SINGLE,
                'expectedHash' => '1e1f1c303dc025bd664acb72e583e933fae4cff9148bf78c157d1e8f78530aea'
            ],
            [
                'unsignedTx' => '010000000136641869ca081e70f394c6948e8af409e18b619df2ed74aa106c1ca29787b96e0100000000ffffffff0200e9a435000000001976a914389ffce9cd9ae88dcc0631e88a821ffdbe9bfe2688acc0832f05000000001976a9147480a33f950689af511e6e84c138dbbd3c3ee41588ac00000000',
                'inputToSign' => 0,
                'outputValueBtc' => '9.87654321',
                'hashScriptCode' => '56210307b8ae49ac90a048e9b53357a2354b3334e9c8bee813ecb98e99a7e07e8c3ba32103b28f0c28bfab54554ae8c658ac5c3e0ce6e79ad336331f78c428dd43eea8449b21034b8113d703413d57761b8b9781957b8c0ac1dfe69f492580ca4195f50376ba4a21033400f6afecb833092a9a21cfdf1ed1376e58c5d1f47de74683123987e967a8f42103a6d48b1131e94ba04d9737d61acdaa1322008af9602b3b14862c07a1789aac162102d8b661b0b3302ee2f162b09e07a55ad5dfbe673a9f01d9f0c19617681024306b56ae',
                'sigHashType' => SigHash::ALL | SigHash::ANYONECANPAY,
                'expectedHash' => '2a67f03e63a6a422125878b40b82da593be8d4efaafe88ee528af6e5a9955c6e'
            ],
            [
                'unsignedTx' => '010000000136641869ca081e70f394c6948e8af409e18b619df2ed74aa106c1ca29787b96e0100000000ffffffff0200e9a435000000001976a914389ffce9cd9ae88dcc0631e88a821ffdbe9bfe2688acc0832f05000000001976a9147480a33f950689af511e6e84c138dbbd3c3ee41588ac00000000',
                'inputToSign' => 0,
                'outputValueBtc' => '9.87654321',
                'hashScriptCode' => '56210307b8ae49ac90a048e9b53357a2354b3334e9c8bee813ecb98e99a7e07e8c3ba32103b28f0c28bfab54554ae8c658ac5c3e0ce6e79ad336331f78c428dd43eea8449b21034b8113d703413d57761b8b9781957b8c0ac1dfe69f492580ca4195f50376ba4a21033400f6afecb833092a9a21cfdf1ed1376e58c5d1f47de74683123987e967a8f42103a6d48b1131e94ba04d9737d61acdaa1322008af9602b3b14862c07a1789aac162102d8b661b0b3302ee2f162b09e07a55ad5dfbe673a9f01d9f0c19617681024306b56ae',
                'sigHashType' => SigHash::NONE | SigHash::ANYONECANPAY,
                'expectedHash' => '781ba15f3779d5542ce8ecb5c18716733a5ee42a6f51488ec96154934e2c890a'
            ],
            [
                'unsignedTx' => '010000000136641869ca081e70f394c6948e8af409e18b619df2ed74aa106c1ca29787b96e0100000000ffffffff0200e9a435000000001976a914389ffce9cd9ae88dcc0631e88a821ffdbe9bfe2688acc0832f05000000001976a9147480a33f950689af511e6e84c138dbbd3c3ee41588ac00000000',
                'inputToSign' => 0,
                'outputValueBtc' => '9.87654321',
                'hashScriptCode' => '56210307b8ae49ac90a048e9b53357a2354b3334e9c8bee813ecb98e99a7e07e8c3ba32103b28f0c28bfab54554ae8c658ac5c3e0ce6e79ad336331f78c428dd43eea8449b21034b8113d703413d57761b8b9781957b8c0ac1dfe69f492580ca4195f50376ba4a21033400f6afecb833092a9a21cfdf1ed1376e58c5d1f47de74683123987e967a8f42103a6d48b1131e94ba04d9737d61acdaa1322008af9602b3b14862c07a1789aac162102d8b661b0b3302ee2f162b09e07a55ad5dfbe673a9f01d9f0c19617681024306b56ae',
                'sigHashType' => SigHash::SINGLE | SigHash::ANYONECANPAY,
                'expectedHash' => '511e8e52ed574121fc1b654970395502128263f62662e076dc6baf05c2e6a99b'
            ],
        ];
    }
}
