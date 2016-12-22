<?php

namespace BitWasp\Bitcoin\Chain;

use BitWasp\Bitcoin\Block\Block;
use BitWasp\Bitcoin\Block\BlockHeader;
use BitWasp\Bitcoin\Math\Math;
use BitWasp\Bitcoin\Script\ScriptFactory;
use BitWasp\Bitcoin\Transaction\Factory\TxBuilder;
use BitWasp\Buffertools\Buffer;

class Params implements ParamsInterface
{
    /**
     * @var int
     */
    protected static $maxBlockSizeBytes = 1000000;

    /**
     * @var int
     */
    protected static $maxMoney = 21000000;

    /**
     * @var int
     */
    protected static $subsidyHalvingInterval = 210000;

    /**
     * @var int
     */
    protected static $coinbaseMaturityAge = 120;

    /**
     * @var int
     */
    protected static $p2shActivateTime = 1333238400;

    /**
     * = 14 * 24 * 60 * 60
     * @var int
     */
    protected static $powTargetTimespan = 1209600;

    /**
     * = 10 * 60
     * @var int
     */
    protected static $powTargetSpacing = 600;

    /**
     * @var int
     */
    protected static $powRetargetInterval = 2016;

    /**
     * @var string
     */
    protected static $powTargetLimit = '26959946667150639794667015087019630673637144422540572481103610249215';

    /**
     * Hex: 1d00ffff
     * @var string
     */
    protected static $powBitsLimit = 486604799;

    /**
     * @var int
     */
    protected static $majorityWindow = 1000;

    /**
     * @var int
     */
    protected static $majorityEnforceBlockUpgrade = 750;


    /**
     * @var Math
     */
    protected $math;

    /**
     * @param Math $math
     */
    public function __construct(Math $math)
    {
        $this->math = $math;
    }

    /**
     * @return \BitWasp\Bitcoin\Block\BlockHeaderInterface
     */
    public function getGenesisBlockHeader()
    {
        return new BlockHeader(
            '1',
            Buffer::hex('00', 32),
            Buffer::hex('4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b', 32),
            '1231006505',
            0x1d00ffff,
            '2083236893'
        );
    }

    /**
     * @return \BitWasp\Bitcoin\Block\BlockInterface
     */
    public function getGenesisBlock()
    {
        $timestamp = new Buffer('The Times 03/Jan/2009 Chancellor on brink of second bailout for banks', null, $this->math);
        $publicKey = Buffer::hex('04678afdb0fe5548271967f1a67130b7105cd6a828e03909a67962e0ea1f61deb649f6bc3f4cef38c4f35504e51ec112de5c384df7ba0b8d578a4c702b6bf11d5f', null, $this->math);

        $inputScript = ScriptFactory::create()
            ->push(Buffer::int('486604799', 4, $this->math)->flip())
            ->push(Buffer::int('4', 1, $this->math))
            ->push($timestamp)
            ->getScript();

        $outputScript = ScriptFactory::create()
            ->push($publicKey)
            ->op('OP_CHECKSIG')
            ->getScript();

        return new Block(
            $this->math,
            $this->getGenesisBlockHeader(),
            [
                (new TxBuilder)
                    ->version('1')
                    ->input(new Buffer('', 32), 0xffffffff, $inputScript)
                    ->output(5000000000, $outputScript)
                    ->locktime(0)
                    ->get()
            ]
        );
    }

    /**
     * @return int
     */
    public function maxBlockSizeBytes()
    {
        return static::$maxBlockSizeBytes;
    }

    /**
     * @return int
     */
    public function subsidyHalvingInterval()
    {
        return static::$subsidyHalvingInterval;
    }

    /**
     * @return int
     */
    public function coinbaseMaturityAge()
    {
        return static::$coinbaseMaturityAge;
    }

    /**
     * @return int
     */
    public function maxMoney()
    {
        return static::$maxMoney;
    }

    /**
     * @return int
     */
    public function powTargetTimespan()
    {
        return static::$powTargetTimespan ;
    }

    /**
     * @return int
     */
    public function powTargetSpacing()
    {
        return static::$powTargetSpacing;
    }

    /**
     * @return int
     */
    public function powRetargetInterval()
    {
        return static::$powRetargetInterval;
    }

    /**
     * @return int|string
     */
    public function powTargetLimit()
    {
        return static::$powTargetLimit;
    }

    /**
     * @return int
     */
    public function powBitsLimit()
    {
        return static::$powBitsLimit;
    }

    /**
     * @return int
     */
    public function majorityEnforceBlockUpgrade()
    {
        return static::$majorityEnforceBlockUpgrade;
    }

    /**
     * @return int
     */
    public function majorityWindow()
    {
        return static::$majorityWindow;
    }

    /**
     * @return int
     */
    public function p2shActivateTime()
    {
        return static::$p2shActivateTime;
    }

    /**
     * @return int
     */
    public function getMaxBlockSigOps()
    {
        return $this->maxBlockSizeBytes() / 50;
    }
    
    /**
     * @return int
     */
    public function getMaxTxSigOps()
    {
        return $this->getMaxBlockSigOps() / 5;
    }
}
