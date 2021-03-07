var version = (new Date()).toISOString().substr(0,19).replace(/[-:T]/g,'');

self.addEventListener( 'install', function( event ) {
    var swScriptUrl = new URL( self.location ),
        base    = swScriptUrl.searchParams.get( 'base' ),
        currentCacheName = 'static-'+version;
    // Populate initial cache
    event.waitUntil(
        caches.open( currentCacheName ).then( function( cache ) {
            return cache.addAll( [
                base,
                base+'?chromeless=1',
                base+'js/main.js?v='+version,
                base+'css/all.css?v='+version,
                base+'css/print.css?v='+version,
                base+'favicon.ico',
                base+'favicon.svg',
                base+'images/external.svg',
                base+'images/loading.gif',
                base+'images/search.svg',
                base+'images/select.svg',
                base+'images/welcome_methods.svg',
                base+'images/welcome_prover.svg',
                base+'images/welcome_tutorials.svg',
                base+'fonts/Blueline.woff2'
            ] );
        } )
    );
    self.skipWaiting();
} );

self.addEventListener( 'activate', function( event ) {
    var currentCacheName = 'static-'+version;

    // Clear old caches
    event.waitUntil(
        caches.keys( function( cacheNames ) {
            return Promise.all(
                cacheNames.filter( function( cacheName ) { return cacheName !== currentCacheName; } )
                          .map( function( cacheName ) { return caches.delete( cacheName ); } )
            );
        } )
    );
    event.waitUntil(clients.claim());
} );

// Fetch URLs when requested, through a cache
self.addEventListener( 'fetch', function( event ) {
    var currentCacheName = 'static-'+version;

    event.respondWith(
        caches.open( currentCacheName ).then( function( cache ) {
            return cache.match( event.request ).then( function( response ) {
                return response || fetch( event.request )
                    .then( function( response ) {
                        if( response.ok ) {
                            cache.put( event.request, response.clone() );
                        }
                        return response;
                    } )
                    .catch( function( error ) {
                        return new Response( '<section class="text"><div class="wrap"><p class="appError">An error occurred, you may be offline. Try <a href="javascript:location.reload(true)">reloading</a>, or <a href="javascript:history.go(-1)">go back</a>.</p></div></section>', { status: 200, headers: { 'Content-Type': 'text/html; charset=UTF-8' } } );
                    } );
            } );
        } )
    );
} );

// Prefetch URLs when requested
self.addEventListener( 'message', function( event ) {
    var swScriptUrl = new URL( self.location ),
        base    = swScriptUrl.searchParams.get( 'base' ),
        currentCacheName = 'static-'+version;

    if( event.data && event.data.type === 'prefetch' ) {
        var requestedURL = new URL( event.data.url, base ),
            params = new URLSearchParams( requestedURL.search.slice(1) );
        params.append( 'chromeless', 1 );
        var url = new URL( requestedURL.pathname+'?'+params, base );

        caches.open( currentCacheName ).then( function( cache ) {
            return cache.match( url ).then( function( response ) {
                return response || fetch( url ).then( function( response ) {
                    cache.put( url, response.clone() );
                    return response;
                } );
            } );
        } )
    }
} );
