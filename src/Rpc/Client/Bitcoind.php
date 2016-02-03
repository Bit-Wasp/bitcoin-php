<?php

namespace BitWasp\Bitcoin\Rpc\Client;

use BitWasp\Bitcoin\Address\AddressInterface;
use BitWasp\Bitcoin\Amount;
use BitWasp\Bitcoin\Block\BlockFactory;
use BitWasp\Bitcoin\JsonRpc\JsonRpcClient;
use BitWasp\Bitcoin\Crypto\EcAdapter\Key\PrivateKeyInterface;
use BitWasp\Bitcoin\Network\NetworkInterface;
use BitWasp\Bitcoin\Script\Script;
use BitWasp\Bitcoin\Transaction\OutPoint;
use BitWasp\Bitcoin\Transaction\SignatureHash\SigHash;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Bitcoin\Transaction\TransactionOutput;
use BitWasp\Bitcoin\Utxo\Utxo;
use BitWasp\Buffertools\Buffer;

class Bitcoind
{
    /**
     * @var JsonRpcClient
     */
    private $client;

    /**
     * @param JsonRpcClient $client
     */
    public function __construct(JsonRpcClient $client)
    {
        $this->client = $client;
    }

    /**
     * @return JsonRpcClient
     */
    public function getRpcClient()
    {
        return $this->client;
    }

    /**
     * @param string $result
     * @throws \Exception
     */
    private function checkNotNull($result)
    {
        if (null === $result) {
            throw new \Exception('Received no response from server');
        }
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getinfo()
    {
        $info = $this->client->execute('getinfo');
        $this->checkNotNull($info);
        return $info;
    }

    /**
     * @return int
     */
    public function getblockcount()
    {
        $count = $this->client->execute('getblockcount');
        return $count;
    }

    /**
     * @return string
     */
    public function getbestblockhash()
    {
        $hash = $this->client->execute('getbestblockhash');
        return $hash;
    }

    /**
     * @param int|string $blockHeight
     * @return string
     */
    public function getblockhash($blockHeight)
    {
        $hash = $this->client->execute('getblockhash', [$blockHeight]);
        return $hash;
    }

    /**
     * @param string $blockhash
     * @return \BitWasp\Bitcoin\Block\Block
     */
    public function getblock($blockhash)
    {
        $blockArray = $this->client->execute('getblock', [$blockhash, false]);
        $this->checkNotNull($blockArray);

        return BlockFactory::fromHex($blockArray);
    }

    /**
     * @param string $txid
     * @param bool $verbose
     * @return TransactionInterface
     * @throws \Exception
     */
    public function getrawtransaction($txid, $verbose = false)
    {
        $tx = $this->client->execute('getrawtransaction', [$txid]);
        $this->checkNotNull($tx);

        if ($verbose) {
            $tx = TransactionFactory::fromHex($tx);
        }

        return $tx;
    }

    /**
     * @param $inputs
     * @param $outputs
     * @return mixed
     * @throws \Exception
     */
    public function createrawtransaction($inputs, $outputs)
    {
        $tx = $this->client->execute('createrawtransaction', [$inputs, $outputs]);
        $this->checkNotNull($tx);
        return $tx;
    }

    /**
     * @param TransactionInterface $tx
     * @param array $inputs
     * @param array $privateKeys
     * @param int $sighash
     * @param NetworkInterface $network
     * @return \BitWasp\Bitcoin\Transaction\Transaction
     * @throws \Exception
     */
    public function signrawtransaction(
        TransactionInterface $tx,
        array $inputs = [],
        array $privateKeys = [],
        $sighash = SigHash::ALL,
        NetworkInterface $network = null
    ) {
        $tx = $this->client->execute('signrawtransaction', [
            $tx->getHex(),
            $inputs,
            array_map(
                function (PrivateKeyInterface $privateKey) use ($network) {
                    return $privateKey->toWif($network);
                },
                $privateKeys
            ),
            $sighash
        ]);

        $this->checkNotNull($tx);
        return TransactionFactory::fromHex($tx['hex']);
    }

    /**
     * @param TransactionInterface $transaction
     * @param bool $allowExtremeFees
     * @return string
     */
    public function sendrawtransaction(TransactionInterface $transaction, $allowExtremeFees = false)
    {
        $send = $this->client->execute('sendrawtransaction', [$transaction->getHex(), $allowExtremeFees]);
        $this->checkNotNull($send);
        return $send;
    }

    /**
     * @param int $minConfirms
     * @param int $maxConfirms
     * @param array $addresses
     * @param NetworkInterface $network
     * @return array
     */
    public function listunspent($minConfirms = 1, $maxConfirms = 9999999, array $addresses = [], NetworkInterface $network = null)
    {
        $amount = new Amount();

        $results = $this->client->execute(
            'listunspent',
            [
                $minConfirms,
                $maxConfirms,
                array_map(
                    function (AddressInterface $address) use ($network) {
                        return $address->getAddress($network);
                    },
                    $addresses
                )
            ]
        );

        return array_map(
            function (array $value) use ($amount) {
                return new Utxo(
                    new OutPoint(
                        Buffer::hex($value['txid'], 32),
                        $value['vout']
                    ),
                    new TransactionOutput(
                        $amount->toSatoshis($value['amount']),
                        new Script(Buffer::hex($value['scriptPubKey']))
                    )
                );
            },
            $results
        );
    }
}
