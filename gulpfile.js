var DEST = './public/';

var gulp         = require( 'gulp' );
var mergeStream  = require( 'merge-stream' );
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
		gulp.src( ['src/Resources/images/*.svg',
		           'src/Resources/images/*.png',
				   'src/Resources/images/*.gif',
				   'src/Resources/images/*.png'] )
			.pipe( imagemin() )
			.pipe( gulp.dest( DEST+'images/' ) ),
		gulp.src( ['src/Resources/images/favicon.ico',
		           'src/Resources/images/favicon.svg'] )
			.pipe( imagemin() )
			.pipe( gulp.dest( DEST ) )
	);
};


// Fonts
function fonts() {
	return gulp.src( 'src/Resources/fonts/*' )
		.pipe( gulp.dest( DEST+'fonts/' ) );
};


// Javascript
var require_paths = {
	jquery:       'lib/jquery',
	eve:          'lib/eve',
	ready:        'lib/ready',
	Modernizr:    'lib/modernizr',
	'Array.fill': 'lib/Array.fill'
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
		baseUrl: 'src/Resources/js/',
		include: 'main',
		paths: require_paths,
		generateSourceMaps: true,
		shim: require_shim,
		optimize: 'none',
		out: 'main.js'
	} ).on('error', function( error ) { console.log( error ); } )
		.pipe( sourcemaps.init( { loadMaps: true } ) )
		.pipe( amdclean.gulp() )
		.pipe( terser( { format: { comments: false } } ) )
		.pipe( sourcemaps.write('../maps') )
		.pipe( gulp.dest( DEST+'js/' ) );
};
function js_serviceWorker() {
	return gulp.src( 'src/Resources/js/service_worker.js' )
	.pipe( sourcemaps.init() )
	.pipe( terser( { format: { comments: false } } ) )
	.pipe( sourcemaps.write( 'maps' ) )
	.pipe( gulp.dest( DEST ) );
};


// CSS
function css() {
	return gulp.src( ['src/Resources/css/all.less', 'src/Resources/css/print.less'] )
		.pipe( sourcemaps.init() )
		.pipe( less() )
		.pipe( autoprefixer() )
		.pipe( cleanCSS( { keepSpecialComments: 0 } ) )
		.pipe( sourcemaps.write('../maps') )
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
	gulp.watch( ['src/Resources/images/**/*'], images );
	gulp.watch( ['src/Resources/fonts/*'], fonts );
	gulp.watch( ['src/Resources/css/**/*'], css );
	gulp.watch( ['src/Resources/js/**/*'], gulp.parallel( js, js_serviceWorker ) );
};


exports.default = gulp.series( gulp.parallel( css, js, js_serviceWorker, fonts ), compressGzip );
exports.css = gulp.series( css, compressGzip );
exports.js = gulp.series( gulp.parallel( js, js_serviceWorker ), compressGzip );
exports.images = gulp.series( gulp.parallel( images ), compressGzip );
exports.watch = watch;
