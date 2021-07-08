define( ['./PlaceNotation'], function( PlaceNotation ) {

	var trimtrailingcommaregex = /,\s*$/,
		trimlastlistitemregex =/,[^,]+$/ ,
		repeatString = function( string, n ) {
			var newString = '';
			while( n-- ) {
				newString += string+',';
			}
			return newString.replace( trimtrailingcommaregex, '' );
		};

	// 'Core' work is defined as jumping, hunting, dodging, making places, making points and doing fishtails
	var core = [
		{ regex: /J\[(\d+)\]/g, text: 'Jump to $1ths' },
		{ regex: /-\{(\d+)\}(,\+\{(\d+)\},-\{(\d+)\})\2*/g, text: 'Dodge $3/$1 down' },
		{ regex: /\+\{(\d+)\}(,-\{(\d+)\},\+\{(\d+)\})\2*/g, text: 'Dodge $1/$3 up' },
		{ regex: /-\{(\d+)\},\+\{(\d+)\}/g, text: 'Point $2ths' },
		{ regex: /\+\{(\d+)\},-\{(\d+)\}/g, text: 'Point $2ths' },
		{ regex: /-\{(\d+)\}/g, text: 'Hunt down' },
		{ regex: /\+\{(\d+)\}/g, text: 'Hunt up' },
		{ regex: /\|\{(\d+)\}/g, text: 'Make $1ths' }
	],
		dodge = /Dodge/g,
		coreDodgeExtract = /Dodge (\d+\/\d+) (up|down),/g,
		coreDodgeDownFix = /([^,]*,)((Dodge \d+\/\d+ down,)\3{2,})([^,]*)/g,
		coreDodgeDownFixFunction = function( match, p1, p2, p3, p4 ) {
			var ftText = p3.replace( coreDodgeExtract, 'Fishtail $1,' );
			// Hunting up, followed by dodging down, followed by hunting up is actually dodging up
			if( p1 == 'Hunt up,' && ( p4 == 'Hunt up' || p4 == 'Hunt up,' ) ) {
				return (p3 + p2 + p3).replace( / down/g, ' up' ).replace( trimtrailingcommaregex, '' );
			}
			// Hunting up, followed by dodging down followed by not hunting up is a fishtail
			else if( p1 == 'Hunt up,' ) {
				p2 = p2.replace( coreDodgeExtract, ftText );
				return (ftText + p2 + p4 );
			}
			// Not hunting up, followed by dodging down, followed by hunting up is actually a fishtail
			else if( p4 == 'Hunt up' || p4 == 'Hunt up,' ) {
				p2 = p2.replace( coreDodgeExtract, ftText );
				return (p1 + ftText + p2 ).replace( trimtrailingcommaregex, '' );
			}
			// Otherwise we've actually got dodging down with a place at either end, which is just fine as it is
			else {
				return match;
			}
		},
		coreDodgeMultipleFix = /(Dodge \d+\/\d+ (up|down),)+/g,
		coreDodgeMultipleFixFunction = function( match ) {
			var count = Math.max( 1, Math.floor((match.split( ',' ).length - 2) / 2) );
			switch( count ) {
				case 1:  return match;
				case 2:  return match.replace( dodge, 'Double-dodge' );
				default: return match.replace( dodge, count + ' dodges in' );
			}
		},
		corePlaceMultipleFix = /(Make (\d+)ths,)+/g,
		CorePlaceMultipleFixFunction = function( match, p1, p2 ) {
			var count = match.split( ',' ).length;
			if( count > 2 ) { return match.replace( new RegExp( p1, 'g' ), count+' blows in '+p2+'ths,' ); }
			else            { return match; }
		};

	var Text = {
		fromRows: function( rows, bell, wrap ) {
			var wrapRows;
			if( typeof wrap === 'undefined' ) { wrap = false; }
			var bell = (typeof bell === 'number')? bell : PlaceNotation.charToBell( bell ), pos,
				intermediate = '', coreText;

			// Create intermediate string showing movements and places
			for( var i = 1; i < rows.length; ++i ) {
				pos = rows[i].indexOf( bell ) - rows[i-1].indexOf( bell );
				if( Math.abs( pos ) > 1 ) {
					intermediate +='J['+(rows[i].indexOf( bell )+1)+'],';
				}
				else {
					if( pos < 0 )      { intermediate += '-'; }
					else if( pos > 0 ) { intermediate += '+'; }
					else               { intermediate += '|'; }
					intermediate += '{'+(rows[i-1].indexOf( bell )+1)+'},';
				}
			}
			if( wrap ) {
				// If the 'wrap' option is on, then add more changes to the end (so we can detect dodging over the lead end and stuff)
				for( wrapRows = 1; wrapRows < rows.length && wrapRows < 10; ++wrapRows ) {
					pos = rows[wrapRows].indexOf( bell ) - rows[wrapRows-1].indexOf( bell );
					if( Math.abs( pos ) > 1 ) {
						intermediate +='J['+(rows[wrapRows].indexOf( bell )+1)+'],';
					}
					else {
						if( pos < 0 )      { intermediate += '-'; }
						else if( pos > 0 ) { intermediate += '+'; }
						else               { intermediate += '|'  }
						intermediate += '{'+(rows[wrapRows-1].indexOf( bell )+1)+'},';
					}
				}
			}
			intermediate = intermediate.replace( trimtrailingcommaregex, '' );
			// Convert to text
			coreText = intermediate;
			// Have a first bash at it by converting as much as possible
			core.forEach( function( r ) {
				coreText = coreText.replace( r.regex, function( match, p1, p2, p3 ) {
					var string = repeatString( r.text, match.split( ',' ).length );
					string = string.replace( /\$1/g, p1 ).replace( /\$2/g, p2 ).replace( /\$3/g, p3 );
					return string;
				} );
			} );
			// Now fix all the bits that didn't quite work out
			coreText = coreText
				// Tell up dodges from down double-dodges, and dodges from fishtails. Note that we have to run this one twice so that it can pick up when dodges and fishtails are close together
				.replace( coreDodgeDownFix, coreDodgeDownFixFunction )
				// Double/triple/etc dodges
				.replace( coreDodgeMultipleFix, coreDodgeMultipleFixFunction )
				// More than one blow in a place
				.replace( corePlaceMultipleFix, CorePlaceMultipleFixFunction )
				// 1ths vs 'Lead'
				.replace( /Make 1ths/g, 'Lead' )
				.replace( /Point 1ths/g, 'Point lead' )
				.replace( / 1ths/g, ' lead' )
				// nths vs 'Lie'
				.replace(new RegExp( 'Make '+rows[0].length+'ths', 'g' ), 'Lie behind' )
				.replace(new RegExp( '([0-9]+) blows in '+rows[0].length+'ths', 'g' ), 'Lie for $1 blows' )
				// Ordinals
				.replace( / 2ths/g, ' 2nds' )
				.replace( / 3ths/g, ' 3rds' )
			// If we've added an extra place on the end to wrap into the next lead, then remove the extra row again now
			if( wrap ) {
				while( --wrapRows ) {
					coreText = coreText.replace( trimlastlistitemregex, '' );
				}
			}
			// Make a note of whether things happen at hand or back
			var coreRows = coreText.split( ',' );
			for( i = 0; i < coreRows.length; ) {
				if( coreRows[i].search( /^Point/ ) !== -1 ) {
					var hb = (i%2 === 0)? ' at hand' : ' at back';
					do {
						coreRows[i] += hb;
						++i;
					} while( i < coreRows.length && coreRows[i].search( /^Point/ ) !== -1 );
				}
				else {
					++i;
				}
			}
			// Done!
			coreText = coreRows.join( ',' );

			// Create human-readable version of coreText (remove duplicates, ignore hunting which is only 1 change long, and put a full stop for longer hunting)
			var coreTextHuman = '';
			for( i = 0; i < coreRows.length; ++i ) {
				if( coreRows[i] === 'Hunt up' || coreRows[i] === 'Hunt down' ) {
					if( i + 1 >= coreRows.length || coreRows[i+1] === 'Hunt up' || coreRows[i+1] === 'Hunt down' ) {
						coreTextHuman = coreTextHuman.replace( trimtrailingcommaregex, '' )+'. ';
						coreTextHuman += coreRows[i]+'. ';
					}
				}
				else {
					coreTextHuman += coreRows[i]+', ';
				}
				while( i < coreRows.length && coreRows[i] === coreRows[i+1] ) { ++i; }
			}
			coreTextHuman = coreTextHuman.replace( trimtrailingcommaregex, '' );
			coreTextHuman = coreTextHuman.toLowerCase().replace( /(^|\. )(.)/g, function( m, p1, p2 ) { return p1+p2.toUpperCase(); } ).replace( /\.\s*$/, '' ).replace( /^\. (h|H)/ , 'H' ) + '.';

			// Send back the text version
			return {
				coreRows: coreRows,
				coreHuman: coreTextHuman
			};
		},

		fromNotation: function( notation, stage, bell, wrap ) {
			if( typeof wrap === 'undefined' ) { wrap = false; }
			return Text.fromRows( PlaceNotation.allRows( PlaceNotation.parse( PlaceNotation.expand( notation, stage ), stage ), PlaceNotation.rounds( stage ) ), bell, wrap );
		}
	};

	return Text;
} );
