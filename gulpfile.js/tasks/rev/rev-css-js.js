var config    = require('../../config')
var gulp      = require('gulp')
var path      = require('path')
var rev       = require('gulp-rev')

// 4) Rev and compress CSS and JS files (this is done after assets, so that if a
//    referenced asset hash changes, the parent hash will change as well
gulp.task('rev-css-js', function(){
  return gulp.src(path.join(config.root.dest,'/**/**.{css,js}'))
    .pipe(rev())
    .pipe(gulp.dest(config.root.dest))
    .pipe(rev.manifest(path.join(config.root.dest, 'rev-manifest.json'), {merge: true}))
    .pipe(gulp.dest(''))
})
