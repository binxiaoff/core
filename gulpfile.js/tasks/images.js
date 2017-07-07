var config      = require('../config')
if(!config.tasks.images) return

var browserSync = require('browser-sync')
var gulp        = require('gulp')
var path        = require('path')
var imagemin    = require('gulp-imagemin')

var paths = {
  src: path.join(config.root.src, config.tasks.images.src, '/**/*.{' + config.tasks.images.extensions + '}'),
  dest: path.join(config.root.dest, config.tasks.images.dest)
}

var imagesTask = function() {
  return gulp.src(paths.src)
    .pipe(imagemin())
    .pipe(gulp.dest(paths.dest))
    .pipe(browserSync.stream())
}

gulp.task('images', imagesTask)
module.exports = imagesTask
