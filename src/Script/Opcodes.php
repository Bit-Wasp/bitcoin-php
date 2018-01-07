<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Script;

class Opcodes implements \ArrayAccess
{

    const OP_0 = 0;
    const OP_PUSHDATA1 = 76;
    const OP_PUSHDATA2 = 77;
    const OP_PUSHDATA4 = 78;
    const OP_1NEGATE = 79;
    const OP_1 = 81;
    const OP_2 = 82;
    const OP_3 = 83;
    const OP_4 = 84;
    const OP_5 = 85;
    const OP_6 = 86;
    const OP_7 = 87;
    const OP_8 = 88;
    const OP_9 = 89;
    const OP_10 = 90;
    const OP_11 = 91;
    const OP_12 = 92;
    const OP_13 = 93;
    const OP_14 = 94;
    const OP_15 = 95;
    const OP_16 = 96;

    // Flow Control
    const OP_NOP = 97;
    const OP_IF = 99;
    const OP_NOTIF = 100;
    const OP_ELSE = 103;
    const OP_ENDIF = 104;
    const OP_VERIFY = 105;
    const OP_RETURN = 106;
    const OP_TOALTSTACK = 107;
    const OP_FROMALTSTACK = 108;
    const OP_IFDUP = 115;
    const OP_DEPTH = 116;
    const OP_DROP = 117;
    const OP_DUP = 118;
    const OP_NIP = 119;
    const OP_OVER = 120;
    const OP_PICK = 121;
    const OP_ROLL = 122;
    const OP_ROT = 123;
    const OP_SWAP = 124;
    const OP_TUCK = 125;
    const OP_2DROP = 109;
    const OP_2DUP = 110;
    const OP_3DUP = 111;
    const OP_2OVER = 112;
    const OP_2ROT = 113;
    const OP_2SWAP = 114;

    // Splice
    const OP_CAT = 126; /* disabled */
    const OP_SUBSTR = 127; /* disabled */
    const OP_LEFT = 128; /* disabled */
    const OP_RIGHT = 129; /* disabled */
    const OP_SIZE = 130; /* disabled */

    // Bitwise logic
    const OP_INVERT = 131; /* Disabled */
    const OP_AND = 132; /* Disabled */
    const OP_OR = 133; /* Disabled */
    const OP_XOR = 134; /* Disabled */
    const OP_EQUAL = 135;
    const OP_EQUALVERIFY = 136;

    // Arithmetic
    const OP_1ADD = 139;
    const OP_1SUB = 140;
    const OP_2MUL = 141;
    const OP_2DIV = 142;
    const OP_NEGATE = 143;
    const OP_ABS = 144;
    const OP_NOT = 145;
    const OP_0NOTEQUAL = 146;
    const OP_ADD = 147;
    const OP_SUB = 148;
    const OP_MUL = 149;
    const OP_DIV = 150;
    const OP_MOD = 151;
    const OP_LSHIFT = 152; /* Disabled */
    const OP_RSHIFT = 153; /* Disabled */
    const OP_BOOLAND = 154;
    const OP_BOOLOR = 155;
    const OP_NUMEQUAL = 156;
    const OP_NUMEQUALVERIFY = 157;
    const OP_NUMNOTEQUAL = 158;
    const OP_LESSTHAN = 159;
    const OP_GREATERTHAN = 160;
    const OP_LESSTHANOREQUAL = 161;
    const OP_GREATERTHANOREQUAL = 162;
    const OP_MIN = 163;
    const OP_MAX = 164;
    const OP_WITHIN = 165;

    // Crypto
    const OP_RIPEMD160 = 166;
    const OP_SHA1 = 167;
    const OP_SHA256 = 168;
    const OP_HASH160 = 169;
    const OP_HASH256 = 170;
    const OP_CODESEPARATOR = 171;
    const OP_CHECKSIG = 172;
    const OP_CHECKSIGVERIFY = 173;
    const OP_CHECKMULTISIG = 174;
    const OP_CHECKMULTISIGVERIFY = 175;

    // Pseudo Words
    const OP_PUBKEYHASH = 253;
    const OP_PUBKEY = 254;
    const OP_INVALIDOPCODE = 255;

    // Reserved
    // So are any which are not assigned
    const OP_RESERVED = 80;
    const OP_VER = 98;
    const OP_VERIF = 101;
    const OP_VERNOTIF = 102;
    const OP_RESERVED1 = 137;
    const OP_RESERVED2 = 138;
    const OP_NOP1 = 176;
    const OP_CHECKLOCKTIMEVERIFY = 177;
    const OP_CHECKSEQUENCEVERIFY = 178;
    const OP_NOP4 = 179;
    const OP_NOP5 = 180;
    const OP_NOP6 = 181;
    const OP_NOP7 = 182;
    const OP_NOP8 = 183;
    const OP_NOP9 = 184;
    const OP_NOP10 = 185;

    /**
     * @var array
     */
    private static $names = [
        self::OP_0 => 'OP_0',
        self::OP_PUSHDATA1 => 'OP_PUSHDATA1',
        self::OP_PUSHDATA2 => 'OP_PUSHDATA2',
        self::OP_PUSHDATA4 => 'OP_PUSHDATA4',
        self::OP_1NEGATE => 'OP_1NEGATE',
        self::OP_1 => 'OP_1',
        self::OP_2 => 'OP_2',
        self::OP_3 => 'OP_3',
        self::OP_4 => 'OP_4',
        self::OP_5 => 'OP_5',
        self::OP_6 => 'OP_6',
        self::OP_7 => 'OP_7',
        self::OP_8 => 'OP_8',
        self::OP_9 => 'OP_9',
        self::OP_10 => 'OP_10',
        self::OP_11 => 'OP_11',
        self::OP_12 => 'OP_12',
        self::OP_13 => 'OP_13',
        self::OP_14 => 'OP_14',
        self::OP_15 => 'OP_15',
        self::OP_16 => 'OP_16',

        // Flow Control
        self::OP_NOP => 'OP_NOP',
        self::OP_IF => 'OP_IF',
        self::OP_NOTIF => 'OP_NOTIF',
        self::OP_ELSE => 'OP_ELSE',
        self::OP_ENDIF => 'OP_ENDIF',
        self::OP_VERIFY => 'OP_VERIFY',
        self::OP_RETURN => 'OP_RETURN',
        self::OP_TOALTSTACK => 'OP_TOALTSTACK',
        self::OP_FROMALTSTACK => 'OP_FROMALTSTACK',
        self::OP_IFDUP => 'OP_IFDUP',
        self::OP_DEPTH => 'OP_DEPTH',
        self::OP_DROP => 'OP_DROP',
        self::OP_DUP => 'OP_DUP',
        self::OP_NIP => 'OP_NIP',
        self::OP_OVER => 'OP_OVER',
        self::OP_PICK => 'OP_PICK',
        self::OP_ROLL => 'OP_ROLL',
        self::OP_ROT => 'OP_ROT',
        self::OP_SWAP => 'OP_SWAP',
        self::OP_TUCK => 'OP_TUCK',
        self::OP_2DROP => 'OP_2DROP',
        self::OP_2DUP => 'OP_2DUP',
        self::OP_3DUP => 'OP_3DUP',
        self::OP_2OVER => 'OP_2OVER',
        self::OP_2ROT => 'OP_2ROT',
        self::OP_2SWAP => 'OP_2SWAP',

        // Splice
        self::OP_CAT => 'OP_CAT', /* disabled */
        self::OP_SUBSTR => 'OP_SUBSTR', /* disabled */
        self::OP_LEFT => 'OP_LEFT', /* disabled */
        self::OP_RIGHT => 'OP_RIGHT', /* disabled */
        self::OP_SIZE => 'OP_SIZE', /* disabled */

        // Bitwise logic
        self::OP_INVERT => 'OP_INVERT', /* Disabled */
        self::OP_AND => 'OP_AND', /* Disabled */
        self::OP_OR => 'OP_OR', /* Disabled */
        self::OP_XOR => 'OP_XOR', /* Disabled */
        self::OP_EQUAL => 'OP_EQUAL',
        self::OP_EQUALVERIFY => 'OP_EQUALVERIFY',

        // Arithmetic
        self::OP_1ADD => 'OP_1ADD',
        self::OP_1SUB => 'OP_1SUB',
        self::OP_2MUL => 'OP_2MUL',
        self::OP_2DIV => 'OP_2DIV',
        self::OP_NEGATE => 'OP_NEGATE',
        self::OP_ABS => 'OP_ABS',
        self::OP_NOT => 'OP_NOT',
        self::OP_0NOTEQUAL => 'OP_0NOTEQUAL',
        self::OP_ADD => 'OP_ADD',
        self::OP_SUB => 'OP_SUB',
        self::OP_MUL => 'OP_MUL',
        self::OP_DIV => 'OP_DIV',
        self::OP_MOD => 'OP_MOD',
        self::OP_LSHIFT => 'OP_LSHIFT', /* Disabled */
        self::OP_RSHIFT => 'OP_RSHIFT', /* Disabled */
        self::OP_BOOLAND => 'OP_BOOLAND',
        self::OP_BOOLOR => 'OP_BOOLOR',
        self::OP_NUMEQUAL => 'OP_NUMEQUAL',
        self::OP_NUMEQUALVERIFY => 'OP_NUMEQUALVERIFY',
        self::OP_NUMNOTEQUAL => 'OP_NUMNOTEQUAL',
        self::OP_LESSTHAN => 'OP_LESSTHAN',
        self::OP_GREATERTHAN => 'OP_GREATERTHAN',
        self::OP_LESSTHANOREQUAL => 'OP_LESSTHANOREQUAL',
        self::OP_GREATERTHANOREQUAL => 'OP_GREATERTHANOREQUAL',
        self::OP_MIN => 'OP_MIN',
        self::OP_MAX => 'OP_MAX',
        self::OP_WITHIN => 'OP_WITHIN',

        // Crypto
        self::OP_RIPEMD160 => 'OP_RIPEMD160',
        self::OP_SHA1 => 'OP_SHA1',
        self::OP_SHA256 => 'OP_SHA256',
        self::OP_HASH160 => 'OP_HASH160',
        self::OP_HASH256 => 'OP_HASH256',
        self::OP_CODESEPARATOR => 'OP_CODESEPARATOR',
        self::OP_CHECKSIG => 'OP_CHECKSIG',
        self::OP_CHECKSIGVERIFY => 'OP_CHECKSIGVERIFY',
        self::OP_CHECKMULTISIG => 'OP_CHECKMULTISIG',
        self::OP_CHECKMULTISIGVERIFY => 'OP_CHECKMULTISIGVERIFY',

        // Pseudo Words
        self::OP_PUBKEYHASH => 'OP_PUBKEYHASH',
        self::OP_PUBKEY => 'OP_PUBKEY',
        self::OP_INVALIDOPCODE => 'OP_INVALIDOPCODE',

        // Reserved
        // So are any which are not assigned
        self::OP_RESERVED => 'OP_RESERVED',
        self::OP_VER => 'OP_VER',
        self::OP_VERIF => 'OP_VERIF',
        self::OP_VERNOTIF => 'OP_VERNOTIF',
        self::OP_RESERVED1 => 'OP_RESERVED1',
        self::OP_RESERVED2 => 'OP_RESERVED2',
        self::OP_NOP1 => 'OP_NOP1',
        self::OP_CHECKLOCKTIMEVERIFY => 'OP_CHECKLOCKTIMEVERIFY',
        self::OP_CHECKSEQUENCEVERIFY => 'OP_CHECKSEQUENCEVERIFY',
        self::OP_NOP4 => 'OP_NOP4',
        self::OP_NOP5 => 'OP_NOP5',
        self::OP_NOP6 => 'OP_NOP6',
        self::OP_NOP7 => 'OP_NOP7',
        self::OP_NOP8 => 'OP_NOP8',
        self::OP_NOP9 => 'OP_NOP9',
        self::OP_NOP10 => 'OP_NOP10',
    ];

    /**
     * @var array
     */
    private $known = [];

    public function __construct()
    {
        $this->known = array_flip(self::$names);
    }

    /**
     * @param int $op
     */
    private function opExists(int $op)
    {
        if (!array_key_exists($op, self::$names)) {
            throw new \RuntimeException("Opcode not found");
        }
    }

    /**
     * @param string $name
     */
    private function opNameExists(string $name)
    {
        if (!array_key_exists($name, $this->known)) {
            throw new \RuntimeException("Opcode by that name not found");
        }
    }

    /**
     * @param int $op
     * @return string
     */
    public function getOp(int $op): string
    {
        $this->opExists($op);
        return self::$names[$op];
    }

    /**
     * @param string $name
     * @return int
     */
    public function getOpByName(string $name): int
    {
        $this->opNameExists($name);
        return $this->known[$name];
    }

    /**
     * @param int $opcode
     * @return string
     */
    public function offsetGet($opcode): string
    {
        return $this->getOp($opcode);
    }

    /**
     * @param int $opcode
     * @return bool
     */
    public function offsetExists($opcode): bool
    {
        return array_key_exists($opcode, self::$names);
    }

    /**
     * @throws \RuntimeException
     */
    private function errorNoWrite()
    {
        throw new \RuntimeException('Cannot write to Opcodes');
    }

    /**
     * @param int $pos
     */
    public function offsetUnset($pos)
    {
        $this->errorNoWrite();
    }

    /**
     * @param int $pos
     * @param mixed $value
     */
    public function offsetSet($pos, $value)
    {
        $this->errorNoWrite();
    }

    /**
     * @return array
     */
    public function __debugInfo()
    {
        return [];
    }
}
