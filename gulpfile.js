import gulp         from 'gulp';
import imagemin     from 'gulp-imagemin';
import gzip         from 'gulp-gzip';
import postcss      from 'gulp-postcss';
import postcssImport from 'postcss-import';
import postcssNested from 'postcss-nested';
import autoprefixer from 'autoprefixer';
import cleanCSS     from 'gulp-clean-css';
import terser       from 'gulp-terser';
import sourcemaps   from 'gulp-sourcemaps';
import footer       from 'gulp-footer';
import { build as esbuild } from 'esbuild';

var DEST = './public/';

// Images
function gulp_images() {
	return gulp.src(['src/Resources/images/*.svg',
	                  'src/Resources/images/*.png'], { encoding: false } )
		.pipe( imagemin() )
		.pipe( gulp.dest( DEST+'images/' ) );
}

// Favicons (different destination)
function gulp_images_favicon() {
	return gulp.src(['src/Resources/images/favicon.ico',
	                  'src/Resources/images/favicon.svg'], { encoding: false } )
		.pipe( imagemin() )
		.pipe( gulp.dest( DEST ) );
}


// Fonts
function gulp_fonts() {
	return gulp.src( 'src/Resources/fonts/*', { encoding: false } )
		.pipe( gulp.dest( DEST+'fonts/' ) );
};


// Javascript
function gulp_js() {
	return esbuild( {
		entryPoints: ['src/Resources/js/main.js'],
		bundle: true,
		format: 'esm',
		sourcemap: true,
		minify: true,
		target: ['es2020'],
		outfile: DEST+'js/main.js'
	} );
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
	const plugins = [
		postcssImport(),
		postcssNested(),
		autoprefixer()
	];

	return gulp.src( ['src/Resources/css/all.css', 'src/Resources/css/print.css'] )
		.pipe( sourcemaps.init() )
		.pipe( postcss( plugins ) )
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
	gulp.watch( ['src/Resources/js/**/*'], gulp.parallel( [gulp_js, gulp_js_serviceWorker] ) );
};


export default gulp.series( gulp.parallel( [gulp_css, gulp_js, gulp_js_serviceWorker, gulp_fonts] ), gulp_compressGzip );
export const css    = gulp.series( [gulp_css, gulp_compressGzip] );
export const js     = gulp.series( gulp.parallel( [gulp_js, gulp_js_serviceWorker] ), gulp_compressGzip );
export const images = gulp.series( gulp.parallel( [gulp_images, gulp_images_favicon] ), gulp_compressGzip );
export const watch  = gulp_watch;
