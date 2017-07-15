define( ['jquery'], function( $ ) {
	return function( colorToTest ) {
		var $dummy = $('<div>').appendTo( $(document.body) );
		$dummy.css( 'backgroundColor', 'white' );
		$dummy.css( 'backgroundColor', colorToTest );
		var check = ($dummy.css('backgroundColor') != 'rgb(255, 255, 255)' || colorToTest == 'white'|| colorToTest == '#FFF'|| colorToTest == '#FFFFFF');
		$dummy.remove();
		return check;
	};
} );