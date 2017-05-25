
Hey all. I've been looking at the MAST BIP and it got me wondering how jl2012 was able to do the hash-locked example. It's a good example for MAST (look what happens the DUP), and for parsing mutually exclusive execution pathways in general

I've come up with something that seems to allow one to produce the mutually exclusive branches for any script. Need to test it over more scripts 2bh, but it's coming along nicely.

https://github.com/bitcoin/bips/blob/master/bip-0114.mediawiki#hashed-time-lock-contract

Keeping in mind most bitcoin libraries only sign scripts that don't have any degrees of freedom with the pathway that's executed.. I'm starting out by iterating over script opcodes and making a tree out of possible execution pathways as they come

To do this, I start with a single node in the tree (the no logical ops case), and build up a vector of values of dependent ifs. for the above script, that's [true], [false, true], and [false, false] (same order jl2012 has them)

Next, you need all the opcodes under that pathway.. I decided to do this separately to building the tree. Basically copy EvalScript, remove all opcodes but IF/ELSE/ENDIF/NOTIF, strip away some checks, and log all logical operations and opcodes where fExec==true. The mainStack is now only operated on by IF/ELSE/ENDIF/NOTIF, so you can pass in the vector of vchs representing the list above, ie, "\x01" for true, "" for false..
 
And that gets you a list of all opcodes in the script that you need to satisfy if you wished to sign it.

I've only gotten as far as normal scripts, ie, bare or P2SH. MAST requires checking for side effects of earlier operations, and stripping away any where the predicate was failed.
 
The general motivation behind all this is to allow signing of arbitrary scripts. you can only do that once you know if there are logical operations.. after that, looking at the opcodes just under that branch tells you what you need to satisfy in order to redeem using that branch

With the branch specific opcodes, you can then try to break up the script into pieces the signer can understand, and may support: hashlocks, csv/cltv checks (signer should know the current time), signature operations

I think using the [true], [false,true], [false, false] could be a good way of relating to another party to your script/payment channel which branch you are expecting them to sign. Wallets like Copay work by creating a proposed spend from a multisig address, and requesting signatures from others, and atm there isn't really a way to specify branches in a script agnostic way. Hardware wallets also may also face the same thing in the future

Anyway, I think if wallets come to deal with scripts with logical operators, the innermost Sign() function should really be checking that the user isn't accidentally working on the wrong branch, hence the need for to designate the branch in a way the software can verify against later.

I don't want to use something like branch 1, 2, 3, because the allowed boolean values are specific to the script.
