define( function() {
    var ready = function( fn ) {
        if( document.readyState != 'loading' ) {
            fn();
        }
        else {
            document.addEventListener( 'DOMContentLoaded', fn );
        }
    };
    return ready;
} );
