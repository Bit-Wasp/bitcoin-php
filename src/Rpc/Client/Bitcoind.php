<?php

namespace BitWasp\Bitcoin\Rpc\Client;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Block\Block;
use BitWasp\Bitcoin\Block\BlockFactory;
use BitWasp\Bitcoin\Block\BlockHeader;
use BitWasp\Bitcoin\Transaction\TransactionCollection;
use BitWasp\Buffertools\Buffer;
use BitWasp\Bitcoin\JsonRpc\JsonRpcClient;
use BitWasp\Bitcoin\Transaction\TransactionFactory;
use BitWasp\Bitcoin\Transaction\TransactionInterface;

class Bitcoind
{
    /**
     * @var JsonRpcClient
     */
    protected $client;

    /**
     * @param JsonRpcClient $client
     */
    public function __construct(JsonRpcClient $client)
    {
        $this->client = $client;
    }

    private function checkNotNull($result)
    {
        if (null === $result) {
            throw new \Exception('Received no response from server');
        }
    }

    public function getinfo()
    {
        $info = $this->client->execute('getinfo');
        $this->checkNotNull($info);

        return $info;
    }

    /**
     * @return mixed
     */
    public function getbestblockhash()
    {
        $hash = $this->client->execute('getbestblockhash');
        return $hash;
    }

    /**
     * @param $blockHeight
     * @return mixed
     */
    public function getblockhash($blockHeight)
    {
        $hash = $this->client->execute('getblockhash', array($blockHeight));
        return $hash;
    }

    /**
     * @param $blockhash
     * @return \BitWasp\Bitcoin\Block\Block
     */
    public function getblock($blockhash)
    {
        $blockArray = $this->client->execute('getblock', array($blockhash, true));
        $this->checkNotNull($blockArray);

        // Establish batch query for loading transactions
        $txs = [];
        if (count($blockArray['tx']) > 0) {
            $this->client->batch();
            foreach ($blockArray['tx'] as $txid) {
                $this->client->execute('getrawtransaction', array($txid));
            }
            $result = $this->client->send();
            $this->checkNotNull($result);

            // Build the transactions
            $txs = array_map(
                function ($value) {
                    return TransactionFactory::fromHex($value);
                },
                $result
            );
        }

        // Build block header$
        $header = new BlockHeader(
            $blockArray['version'],
            @$blockArray['previousblockhash'],
            $blockArray['merkleroot'],
            $blockArray['time'],
            Buffer::hex($blockArray['bits']),
            $blockArray['nonce']
        );

        if (isset($blockArray['nextblockhash'])) {
            $header->setNextBlock($blockArray['nextblockhash']);
        }

        return new Block(
            Bitcoin::getMath(),
            $header,
            new TransactionCollection($txs)
        );
    }

    /**
     * @param $txid
     * @param bool $verbose
     * @return TransactionInterface
     * @throws \Exception
     */
    public function getrawtransaction($txid, $verbose = false)
    {
        $tx = $this->client->execute('getrawtransaction', array($txid));
        $this->checkNotNull($tx);

        if ($verbose) {
            $tx = TransactionFactory::fromHex($tx);
        }

        return $tx;
    }

    /**
     * @param TransactionInterface $transaction
     * @param bool $allowExtremeFees
     * @return mixed
     */
    public function sendrawtransaction(TransactionInterface $transaction, $allowExtremeFees = false)
    {
        $hex = $transaction->getBuffer()->getHex();
        $send = $this->client->execute('sendrawtransaction', array($hex, $allowExtremeFees));
        return $send;
    }
}
