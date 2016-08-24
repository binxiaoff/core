var config       = require('../config')
if(!config.tasks.browserify) return

var gulp         = require('gulp')
var browserify   = require('browserify')
var source       = require('vinyl-source-stream')
var bundleLogger = require('../lib/bundleLogger')
var handleErrors = require('../lib/handleErrors')
var path         = require('path')
var buffer       = require('vinyl-buffer')
var uglify       = require('gulp-uglify')
var sourcemaps   = require('gulp-sourcemaps')
var gulpIf       = require('gulp-if')
var browserSync  = require('browser-sync')
var watchify     = require('watchify')

var browserifyTask =  function(watchMode) {

  var browserifyThis = function(bundleConfig) {

    var bundler = browserify({
      cache: {}, packageCache: {},
      entries: path.join(config.root.src, bundleConfig.entries),
      extensions: bundleConfig.extensions,
      debug: bundleConfig.debug
    });

    var bundle = function() {
      bundleLogger.start(bundleConfig.outputName);
      return bundler
        .bundle()
        .on('error', handleErrors)
        .pipe(source(bundleConfig.outputName))
        .pipe(buffer())
        .pipe(gulpIf(!global.production, sourcemaps.init({loadMaps: true})))
        // .pipe(gulpIf(!global.production, uglify()))
        .pipe(gulpIf(!global.production, sourcemaps.write('./')))
        .pipe(gulp.dest(path.join(config.root.dest, bundleConfig.dest)))
        .on('end', function() {bundleLogger.end(bundleConfig.outputName)})
        .pipe(browserSync.reload({
          stream: true
        }));

    }

    if(watchMode) {
      // Wrap with watchify and rebundle on changes
      bundler = watchify(bundler);
      // Rebundle on update
      bundler.on('update', bundle);
      bundleLogger.watch(bundleConfig.outputName);
    }

    return bundle()
  }

  config.tasks.browserify.bundleConfigs.forEach(browserifyThis)
}

/**
 * Run JavaScript through Browserify
 */
gulp.task('browserify', browserifyTask)
module.exports = browserifyTask