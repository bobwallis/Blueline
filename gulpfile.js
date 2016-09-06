var DEST = './web/';

var gulp            = require( 'gulp' );
var plumber         = require( 'gulp-plumber' );
var rename          = require( 'gulp-rename' );
var concat          = require( 'gulp-concat');
var flatten         = require( 'gulp-flatten' );
var zopfli          = require( 'gulp-zopfli' );
var es              = require( 'event-stream' );
var merge           = require( 'merge-stream' );
var svg2png         = require( 'gulp-svg2png' );
var less            = require( 'gulp-less' );
var autoprefixer    = require( 'gulp-autoprefixer' );
var cleanCSS        = require( 'gulp-clean-css' );
var imagemin        = require( 'gulp-imagemin' );
var imagemin_zopfli = require( 'imagemin-zopfli' );
var imageresize     = require( 'gulp-image-resize' );
var requirejs       = require( 'gulp-requirejs' );
var amdclean        = require( 'gulp-amdclean' );
var uglify          = require( 'gulp-uglify' );
var sourcemaps      = require( 'gulp-sourcemaps' );

gulp.task( 'default', ['css', 'js', 'fonts'], function() {} );
gulp.task( 'images_all', ['appicon', 'favicon', 'maskicon', 'androidicon', 'splash', 'images'], function() {} );

// App Icon
gulp.task( 'appicon', ['appicon-png', 'appicon-svg'], function() {} );
var appicon_sizes = [57, 72, 76, 96, 114, 120, 144, 152, 180, 192, 196, 256, 384, 512, 768];
gulp.task( 'appicon-png', function() {
	var tasks = appicon_sizes.map( function( size ) {
		return gulp.src( 'src/Blueline/BluelineBundle/Resources/public/images/appicon.svg' )
			.pipe( svg2png( size/63 ) )
			.pipe( imagemin( { use: [imagemin_zopfli()] } ) )
			.pipe( rename( function( path ) {
				path.basename += '-'+size+'x'+size;
			} ) )
			.pipe( gulp.dest( DEST+'images/' ) );
	} );
	return es.merge.apply( null, tasks );
} );
gulp.task( 'appicon-svg', function() {
	return gulp.src( 'src/Blueline/BluelineBundle/Resources/public/images/appicon.svg' )
		.pipe( imagemin() )
		.pipe( gulp.dest( DEST+'images/' ) )
		.pipe( zopfli() )
		.pipe( gulp.dest( DEST+'images/' ) );
} );


// Favicon
gulp.task( 'favicon', ['favicon-png', 'favicon-ico', 'favicon-svg'], function() {} );
var favicon_sizes = [70, 144, 150, 310];
gulp.task( 'favicon-ico', function() {
	gulp.src( 'src/Blueline/BluelineBundle/Resources/public/images/favicon.ico' )
		.pipe( gulp.dest( DEST ) );
} );
gulp.task( 'favicon-png', function() {
	var fp1 = gulp.src( 'src/Blueline/BluelineBundle/Resources/public/images/favicon.svg' )
		.pipe( svg2png( 64/63 ) )
		.pipe( imagemin( { use: [imagemin_zopfli()] } ) )
		.pipe( gulp.dest( DEST+'images/' ) );
	var fp2 = es.merge.apply( null, favicon_sizes.map( function( size ) {
		return gulp.src( 'src/Blueline/BluelineBundle/Resources/public/images/favicon.svg' )
			.pipe( svg2png( size/63 ) )
			.pipe( imagemin( { use: [imagemin_zopfli()] } ) )
			.pipe( rename( function( path ) {
				path.basename += '-'+size+'x'+size;
			} ) )
			.pipe( gulp.dest( DEST+'images/' ) );
	} ) );
	return merge( fp1, fp2 );
} );
gulp.task( 'favicon-svg', function() {
	gulp.src( 'src/Blueline/BluelineBundle/Resources/public/images/favicon.svg' )
		.pipe( imagemin() )
		.pipe( gulp.dest( DEST ) )
		.pipe( zopfli() )
		.pipe( gulp.dest( DEST ) );
} );


// Mask icon
gulp.task( 'maskicon', ['maskicon-svg'], function() {} );
gulp.task( 'maskicon-svg', function() {
	gulp.src( 'src/Blueline/BluelineBundle/Resources/public/images/maskicon.svg' )
		.pipe( imagemin() )
		.pipe( gulp.dest( DEST+'images/' ) )
		.pipe( zopfli() )
		.pipe( gulp.dest( DEST+'images/' ) );
} );


// Android Icon
gulp.task( 'androidicon', ['androidicon-png', 'androidicon-svg'], function() {} );
var androidicon_sizes = [48, 96, 128, 144, 192, 256, 384, 512];
gulp.task( 'androidicon-png', function() {
	var fp1 = gulp.src( 'src/Blueline/BluelineBundle/Resources/public/images/androidicon.svg' )
		.pipe( svg2png( 64/192 ) )
		.pipe( imagemin( { use: [imagemin_zopfli()] } ) )
		.pipe( gulp.dest( DEST+'images/' ) );
	var fp2 = es.merge.apply( null, androidicon_sizes.map( function( size ) {
		return gulp.src( 'src/Blueline/BluelineBundle/Resources/public/images/androidicon.svg' )
			.pipe( svg2png( size/192 ) )
			.pipe( imagemin( { use: [imagemin_zopfli()] } ) )
			.pipe( rename( function( path ) {
				path.basename += '-'+size+'x'+size;
			} ) )
			.pipe( gulp.dest( DEST+'images/' ) );
	} ) );
	return merge( fp1, fp2 );
} );
gulp.task( 'androidicon-svg', function() {
	gulp.src( 'src/Blueline/BluelineBundle/Resources/public/images/androidicon.svg' )
		.pipe( imagemin() )
		.pipe( gulp.dest( DEST+'images/' ) )
		.pipe( zopfli() )
		.pipe( gulp.dest( DEST+'images/' ) );
} );


// Splash
gulp.task( 'splash', ['splash-png'], function() {} );
var splash_sizes = [ [1536,2008], [1496,2048], [768,1004], [748,1024],[1242,2148], [1182,2208], [750,1294],[640,1096], [640,920], [320,460] ];
gulp.task( 'splash-png', function() {
	var tasks = splash_sizes.map( function( size ) {
		return gulp.src( 'src/Blueline/BluelineBundle/Resources/public/images/splash.svg' )
			.pipe( svg2png() )
			.pipe( imageresize( { width: size[0], height: size[1], upscale: true, crop: true } ) )
			.pipe( imagemin( { use: [imagemin_zopfli()] } ) )
			.pipe( rename( function( path ) {
				path.basename += '-'+size[0]+'x'+size[1];
			} ) )
			.pipe( gulp.dest( DEST+'images/' ) );
	} );
	return es.merge.apply( null, tasks );
} );


// Other images
gulp.task( 'images', ['images-svg', 'images-png', 'images-gif'], function() {} );
gulp.task( 'images-svg', function() {
	gulp.src( 'src/Blueline/BluelineBundle/Resources/public/images/!(favicon|appicon|splash|maskicon|androidicon).svg' )
		.pipe( imagemin() )
		.pipe( gulp.dest( DEST+'images/' ) )
		.pipe( zopfli() )
		.pipe( gulp.dest( DEST+'images/' ) );
} );
gulp.task( 'images-png', function() {
	gulp.src( 'src/Blueline/BluelineBundle/Resources/public/images/!(favicon|appicon|splash|maskicon|androidicon).svg' )
		.pipe( svg2png() )
		.pipe( imagemin( { use: [imagemin_zopfli()] } ) )
		.pipe( gulp.dest( DEST+'images/' ) );
	gulp.src( 'src/Blueline/MethodsBundle/Resources/public/images/*.png' )
		.pipe( imagemin( { use: [imagemin_zopfli()] } ) )
		.pipe( gulp.dest( DEST+'images/' ) );
} );
gulp.task( 'images-gif', function() {
	gulp.src( 'src/Blueline/BluelineBundle/Resources/public/images/*.gif' )
		.pipe( imagemin() )
		.pipe( gulp.dest( DEST+'images/' ) );
} );


// Fonts
gulp.task( 'fonts', function() {
	gulp.src( 'src/Blueline/BluelineBundle/Resources/public/fonts/*' )
		.pipe( gulp.dest( DEST+'fonts/' ) );
} );


// Javascripts
gulp.task( 'js', ['js-old_ie', 'js-main', 'js-export', 'js-workers'], function() {} );
var old_ie_js_sources = ['src/Blueline/BluelineBundle/Resources/public/js/helpers/old_ie.js',
                         'src/Blueline/BluelineBundle/Resources/public/js/lib/Array.filter.js',
                         'src/Blueline/BluelineBundle/Resources/public/js/lib/Array.indexOf.js',
                         'src/Blueline/BluelineBundle/Resources/public/js/lib/Array.map.js',
                         'src/Blueline/BluelineBundle/Resources/public/js/lib/Array.forEach.js',
                         'src/Blueline/BluelineBundle/Resources/public/js/lib/html5shiv.js'];
var require_paths = {
	shared:       'src/Blueline/BluelineBundle/Resources/public/js/',
	methods:      'src/Blueline/MethodsBundle/Resources/public/js/',
	towers:       'src/Blueline/TowersBundle/Resources/public/js/',
	services:     'src/Blueline/ServicesBundle/Resources/public/js/',
	jquery:       'src/Blueline/BluelineBundle/Resources/public/js/lib/jquery',
	eve:          'src/Blueline/BluelineBundle/Resources/public/js/lib/eve',
	db:           'src/Blueline/BluelineBundle/Resources/public/js/lib/db',
	Modernizr:    'src/Blueline/BluelineBundle/Resources/public/js/lib/modernizr',
	'Array.fill': 'src/Blueline/BluelineBundle/Resources/public/js/lib/Array.fill'
};
var require_shim = {
	'Modernizr': {
		exports: 'Modernizr'
	},
	'Array.fill': {
		exports: 'Array.prototype.fill'
	}
};
gulp.task( 'js-old_ie', function() {
	gulp.src( old_ie_js_sources )
		.pipe( concat( 'old_ie.js' ) )
		.pipe( uglify() )
		.pipe( gulp.dest( DEST+'js/' ) )
		.pipe( zopfli() )
		.pipe( gulp.dest( DEST+'js/' ) );

} );
gulp.task( 'js-main', function() {
	requirejs( {
		baseUrl: './',
		include: 'shared/main',
		paths: require_paths,
		shim: require_shim,
		optimize: 'none',
		out: 'main.js'
    } )
		.pipe( amdclean.gulp() )
		.pipe( sourcemaps.init() )
		.pipe( uglify() )
		.pipe( sourcemaps.write( '.' ) )
		.pipe( gulp.dest( DEST+'js/' ) )
		.pipe( zopfli() )
		.pipe( gulp.dest( DEST+'js/' ) );
} );
gulp.task( 'js-export', function() {
	requirejs( {
		baseUrl: './',
		include: 'methods/export',
		paths: require_paths,
		shim: require_shim,
		optimize: 'none',
		out: 'export.js'
    } )
		.pipe( amdclean.gulp() )
		.pipe( sourcemaps.init() )
		.pipe( uglify() )
		.pipe( sourcemaps.write( '.' ) )
		.pipe( gulp.dest( DEST+'js/' ) )
		.pipe( zopfli() )
		.pipe( gulp.dest( DEST+'js/' ) );
} );
gulp.task( 'js-workers', function() {
	gulp.src( ['src/Blueline/ServicesBundle/Resources/public/js/gsiril.worker.js'] )
		.pipe( gulp.dest( DEST+'js/' ) )
		.pipe( zopfli() )
		.pipe( gulp.dest( DEST+'js/' ) );
} );


// CSS
gulp.task( 'css', function() {
	var all = gulp.src( ['src/Blueline/BluelineBundle/Resources/public/css/all.less', 'src/Blueline/BluelineBundle/Resources/public/css/print.less', 'src/Blueline/BluelineBundle/Resources/public/css/old_ie.less', 'src/Blueline/MethodsBundle/Resources/public/css/export.less'] )
		.pipe( less() )
		.pipe( autoprefixer( { browsers: ['> 5%'] } ) )
		.pipe( sourcemaps.init() )
		.pipe( cleanCSS( { keepSpecialComments: 0 } ) )
		.pipe( sourcemaps.write( '.' ) )
		.pipe( gulp.dest( DEST+'css/' ) )
		.pipe( zopfli() )
		.pipe( gulp.dest( DEST+'css/' ) );
} );


// Watch task
gulp.task( 'watch', function() {
	gulp.watch( ['src/Blueline/BluelineBundle/Resources/public/images/splash.svg'], ['splash'] );
	gulp.watch( ['src/Blueline/BluelineBundle/Resources/public/images/appicon.svg'], ['appicon'] );
	gulp.watch( ['src/Blueline/BluelineBundle/Resources/public/images/favicon.svg'], ['favicon'] );
	gulp.watch( ['src/Blueline/BluelineBundle/Resources/public/images/androidicon.svg'], ['androidicon'] );
	gulp.watch( ['src/Blueline/BluelineBundle/Resources/public/images/!(favicon|appicon|splash|maskicon|androidicon).svg'], ['images'] );
	gulp.watch( ['src/Blueline/BluelineBundle/Resources/public/fonts/*'], ['fonts'] );
	gulp.watch( ['src/Blueline/BluelineBundle/Resources/public/css/**/*', 'src/Blueline/AssociationsBundle/Resources/public/css/**/*', 'src/Blueline/MethodsBundle/Resources/public/css/**/*', 'src/Blueline/ServicesBundle/Resources/public/css/**/*', 'src/Blueline/TowersBundle/Resources/public/css/**/*'], ['css'] );
	gulp.watch( ['src/Blueline/BluelineBundle/Resources/public/js/**/*', 'src/Blueline/AssociationsBundle/Resources/public/js/**/*', 'src/Blueline/MethodsBundle/Resources/public/js/**/*', 'src/Blueline/ServicesBundle/Resources/public/js/**/*', 'src/Blueline/TowersBundle/Resources/public/js/**/*'], ['js-main', 'js-export'] );
	gulp.watch( old_ie_js_sources, ['js-old_ie'] );
} );