var fs = require('fs')
var bitcore = require('bitcore')

function bin2hex(s) {
  //  discuss at: http://phpjs.org/functions/bin2hex/
  // original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
  // bugfixed by: Onno Marsman
  // bugfixed by: Linuxworld
  // improved by: ntoniazzi (http://phpjs.org/functions/bin2hex:361#comment_177616)
  var i, l, o = '',
    n;

  s += '';

  for (i = 0, l = s.length; i < l; i++) {
    n = s.charCodeAt(i)
      .toString(16);
    o += n.length < 2 ? '0' + n : n;
  }

  return o;
}

module.exports.stack = function(fileName, cb) {
    fs.readFile(fileName, 'binary', function (err,data) {
      if (err) {
          cb(err);
          return;
      }
      try {
          scriptPubkey = new bitcore.Script(bin2hex(data));
          tx = bitcore.Transaction("0100000001a38021e92bd97f43857328a337df14a084bb9d87e9dd82e607e96170e13c103a0000000000ffffffff0100f2052a010000001976a9146aeffd5d1dcc7f85a431d9d5798e2e13c8bf847a88ac00000000");
          nIn = parseInt(0);
          var flags = bitcore.Script.Interpreter.SCRIPT_VERIFY_P2SH | bitcore.Script.Interpreter.SCRIPT_VERIFY_DERSIG
          //var flags = 0
          var interpreter = bitcore.Script.Interpreter();
          var verified = interpreter.verify(tx.inputs[nIn].script, scriptPubkey, tx, nIn);

          var stack = interpreter.stack;

          if (stack.length > 0) {
              cb(stack[stack.length -1].toString('hex').concat(''));
          }
      } catch(err) {
          cb('');
      }
      cb('')
    });
}
