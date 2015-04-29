module.exports = function(grunt) {

	'use strict';

	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

		sass: {
			options: {
				compass: true,
				style: "compressed"
			},
			code: {
				files: {
					'style.css' : 'src/scss/style.scss',
				}	
			}
		},
		jade: {
			debug: {
				options: {
					// data: {
					// 	debug: true,
					// 	timestamp: "<%= grunt.template.today() %>"
					// }
				},
				files: [{
					cwd: "src/jade",
					src: "*.jade",
					dest: "views",
					expand: true,
					ext: ".html"
				}]
			}
		},

		browserify: {
			dist: {
				files: {
					'script.js' : ['src/javascript/script.js']
				}
				// ,
				// options: {
				// 	browserifyOptions: {
				// 		'standalone': 'Week'
				// 	}
				// }
			}
		},

		watch: {
			grunt: {
				files: [ 'Gruntfile.js' ],
				options: {
					reload: true
				}
			},
			src: {
				files: ['src/**/*'],
				tasks: ['default'],
				options: {
					livereload: 35730
				}
			},
			options: {
				spawn: false
			}
		}
	});

	grunt.loadNpmTasks('grunt-contrib-sass');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-contrib-jade');
	grunt.loadNpmTasks('grunt-browserify');

	grunt.registerTask('default', ['jade', 'sass', 'browserify', 'watch']);

};