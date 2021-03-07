define( ['./URL'], function( URL ) {
    var dataAge = document.getElementsByTagName('html')[0].getAttribute( 'data-age' );
    return {
        load: function() {
            if( 'serviceWorker' in navigator ) {
                navigator.serviceWorker
                    .register( URL.baseURL+'service_worker.js?base='+encodeURIComponent(URL.baseURL) )
                    .then( function( registration ) {
                        // Force an instant refresh if the service worker's changed and we're in a dev environment
                        if( dataAge === 'dev' ) {
                            registration.addEventListener( 'updatefound', function() {
                                newWorker = registration.installing;
                                newWorker.addEventListener( 'statechange', function() {
                                    switch( newWorker.state ) {
                                        case 'installed':
                                            newWorker.postMessage( { action: 'skipWaiting' } );
                                            window.location.reload();
                                            break;
                                    }
                                } );
                            } );
                        }
                    } );
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
