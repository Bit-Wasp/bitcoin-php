<?php

namespace BitWasp\Bitcoin\Serializer\Transaction;

use BitWasp\Bitcoin\Bitcoin;
use BitWasp\Bitcoin\Collection\Transaction\TransactionInputCollection;
use BitWasp\Bitcoin\Collection\Transaction\TransactionOutputCollection;
use BitWasp\Bitcoin\Collection\Transaction\TransactionWitnessCollection;
use BitWasp\Bitcoin\Serializer\Script\ScriptWitnessSerializer;
use BitWasp\Bitcoin\Transaction\Transaction;
use BitWasp\Bitcoin\Transaction\TransactionInterface;
use BitWasp\Buffertools\Buffer;
use BitWasp\Buffertools\ByteOrder;
use BitWasp\Buffertools\Parser;
use BitWasp\Buffertools\TemplateFactory;
use BitWasp\Buffertools\Types\Int32;
use BitWasp\Buffertools\Types\Int8;
use BitWasp\Buffertools\Types\Uint32;
use BitWasp\Buffertools\Types\VarInt;
use BitWasp\Buffertools\Types\Vector;

class NTransactionSerializer
{
    /**
     *
     */
    public function __construct()
    {
        $this->inputSerializer = new TransactionInputSerializer(new OutPointSerializer());
        $this->outputSerializer = new TransactionOutputSerializer;
        $this->witnessSerializer = new ScriptWitnessSerializer();
    }

    public function vinParser()
    {
        return (new TemplateFactory())
            ->vector(function (Parser $parser) {
                return $this->inputSerializer->fromParser($parser);
            })
            ->getTemplate();
    }

    public function voutParser()
    {
        return (new TemplateFactory())
            ->vector(function (Parser $parser) {
                return $this->outputSerializer->fromParser($parser);
            })
            ->getTemplate();
    }

    public function vwitParser($inCount)
    {
        return (new TemplateFactory())
            ->vector(function (Parser $parser) use ($inCount) {
                return $this->witnessSerializer->fromParser($parser, $inCount);
            })
            ->getTemplate();
    }

    public function fromParser(Parser $parser)
    {
        $math = Bitcoin::getMath();
        $int32le = new Int32($math, ByteOrder::LE);
        $uint32le = new Uint32($math, ByteOrder::LE);
        $varint = new VarInt($math, ByteOrder::LE);
        $vinParser = $this->vinParser();

        $version = $int32le->read($parser);
        list ($vin) = $vinParser->parse($parser);

        $vout = [];
        $flags = 0;
        if (count($vin) == 0) {
            $flags = (int) $varint->read($parser);
            if ($flags != 0) {
                $vinCount = $varint->read($parser);
                for ($i = 0; $i < $vinCount; $i++) {
                    $vin[] = $this->inputSerializer->fromParser($parser);
                }

                $voutCount = $varint->read($parser);
                for ($i = 0; $i < $voutCount; $i++) {
                    $vout[] = $this->outputSerializer->fromParser($parser);
                }
            }
        } else {
            $voutCount = $varint->read($parser);
            for ($i = 0; $i < $voutCount; $i++) {
                $vout[] = $this->outputSerializer->fromParser($parser);
            }
        }

        $vwit = [];
        if (($flags & 1)) {
            echo "Check flags for witness: " . ($flags & 1 ? 'yes' : 'no') . PHP_EOL;
            $flags ^= 1;
            $witCount = count($vin);
            for ($i = 0; $i < $witCount; $i++) {
                echo "parse a witness structure\n";
                $vectorCount = $varint->read($parser);
                $vwit[] = $this->witnessSerializer->fromParser($parser, $vectorCount);
            }
        }

        if ($flags) {
            throw new \RuntimeException('Flags byte was 0');
        }

        $lockTime = $uint32le->read($parser);

        return new Transaction(
            $version,
            new TransactionInputCollection($vin),
            new TransactionOutputCollection($vout),
            new TransactionWitnessCollection($vwit),
            $lockTime
        );
    }

    public function parse($data)
    {
        return $this->fromParser(new Parser($data));
    }

    public function serialize(TransactionInterface $transaction)
    {
        $math = Bitcoin::getMath();
        $int8le = new Int8($math, ByteOrder::LE);
        $int32le = new Int32($math, ByteOrder::LE);
        $uint32le = new Uint32($math, ByteOrder::LE);
        $varint = new VarInt($math, ByteOrder::LE);
        $vector = new Vector($varint, function () {
        });

        $binary = $int32le->write($transaction->getVersion());

        $flags = 0;
        if (!$transaction->getWitnesses()->isNull()) {
            $flags |= 1;
        }

        if ($flags) {
            $binary .= $int8le->write(0);
            $binary .= $int8le->write($flags);
        }

        $binary .= $vector->write($transaction->getInputs()->all());
        $binary .= $vector->write($transaction->getOutputs()->all());

        if ($flags & 1) {
            $binary .= $vector->write($transaction->getWitnesses()->all());
        }

        $binary .= $uint32le->write($transaction->getLockTime());

        return new Buffer($binary);
    }
}
