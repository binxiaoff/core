var config       = require('../config')
if(!config.tasks.jsDependencies) return

var gulp         = require('gulp')
var handleErrors = require('../lib/handleErrors')
var path         = require('path')
var concat       = require('gulp-concat')
var uglify       = require('gulp-uglify')
var rename       = require('gulp-rename')
var browserSync  = require('browser-sync')
var gulpif       = require('gulp-if')
var sourcemaps   = require('gulp-sourcemaps')
var del          = require('del')

var paths = {
  src: path.join(config.root.src, config.tasks.jsDependencies.src),
  dest: path.join(config.root.dest, config.tasks.jsDependencies.dest),
  vendorSrc: path.join(config.root.src, config.tasks.jsDependencies.vendorSrc, '/**/*'),
  vendorDest: path.join(config.root.dest, config.tasks.jsDependencies.vendorDest)
}

var dependencies = [];

config.tasks.jsDependencies.dependencies.forEach(function(entry) {
  dependencies.push(entry)
})

var jsDependenciesTask = function () {

  var cleanModernizr = function() {
    del(path.join(config.root.src, config.tasks.modernizr.dest))
  }

  gulp.src(dependencies)
    .on('error', handleErrors)
    .pipe(gulpif(!global.production, sourcemaps.init()))
    .pipe(concat('dependencies.js'))
    .pipe(gulpif(!global.production, sourcemaps.write('.')))
    .pipe(gulp.dest(paths.dest))
    .pipe(browserSync.stream())

  gulp.src(paths.vendorSrc)
    .pipe(gulp.dest(paths.vendorDest))
    .on('end', cleanModernizr)
}

gulp.task('jsDependencies', jsDependenciesTask)
module.exports = jsDependenciesTask