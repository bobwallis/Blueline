var version = '{$DATABASE_UPDATE}';
var legacyCacheCleanupPromise = null;
var prefetchTimeoutMs = 3000;

var fetchWithTimeout = function( url, timeoutMs ) {
    if ( typeof AbortController === 'undefined' ) {
        return fetch( url );
    }

    var controller = new AbortController(),
        timeoutId = setTimeout( function() {
            controller.abort();
        }, timeoutMs );

    return fetch( url, { signal: controller.signal } ).finally( function() {
        clearTimeout( timeoutId );
    } );
};

var offlineResponse = function() {
    return new Response( '<section class="text"><div class="wrap"><p class="appError">An error occurred, you may be offline. Try <a href="javascript:location.reload(true)">reloading</a>, or <a href="javascript:history.go(-1)">go back</a>.</p></div></section>', { status: 200, headers: { 'Content-Type': 'text/html; charset=UTF-8' } } );
};

var clearLegacyCaches = function( currentCacheName ) {
    if ( legacyCacheCleanupPromise ) {
        return legacyCacheCleanupPromise;
    }

    legacyCacheCleanupPromise = caches.keys().then( function( cacheNames ) {
        return Promise.all(
            cacheNames.filter( function( cacheName ) { return cacheName !== currentCacheName; } )
                .map( function( cacheName ) { return caches.delete( cacheName ); } )
        );
    } ).finally( function() {
        legacyCacheCleanupPromise = null;
    } );

    return legacyCacheCleanupPromise;
};

var matchFromLegacyCaches = function( request, currentCacheName ) {
    return caches.keys().then( function( cacheNames ) {
        return Promise.all(
            cacheNames.filter( function( cacheName ) { return cacheName !== currentCacheName; } )
                .map( function( cacheName ) {
                    return caches.open( cacheName ).then( function( cache ) {
                        return cache.match( request );
                    } );
                } )
        );
    } ).then( function( responses ) {
        return responses.find( function( response ) { return Boolean( response ); } );
    } );
};

self.addEventListener( 'install', function( event ) {
    var swScriptUrl = new URL( self.location ),
        base    = swScriptUrl.searchParams.get( 'base' ),
        currentCacheName = 'static-'+version;
    // Populate initial cache with key pages only
    event.waitUntil(
        caches.open( currentCacheName ).then( function( cache ) {
            return cache.addAll( [
                base,
                base+'?chromeless=1'
            ] );
        } )
    );
    self.skipWaiting();
} );

self.addEventListener( 'activate', function( event ) {
    event.waitUntil( clients.claim() );
} );

// Fetch URLs when requested, through a cache
self.addEventListener( 'fetch', function( event ) {
    var currentCacheName = 'static-'+version;

    if ( event.request.method !== 'GET' ) {
        return;
    }
    if ( event.request.mode !== 'navigate' && event.request.destination !== 'document' ) {
        return;
    }

    event.respondWith(
        caches.open( currentCacheName ).then( function( cache ) {
            return cache.match( event.request ).then( function( response ) {
                return response || fetch( event.request )
                    .then( function( response ) {
                        if( response.ok ) {
                            cache.put( event.request, response.clone() );
                            event.waitUntil( clearLegacyCaches( currentCacheName ) );
                        }
                        return response;
                    } )
                    .catch( function() {
                        return matchFromLegacyCaches( event.request, currentCacheName ).then( function( legacyResponse ) {
                            return legacyResponse || offlineResponse();
                        } );
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
        var appBaseURL = new URL( base ),
            requestedURL = new URL( event.data.url, base ),
            params = new URLSearchParams( requestedURL.search.slice(1) );

        if ( requestedURL.origin !== appBaseURL.origin ) {
            return;
        }

        params.append( 'chromeless', 1 );
        var url = new URL( requestedURL.pathname+'?'+params, base );

        event.waitUntil(
            caches.open( currentCacheName ).then( function( cache ) {
                return cache.match( url ).then( function( response ) {
                    return response || fetchWithTimeout( url, prefetchTimeoutMs ).then( function( response ) {
                        if ( response.ok ) {
                            cache.put( url, response.clone() );
                        }
                        return response;
                    } ).catch( function() {
                        return null;
                    } );
                } );
            } )
        );
    }
} );
