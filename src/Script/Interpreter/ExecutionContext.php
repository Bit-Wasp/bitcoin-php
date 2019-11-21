<?php declare(strict_types=1);

namespace BitWasp\Bitcoin\Script\Interpreter;

use BitWasp\Buffertools\BufferInterface;

class ExecutionContext
{
    const UNEXECUTED_CODE_SEPARATOR_POSITION = 0xffffffff;

    /**
     * 0xffffffff if not set, the lastCodeSepPosition otherwise
     * @var int
     */
    private $codeSepPosition = self::UNEXECUTED_CODE_SEPARATOR_POSITION;

    /**
     * Ensures Annex codepath was run
     * @var bool
     */
    private $annexInit = false;

    /**
     * Null or a 32 byte hash
     * @var null|BufferInterface
     */
    private $annexHash;

    /**
     * Null or a 32 byte hash
     * @var null|BufferInterface
     */
    private $tapLeafHash;

    /**
     * Serialization weight of witness for tapscript. Is decreased
     * with each checksig opcode.
     * @var int
     */
    private $validationWeightLeft;

    public function setAnnexHash(BufferInterface $annexHash)
    {
        $this->annexHash = $annexHash;
    }

    public function setAnnexCheckDone()
    {
        $this->annexInit = true;
    }

    public function isAnnexCheckDone(): bool
    {
        return $this->annexInit;
    }

    public function hasAnnex(): bool
    {
        return null !== $this->annexHash;
    }

    /**
     * @return BufferInterface|null
     */
    public function getAnnexHash()
    {
        return $this->annexHash;
    }

    public function setTapLeafHash(BufferInterface $leafHash)
    {
        $this->tapLeafHash = $leafHash;
    }

    public function hasTapLeaf(): bool
    {
        return null !== $this->tapLeafHash;
    }

    /**
     * @return BufferInterface|null
     */
    public function getTapLeafHash()
    {
        return $this->tapLeafHash;
    }

    public function setCodeSeparatorPosition(int $codeSepPos)
    {
        $this->codeSepPosition = $codeSepPos;
    }

    public function getCodeSeparatorPosition(): int
    {
        return $this->codeSepPosition;
    }

    public function hasValidationWeightSet(): bool
    {
        return null !== $this->validationWeightLeft;
    }

    /**
     * @return null|int
     */
    public function getValidationWeightLeft()
    {
        return $this->validationWeightLeft;
    }

    public function setValidationWeightLeft(int $weight)
    {
        $this->validationWeightLeft = $weight;
    }
}
