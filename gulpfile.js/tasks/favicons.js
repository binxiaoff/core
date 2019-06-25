var config      = require('../config')
if(!config.tasks.favicons) return

var gulp = require('gulp')
var path = require('path')

var paths = {
  src: path.join(config.root.src, config.tasks.favicons.src, '/**/*.{' + config.tasks.favicons.extensions + '}'),
  dest: path.join(config.root.dest, config.tasks.favicons.dest)
}

var faviconsTask = function() {
  return gulp.src(paths.src)
    .pipe(gulp.dest(paths.dest))
}

gulp.task('favicons', faviconsTask)
module.exports = faviconsTask
