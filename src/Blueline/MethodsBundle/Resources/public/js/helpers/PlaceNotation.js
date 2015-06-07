define( function() {

	var PlaceNotation = {
		bellToCharMap: ['1','2','3','4','5','6','7','8','9','0','E','T','A','B','C','D','F','G','H','J','K','L'],
		bellToChar: function( bell ) {
			return PlaceNotation.bellToCharMap[parseInt( bell, 10 )];
		},
		charToBell: function( ch ) {
			var bell = parseInt( ch, 10 );
			if( isNaN( bell ) || bell === 0 ) {
				switch( ch ) {
					case '0': return 9;
					case 'E': return 10;
					case 'T': return 11;
					case 'A': return 12;
					case 'B': return 13;
					case 'C': return 14;
					case 'D': return 15;
					case 'F': return 16;
					case 'G': return 17;
					case 'H': return 18;
					case 'J': return 19;
					case 'K': return 20;
					case 'L': return 21;
					default: return null;
				}
			}
			return --bell;
		},
		expand: function( notation, stage ) {
			var fullNotation, matches, stageText;
			// Tries to normalise place notation given in abbreviated form into full notation
			// If stage isn't given try to guess
			if( typeof stage === 'undefined' ) {
				stage = Math.max.apply( Math, notation.split( '' ).map( PlaceNotation.charToBell ) ) + 1;
			}
			stageText = PlaceNotation.bellToChar( stage-1 );
			
			fullNotation = notation
				.toUpperCase().replace( /X/g, 'x' ) // Tidy up cases
				.replace( /[\(\[{<].*[\)\]}>]/, '' ).replace( / FCH.*$/, '' ) // Remove anything inside brackets, or appended fch details
				.replace( /\.?x\.?/g, 'x' ) // Remove weird input that might mess things up later
				.trim();

			// Deal with notation like 'x1x1x1-2' (After checking for this form we can assume - means x)
			matches = fullNotation.match( /^([^-]+)-([^-\.,x]+)$/ );
			if( matches !== null ) {
				fullNotation = PlaceNotation.expandHalf( matches[1] ) + matches[2];
			}
			fullNotation = fullNotation.replace( /-/g, 'x' );

			// Turn notation like ...x34 hl 16 le 12 into ...x34.16 le 12
			if( fullNotation.indexOf( ' HL ' ) !== -1 ) {
				fullNotation = fullNotation.replace( ' HL ', '.' );
			}

			// Deal with notation like '-1-1-1LH2', or '-1-1-1 le2'. Allow a preceding ampersand
			matches = fullNotation.match( /^&?(.*)(LH|LE)/ );
			if( matches !== null ) {
				fullNotation = fullNotation.replace( matches[0], PlaceNotation.expandHalf( matches[1] ) );
			}

			// Parse microSiril format notation
			if( fullNotation.indexOf( ',' ) !== -1 ) {
				var splitOnComma = fullNotation.split( ',' ).map( function(s) { return s.trim(); } );
				if( splitOnComma.reduce( function( prev, cur ) { // If every block starts with either an & or a +
					return prev && ( cur.charAt(0) == '&' || cur.charAt(0) == '+' );
				}, true ) ) {
					fullNotation = splitOnComma.reduce( function( prev, cur ) { // Expand the symmetrical blocks, keep the asymmetrical ones as they are but remove the +
						return prev + ((cur.charAt(0) == '&')?  PlaceNotation.expandHalf( cur ) : cur.replace( '+', '' ));
					}, '' );
				}
			}

			// Now we've checked for proper microSiril format we'll make some assumptions about what people might actually mean.
			// Deal with notation like '-1-1-1,2' or '3,1.5.1.5.1'.
			matches = fullNotation.match( /^\s*&?\s*(.*),(.*)/ );
			if( matches !== null ) {
				fullNotation = (PlaceNotation.expandHalf( matches[1] )+'.'+PlaceNotation.expandHalf( matches[2] )).replace( /\.?x\.?/g, 'x' );
			}

			// Get rid of +
			fullNotation = fullNotation.replace( /\+/g, ' ' );

			// Convert 'a &-1-1-1' type notation into '&x1x1x1 2' type
			// regular lead heads
			matches = fullNotation.match( /^([A-S]{1}[1-9]?)\s+(.*)$/ );
			if( matches !== null ) {
				if( stage % 2 === 0 ) {
					// a to f is 12
					if( /^[A-F]{1}/.test( matches[1] ) ) {
						fullNotation = matches[2]+' 12';
					}
					// g to m is 1n
					else if( /^[G-M]{1}/.test( matches[1] ) ) {
						fullNotation = matches[2]+' 1'+stageText;
					}
					// p, q is 3n post lead head (if 3n isn't the start of $match[2] then add it to the start)
					else if( /^[P-Q]{1}/.test( matches[1] ) ) {
						if( matches[2].indexOf( '3' ) === 0 ) {
							notationFull = matches[2];
						}
						else {
							notationFull = '3' + stageText + ' ' + matches[2];
						}
					}
					// r, s is n post lead head
					// I don't know why I think this... but it doesn't seem to be true looking at actual methods in the current collections.
					// Let me know if you know!
					//else {
					//	if( matches[2].indexOf( 'x' ) === 0 ) {
					//		notationFull = matches[2];
					//	}
					//	else {
					//		notationFull = 'x' + matches[2];
					//	}
					//}
				}
				else {
					// a to f is 3 post lead head (if 3 isn't the start of $match[2] then add it to the start)
					if( /^[A-F]{1}/.test( matches[1] ) ) {
						if( matches[2].indexOf( '3' ) === 0 ) {
							notationFull = matches[2];
						}
						else {
							notationFull = '3 ' + matches[2];
						}
					}
					// g to m is n post lead head
					else if( /^[G-M]{1}/.test( matches[1] ) ) {
						if( matches[2].indexOf( stageText ) === 0 ) {
							notationFull = matches[2];
						}
						else {
							notationFull = stageText + ' ' + matches[2];
						}
					}
					// p, q is 12n
					else if( /^[P-Q]{1}/.test( matches[1] ) ) {
						fullNotation = matches[2]+' 12'+stageText;
					}
					// r, s is 1
					else {
						fullNotation = matches[2]+' 1';
					}
				}
			}
			// z is an irregular lead head
			matches = fullNotation.match( /^(.*)Z\s+(.*)$/ );
			if( matches !== null ) {
				fullNotation = matches[2] + ' ' + matches[1];
			}

			// Deal with, '&x1x1x1 2' type notation
			matches = fullNotation.match( /^&(.*)\s+([^x.]+)$/ );
			if( matches !== null ) {
				fullNotation = PlaceNotation.expandHalf( matches[1] )+' '+matches[2];
			}

			// Some last bits of cleaning up
			fullNotation = fullNotation
				.replace( /\s+/g, '.' )       // Replace any remaining whitespace with dots
				.replace( /(^\.+|\.+$)/g, '') // Remove trailing or preceding dots
				.replace( /\.+/g, '.' )       // Remove any unecessary doubling up of dots
				.replace( /\.?x\.?/g, 'x' );  // and any .x.

			// Explode the notation so we can work on each piece individually, the join back together
			fullNotation = PlaceNotation.explode( fullNotation ).map( function( piece ) {
				// Tidy up 'x' on odd stages
				if( piece == 'x' ) {
					return (stage % 2 === 0)? 'x' : stageText;
				}
				// First sort what we have
				piece = piece.split( '' ).map( PlaceNotation.charToBell ).sort().map( PlaceNotation.bellToChar ).join( '' );
				// Then add missing external places
				// If the first bell is even, prepend a 1
				if( (PlaceNotation.charToBell( piece.charAt( 0 ) )+1) % 2 === 0 ) {
					piece = '1'+piece;
				}
				// If stage odd and last bell even, or stage even and last bell odd, append an n
				if( (stage % 2 === 0) ? ((PlaceNotation.charToBell( piece.charAt( piece.length-1 ) )+1) % 2 !== 0) : ((PlaceNotation.charToBell( piece.charAt( piece.length-1 ) )+1) % 2 === 0) ) {
					piece = piece + stageText;
				}
				return piece;
			} ).join( '.' ).replace( /\.?x\.?/g, 'x' );

			return fullNotation;
		},
		expandHalf: function( notation ) {
			// Expands a symmetrical block of place notation
			notation = notation.replace( /^&/, '' );
			var notationReversed = notation.split( '' ).reverse().join( '' ),
				firstDot = notationReversed.indexOf( '.' ),
				firstX = notationReversed.indexOf( 'x' ),
				trim;
			if( firstDot < 0 && firstX < 0 ) {
				return notation;
			}
			else if( firstDot < 0 || firstX < firstDot ) {
				trim = (firstX === 0) ? 1 : firstX;
			}
			else {
				trim = firstDot + 1;
			}
			return (notation + notationReversed.substring( trim )).replace( /\.?(x|-)\.?/g, 'x' ).trim();
		},
		parse: function( notation, stage ) {
			// Parses normalised place notation into permutations
			var parsed = [],
				exploded = this.explode( notation ),
				xPermutation = new Array( stage );
			// Construct the X permutation for stage
			for( var i = 0; i < stage; i+=2 ) { xPermutation[i] = i+1; xPermutation[i+1] = i; }
			if( i-1 === stage ) { xPermutation[i-1] = i-1; }

			// Then parse section by section
			for( i = 0; i < exploded.length; i++ ) {
				// For an x, push our pregenerated x permutation
				if( exploded[i] === 'x' ) {
					parsed.push( xPermutation );
				}
				// Otherwise calculate the permutation
				else {
					var stationary = exploded[i].split( '' ).map( this.charToBell ),
						permutation = new Array( stage ),
						j;
					// First put in any stationary bells
					for( j = 0; j < stationary.length; j++ ) {
						permutation[stationary[j]] = stationary[j];
					}
					// Then 'x' what's left
					for( j = 0; j < stage; j++ ) {
						if( typeof( permutation[j] ) === 'undefined' ) {
							if( typeof( permutation[j+1] ) === 'undefined' && j+1 < stage ) {
								permutation[j] = j+1;
								permutation[j+1] = j;
								j++;
							}
							else {
								permutation[j] = j;
							}
						}
					}
					parsed.push( permutation );
				}
			}
			return parsed;
		},
		implode: function( notationArray ) {
			return ( typeof notationArray.join === 'function' )? notationArray.join( '.' ).replace( /\.?x\.?/g, 'x' ) : notationArray;
		},
		explode: function( notation ) {
			return (typeof notation === 'string')? notation.replace( /x/gi, '.x.' ).split( '.' ).filter( function( e ) { return e !== ''; } ) : notation;
		},
		rounds: function( stage ) {
			var row = new Array( stage ), i = stage;
			while( i-- ) { row[i] = i; }
			return row;
		},
		rowsEqual: function( row1, row2 ) {
			var i = row1.length;
			if( i !== row2.length) {
				return false;
			}
			while( i-- ) {
				if( row1[i] !== row2[i] ) {
					return false;
				}
			}
			return true;
		},
		allRows: function( notation, startRow ) {
			var rows = [startRow],
				i = 0, iLim = notation.length;
			while( i < iLim ) {
				rows.push( this.apply( notation[i], rows[i] ) );
				++i;
			}
			return rows;
		},
		apply: function( permutation, row ) {
			var permuted;
			if( typeof permutation[0].forEach === 'function' ) {
				permuted = row;
				permutation.forEach( function( p ) { permuted = this.apply( p, permuted ); }, this );
				return permuted;
			}
			var i = permutation.length,
				j = row.length;
			permuted = new Array( j );
			while( j-- > i ) {
				permuted[j] = row[j];
			}
			do {
				permuted[j] = row[permutation[j]];
			} while( j-- );
			return permuted;
		},
		cycles: function( permutation ) {
			var cycles = [],
				i = permutation.length,
				rounds = this.rounds( i ),
				cycle, calcRow;
			while( i-- ) {
				if( rounds[i] === -1 ) { continue; }
				cycle = [rounds[i]];
				for( calcRow = permutation; calcRow[i] !== rounds[i]; calcRow = this.apply( permutation, calcRow ) ) {
					cycle.push( calcRow[i] );
					rounds[calcRow[i]] = -1;
				}
				rounds[i] = -1;
				cycles.push( cycle );
			}
			return cycles;
		},
		huntBells: function( notation, stage ) {
			var start = this.rounds( stage ),
				end = this.apply( notation, start ),
				hunts = [];
			for( var i = 0; i < stage; ++i ) {
				if( start[i] === end[i] ) { hunts.push( i ); }
			}
			return hunts;
		}
	};

	return PlaceNotation;

} );