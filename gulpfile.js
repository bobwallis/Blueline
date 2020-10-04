var DEST = './web/';

var gulp         = require( 'gulp' );
var mergeStream  = require( 'merge-stream' );
var streamify    = require( 'gulp-streamify' );
var gzip         = require( 'gulp-gzip' );
var less         = require( 'gulp-less' );
var autoprefixer = require( 'gulp-autoprefixer' );
var cleanCSS     = require( 'gulp-clean-css' );
var imagemin     = require( 'gulp-imagemin' );
var requirejs    = require( 'gulp-requirejs' );
var amdclean     = require( 'gulp-amdclean' );
var terser       = require( 'gulp-terser' );
var sourcemaps   = require( 'gulp-sourcemaps' );


// Images
function images() {
	return mergeStream(
		gulp.src( ['src/Blueline/BluelineBundle/Resources/public/images/*.svg',
		           'src/Blueline/BluelineBundle/Resources/public/images/*.png',
				   'src/Blueline/BluelineBundle/Resources/public/images/*.gif',
				   'src/Blueline/MethodsBundle/Resources/public/images/*.png'] )
			.pipe( imagemin() )
			.pipe( gulp.dest( DEST+'images/' ) ),
		gulp.src( ['src/Blueline/BluelineBundle/Resources/public/images/favicon.ico',
		           'src/Blueline/BluelineBundle/Resources/public/images/favicon.svg'] )
			.pipe( imagemin() )
			.pipe( gulp.dest( DEST ) )
	);
};


// Fonts
function fonts() {
	return gulp.src( 'src/Blueline/BluelineBundle/Resources/public/fonts/*' )
		.pipe( gulp.dest( DEST+'fonts/' ) );
};


// Javascript
var require_paths = {
	shared:       'src/Blueline/BluelineBundle/Resources/public/js/',
	methods:      'src/Blueline/MethodsBundle/Resources/public/js/',
	services:     'src/Blueline/ServicesBundle/Resources/public/js/',
	jquery:       'src/Blueline/BluelineBundle/Resources/public/js/lib/jquery',
	eve:          'src/Blueline/BluelineBundle/Resources/public/js/lib/eve',
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
function js() {
	return requirejs( {
		baseUrl: './',
		include: 'shared/main',
		paths: require_paths,
		shim: require_shim,
		optimize: 'none',
		out: 'main.js'
    } )
		.pipe( amdclean.gulp() )
		.pipe( streamify( terser() ) )
		.pipe( gulp.dest( DEST+'js/' ) );
};


// CSS
function css() {
	return gulp.src( ['src/Blueline/BluelineBundle/Resources/public/css/all.less', 'src/Blueline/BluelineBundle/Resources/public/css/print.less'] )
		.pipe( sourcemaps.init() )
		.pipe( less() )
		.pipe( autoprefixer() )
		.pipe( cleanCSS( { keepSpecialComments: 0 } ) )
		.pipe( sourcemaps.write() )
		.pipe( gulp.dest( DEST+'css/' ) );
};


// Compress
function compressGzip() {
	return gulp.src( [DEST+'/**/*.svg', DEST+'/**/*.html', DEST+'/**/*.js', DEST+'/**/*.css'] )
		.pipe( gzip({ gzipOptions: { level: 9 } }) )
		.pipe( gulp.dest( DEST+'/' ) );
};


// Watch task
function watch() {
	gulp.watch( ['src/Blueline/BluelineBundle/Resources/public/images/**/*'], images );
	gulp.watch( ['src/Blueline/BluelineBundle/Resources/public/fonts/*'], fonts );
	gulp.watch( ['src/Blueline/BluelineBundle/Resources/public/css/**/*', 'src/Blueline/MethodsBundle/Resources/public/css/**/*', 'src/Blueline/ServicesBundle/Resources/public/css/**/*'], css );
	gulp.watch( ['src/Blueline/BluelineBundle/Resources/public/js/**/*', 'src/Blueline/MethodsBundle/Resources/public/js/**/*', 'src/Blueline/ServicesBundle/Resources/public/js/**/*'], js );
};


exports.default = gulp.series( gulp.parallel( css, js, fonts ), compressGzip );
exports.images = gulp.series( gulp.parallel( images ), compressGzip );
exports.watch = watch;
