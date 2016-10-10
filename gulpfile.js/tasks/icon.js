var config      = require('../config')
if(!config.tasks.icon) return

var gulp         = require('gulp')
var browserSync  = require('browser-sync')
var path         = require('path')
var rename       = require('gulp-rename')
var handleErrors = require('../lib/handleErrors')
var svgstore     = require('gulp-svgstore')
var svgmin       = require('gulp-svgmin')

var paths = {
  src: path.join(config.root.src, config.tasks.icon.src, '/**/*.svg'),
  dest: path.join(config.root.dest, config.tasks.icon.dest)
}

var iconTask = function() {
  return gulp.src(paths.src)
    .on('error', handleErrors)
    .pipe(rename(function (svgPath) {
      var name = svgPath.dirname.split(path.sep)
      name.push(svgPath.basename)
      svgPath.basename = name.join('-')
    }))
    .pipe(svgmin({ // Minify SVG files
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
      }]
    }))
    .pipe(svgstore())
    .pipe(rename('icons.svg'))
    .pipe(gulp.dest(paths.dest))
    .pipe(browserSync.stream())
}

gulp.task('icon', iconTask)
module.exports = iconTask