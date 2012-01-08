/*
 * Blueline - Shim.js
 * http://blueline.rsw.me.uk
 *
 * Copyright 2012, Robert Wallis
 * This file is part of Blueline.

 * Blueline is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * Blueline is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with Blueline.  If not, see <http://www.gnu.org/licenses/>.
 */
define( ['jquery', './Is', './Can'], function( $, Is, Can ) {
	// Most things used should fallback themselves to less functional, but still 
	// workable behaviour.
	// Here are some exceptions.
	
	// Fallback to PNG for browsers that don't support SVG in CSS backgrounds.
	// (IE is done by the old_ie.css stylesheet, Android <3 is only other culprit)
	var isAndroid = Is.android();
	if( typeof isAndroid === 'number' && isAndroid < 3 ) {
		$( '<style>#topSearch button, #bigSearch button{background-image:url(/images/search.png) !important}a.external{background-image:url(/images/external.png) !important}.search li.selected{background-image: url(/images/selectIndicator.png) !important}</style>' ).appendTo( 'head' );
	}
	
	// Placeholders in input fields
	if( !Can.placeholder() ) {
		var addPlaceholder = function() {
			var $this = $( this );
			if( $this.val() === '' ) {
				$this.val( $this.attr( 'placeholder' ) ).addClass( 'placeholder' );
			}
		},
		removePlaceholder = function() {
			var $this = $( this );
			if( $this.val() === $this.attr( 'placeholder' ) ) {
				$this.val( '' ).removeClass( 'placeholder' );
			}
		};
		$( function() {
			// Select the elements that have a placeholder attribute
			$( 'input[placeholder], textarea[placeholder]').blur( addPlaceholder ).focus( removePlaceholder ).each( addPlaceholder );
			// Remove the placeholder text before the form is submitted
			$( 'form' ).submit( function() {
				$(this).find( 'input[placeholder], textarea[placeholder]' ).each( removePlaceholder );
			} );
		} );
	}
} );
