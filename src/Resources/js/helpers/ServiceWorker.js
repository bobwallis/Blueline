define( ['./URL'], function( URL ) {
    return {
        load: function() {
            if( 'serviceWorker' in navigator ) {
                var version = document.getElementsByTagName('html')[0].getAttribute( 'data-age' );
                navigator.serviceWorker
                    .register( URL.baseURL+'service_worker.js?v='+encodeURIComponent(version)+'&base='+encodeURIComponent(URL.baseURL) )
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
