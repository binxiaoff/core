var config       = require('../config')
if(!config.tasks.modernizr) return

var gulp         = require('gulp')
var handleErrors = require('../lib/handleErrors')
var path         = require('path')
var modernizr    = require('gulp-modernizr')
var browserSync  = require('browser-sync')
var del          = require('del')

var paths = {
  srcCss: path.join(config.root.src, config.tasks.modernizr.srcCss, '/**/*.css'),
  srcJs: path.join(config.root.src, config.tasks.modernizr.srcJs, '/**/*.js'),
  srcExclude: path.join(config.root.dest, config.tasks.modernizr.srcExclude),
  dest: path.join(config.root.src, config.tasks.modernizr.dest)
}

var modernizrTask = function () {
  var cleanCompass = function() {
    del(path.join(config.root.src, config.tasks.css.compass.css))
  }

  return gulp.src([paths.srcCss, paths.srcJs, '!' +  paths.srcExclude])
    .on('error', handleErrors)
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
    .pipe(gulp.dest(paths.dest))
    .pipe(browserSync.stream())
    .on('end', cleanCompass)
}

gulp.task('modernizr', modernizrTask)
module.exports = modernizrTask