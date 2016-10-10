var config         = require('../config')
var gulp           = require('gulp')
var path           = require('path')
var watch          = require('gulp-watch')
var browserifyTask = require('./browserify');

var watchTask = function() {
  var watchableTasks = ['fonts', 'css', 'images','browserify', 'icon']

  watchableTasks.forEach(function(taskName) {
    var task = config.tasks[taskName]

    if(task) {
      if (taskName === 'browserify') {
        browserifyTask(true)
      } else {
        var glob = path.join(config.root.src, task.src, '**/*.{' + task.extensions.join(',') + '}')
        watch(glob, function() {
          require('./' + taskName)()
        })
      }
    }
  })
}

gulp.task('watch', watchTask)
module.exports = watchTask
