module.exports = function(grunt) {

  grunt.initConfig({
    pkg: grunt.file.readJSON("package.json"),
		coffee: {
			compileJoined: {
				options: {
					join: true
				},
				files: {
					"public/js/<%= pkg.name %>.js": ["public/coffeescript/*.coffee"]
				}
	    }
	  },
		sass: {
	    dist: {
	      files: {
					"public/css/<%= pkg.name %>.css": ["public/scss/main.scss"]
	      }
	    }
	  },
    uglify: {
      js: {
				options: {
					banner: "/*! <%= pkg.name %> <%= pkg.version %> compiled on <%= grunt.template.today('yyyy-mm-dd') %> */\n",
				},
				files: {
					"public/js/<%= pkg.name %>.min.js": ["public/js/vendor/*", "public/js/<%= pkg.name %>.js"]
				}
      }
    },
		cssmin: {
		  css: {
				options: {
					banner: "/*! <%= pkg.name %> <%= pkg.version %> compiled on <%= grunt.template.today('yyyy-mm-dd') %> */\n",
				},
				files: {
					"public/css/<%= pkg.name %>.min.css": ["public/css/<%= pkg.name %>.css"]
				}
		  }
		},
		watch: {
		  js: {
		    files: ["public/coffeescript/*.coffee"],
		    tasks: ["coffee", "uglify"]
		  },
		  css: {
		    files: ["public/scss/*.scss", "public/scss/themes/*.scss", "public/scss/styles/*.scss"],
		    tasks: ["sass", "cssmin"]
		  }
		}
  });

  grunt.loadNpmTasks("grunt-contrib-uglify");
	grunt.loadNpmTasks("grunt-contrib-coffee");
	grunt.loadNpmTasks("grunt-contrib-watch");
	grunt.loadNpmTasks("grunt-contrib-sass");
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	
	grunt.registerTask("default", ["coffee", "sass", "uglify"]);

};