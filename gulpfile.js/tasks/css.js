var config       = require('../config')
if(!config.tasks.css) return

var gulp         = require('gulp')
var gutil        = require('gulp-util')
var browserSync  = require('browser-sync')
var handleErrors = require('../lib/handleErrors')
var autoprefixer = require('gulp-autoprefixer')
var path         = require('path')
var cleanCss     = require('gulp-clean-css')
var compass      = require('gulp-compass')
var sourcemaps   = require('gulp-sourcemaps');
var concat       = require('gulp-concat');
var gulpIf       = require('gulp-if');

var paths = {
  src: path.join(config.root.src, config.tasks.css.src, '/**/*.{' + config.tasks.css.extensions + '}'),
  dest: path.join(config.root.dest, config.tasks.css.dest)
}

var cssTask = function () {

  var compassConfig = {
    css: path.join(config.root.src, config.tasks.css.compass.css),
    sass: path.join(config.root.src, config.tasks.css.compass.sass),
    image: path.join(config.root.src, config.tasks.css.compass.image),
    sourcemap: true
  }

  if (global.production) {
    compassConfig.sourcemap = false
  }

  // Compass swallows messages and takes ages, so I want to know when it is started and when it is finished
  var startTime = new Date()
  if (!global.production) {
    compassConfig.time = true
    gutil.log('Compiling CSS...')
  }

  return gulp.src(paths.src)
    .pipe(compass(compassConfig))
    .on('error', handleErrors)
    .pipe(gulpIf(!global.production, sourcemaps.init({loadMaps: true})))
    .pipe(autoprefixer(config.tasks.css.autoprefixer))
    .pipe(concat('main.css'))
    .pipe(cleanCss({
        shorthandCompacting: false,
        processImport: false
      }, function(details) {
        if (!global.production) {
          var endTime = new Date()
          var totalTime = ((endTime.getTime() - startTime.getTime()) / 1000)
          gutil.log('Finished compiling CSS (' + totalTime.toFixed(2) + 's)')
        }
      }))
    .pipe(gulpIf(!global.production, sourcemaps.write('./')))
    .pipe(gulp.dest(paths.dest))
    .pipe(browserSync.stream())
}

gulp.task('css', cssTask)
module.exports = cssTask