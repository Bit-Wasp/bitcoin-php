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