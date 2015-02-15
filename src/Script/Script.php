<?php

namespace Afk11\Bitcoin\Script;

use Bitcoin\Bitcoin;
use Bitcoin\Buffer;
use Bitcoin\Parser;

use \Afk11\Bitcoin\Crypto\Hash;
use \Afk11\Bitcoin\Key\PublicKeyInterface;

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
        'OP_0NOTEQUAL' => 146,
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
        'OP_MAX' => 164,
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

    /**
     * @var array
     */
    private $rOpCodes;

    /**
     * @var null|string
     */
    protected $script = null;

    /**
     * Initialize container
     *
     * @param Buffer $script
     */
    public function __construct(Buffer $script = null)
    {
        $this->set($script);
        $this->setRegisteredOpCodes();
        return $this;
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
        $script->push($public_key->serialize('hex'))->op('OP_CHECKSIG');

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
        $hash = $public_key->getPubKeyHash();

        $script = new self();
        $script->op('OP_DUP')->op('OP_HASH160')->push($hash)->op('OP_EQUALVERIFY');

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
        $new_script->op('OP_HASH160')->push($hash)->op('OP_EQUAL');
        return $new_script;
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
     * Get all opcodes (OP_X => opcode)
     *
     * @return array
     */
    public function getOpCodes()
    {
        return $this->opCodes;
    }

    /**
     * When given a text 'OP_X' will return the opcode.
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
     * @param $code
     * @return $this
     */
    public function op($code)
    {
        if (!isset($this->opCodes[$code])) {
            throw new \RuntimeException('Invalid script opcode encountered: '.$code);
        }

        $op = $this->opCodes[$code];
        $this->script .= pack("H*", (Bitcoin::getMath()->decHex($op)));
        return $this;
    }

    /**
     * @param $code
     * @return $this
     */
    public function rOp($code)
    {
        if (!isset($this->rOpCodes[$code])) {
            throw new \RuntimeException('Invalid script opcode encountered: '.$code);
        }

        $this->script .= chr($code);
        return $this;
    }

    /**
     * Push data into the stack.
     *
     * @param $data
     * @return $this
     * @throws \Exception
     */
    public function push($data)
    {
        if (!$data instanceof Buffer) {
            $data = Buffer::hex($data);
        }

        $length = $data->getSize();
        $parsed = new Parser();

        /** Note that larger integers are serialized without flipping bits - Big endian */

        if ($length < $this->getOpCode('OP_PUSHDATA1')) {
            $parsed = $parsed->writeWithLength($data);

        } elseif ($length <= 0xff) {
            $parsed->writeInt(1, $this->getOpCode('OP_PUSHDATA1'))
                ->writeInt(1, $length, false)
                ->writeBytes($length, $data);

        } elseif ($length <= 0xffff) {
            $parsed->writeInt(1, $this->getOpCode('OP_PUSHDATA2'))
                ->writeInt(2, $length, true)
                ->writeBytes($length, $data);

        } else {
            $parsed->writeInt(1, $this->getOpCode('OP_PUSHDATA4'))
                ->writeInt(4, $length, true)
                ->writeBytes($length, $data);
        }

        $this->script .=  $parsed->getBuffer()->serialize();
        return $this;
    }

    /**
     * Parse a script into opcodes and Buffers of data
     *
     * @return array
     */
    public function parse()
    {
        $data = array();

        // Load script as a byte string
        $script = $this->serialize('hex');
        $parser = new Parser($script);

        while ($op = $parser->readBytes(1)) {
            $opCode = $op->serialize('int');

            if ($opCode < 1) {
                // False, or OP_0
                $push = Buffer::hex('00');

            } elseif ($opCode < 75) {
                // When < 75, this opCode is the length of the following string
                $push = $parser->readBytes($opCode);

            } elseif ($opCode <= 78) {
                // Each pushdata opcode is followed by the length of the string.
                // The number of bytes which encode the length change with the opcode.
                if ($opCode == $this->getOpCode('OP_PUSHDATA1')) {
                    $lengthOfLen = 1;
                } elseif ($opCode == $this->getOpCode('OP_PUSHDATA2')) {
                    $lengthOfLen = 2;
                } else {
                    // ($opCode == $this->getOpCode('OP_PUSHDATA4')) {
                    $lengthOfLen = 4;
                }

                $length = $parser->readBytes($lengthOfLen, true)->serialize('int');
                $push   = $parser->readBytes($length);

            } else {
                // None of these pushdatas, so just an opcode
                if (isset($this->rOpCodes[$opCode])) {
                    $push = $this->rOpCodes[$opCode];
                } else {
                    throw new \RuntimeException('Unknown opcode: '. $opCode);
                }
            }

            $data[] = $push;

        }
        return $data;

    }

    /**
     * When given a Buffer or hex string, set the script to be this.
     *
     * @param $scriptData
     * @return $this
     */
    public function set($scriptData)
    {
        if ($scriptData instanceof Buffer) {
            $this->script = $scriptData->serialize();
        } else {
            $this->script = pack("H*", $scriptData);
        }
        return $this;
    }

    /**
     * Return a human readable representation of the Script Opcodes, and data being
     * pushed to the stack
     *
     * @return string
     */
    public function getAsm()
    {
        $result = array();
        $parse  = $this->parse();

        foreach ($parse as $item) {
            if ($item instanceof Buffer) {
                // Buffer
                $result[] = $item->serialize('hex');
            } else {
                // Opcode
                $result[] = $item;

            }
        }

        return implode(" ", $result);
    }

    /**
     * Return a buffer containing the hash of this script.
     *
     * @return \Bitcoin\Buffer
     */
    public function getScriptHash()
    {
        $hex  = $this->serialize('hex');
        $hash = Hash::sha256ripe160($hex, true);

        $buffer = new Buffer($hash);
        return $buffer;
    }



    /**
     * Return a varInt, based on the size of the script.
     *
     * @return string
     * @throws \Exception
     */
    public function getVarInt()
    {
        $size   = $this->getSize();
        $varInt = Parser::numToVarInt($size);
        return $varInt;
    }

    /**
     * Return the script as a printable string
     *
     * @return string
     */
    public function __toString()
    {
        return bin2hex($this->script);
    }

    /**
     * Serialize the script into a hex string or a byte string
     *
     * @param null $type
     * @return mixed|string
     */
    public function serialize($type = null)
    {
        if ($type == 'hex') {
            $data = $this->__toString();
        } else {
            $data = $this->script;
        }

        return $data;
    }

    /**
     * Return the size of the script - for either a binary string (number of bytes - default)
     * or a hex string (twice the byte length)
     *
     * @param null $type
     * @return int
     */
    public function getSize($type = null)
    {
        if ($type == 'hex') {
            $size = strlen($this->__toString());
        } else {
            $size = strlen($this->script);
        }

        return $size;
    }

    /**
     * Return the object as an array
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'hex' => $this->serialize('hex'),
            'asm' => $this->getAsm()
        );
    }
}
