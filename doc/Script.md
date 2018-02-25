# Scripts

Scripts are encapsulated using the \BitWasp\Bitcoin\Script\Script class.

Script types supported by the library can be classified. 

### \BitWasp\Bitcoin\Script\Classifier\OutputScriptClassifier

This class is used to classify, and extract information about the subject of the script. 
This includes public key hashes, raw public keys, script hashes, and so on. 

Solving the type, and the subject of the script is relied on for signing. 


### Parser namespace

The `BitWasp\Bitcoin\Script\Parser\Parser` class is used to parse & decode scripts. 

Calling `Parser::decode()` will return an array of Operations, containing data about effects the each operation in the script.

   `Operation::getOp()` - Returns the opcode 
   
   `Operation::isPush()` - Returns a boolean indicating whether the operation pushed a value to the stack
   
   `Operation::getData()` - Returns the push-data if there was any.
   
   `Operation::getDataSize()` - If data was pushed, this function returns the length indicated by the pushdata opcode.

Calling `ScriptParser::getHumanReadable()` will return a string of the operation names, and value pushes in the script. 

The class implements the \Iterator interface. 


### Interpeter namespace

`BitWasp\Bitcoin\Script\Interpreter\Interpreter` is the native implementation of the script language. 
 
  - It uses the `Number` class to work with integers on the stack. 
  - The state of the stack is stored in a `Stack` instance. This is mutated while the script is interpreted.
  - `Checker` exposes methods which rely on contextual data for the signing operation.
   
### Consensus namespace

There are currently two ways of verifying bitcoin scripts:
 - libbitcoinconsensus bindings exposed through https://github.com/Bit-Wasp/bitcoinconsensus-php
 - the native implementation
 
 The native implementation cannot be guaranteed to be bug-for-bug compatible at this stage.
 It is verified by several lots of test cases, some drawn from the blockchain itself. 
 If script verification is critical, the extension should be used. 
 
 Script verification flags are set in the constructor parameters. 
 
 ConsensusInterface exposes just one method:
  `ConsensusInterface::verifyScript(TransactionInterface $tx, ScriptInterface $scriptPubKey, $nInputToSign, $amount, ScriptWitness $scriptWitness = null)`
  
 It returns a boolean value indicating whether execution was successful.
 
### Verifying Scripts

A great way to learn the scripting engine is to explore cases that don't involve ECDSA.   

ScriptSig's are executed first, and are checked by evaluating the scriptPubKey. 
Scripts return true or false depending on the final value in the stack. 

In this example, we challenge someone to provide a value which when added to 1 yields 2. 

The ScriptPubKey looks like: 1 OP_ADD 2 OP_EQUAL (hex: 51935287)

OP_ADD removes the top values from the stack and pushes the result of addition. 
OP_EQUAL compares two strings and pushes a `true` value to the stack. 

The solution to this is a single data-push of `1`.

[Evaluating 1 + 1 = 2 script](../examples/doc/script/001_run_simple_custom_script.php)

We can change the scripts to explore more of bitcoin's language. 

We can add `Opcodes::OP_DEPTH, Opcodes::OP_1, Opcodes::OP_EQUALVERIFY` to the start of the scriptPubKey
to ensure that only one value is pushed in the scriptSig before moving on to the addition, because
 EQUALVERIFY causes the script to fail if the items are not equal. 
 
Be careful when playing with flags! These control what features are active
in the interpreter, so it's important to add the right checks, otherwise your
scripts might fail, or skip a key validation step. 

Another interesting example are hash-locked contracts. A scriptPubKey can lock funds, requiring the preimage 
of the hash to be provided.

[Evaluating script preimage](../examples/doc/script/002_run_simple_hashlock_script.php)
