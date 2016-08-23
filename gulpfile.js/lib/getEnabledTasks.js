var config  = require('../config')
var compact = require('lodash/compact')

// Grouped by what can run in parallel
var assetTasks = ['fonts', 'icon', 'images']

module.exports = function(env) {

  function matchFilter(task) {
    if(config.tasks[task]) {
      return task
    }
  }

  function exists(value) {
    return !!value
  }

  return {
    assetTasks: compact(assetTasks.map(matchFilter).filter(exists))
  }
}
