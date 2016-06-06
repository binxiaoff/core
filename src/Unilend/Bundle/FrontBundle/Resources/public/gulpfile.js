// Build file

var gulp = require('gulp')
var util = require('gulp-util')
var browserify = require('browserify')
var buffer = require('vinyl-buffer')
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
var source = require('vinyl-source-stream')
var streamify = require('gulp-streamify')
var svgmin = require('gulp-svgmin')
var svgstore = require('gulp-svgstore')
var twig = require('gulp-twig')
var uglify = require('gulp-uglify')
var watchify = require('watchify')
var yargs = require('yargs').argv
var logCapture = require('gulp-log-capture')
var urlAdjuster = require('gulp-css-url-adjuster')

// Unilend GLOBAL vars
var Unilend = GLOBAL.Unilend = {
  config: {}
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
  dest: goodPath(yargs.dest) || goodPath('./build/' + env),

  // Browser-sync server configuration
  browserSync: deepAssign({
    server: {
      baseDir: goodPath(yargs.serverBaseDir) || goodPath('./build/' + env)
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
  //verbose: yargs.verbose || false,

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
    css: goodPath('/src/css'),
    sass: goodPath('/src/sass'),
    image: goodPath('/src/media'),
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
gulp.task('watch', ['watchfiles'])
gulp.task('build', ['clean', 'cssdependencies', 'scss', 'modernizr', 'jsdependencies', 'jsbundles', 'copy'])

// Clean build directory
gulp.task('clean', function () {
  util.log(util.colors.red('Clearing contents from ' + getDest()))

  // Synchronise delete
  del.sync([getDest('/**'), '!' + getDest()])
})

// Copy assets to build directory
// Depends on svg2png task to run first before copying the files
gulp.task('copy', function () {
  // Copy JS assets
  gulp.src([getSrc('js/vendor/**/*')])
      .pipe(gulp.dest(getDest('js/vendor')))
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

/*
 * General CSS tasks
 */
var cssTasks = lazypipe()
// Minify CSS
    .pipe(function () {
      return _if(config.compressCss, cleanCss({
        advanced: true,
        restructuring: true,
        aggressiveMerging: true,
        shorthandCompacting: false,
        mediaMerging: true,
        processImport: false,
        benchmark: true
      }))
    })

    .pipe(function () {
      return _if(config.compressCss, rename({
        extname: '.min.css'
      }))
    })
    // Move to dest CSS folder
    .pipe(gulp.dest, getDest('css'))

/*
 * General HTML tasks
 */
var htmlTasks = lazypipe()
// Move to dest HTML folder
    .pipe(gulp.dest, getDest())

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
    getSrc('js/vendor/swiper/js/swiper.jquery.js'),
    getSrc('js/vendor/highcharts/highstock.src.js'),
    getSrc('js/vendor/highcharts/modules/map.src.js'),
    // goodPath('./node_modules/draggabilly/dist/draggabilly.pkgd.js')
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
      // here it is : "CSSREWRITE" but with gulp
      .pipe(urlAdjuster({
        replace:['../media/', '/frontbundle/media/'],
      }))

      // Save to src for backup
      .pipe(gulp.dest(getSrc('css')))


      // Do all the generic css tasks
      .pipe(cssTasks())
})

// Watch Files
gulp.task('watchfiles', function () {
  if (config.watchFiles) {
    gulp.watch(getSrc('sass/**/*.scss'), ['scss'])
  }
})
