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
