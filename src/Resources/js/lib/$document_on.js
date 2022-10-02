define( ['matches'], function( matches ) {
    return function( eventName, elementSelector, handler ) {
        document.addEventListener( eventName, function( e ) {
            // loop parent nodes from the target to the delegation node
            for( var target = e.target; target && target != this; target = target.parentNode ) {
                if( matches( target, elementSelector ) ) {
                    handler.call( target, e );
                    break;
                }
            }
        }, false);
    }
} );
