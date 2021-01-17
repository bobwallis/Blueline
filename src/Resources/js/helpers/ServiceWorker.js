define( ['./URL'], function( URL ) {
    return {
        load: function() {
            if( 'serviceWorker' in navigator ) {
                navigator.serviceWorker
                    .register( URL.baseURL+'service_worker.js?base='+encodeURIComponent(URL.baseURL) )
            }
        },
        prefetch: function( url ) {
            if( 'serviceWorker' in navigator ) {
                navigator.serviceWorker.ready.then( function( registration ) {
                    registration.active.postMessage( {
                        type: 'prefetch',
                        url: url
                    } );
                } );
            }
        }
    };
} );
