var config    = require('../../config')
var gulp      = require('gulp')
var path      = require('path')
var rev       = require('gulp-rev')
var revFormat = require('gulp-rev-format')
var revDel    = require('rev-del')

// 4) Rev and compress CSS and JS files (this is done after assets, so that if a
//    referenced asset hash changes, the parent hash will change as well
gulp.task('rev-css-js', function(){
  return gulp.src([
    path.join(config.root.dest, '/**/**.{css,js}'),
    // Exclude already revisioned files
    '!' + path.join(config.root.dest, '/**/**-[0-9a-f]*.{css,js}')
  ])
    .pipe(rev())
    // Use this to ensure the rev hash is before the file's ext, e.g. jquery.example.js => jquery.example-<hash>.js
    .pipe(revFormat({
      lastExt: true
    }))
    .pipe(gulp.dest(config.root.dest))
    .pipe(rev.manifest(path.join(config.root.dest, 'rev-manifest.json'), {merge: true}))
    // revDel deletes any old revisioned files that don't exist in the manifest
    .pipe(revDel({ dest: path.join(config.root.dest) }))
    .pipe(gulp.dest(''))
})
