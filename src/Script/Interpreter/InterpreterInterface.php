<?php

declare(strict_types=1);

namespace BitWasp\Bitcoin\Script\Interpreter;

use BitWasp\Bitcoin\Script\ScriptInterface;
use BitWasp\Bitcoin\Script\ScriptWitnessInterface;

interface InterpreterInterface
{
    const MAX_SCRIPT_ELEMENT_SIZE = 520;
    const SCRIPT_ERR_BAD_OPCODE = '';
    const SCRIPT_ERR_PUSH_SIZE = '';
    const SCRIPT_ERR_OP_COUNT = '';
    const SCRIPT_ERR_MINIMALDATA = '';

    const VERIFY_NONE = 0;

    // Evaluate P2SH subscripts (softfork safe, BIP16).
    const VERIFY_P2SH = 1 << 0;

    // Passing a non-strict-DER signature or one with undefined hashtype to a checksig operation causes script failure.
    // Evaluating a pubkey that is not (0x04 + 64 bytes) or (0x02 or 0x03 + 32 bytes) by checksig causes script failure.
    // (softfork safe, but not used or intended as a consensus rule).
    const VERIFY_STRICTENC = 1 << 1;

    // Passing a non-strict-DER signature to a checksig operation causes script failure (softfork safe, BIP62 rule 1)
    const VERIFY_DERSIG = 1 << 2;

    // Passing a non-strict-DER signature or one with S > order/2 to a checksig operation causes script failure
    // (softfork safe, BIP62 rule 5).
    const VERIFY_LOW_S = 1 << 3;

    // verify dummy stack item consumed by CHECKMULTISIG is of zero-length (softfork safe, BIP62 rule 7).
    const VERIFY_NULL_DUMMY = 1 << 4;

    // Using a non-push operator in the scriptSig causes script failure (softfork safe, BIP62 rule 2).
    const VERIFY_SIGPUSHONLY = 1 << 5;

    // Require minimal encodings for all push operations (OP_0... OP_16, OP_1NEGATE where possible, direct
    // pushes up to 75 bytes, OP_PUSHDATA up to 255 bytes, OP_PUSHDATA2 for anything larger). Evaluating
    // any other push causes the script to fail (BIP62 rule 3).
    // In addition, whenever a stack element is interpreted as a number, it must be of minimal length (BIP62 rule 4).
    // (softfork safe)
    const VERIFY_MINIMALDATA = 1 << 6;
    // Discourage use of NOPs reserved for upgrades (NOP1-10)
    //
    // Provided so that nodes can avoid accepting or mining transactions
    // containing executed NOP's whose meaning may change after a soft-fork,
    // thus rendering the script invalid; with this flag set executing
    // discouraged NOPs fails the script. This verification flag will never be
    // a mandatory flag applied to scripts in a block. NOPs that are not
    // executed, e.g.  within an unexecuted IF ENDIF block, are *not* rejected.
    const VERIFY_DISCOURAGE_UPGRADABLE_NOPS = 1 << 7;

    // Require that only a single stack element remains after evaluation. This changes the success criterion from
    // "At least one stack element must remain, and when interpreted as a boolean, it must be true" to
    // "Exactly one stack element must remain, and when interpreted as a boolean, it must be true".
    // (softfork safe, BIP62 rule 6)
    // Note: CLEANSTACK should never be used without P2SH.
    const VERIFY_CLEAN_STACK = 1 << 8;

    // Verify CHECKLOCKTIMEVERIFY
    //
    // See BIP65 for details.
    const VERIFY_CHECKLOCKTIMEVERIFY = 1 << 9;

    const VERIFY_CHECKSEQUENCEVERIFY = 1 << 10;

    const VERIFY_WITNESS = 1 << 11;

    const VERIFY_DISCOURAGE_UPGRADABLE_WITNESS_PROGRAM = 1 << 12;

    const VERIFY_MINIMALIF = 1 << 13;

    const VERIFY_NULLFAIL = 1 << 14;

    // Verify CHECKSEQUENCEVERIFY
    //
    // See BIP112 for details.



    const SIGHASH_ALL          = 0x1;
    const SIGHASH_NONE         = 0x2;
    const SIGHASH_SINGLE       = 0x3;
    const SIGHASH_ANYONECANPAY = 0x80;

    /**
     * @param ScriptInterface $scriptSig
     * @param ScriptInterface $scriptPubKey
     * @param int $flags
     * @param CheckerBase $checker
     * @param ScriptWitnessInterface|null $witness
     * @return bool
     */
    public function verify(ScriptInterface $scriptSig, ScriptInterface $scriptPubKey, int $flags, CheckerBase $checker, ScriptWitnessInterface $witness = null);

    /**
     * @param ScriptInterface $script
     * @param Stack $stack
     * @param int $sigVersion
     * @param int $flags
     * @param CheckerBase $checker
     * @return bool
     */
    public function evaluate(ScriptInterface $script, Stack $stack, int $sigVersion, int $flags, CheckerBase $checker);
}
