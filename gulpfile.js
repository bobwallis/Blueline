var DEST = './public/';

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
	return gulp.src( 'src/Resources/public/fonts/*' )
		.pipe( gulp.dest( DEST+'fonts/' ) );
};


// Javascript
var require_paths = {
	blueline:     'src/Resources/js/',
	jquery:       'src/Resources/js/lib/jquery',
	eve:          'src/Resources/js/lib/eve',
	Modernizr:    'src/Resources/js/lib/modernizr',
	'Array.fill': 'src/Resources/js/lib/Array.fill'
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
		include: 'blueline/main',
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
	return gulp.src( ['src/Resources/css/all.less', 'src/Resources/css/print.less'] )
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
	gulp.watch( ['src/Resources/public/images/**/*'], images );
	gulp.watch( ['src/Resources/public/fonts/*'], fonts );
	gulp.watch( ['src/Resources/public/css/**/*'], css );
	gulp.watch( ['src/Resources/public/js/**/*'], js );
};


exports.default = gulp.series( gulp.parallel( css, js, fonts ), compressGzip );
exports.css = gulp.series( css, compressGzip );
exports.js = gulp.series( js, compressGzip );
exports.images = gulp.series( gulp.parallel( images ), compressGzip );
exports.watch = watch;
