// Build file

var gulp = require('gulp')
var util = require('gulp-util')
// var assign = require('object-assign')
var browserify = require('browserify')
var browsersync = require('browser-sync').create()
var buffer = require('vinyl-buffer')
// var cheerio = require('cheerio')
var compass = require('gulp-compass')
var concat = require('gulp-concat')
var csso = require('gulp-csso')
var cleanCss = require('gulp-clean-css')
var del = require('del')
var deepAssign = require('deep-assign')
var fs = require('fs')
var _if = require('gulp-if')
var lazypipe = require('lazypipe')
var modernizr = require('gulp-modernizr')
var autoprefixer = require('gulp-autoprefixer')
var raster = require('gulp-raster')
var rename = require('gulp-rename')
var path = require('path')
// var sass = require('gulp-sass')
var source = require('vinyl-source-stream')
var streamify = require('gulp-streamify')
var svgmin = require('gulp-svgmin')
var svgstore = require('gulp-svgstore')
// var transform = require('vinyl-transform')
// var through2 = require('through2')
var twig = require('gulp-twig')
var uglify = require('gulp-uglify')
// var uglifycss = require('gulp-uglifycss')
var watchify = require('watchify')
var yargs = require('yargs').argv
var logCapture = require('gulp-log-capture')

// Custom i18n
var Dictionary = require('./src/js/app/lib/Dictionary.js')

// Unilend GLOBAL vars
var Unilend = GLOBAL.Unilend = {
  // Config settings object
  config: {},

  // Dictionary data
  dictionaryData: {},

  // Intl (instance of Dictionary, references dictionaryData)
  __: false
}

// Fix gulp's error handling
// See: https://github.com/gulpjs/gulp/issues/71
var origSrc = gulp.src
gulp.src = function () {
  return fixPipe(origSrc.apply(this, arguments))
}
function fixPipe (stream) {
  var origPipe = stream.pipe
  stream.pipe = function (dest) {
    arguments[0] = dest.on('error', function (error) {
      var nextStreams = dest._nextStreams
      if (nextStreams) {
        nextStreams.forEach(function (nextStream) {
          nextStream.emit('error', error)
        })
      } else if (dest.listeners('error').length === 1) {
        throw error
      }
    })
    var nextStream = fixPipe(origPipe.apply(this, arguments));
    (this._nextStreams || (this._nextStreams = [])).push(nextStream)
    return nextStream
  }
  return stream
}

/*
 * Environment config
 */
var env = yargs.env || 'dev'

// Configure path to config files
var configPath = yargs.configPath || goodPath('./config')

// -- Add trailing slash
if (!new RegExp(path.sep + '$', 'g').test(configPath)) configPath += path.sep

// Reference an additional configFile to override
var configFile = yargs.configFile || false

// Default configs (and loading config overrides)
util.log(util.colors.yellow('@@@ Loading config...'))
Unilend.config = deepAssign({
  // The environment
  env: env || 'dev',

  // Root src path, used by getSrc
  src: goodPath(yargs.src) || goodPath('./src'),

  // Root dest path, used by getDest
  dest: goodPath(yargs.dest) || goodPath('./public/'),

  // Browser-sync server configuration
  browserSync: deepAssign({
    server: {
      baseDir: goodPath(yargs.serverBaseDir) || goodPath('./public/')
    }
  }, getJSON(goodPath(configPath + '/browserSync.json')), getJSON(goodPath(configPath + env + '/browserSync.json'))),

  // Watch files
  watchFiles: yargs.watchFiles || false,

  // Compression
  compressCss: yargs.compress || yargs.compressCss || false,
  compressJs: yargs.compress || yargs.compressJs || false,

  // JSON Dictionary file (for translation)
  dictionaryFile: goodPath(yargs.dictionaryFile) || goodPath('./src/lang/Unilend.lang.json'),

  // Verbose output
  verbose: yargs.verbose || false,

  // Use SVG symbols (if deploying to file-system environment, i.e. not via server, then recommended to leave this false)
  useSVG: false,

  // JS Bundles
  bundles: {
    main: {
      src: goodPath('./src/js/main.dev.js'),
      dest: goodPath('./src/js/main.js')
    }
  },

  // Compass defaults
  compass: deepAssign({
    http_path: '/',
    css: goodPath('./src/css'),
    sass: goodPath('./src/sass'),
    image: goodPath('./src/media'),
    relative: true,
    debug: false
  }, getJSON(goodPath(configPath + '/compass.json')), getJSON(goodPath(configPath + env + '/compass.json'))),

  // Twig defaults
  twig: deepAssign({
    // The base path where Twig files are located. Referenced by Twig's `import` and `extends` methods
    base: goodPath('./src/twig'),

    // Data used within Twig templates
    data: {
      env: env,
      // The lang to build HTML files as
      lang: yargs.htmlLang || 'fr',

      // Site options
      site: {
        // Site base HTTP URL
        base: '/',

        // AJAX base HTTP URL
        ajax: '/',

        // Site assets HTTP URLs: where css, js, and media assets are located
        // (no trailing slash)
        assets: {
          base: '/',
          css: '/css/',
          js: '/js/',
          media: '/media/'
        }
      }

      // There are a lot more variables to reference here and within twig.json
      // See config/twig.example.json for all possible values
    }
  }, getJSON(goodPath(configPath + '/twig.json')), getJSON(goodPath(configPath + env + '/twig.json')))
}, getJSON(goodPath(configPath + '/config.json')), getJSON(goodPath(configPath + env + '/config.json')))

// Load in the additional config file
if (configFile) {
  Unilend.config = deepAssign(Unilend.config, getJSON(goodPath(configFile)))
}

// Load the dictionary data and set the default lang
util.log(util.colors.yellow('@@@ Loading dictionary...'))
Unilend.dictionaryData = getJSON(Unilend.config.dictionaryFile)
if (Unilend.dictionaryData) {
  Unilend.__ = new Dictionary(Unilend.dictionaryData, Unilend.config.twig.data.lang)
  util.log(util.colors.yellow('@@@ Set dictionary to use lang ') + util.colors.cyan(Unilend.config.twig.data.lang))
}

// Load Twig extensions (after the Dictionary)
//Unilend.config.twig.extend = require(goodPath('./src/twig/extensions/twig.extensions.js'))

// Add Twig routes to the data to output in the templates for debugging
Unilend.config.twig.data.routes = Unilend.config.twig.routes

// Shorthand to reference config
var config = Unilend.config

// A good path, for Mac and Windows
function goodPath (input) {
  if (!input) return input
  var inputItems = input.split(/[\/\\]/)
  return (/^\./.test(input) ? '.' + path.sep : '') + path.join.apply(input, inputItems)
}

// The config's src path
function getSrc (input) {
  return goodPath((/^\./.test(config.src) ? '.' + path.sep : '') + path.join(config.src, (input || '')))
}

// The config's dest path
function getDest (input) {
  return goodPath((/^\./.test(config.dest) ? '.' + path.sep : '') + path.join(config.dest, (input || '')))
}

// Get JSON file contents
function getJSON (input) {
  if (fs.existsSync(input)) {
    util.log(util.colors.green(input + ' exists!'))
    // Will error if JSON is invalid
    try {
      var loadJson = fs.readFileSync(input, 'utf8')
      // Reduce any funky encoding possibilities
      // See: http://stackoverflow.com/a/24376813/1421162
      loadJson = JSON.parse(loadJson.toString('utf8').replace(/^\uFEFF/, ''))
      return loadJson
    } catch (e) {
      throw new Error(util.colors.red('Invalid JSON: ') + util.colors.yellow(input))
    }
  }
  util.log(util.colors.black(input + ' doesn\'t exist, ignoring...'))
  return
}

/*
 * Bundler
 * Enables browserify `require`, re-compiles when file changes happen and streams/reloads into browser (via watchify and browser-sync)
 */
function Bundler (options) {
  var self = this

  // Properties
  self.src = goodPath(options.src)
  self.dest = goodPath(options.dest)

  // Get specific files and folders of src and dest
  self.srcFile = self.src.split(path.sep).pop()
  self.srcFolder = self.src.replace(self.srcFile, '')
  self.destFile = self.dest.split(path.sep).pop()
  self.destFolder = self.dest.replace(self.destFile, '')

  // Bundle options
  self.bundleOptions = {
    entries: [self.src],
    cache: {},
    packageCache: {},
    debug: true
  }

  // Browserify bundle once and done for straight builds
  if (!config.watchFiles) {
    self.b = browserify(self.bundleOptions)

  // Browserify bundling + Watchify
  } else {
    util.log(util.colors.magenta('Watching bundle ') + util.colors.yellow(self.src))
    self.b = watchify(browserify(self.bundleOptions), {poll: true})
  }

  // Link Bundler.bundle() to browserify/watchify bundle method
  self.bundle = function () {
    // Notify user of bundling what and where
    util.log(util.colors.magenta('Bundling ') + util.colors.yellow(self.src) + ' --> ' + util.colors.green(self.dest))

    // Do the browserify/watchify bundling
    return self.b.bundle()
      .on('error', util.log.bind(util, util.colors.red('Browserify Error!')))
      // Pipe all bundled code into a destination file
      .pipe(source(self.destFile))
      .pipe(buffer())

      // Place in bundle's dest folder
      .pipe(gulp.dest(self.destFolder))

      // Do generic JS tasks
      .pipe(jsTasks())
  }

  // Hook into file changes
  self.b.on('update', self.bundle)
  self.b.on('update', self.bundle)

  // Hook browserify/watchify log method to gulp-util
  // Hook browserify/watchify log method to gulp-util
  self.b.on('log', util.log)

  // Perform the first bundle
  self.bundle()
}

/*
 * This function automates setting up the bundles
 */
function setupBundles () {
  var bundleDests = []
  for (var x in config.bundles) {
    if (!(config.bundles[x] instanceof Bundler)) {
      // Too inception-y?
      config.bundles[x] = new Bundler(config.bundles[x])
    }
    bundleDests.push(config.bundles[x].dest)
  }

  // Return the bundles' dest files for further piping to other tasks
  return gulp.src(bundleDests)
}

/*
 * Gulp Tasks
 */
gulp.task('default', ['build', 'watch'])
gulp.task('watch', [/*'browsersync',*/ 'build', 'watchfiles'])
gulp.task('build', ['clean', 'svg', /*'twig',*/ 'cssdependencies', 'scss', 'modernizr', 'jsdependencies', 'jsbundles', 'copy'])

// Clean build directory
gulp.task('clean', function () {
  util.log(util.colors.red('Clearing contents from ' + getDest()))

  // Synchronise delete
  del.sync([getDest('/**'), '!' + getDest()])
})

// Copy assets to build directory
// Depends on svg2png task to run first before copying the files
gulp.task('copy', function () {
  // Copy media assets
  gulp.src([getSrc('images/**/*'),
      '!' + getSrc('images/svg/**/*')]) // Ignore files in the SVG folder (they'll be handled with `svg` task)
    .pipe(gulp.dest(getDest('images')))

  // Copy JS assets
  gulp.src([getSrc('js/vendor/**/*')])
    .pipe(gulp.dest(getDest('js/vendor')))

  // Copy font-awesome directory
  gulp.src([getSrc('images/fonts/unilend-fontawesome/fonts/*')])
      .pipe(gulp.dest(getDest('fonts/fonts')))
  gulp.src([getSrc('images/fonts/unilend-fontawesome/style.css')])
      .pipe(gulp.dest(getDest('fonts')))
})

/*
 * Setup the JS bundles
 */
gulp.task('jsbundles', setupBundles)

/*
 * General JS tasks
 */
var jsTasks = lazypipe()
  // Minify JS
  .pipe(function () {
    return _if(config.compressJs, uglify())
  })
  .pipe(function () {
    return _if(config.compressJs, rename({
      extname: '.min.js'
    }))
  })

  // Move to dest JS folder
  .pipe(gulp.dest, getDest('js'))

  // Update browsersync
  .pipe(function () {
    return _if(config.watchFiles, browsersync.stream())
  })

/*
 * General CSS tasks
 */
var cssTasks = lazypipe()
  // Minify CSS
  .pipe(function () {
    // return _if(config.compressCss, csso({
    //   restructure: true,
    //   sourceMap: true
    // }))
    return _if(config.compressCss, cleanCss({
      advanced: true,
      restructuring: true,
      aggressiveMerging: true,
      shorthandCompacting: false,
      mediaMerging: true,
      processImport: false,
      benchmark: true//,
      // sourceMap: true
    }))
  })
  .pipe(function () {
    return _if(config.compressCss, rename({
      extname: '.min.css'
    }))
  })

  // Move to dest CSS folder
  .pipe(gulp.dest, getDest('css'))

  // Update browsersync
  .pipe(function () {
    return _if(config.watchFiles, browsersync.stream())
  })

/*
 * General Twig tasks
 */
var twigTasks = lazypipe()
  // Process Twig
  .pipe(twig, config.twig)

  // Move to dest HTML folder
  .pipe(gulp.dest, getDest())

  // Update browsersync
  .pipe(function () {
    return _if(config.watchFiles, browsersync.stream())
  })

/*
 * General HTML tasks
 */
var htmlTasks = lazypipe()
  // Move to dest HTML folder
  .pipe(gulp.dest, getDest())

  // Update browsersync
  .pipe(function () {
    return _if(config.watchFiles, browsersync.stream())
  })

// Concat CSS dependencies into a single file
// @note Only reference files which don't have further assets (images, fonts, etc.). Put those into the `./src/js/vendor` folder and reference within the `./src/twig/layouts/_layout.twig` file
gulp.task('cssdependencies', function () {
  return gulp.src([//getSrc('')
                   ])

    // Concat into single `dependencies.css` to save on network IO
    .pipe(concat('dependencies.css'))

    // Generic CSS tasks
    .pipe(cssTasks())
})

// Concat JS files to build to one single dependency file
// @note this file should be used if any dependencies can't be loaded via browserify `require` (e.g. third-party plugins/scripts)
//       Additionally, use browserify-shim in the package.json to set global variables to use within bundled files
gulp.task('jsdependencies', function () {
  return gulp.src([getSrc('js/app/modernizr/modernizr.js'),
                   goodPath('./node_modules/jquery/dist/jquery.js'),
                   getSrc('js/vendor/jquery.caret.js'),
                   getSrc('js/vendor/videojs/video.js'),
                   goodPath('./node_modules/videojs-youtube/dist/Youtube.js'),
                   goodPath('./node_modules/fancybox/dist/js/jquery.fancybox.js'),
                   goodPath('./node_modules/fancybox/dist/helpers/js/jquery.fancybox-media.js'),
                   getSrc('js/vendor/swiper/js/swiper.jquery.js')
                   ])

    // Concat into single `dependencies.js` to save on network IO
    .pipe(concat('dependencies.js'))

    // Do generic JS tasks
    .pipe(jsTasks())
})

// Modernizr
gulp.task('modernizr', function () {
  // Generate modernizr from src development files
  return gulp.src([getSrc('css/*.css'),
                   getSrc('js/*.js'),
             '!' + getSrc('js/*.min.js')]) // Ignore minified
    .pipe(modernizr({
      classPrefix: 'has-',
      options: ['setClasses',
                'addTest',
                'html5printshiv',
                'testProp',
                'fnBind'],
      tests: ['cssanimations',
              'csstransitions',
              'csstransforms',
              'backgroundsize',
              'bgsizecover',
              'cssfilters',
              'touchevents',
              'csspointerevents']
    }))

    // Put this in the src as it gets referenced by jsdependencies
    .pipe(gulp.dest(getSrc('js/app/modernizr')))
})

// Twig
// -- Convert .twig layouts to .html files
gulp.task('twig', function () {

  // Run twig operations
  return gulp.src([getSrc('twig/pages/**/*.twig'),
             '!' + getSrc('twig/pages/**/_*.twig')]) // Ignore underscored files

    // Twig
    .pipe(twig(config.twig).on('error', util.log))

    // Do generic HTML tasks
    .pipe(htmlTasks())
})

// SCSS
// -- Convert SCSS files to CSS
gulp.task('scss', function () {
  return gulp.src(getSrc('sass/*.scss'))
    // Ruby/Compass is so sloooooow
    .pipe(compass(config.compass).on('error', util.log))
    .pipe(autoprefixer({
      browsers: ['last 2 versions'],
      cascade: true
    }))

    // Save to src for backup
    .pipe(gulp.dest(getSrc('css')))

    // Do all the generic css tasks
    .pipe(cssTasks())
})

// SVG Store
gulp.task('svg', function () {
  return gulp.src([getSrc('media/svg/**/*.svg'),
             '!' + getSrc('media/svg/icons.svg')]) // Ignore destination file
    .on('error', util.log)
    // Rename to include folder path in ID
    .pipe(rename(function (svgPath) {
      var name = svgPath.dirname.split(path.sep)
      name.push(svgPath.basename)
      svgPath.basename = name.join('-')
    }))
    // Minify SVG files
    .pipe(svgmin({
      plugins: [{
        removeDoctype: true
      },{
        removeComments: true
      },{
        removeViewBox: true
      },{
        convertStyleToAttrs: true
      },{
        cleanupNumericValues: {
          floatPrecision: 2
        }
      },{
        removeStyleElement: true
      }/*,{
        removeAttrs: {
          attrs: ['stroke-linecap', 'stroke-linejoin', 'stroke-miterlimit', 'clip-rule']
        }
      }*/]
    }))
    // Store in single SVG file
    .pipe(svgstore())
    // Rename file to icons.svg
    .pipe(rename('icons.svg'))
    .pipe(gulp.dest(getSrc('media/svg')))
    .pipe(gulp.dest(getDest('media/svg')))

    // Update browsersync
    .pipe(_if(config.watchFiles, browsersync.stream()))
})

// This task is a one-off, as in it's not part of the build stack
// @note currently this is not converting them correctly due to phantomjs not working
// @note Run on a per-use basis!, e.g. `gulp svg2png`
// gulp.task('svg2png', function () {
//   return gulp.src([getSrc('media/**/svg/*.svg'),
//              '!' + getSrc('media/svg/**/*.svg')])
//     // Do some minification/standardisation on the SVG files first
//     .pipe(svgmin({
//       plugins: [{
//         removeDoctype: true
//       },{
//         removeComments: true
//       },{
//         removeViewBox: true
//       },{
//         convertStyleToAttrs: true
//       },{
//         cleanupNumericValues: {
//           floatPrecision: 2
//         }
//       },{
//         removeStyleElement: true
//       }/*,{
//         removeAttrs: {
//           attrs: ['stroke-linecap', 'stroke-linejoin', 'stroke-miterlimit', 'clip-rule']
//         }
//       }*/]
//     }))
//     .pipe(raster())
//     .pipe(rename(function (pngPath) {
//       // Change SVG to PNG folder
//       pngPath.dirname = pngPath.dirname.replace('svg', 'png')
//       pngPath.extname = '.png'
//     }))
//     // Copy into the src and dest media folders
//     .pipe(gulp.dest(getSrc('media')))
//     .pipe(gulp.dest(getDest('media')))
// })

// Watch Files
gulp.task('watchfiles', /*['browsersync'],*/ function () {
  if (config.watchFiles) {
    // Compile src files
    // -- Only compile the changed twig page files
    gulp.watch(getSrc('twig/pages/**/*.twig')).on('change', function () {
      var changed = Array.prototype.slice.call(arguments)
      var files = []
      for (var i=0; i < changed.length; i++) {
        files.push(changed[i].path)
      }
      gulp.src(files).pipe(twigTasks())
    })
    // -- Compile all twig files when these files change
    gulp.watch([getSrc('twig/**/*.twig'),
          '!' + getSrc('twig/pages/*.twig')], ['twig'])

    // -- These need to recompile all
//    gulp.watch(getSrc('sass/**/*.scss'), ['scss'])
//    gulp.watch([getSrc('media/svg/**/*.svg'),
//          '!' + getSrc('media/svg/icons.svg')], ['svg'])
//
//    // Update dest files in browser
//    // gulp.watch(getDest('css/*.css')).on('change', browsersync.stream)
//    // gulp.watch(getDest('js/*.js')).on('change', browsersync.stream)
//    // gulp.watch(getDest('**/*.html')).on('change', browsersync.stream)
//    gulp.watch(getDest('media/svg/icons.svg')).on('change', browsersync.stream)
  }
})

// Web Server
gulp.task('browsersync', ['build'], function () {
  browsersync.init(config.browserSync)
})
