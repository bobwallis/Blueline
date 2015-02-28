module.exports = function (grunt) {
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		copy: {
			fonts: {
				files: [
					{ dest: 'web/fonts/Blueline.woff', src: 'src/Blueline/BluelineBundle/Resources/public/fonts/Blueline.woff' },
					{ dest: 'web/fonts/Blueline.ttf', src: 'src/Blueline/BluelineBundle/Resources/public/fonts/Blueline.ttf' }
				]
			}
		}
	});

	grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.registerTask('default', ['copy']);
};