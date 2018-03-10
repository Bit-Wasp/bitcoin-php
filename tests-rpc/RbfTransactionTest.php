<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\RpcTest;

use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Crypto\Random\Random;
use BitWasp\Bitcoin\Key\Factory\PrivateKeyFactory;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\Factory\Signer;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Bitcoin\Transaction\TransactionInput;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Utxo\Utxo;

class RbfTransactionTest extends AbstractTestCase
{
    /**
     * @var RegtestBitcoinFactory
     */
    private $rpcFactory;

    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        static $rpcFactory = null;
        if (null === $rpcFactory) {
            $rpcFactory = new RegtestBitcoinFactory();
        }
        $this->rpcFactory = $rpcFactory;
    }

    private function assertSendRawTransaction($result)
    {
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(null, $result['error']);

        $this->assertArrayHasKey('result', $result);
        $this->assertEquals(64, strlen($result['result']));
    }

    private function assertBitcoindError($errorCode, $result)
    {
        $this->assertInternalType('array', $result);
        $this->assertArrayHasKey('error', $result);
        $this->assertInternalType('array', $result['error']);
        $this->assertEquals($errorCode, $result['error']['code']);

        $this->assertArrayHasKey('error', $result);
        $this->assertEquals(null, $result['result']);
    }

    /**
     * @param Utxo[] $utxos
     * @param PrivateKeyInterface[] $privKeys
     * @param TransactionOutput[] $outputs
     * @param array $sequences - sequence to set on inputs
     * @return TransactionInterface
     */
    private function createTransaction(array $utxos, array $privKeys, array $outputs, array $sequences)
    {
        $this->assertEquals(count($utxos), count($privKeys));
        $this->assertEquals(count($utxos), count($sequences));

        // First transaction, spends UTXO 0, to $destSPK 0.25 and $changeSPK 0.2499
        $txBuilder = new TxBuilder();

        $totalIn = 0;
        foreach ($utxos as $i => $utxo) {
            $txid = $utxo->getOutPoint()->getTxId();
            $vout = $utxo->getOutPoint()->getVout();
            $txBuilder->input($txid, $vout, null, $sequences[$i]);
            $totalIn += $utxo->getOutput()->getValue();
        }

        $totalOut = 0;
        foreach ($outputs as $output) {
            $txBuilder->output($output->getValue(), $output->getScript());
            $totalOut += $output->getValue();
        }

        $this->assertGreaterThanOrEqual($totalOut, $totalIn, "TotalIn should be greater than TotalOut");

        $signer = new Signer($txBuilder->get());
        foreach ($utxos as $i => $utxo) {
            $iSigner = $signer
                ->input($i, $utxo->getOutput())
                ->sign($privKeys[$i])
            ;
            $this->assertTrue($iSigner->isFullySigned());
        }

        return $signer->get();
    }

    public function testCanReplaceSingleOutputIfOptin()
    {
        $bitcoind = $this->rpcFactory->startBitcoind();
        $this->assertTrue($bitcoind->isRunning());

        $rng = new Random();
        $factory = PrivateKeyFactory::compressed();
        $destKey = $factory->generate($rng);
        $destSPK = ScriptFactory::scriptPubKey()->p2wkh($destKey->getPubKeyHash());

        $privateKey = $factory->generate($rng);
        $scriptPubKey = ScriptFactory::scriptPubKey()->p2wkh($privateKey->getPubKeyHash());
        $amount = 100000000;

        /** @var Utxo[] $utxos */
        $utxos = [
            $bitcoind->fundOutput($amount, $scriptPubKey),
            $bitcoind->fundOutput($amount, $scriptPubKey),
        ];

        // Part 1: tx[#1: replacable once]
        $tx = $this->createTransaction(
            [$utxos[0]],
            [$privateKey],
            [new TransactionOutput(99999000, $destSPK),],
            [TransactionInput::SEQUENCE_FINAL - 2]
        );

        $result = $bitcoind->makeRpcRequest('sendrawtransaction', [$tx->getHex()]);
        $this->assertSendRawTransaction($result);

        // Part 2: tx[#1: not replaceable | #2 not replaceable]
        $tx = $this->createTransaction(
            $utxos,
            [$privateKey, $privateKey],
            [new TransactionOutput($amount + 99980000, $destSPK),],
            [TransactionInput::SEQUENCE_FINAL - 1, TransactionInput::SEQUENCE_FINAL - 1]
        );

        $result = $bitcoind->makeRpcRequest('sendrawtransaction', [$tx->getHex()]);
        $this->assertSendRawTransaction($result);

        $bitcoind->destroy();
    }

    public function testCannotReplaceIfNotOptin()
    {
        $bitcoind = $this->rpcFactory->startBitcoind();
        $this->assertTrue($bitcoind->isRunning());

        $random = new Random();
        $factory = PrivateKeyFactory::compressed();
        $destKey = $factory->generate($random);
        $destSPK = ScriptFactory::scriptPubKey()->p2wkh($destKey->getPubKeyHash());

        $privateKey = $factory->generate($random);
        $scriptPubKey = ScriptFactory::scriptPubKey()->p2wkh($privateKey->getPubKeyHash());
        $amount = 100000000;

        /** @var Utxo[] $utxos */
        $utxos = [
            $bitcoind->fundOutput($amount, $scriptPubKey),
            $bitcoind->fundOutput($amount, $scriptPubKey),
        ];

        // Part 1: tx[#1: not replacable]
        $tx = $this->createTransaction(
            [$utxos[0]],
            [$privateKey],
            [new TransactionOutput(99990000, $destSPK),],
            [TransactionInput::SEQUENCE_FINAL]
        );

        $result = $bitcoind->makeRpcRequest('sendrawtransaction', [$tx->getHex()]);
        $this->assertSendRawTransaction($result);

        // Part 2: [fail] tx[#1: not replacable | #2 replacable]
        $tx = $this->createTransaction(
            $utxos,
            [$privateKey, $privateKey],
            [new TransactionOutput($amount + 99990000, $destSPK),],
            [TransactionInput::SEQUENCE_FINAL, 0]
        );

        $result = $bitcoind->makeRpcRequest('sendrawtransaction', [$tx->getHex()]);
        $this->assertBitcoindError(RpcServer::ERROR_TX_MEMPOOL_CONFLICT, $result);

        $bitcoind->destroy();
    }

    public function testCanReplaceIfOptin()
    {
        $bitcoind = $this->rpcFactory->startBitcoind();
        $this->assertTrue($bitcoind->isRunning());

        $random = new Random();
        $factory = PrivateKeyFactory::compressed();
        $destKey = $factory->generate($random);
        $destSPK = ScriptFactory::scriptPubKey()->p2wkh($destKey->getPubKeyHash());

        $changeKey = $factory->generate($random);
        $changeSPK = ScriptFactory::scriptPubKey()->p2wkh($changeKey->getPubKeyHash());

        $privateKey = $factory->generate($random);
        $scriptPubKey = ScriptFactory::scriptPubKey()->p2wkh($privateKey->getPubKeyHash());
        $amount = 100000000;

        /** @var Utxo[] $utxos */
        $utxos = [
            $bitcoind->fundOutput($amount, $scriptPubKey),
            $bitcoind->fundOutput($amount, $scriptPubKey),
            $bitcoind->fundOutput($amount, $scriptPubKey),
            $bitcoind->fundOutput($amount, $scriptPubKey),
        ];


        // Part 1: replacable tx[#1: replaceable 2]
        $nIn = 1;
        $tx = $this->createTransaction(
            array_slice($utxos, 0, $nIn),
            array_fill(0, $nIn, $privateKey),
            [
                new TransactionOutput(25000000, $destSPK),
                new TransactionOutput(74990000, $changeSPK),
            ],
            array_fill(0, $nIn, TransactionInput::SEQUENCE_FINAL - 3)
        );

        $result = $bitcoind->makeRpcRequest('sendrawtransaction', [$tx->getHex()]);
        $this->assertSendRawTransaction($result);


        // Part 2: replace tx[#1: replaceable 1 | #2: replaceable 1]
        $nIn = 2;
        $tx = $this->createTransaction(
            array_slice($utxos, 0, $nIn),
            array_fill(0, $nIn, $privateKey),
            [
                new TransactionOutput(25000000, $destSPK),
                new TransactionOutput($amount + 74950000, $changeSPK),
            ],
            array_fill(0, $nIn, TransactionInput::SEQUENCE_FINAL - 2)
        );

        $result = $bitcoind->makeRpcRequest('sendrawtransaction', [$tx->getHex()]);
        $this->assertSendRawTransaction($result);


        // Part 3: replace tx[#1: replaceable 0 | #2: replaceable 0 | #3: replaceable 0]
        $nIn = 3;
        $tx = $this->createTransaction(
            array_slice($utxos, 0, $nIn),
            array_fill(0, $nIn, $privateKey),
            [
                new TransactionOutput(25000000, $destSPK),
                new TransactionOutput((2 * $amount) + 74920000, $changeSPK),
            ],
            array_fill(0, $nIn, TransactionInput::SEQUENCE_FINAL - 1)
        );

        $result = $bitcoind->makeRpcRequest('sendrawtransaction', [$tx->getHex()]);
        $this->assertSendRawTransaction($result);


        // Part 4: this one won't work, inputs are all irreplacable
        $nIn = 4;
        $tx = $this->createTransaction(
            array_slice($utxos, 0, $nIn),
            array_fill(0, $nIn, $privateKey),
            [
                new TransactionOutput(25000000, $destSPK),
                new TransactionOutput((3 * $amount) + 74900000, $changeSPK),
            ],
            array_fill(0, $nIn, TransactionInput::SEQUENCE_FINAL)
        );

        $result = $bitcoind->makeRpcRequest('sendrawtransaction', [$tx->getHex()]);
        $this->assertBitcoindError(RpcServer::ERROR_TX_MEMPOOL_CONFLICT, $result);

        $bitcoind->destroy();
    }
}
