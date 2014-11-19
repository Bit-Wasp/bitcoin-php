bitcoin
=======

An attempt to make bitcoin-lib-php into something as good as bitcore..

I have a good idea about how I want to proceed, but could do with people 
weighing in on clever tricks to make it all work better.

Eg, the Math class in this library just statically stores the math adapter from PHPECC,
and you can call all methods through that. (Maybe I could just tweak Bitcoinlib to do this, 
but I think there are a lot of key parts that need to be teased apart)

Let me know what your thoughts are, if this seems worthwhile. I think it'd be nice to
work towards because there are tonnes of issues in bitcoinlibphp that could be addressed
by planning from the start. 


One thing I'd love an answer on if you can help!
I'm thinking of a serializable trait.. maybe just itching to use traits, but 
it would probably put me in the direction of passing handlers for all the various
objects which could be serialized into hex format. (Scripts, Transactions, or TransactionInputs, or TransactionInputs Scripts..)
There are a heirarchy of objects to serialize in the case of transactions, but I think having each object 
take care of serializing itself and then calling on children to serialize themselves.. and so.. could be neat.



