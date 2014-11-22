<?php

namespace Bitcoin;

/**
 * Class Script
 * @package Bitcoin
 */
class Script implements ScriptInterface
{

    /**
     * @var array
     */
    public $opCodes = array(
        // Constants
        'OP_0'    => 0,
        'OP_PUSHDATA1' => 76,
        'OP_PUSHDATA2' => 77,
        'OP_PUSHDATA4' => 78,
        'OP_1NEGATE' => 79,
        'OP_1' => 81,
        'OP_2' => 82,
        'OP_3' => 83,
        'OP_4' => 84,
        'OP_5' => 85,
        'OP_6' => 86,
        'OP_7' => 87,
        'OP_8' => 88,
        'OP_9' => 89,
        'OP_10' => 90,
        'OP_11' => 91,
        'OP_12' => 92,
        'OP_13' => 93,
        'OP_14' => 94,
        'OP_15' => 95,
        'OP_16' => 96,

        // Flow Control
        'OP_NOP' => 97,
        'OP_IF' => 99,
        'OP_NOTIF' => 100,
        'OP_ELSE' => 103,
        'OP_ENDIF' => 104,
        'OP_VERIFY' => 105,
        'OP_RETURN' => 106,
        'OP_TOALTSTACK' => 107,
        'OP_FROMALTSTACK' => 108,
        'OP_IFDUP' => 115,
        'OP_DEPTH' => 116,
        'OP_DROP' => 117,
        'OP_DUP' => 118,
        'OP_NIP' => 119,
        'OP_OVER' => 120,
        'OP_PICK' => 121,
        'OP_ROLL' => 122,
        'OP_ROT' => 123,
        'OP_SWAP' => 124,
        'OP_TUCK' => 125,
        'OP_2DROP' => 109,
        'OP_2DUP' => 110,
        'OP_3DUP' => 111,
        'OP_2OVER' => 112,
        'OP_2ROT' => 113,
        'OP_2SWAP' => 114,

        // Splice
        'OP_CAT' => 126,                    /* disabled */
        'OP_SUBSTR' => 127,                 /* disabled */
        'OP_LEFT' => 128,                   /* disabled */
        'OP_RIGHT' => 129,                  /* disabled */
        'OP_SIZE' => 130,                   /* disabled */

        // Bitwise logic
        'OP_INVERT' => 131,                 /* Disabled */
        'OP_AND' => 132,                    /* Disabled */
        'OP_OR' => 133,                     /* Disabled */
        'OP_XOR' => 134,                    /* Disabled */
        'OP_EQUAL' => 135,
        'OP_EQUALVERIFY' => 136,

        // Arithmetic
        'OP_1ADD' => 139,
        'OP_1SUB' => 140,
        'OP_2MUL' => 141,
        'OP_2DIV' => 142,
        'OP_NEGATE' => 143,
        'OP_ABS' => 144,
        'OP_NOT' => 145,
        'OP_0NOTEQUAL' => 156,
        'OP_ADD' => 147,
        'OP_SUB' => 148,
        'OP_MUL' => 149,
        'OP_DIV' => 150,
        'OP_MOD' => 151,
        'OP_LSHIFT' => 152,
        'OP_RSHIFT' => 153,
        'OP_BOOLAND' => 154,
        'OP_BOOLOR' => 155,
        'OP_NUMEQUAL' => 156,
        'OP_NUMEQUALVERIFY' => 157,
        'OP_NUMNOTEQUAL' => 158,
        'OP_LESSTHAN' => 159,
        'OP_GREATERTHAN' => 160,
        'OP_LESSTHANOREQUAL' => 161,
        'OP_GREATERTHANOREQUAL' => 162,
        'OP_MIN' => 163,
        'OP_WITHIN' => 165,

        // Crypto
        'OP_RIPEMD160' => 166,
        'OP_SHA1' => 167,
        'OP_SHA256' => 168,
        'OP_HASH160' => 169,
        'OP_HASH256' => 170,
        'OP_CODESEPARATOR' => 171,
        'OP_CHECKSIG' => 172,
        'OP_CHECKSIGVERIFY' => 173,
        'OP_CHECKMULTISIG' => 174,
        'OP_CHECKMULTISIGVERIFY' => 175,

        // Pseudo Words
        'OP_PUBKEYHASH' => 253,
        'OP_PUBKEY' => 254,
        'OP_INVALIDOPCODE' => 255,

        // Reserved
        // So are any which are not assigned
        'OP_RESERVED' => 80,
        'OP_VER' => 98,
        'OP_VERIF' => 101,
        'OP_VERNOTIF' => 102,
        'OP_RESERVED1' => 137,
        'OP_RESERVED2' => 138,
        'OP_NOP1' => 176,
        'OP_NOP2' => 177,
        'OP_NOP3' => 178,
        'OP_NOP4' => 179,
        'OP_NOP5' => 180,
        'OP_NOP6' => 181,
        'OP_NOP7' => 182,
        'OP_NOP8' => 183,
        'OP_NOP9' => 184,
        'OP_NOP10' => 185,
    );

    private $rOpCodes;

    /**
     * Initialize container
     */
    public function __construct()
    {
        $this->script = '';
        $this->setRegisteredOpCodes();

    }

    /**
     * Set up registered Op Codes
     */
    public function setRegisteredOpCodes()
    {
        foreach ($this->opCodes as $key => $codeNum) {
            $this->rOpCodes[$codeNum] = $key;
        }
    }

    /**
     * Get a list of op codes: opCode => OP_X
     *
     * @return array
     */
    public function getRegisteredOpCodes()
    {
        return $this->rOpCodes;
    }

    /**
     * When given an $opCode, returns OP_X
     *
     * @param $opCode
     * @return mixed
     * @throws \Exception
     */
    public function getRegisteredOpCode($opCode)
    {
        if (!isset($this->rOpCodes[$opCode])) {
            throw new \Exception('Script op byte '. $opCode . ' not found');
        }

        return $this->rOpCodes[$opCode];
    }

    /**
     * Get Registered opcode (indexed by decimal opcode)
     *
     * @return array
     */
    public function getOpCodes()
    {
        return $this->opCodes;
    }

    /**
     * When given a text 'OP_X' will return the op code.
     * @param $code
     * @return mixed
     * @throws \Exception
     */
    public function getOpCode($code)
    {
        if (!isset($this->opCodes[$code])) {
            throw new \Exception('Script opcode '. $code . ' not found');
        }

        return $this->opCodes[$code];
    }


    /**
     * Add an opcode to the script
     *
     * @param $opCode
     * @throws ScriptRuntimeException
     * @return $this
     */
    public function op($opCode)
    {
        $code = 'OP_' . $opCode;

        if (!$this->opCodes[$code]) {
            throw new ScriptRuntimeException('Invalid script opcode encountered: '.$opCode);
        }

        $op = $this->opCodes[$code];
        $this->script .= hex2bin((Math::decHex($op)));
        return $this;
    }

    /**
     * Push data into the stack
     *
     * @param $data
     * @return $this
     * @throws \Exception
     */
    public function push($data)
    {
        $bin    = hex2bin($data);
        $varInt = self::numToVarInt(strlen($bin));
        $string = $varInt . $bin;

        $this->script .=  $string;
        return $this;
    }

    /**
     * Parse a script into opcodes and Buffers of data
     * @return array
     */
    public function parse() {
        $pos = 0;
        $data = array();

        // Load script as a byte string
        $script = $this->serialize();
        $scriptLen = strlen($script);

        while ($pos < $scriptLen) {
            // Load decimal opcode
            $hexOp = bin2hex(substr($script, $pos, 1));
            $opCode = Math::hexDec($hexOp);
            $pos += 1;

            if ($opCode < 1) {
                // False, or OP_0
                $push = Buffer::hex('');

            } else if ($opCode < 75) {
                // When < 75, this opCode is the length of the following string
                $push = new Buffer(substr($script, $pos, $opCode));
                $pos += $opCode;

            } else if ($opCode <= 78) {
                // Get length of following string
                $lenLen = 2 ^ ($opCode - 76);
                $len = Math::hexDec(substr($script, $pos, $lenLen));
                $pos += $len;

                $push = new Buffer(substr($script, $pos, ($pos + $len)));
                $pos += $len;

            } else {
                // None of these pushdatas, so just an opcode
                if (isset($this->rOpCodes[$opCode])) {
                    $push = $this->rOpCodes[$opCode];
                } else {
                    $push = "[unknown:$opCode]";
                }
            }

            $data[] = $push;
        }
        return $data;

    }

    /**
     * @return string
     */
    public function getAsm()
    {
        $result = array();
        $parse  = $this->parse();

        foreach ($parse as $item) {
            if ($item instanceof Buffer) {
                $result[] = $item->serialize('hex');
                // Buffer
            } else {
                $result[] = $item;
                // Opcode
            }
        }

        return implode(" ", $result);
    }

    /**
     * Create a Pay to pubkey output
     *
     * @param PublicKeyInterface $public_key
     * @return Script
     */
    public static function payToPubKey(PublicKeyInterface $public_key)
    {
        $script = new self();
        $script->push($public_key->getHex())->op('CHECKSIG');

        return $script;
    }

    /**
     * Create a P2PKH output script
     *
     * @param PublicKeyInterface $public_key
     * @return Script
     */
    public static function payToPubKeyHash(PublicKeyInterface $public_key)
    {
        $hash = Hash::sha256ripe160($public_key->getHex());

        $script = new self();
        $script->op('DUP')->op('HASH160')->push($hash)->op('EQUALVERIFY');

        return $script;
    }

    /**
     * Create a P2SH output script
     *
     * @param Script $script
     * @return Script
     */
    public static function payToScriptHash(Script $script)
    {
        $script_hex = $script->serialize('hex');
        $hash = Hash::sha256ripe160($script_hex);

        $new_script = new self();
        $new_script->op('HASH160')->push($hash)->op('EQUAL');
        return $new_script;
    }

    /**
     * Flip byte order of this string
     *
     * @param $hex
     * @return string
     */
    public static function flipBytes($hex)
    {
        return implode('', array_reverse(str_split($hex, 2)));
    }

    /**
     * Convert a decimal number into a VarInt
     *
     * @param $decimal
     * @return string
     * @throws \Exception
     */
    public static function numToVarInt($decimal)
    {
        if ($decimal < 0xfd) {
            return chr($decimal);

        } elseif ($decimal > 0xffffffffffffffff) {
            throw new \Exception('numToVarInt(): Integer too large');

        } else {

            // Loop through
            foreach (array(2, 4, 8) as $j => $numBytes) {
                $uint_max = Math::pow(16, $numBytes);

                if ($decimal <= $uint_max) {
                    $prefix = 0xfe + $j;
                    return hex2bin($prefix) . decbin($decimal);
                }
            }
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return bin2hex($this->script);
    }

    /**
     * @param null $type
     * @return mixed|string
     */
    public function serialize($type = null)
    {
        if ($type == 'hex') {
            return $this->__toString();
        } else {
            return $this->script;
        }
    }

    /**
     * @param null $type
     * @return int
     */
    public function getSize($type = null)
    {
        $data = $this->serialize($type);
        $size = strlen($data);
        return $size;
    }
} 