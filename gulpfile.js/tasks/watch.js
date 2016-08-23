var config = require('../config')
var gulp   = require('gulp')
var path   = require('path')
var watch  = require('gulp-watch')

var watchTask = function() {
  var watchableTasks = ['fonts', 'css', 'images','browserify', 'icon']

  watchableTasks.forEach(function(taskName) {
    var task = config.tasks[taskName]

    if(task) {
      var watchfn = function() {
        require('./' + taskName)()
      }

      if (taskName === 'browserify') {
        var bundles = task.bundleConfigs;
        bundles.forEach(function (bundle){
          var glob = path.join(config.root.src, bundle.entries)
          watch(glob, watchfn)
        })
      } else {
        var glob = path.join(config.root.src, task.src, '**/*.{' + task.extensions.join(',') + '}')
        watch(glob, watchfn)
      }
    }
  })
}

gulp.task('watch', watchTask)
module.exports = watchTask
