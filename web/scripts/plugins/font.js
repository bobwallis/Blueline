/*
 * Blueline - font.js
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
define( {
	load: function( name, req, load, config ) {
		if( config.isBuild ) {
			load( null );
		}
		else {
			// Special case to help out Android since BluelineMono is monospace there
			if( name === 'BluelineMono' && navigator.userAgent.toLowerCase().indexOf( 'android' ) !== -1 ) {
				load( false );
			}
			else {
				req( ['jquery', 'helpers/Can'], function( $, Can ) {
					if( Can.webFont() ) {
						// Compare the width of a test string in a sans font against the test string 
						// using the custom font, and wait for them to be different
						var measureFont = function( $, $container, family ) {
							var $testContainer = $( '<div class="fontPreload" style="font-family:' + family + '">BES</div>' ), width;
							$container.append( $testContainer );
							width = $testContainer.width();
							$testContainer.remove();
							return width;
						},
							$body = $( document.body ),
							calls = 0,
							testAgainstFont = "arial,'URW Gothic L',sans-serif",
							testAgainst = measureFont( $, $body, testAgainstFont ),
							differenceLimit = 50, // WebKit seems to give slightly different widths even if the font hasn't loaded. Compensate by only confirming load if the difference is large
							checkIfLoaded = function() {
								if( Math.abs( measureFont( $, $body, name+','+testAgainstFont ) - testAgainst ) > differenceLimit ) {
									load( true );
									return;
								}
								if( ++calls > 20 ) {
									load( false );
								}
								else {
									setTimeout( checkIfLoaded, 100 );
								}
							};
						checkIfLoaded();
					}
					else {
						load( false );
					}
				} );
			}
		}
	}
} );
