module.exports = function (grunt) {
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		copy: {
			fonts: {
				files: [
					{ expand: true, flatten: true, dest: 'web/fonts/', src: 'src/Blueline/**/fonts/*.{woff,ttf}' }
				]
			},
			images: {
				files: [
					{ expand: true, flatten: true, dest: 'web/images/', src: ['src/Blueline/**/images/*.{svg,gif}', '!**/{favicon,iosicon}.svg'] }
				]
			}
		},
		svg2png: {
			all: {
				src: ['web/images/*.svg']
			}
		},
		imagemin: {
			all: {
				files: [
					{ expand: true, src: ['web/images/*'] }
				]
			}
		},
		compress: {
			all: {
				options: {
					mode: 'gzip',
					pretty: true,
					level: 6
				},
				files: [
					{ expand: true, rename: function(d,s) { return s+'.gz'; },  src: ['web/**/*.{css,js,svg}'] }
				]
			}
		}
	});

	grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.loadNpmTasks('grunt-svg2png');
	grunt.loadNpmTasks('grunt-contrib-imagemin');
	grunt.loadNpmTasks('grunt-contrib-compress');
	grunt.registerTask('default', 'Build all assets.', ['copy', 'svg2png', 'imagemin', 'compress']);
};