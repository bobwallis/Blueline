define( ['eve', '../../helpers/URL'], function( eve, URL ) {
	var breadcrumbEl     = document.getElementById( 'breadcrumb' ),
		breadcrumb_sepEl = document.getElementById( 'breadcrumb_sep' );

	var Breadcrumb = {
		section: null,
		set: function( section ) {
			if( typeof section === 'string' ) {
				breadcrumb_sepEl.style.display = 'block';
				breadcrumbEl.innerHTML = '<a href="'+URL.baseURL+section+'/">'+section.charAt(0).toUpperCase()+section.slice(1)+'</a>';
				breadcrumbEl.style.display = 'block';
				Breadcrumb.section = section;
			}
			else {
				breadcrumb_sepEl.style.display = 'none';
				breadcrumbEl.style.display = 'none';
				Breadcrumb.section = null;
			}
		}
	};

	// Add the breadcrumb elements to the header if the page doesn't have it already
	if( breadcrumb_sepEl === null ) {
		breadcrumb_sepEl = document.createElement( 'h2' );
		breadcrumb_sepEl.id = 'breadcrumb_sep';
		breadcrumb_sepEl.style.display = 'none';
		breadcrumb_sepEl.innerHTML = '&raquo;';
		document.getElementById( 'top' ).appendChild( breadcrumb_sepEl );
	}
	if( breadcrumbEl === null ) {
		breadcrumbEl = document.createElement( 'h2' );
		breadcrumbEl.id = 'breadcrumb';
		breadcrumbEl.style.display = 'none';
		document.getElementById( 'top' ).appendChild( breadcrumbEl );
		Breadcrumb.section = null;
	}
	else {
		Breadcrumb.section = breadcrumbEl.textContent.toLowerCase();
	}

	// Update the breadcrumb when a new page is requested
	eve.on( 'page.request', function( data ) {
		Breadcrumb.set( data.section || null );
	} );

	return Breadcrumb;
} );
