module.exports = function(grunt) {

  // Project configuration.
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    
		
	 cssmin: {
	  target: {
		files: [{
		  expand: true,
		  cwd: 'assets/css',
		  src: ['*.css', '!*.min.css'],
		  dest: 'assets/css',
		  ext: '.min.css'
		}]
	  }
	},
	uglify: {
      options: {
        banner: '/*! <%= pkg.name %> <%= grunt.template.today("yyyy-mm-dd") %> */\n'
      },
      build: {
        src: 'assets/js/scripts.js',
        dest: 'assets/js/scripts.min.js'
      }
    },
	clean: {

  all_dw: ['**/dwsync.xml'],
  all_dw_folders: ['**/_notes'],
},
 cleanempty: {
    options: {
      // Task-specific options go here.
    },
    your_target: {
      // Target-specific file lists and/or options go here.
    },
  },
	
	
  });

  // Load the plugin that provides the "uglify" task.
  grunt.loadNpmTasks('grunt-contrib-uglify');
  //minify css
  grunt.loadNpmTasks('grunt-contrib-cssmin');
  //clean files
  grunt.loadNpmTasks('grunt-contrib-clean');
  //remove empty folders
  grunt.loadNpmTasks('grunt-cleanempty');

  // Default task(s).
  grunt.registerTask('default', ['uglify','cssmin','clean','cleanempty']);

};