onmessage = function(e) {
  runGsiril( e.data.input, ((typeof e.data.args == 'object')? e.data.args : []).concat(['-N', '-f', 'gsiril.input']) );
};

var runGsiril = function(input, args) {
  var Module = {
    arguments: args,
    preRun: function() {
      FS.writeFile( 'gsiril.input', input );
    },
    print: function(text) {
      postMessage({output: text});
    },
    printErr: function(text) {
      postMessage({error: text});
    }
  };
