import gulp         from 'gulp';
import imagemin     from 'gulp-imagemin';
import mergeStream  from 'merge-stream';
import gzip         from 'gulp-gzip';
import less         from 'gulp-less';
import autoprefixer from 'gulp-autoprefixer';
import cleanCSS     from 'gulp-clean-css';
import requirejs    from 'gulp-requirejs';
import amdclean     from 'gulp-amdclean';
import terser       from 'gulp-terser';
import sourcemaps   from 'gulp-sourcemaps';
import footer       from 'gulp-footer';

var DEST = './public/';

// Images
function gulp_images() {
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
function gulp_fonts() {
	return gulp.src( 'src/Resources/fonts/*' )
		.pipe( gulp.dest( DEST+'fonts/' ) );
};


// Javascript
var require_paths = {
	eve:            'lib/eve',
	ready:          'lib/ready',
	deepmerge:      'lib/deepmerge',
	matches:        'lib/matches',
	'$document_on': 'lib/$document_on',
	'Array.fill':   'lib/Array.fill'
};
var require_shim = {
	'Modernizr': {
		exports: 'Modernizr'
	},
	'Array.fill': {
		exports: 'Array.prototype.fill'
	}
};
function gulp_js() {
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
function gulp_js_serviceWorker() {
	return gulp.src( 'src/Resources/js/service_worker.js' )
	.pipe( sourcemaps.init() )
	.pipe( terser( { format: { comments: false } } ) )
	.pipe( footer( "\n//# "+(new Date().toISOString()) ) )
	.pipe( sourcemaps.write( 'maps' ) )
	.pipe( gulp.dest( DEST ) );
};


// CSS
function gulp_css() {
	return gulp.src( ['src/Resources/css/all.less', 'src/Resources/css/print.less'] )
		.pipe( sourcemaps.init() )
		.pipe( less() )
		.pipe( autoprefixer() )
		.pipe( cleanCSS( { keepSpecialComments: 0 } ) )
		.pipe( sourcemaps.write('../maps') )
		.pipe( gulp.dest( DEST+'css/' ) );
};


// Compress
function gulp_compressGzip() {
	return gulp.src( [DEST+'/**/*.svg', DEST+'/**/*.html', DEST+'/**/*.js', DEST+'/**/*.css'] )
		.pipe( gzip({ gzipOptions: { level: 9 } }) )
		.pipe( gulp.dest( DEST+'/' ) );
};


// Watch task
function gulp_watch() {
	gulp.watch( ['src/Resources/images/**/*'], gulp_images );
	gulp.watch( ['src/Resources/fonts/*'], gulp_fonts );
	gulp.watch( ['src/Resources/css/**/*'], gulp_css );
	gulp.watch( ['src/Resources/js/**/*'], gulp.parallel( gulp_js, gulp_js_serviceWorker ) );
};


export default gulp.series( gulp.parallel( gulp_css, gulp_js, gulp_js_serviceWorker, gulp_fonts ), gulp_compressGzip );
export const css    = gulp.series( gulp_css, gulp_compressGzip );
export const js     = gulp.series( gulp.parallel( gulp_js, gulp_js_serviceWorker ), gulp_compressGzip );
export const images = gulp.series( gulp.parallel( gulp_images ), gulp_compressGzip );
export const watch  = gulp_watch;
