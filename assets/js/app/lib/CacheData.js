/*
 * CacheData
 * When users fill in information this module helps to save that to the
 * in-built browser storage in case any potential errors occur
 * This lib module could be further extended to support external database data
 */

// Dependencies
var $ = require('jquery')
var Utility = require('Utility')

// Private
var STORAGE_TYPES = ['sessionStorage', 'localStorage']

// Public
var CacheData = {
  /*
   * Default Properties
   */
  // Set the default storage type
  defaultStorageType: STORAGE_TYPES[1],

  /*
   * Tests
   */

  // Test if local/session storage works
  // @returns {Boolean}
  test: function (storageType) {
    try {
      var storage = window[storageType]
      var x = '__storage_test__'
      storage.setItem(x, x)
      storage.removeItem(x)
      return true
    } catch(e) {
      return false
    }
  },

  // Check if local/session storage supported
  // @returns {Object} contains {Boolean} values for each storage type supported
  supports: function () {
    var self = this
    var supports = {}
    $.each(STORAGE_TYPES, function (i, storageType) {
      supports[storageType] = self.test(storageType)
    })
    return supports
  },

  /*
   * Clear
   */

  // Clear all the data in the support storage units
  clearAll: function () {
    var self = this
    var supports = self.supports()
    $.each(supports, function (storageType, supported) {
      if (supported) window[storageType].clear()
    })
  },

  // Clear a single storage type
  clear: function (storageType) {
    var self = this
    if (self.test(storageType)) window[storageType].clear()
  },

  // Clear session storage
  // @alias clear('sessionStorage')
  clearSession: function () {
    var self = this
    return self.clear('sessionStorage')
  },

  // Clear local storage
  // @alias clear('localStorage')
  clearLocal: function () {
    var self = this
    return self.clear('localStorage')
  },

  /*
   * Get
   */

  // Get data from either storage
  // @returns {Mixed} dataValue
  get: function (dataKey, callback) {
    var self = this

    // Checks each supported storage type in order of self.supports() return value
    var dataValue = undefined
    var supports = self.supports()
    for (var storageType in supports) {
      // Storage type is supported, get/test the value
      if (supports[storageType]) {
        dataValue = self.getFrom(storageType, dataKey, callback)

        // Return valid value
        if (!Utility.isEmpty(dataValue)) return dataValue
      }
    }

    return dataValue
  },

  // Get data from local or session storage
  // @returns {Mixed} dataValue
  getFrom: function (storageType, dataKey, callback) {
    var self = this

    if (!self.test(storageType)) {
      // @debug
      // console.log('CacheData.getFrom: ' + storageType + ' unsupported')
      return undefined
    }

    // Get the stored data value
    var dataValue = window[storageType].getItem(dataKey)

    // Expand any JSON strings
    if (typeof dataValue === 'string' && /^[\{\[]|[\}\]]$/.test(dataValue.trim())) dataValue = JSON.parse(dataValue)

    // Fire callback
    if (typeof callback === 'function') callback.apply(dataValue, [dataValue])

    // Return data
    return dataValue
  },

  // Get data from the local storage
  // @alias getFrom('localStorage', ..)
  getLocal: function (dataKey, callback) {
    var self = this
    return self.getFrom('localStorage', dataKey, callback)
  },

  // Get data from the session storage
  // @alias getFrom('sessionStorage', ..)
  getSession: function (dataKey, callback) {
    var self = this
    return self.getFrom('sessionStorage', dataKey, callback)
  },

  /*
   * Set
   */

  // Set data to specific storage
  // @returns {Boolean}
  setTo: function (storageType, dataKey, dataValue, callback) {
    var self = this

    if (!self.test(storageType)) {
      // @debug
      // console.log('CacheData.setTo: ' + storageType + ' unsupported')
      return false
    }

    // Ensure value is string
    if (typeof dataValue !== 'string') dataValue = JSON.stringify(dataValue)

    // Set item within the storage
    window[storageType].setItem(dataKey, dataValue)

    // Fire callback
    if (typeof callback === 'function') callback.apply(dataValue, [dataValue])

    return true
  },

  // Set data to session storage
  // @alias setTo('sessionStorage', ..)
  setSession: function (dataKey, dataValue, callback) {
    var self = this
    return self.setTo('sessionStorage', dataKey, dataValue, callback)
  },

  // Set data to local storage
  // @alias setTo('localStorage', ..)
  setLocal: function (dataKey, dataValue, callback) {
    var self = this
    return self.setTo('localStorage', dataKey, dataValue, callback)
  },

  /*
   * Remove
   */

  // Remove data from specific storage
  // @returns {Boolean}
  removeFrom: function (storageType, dataKey, callback) {
    var self = this

    if (!self.test(storageType)) {
      // @debug
      // console.log('CacheData.remove: ' + storageType + ' unsupported')
      return false
    }

    // Set item within the storage
    window[storageType].removeItem(dataKey)

    // Fire callback
    if (typeof callback === 'function') callback.apply(dataKey, [dataKey])

    return true
  },

  // Remove data from session storage
  // @alias removeFrom('sessionStorage', ..)
  removeSession: function (dataKey, callback) {
    var self = this
    return self.removeFrom('sessionStorage', dataKey, callback)
  },

  // Remove data from local storage
  // @alias removeFrom('localStorage', ..)
  removeLocal: function (dataKey, callback) {
    var self = this
    return self.removeFrom('localStorage', dataKey, callback)
  }
}

module.exports = CacheData
