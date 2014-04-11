/**
 * Build Plugin
 *
 * @author potanin@UD
 * @version 1.1.2
 * @param grunt
 */
module.exports = function build( grunt ) {

  grunt.initConfig( {

    pkg: grunt.file.readJSON( 'composer.json' ),

    // Generate Locale Files.
    makepot: {
      target: {
        options: {
          domainPath: 'static/languages',
          mainFile: 'wp-rpc.php',
          potFilename: 'wp-rpc.pot',
          type: 'wp-plugin'
        }
      }
    },

    yuidoc: {
      compile: {
        name: '<%= pkg.name %>',
        description: '<%= pkg.description %>',
        version: '<%= pkg.version %>',
        url: '<%= pkg.homepage %>',
        options: {
          paths: 'lib',
          outdir: 'static/codex/'
        }
      }
    },

    // Compile LESS in app.css
    less: {
      production: {
        options: {
          yuicompress: true,
          relativeUrls: true
        },
        files: {
          'styles/wp-rpc.css': [ 'styles/src/wp-rpc.less' ],
          'styles/wp.rpc.editor.style.css': [ 'styles/src/wp.rpc.editor.style.less' ]
        }
      },
      development: {
        options: {
          relativeUrls: true
        },
        files: {
          'styles/wp-rpc.dev.css': [ 'styles/src/wp-rpc.less' ]
        }
      }
    },

    watch: {
      options: {
        interval: 100,
        debounceDelay: 500
      },
      less: {
        files: [
          'styles/src/*.*'
        ],
        tasks: [ 'less' ]
      },
      js: {
        files: [
          'scripts/src/*.*'
        ],
        tasks: [ 'uglify' ]
      }
    },

    uglify: {
      production: {
        options: {
          mangle: false,
          beautify: false
        },
        files: [
          {
            expand: true,
            cwd: 'scripts/src',
            src: [ '*.js' ],
            dest: 'scripts'
          }
        ]
      },
      staging: {
        options: {
          mangle: false,
          beautify: true
        },
        files: [
          {
            expand: true,
            cwd: 'scripts/src',
            src: [ '*.js' ],
            dest: 'scripts'
          }
        ]
      }
    },

    markdown: {
      all: {
        files: [
          {
            expand: true,
            src: 'readme.md',
            dest: 'static/',
            ext: '.html'
          }
        ],
        options: {
          markdownOptions: {
            gfm: true,
            codeLines: {
              before: '<span>',
              after: '</span>'
            }
          }
        }
      }
    },

    clean: {
      all: [
        "composer.lock",
        "scripts/app.js",
        "scripts/contact-form-7.js",
        "scripts/foobox.js",
        "scripts/require.js",
        "scripts/utility.js",
        "components/*",
        "vendor/*",
        "styles/*.css",
        "scripts/emitter",
        "scripts/event",
        "scripts/indexof",
        "scripts/ui",
        "scripts/utility"
      ],
      "update": [
        "composer.lock",
        "vendor/*"
      ]
    },

    symlink: {

      explicit: {
        dest: 'vendor/usabilitydynamics',
        src: '/vendor/usabilitydynamics'
      }

    },

    shell: {
      update: {
        options: {
          stdout: true
        },
        command: 'composer update --prefer-source'
      }
    }

  });

  // Load tasks
  grunt.loadNpmTasks( 'grunt-markdown' );
  grunt.loadNpmTasks( 'grunt-contrib-yuidoc' );
  grunt.loadNpmTasks( 'grunt-contrib-uglify' );
  grunt.loadNpmTasks( 'grunt-contrib-watch' );
  grunt.loadNpmTasks( 'grunt-contrib-less' );
  grunt.loadNpmTasks( 'grunt-contrib-concat' );
  grunt.loadNpmTasks( 'grunt-contrib-clean' );
  grunt.loadNpmTasks( 'grunt-wp-i18n' );

  // Register tasks
  grunt.registerTask( 'default', [ 'markdown', 'less' , 'yuidoc', 'uglify' ] );

  // Build Distribution
  grunt.registerTask( 'distribution', [] );

  // Update Environment
  grunt.registerTask( 'update', [ "clean:update", "shell:update" ] );

  // Clean, preparing for update
  grunt.registerTask( 'clean', [  ] );

};