module.exports = function (grunt) {
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		watch: {
			gif: {
				files: ['src/Blueline/**/*.gif'],
				tasks: ['copy:gif', 'imagemin:gif'],
				options: { spawn: false }
			},
			less: {
				files: ['src/Blueline/**/*.{less,css}'],
				tasks: ['less', 'compress:css'],
				options: { spawn: false }
			},
			svg: {
				files: ['src/Blueline/**/*.svg'],
				tasks: ['copy:svg', 'imagemin:svg', 'compress:svg', 'svg2png', 'imagemin:png'],
				options: { spawn: false }
			}
		},

		copy: {
			fonts: {
				files: [
					{ expand: true, flatten: true, dest: 'web/fonts/', src: 'src/Blueline/**/fonts/*.{woff,woff2,ttf}' }
				]
			},
			gif: {
				files: [
					{ expand: true, flatten: true, dest: 'web/images/', src: ['src/Blueline/**/images/*.gif'] }
				]
			},
			js: {
				files: [
					{ dest: 'web/js/gsiril.worker.js', src: ['src/Blueline/ServicesBundle/Resources/public/js/gsiril.worker.js'] }
				]
			},
			svg: {
				files: [
					{ expand: true, flatten: true, dest: 'web/images/', src: ['src/Blueline/**/images/*.svg', '!**/{favicon,iosicon}.svg'] }
				]
			}
		},

		svg2png: {
			all: {
				src: ['web/images/*.svg']
			}
		},

		less: {
			all: {
				options: {
					plugins: [
						new (require('less-plugin-autoprefix'))({browsers: ["last 2 versions"]}),
						new (require('less-plugin-clean-css'))()
					]
				},
				files: [
					{ dest: 'web/css/all.css', src: ['bower_components/normalize.css/normalize.css', 'src/Blueline/BluelineBundle/Resources/public/css/all.less'] },
					{ dest: 'web/css/print.css', src: 'src/Blueline/BluelineBundle/Resources/public/css/print.less' },
					{ dest: 'web/css/old_ie.css', src: 'src/Blueline/BluelineBundle/Resources/public/css/old_ie.less' }
				]
			}
		},

		imagemin: {
			gif: {
				files: [
					{ expand: true, src: ['web/images/*.gif'] }
				]
			},
			png: {
				files: [
					{ expand: true, src: ['web/images/*.png'] }
				]
			},
			svg: {
				files: [
					{ expand: true, src: ['web/images/*.svg'] }
				]
			}
		},

		compress: {
			css: {
				options: {
					mode: 'gzip',
					pretty: true,
					level: 6
				},
				files: [
					{ expand: true, rename: function(d,s) { return s+'.gz'; },  src: ['web/**/*.css'] }
				]
			},
			js: {
				options: {
					mode: 'gzip',
					pretty: true,
					level: 6
				},
				files: [
					{ expand: true, rename: function(d,s) { return s+'.gz'; },  src: ['web/**/*.js'] }
				]
			},
			svg: {
				options: {
					mode: 'gzip',
					pretty: true,
					level: 6
				},
				files: [
					{ expand: true, rename: function(d,s) { return s+'.gz'; },  src: ['web/**/*.svg'] }
				]
			}
		}
	});

	grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.loadNpmTasks('grunt-svg2png');
	grunt.loadNpmTasks('grunt-contrib-imagemin');
	grunt.loadNpmTasks('grunt-contrib-less');
	grunt.loadNpmTasks('grunt-contrib-compress');
	grunt.loadNpmTasks('grunt-contrib-watch');

	grunt.registerTask('default', 'Build all assets.', ['copy', 'svg2png', 'imagemin', 'less', 'compress']);
};