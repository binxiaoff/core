/*
 * Unilend Password Check
 */

// @TODO integrate Dictionary

var $ = require('jquery')

// AutoComplete Language
var Dictionary = require('Dictionary')
var ElementAttrsObject = require('ElementAttrsObject')

function escapeQuotes (input) {
  return input.replace(/'/g, '&#39;').replace(/"/g, '&#34;')
}

var PasswordCheck = function (input, options) {
  var self = this
  self.$input = $(input)

  // Error: invalid element
  if (self.$input.length === 0 || !self.$input.is('input, textarea')) {
    console.log('PasswordCheck Error: given element is not an <input>', input)
    return
  }

  // Settings
  self.settings = $.extend(
  // Default settings
  {
    // Extra evaluation rules to check
    evaluationRules: [],

    // Max amount to score with all the rules
    maxScore: 15,

    // The minimum length of the password
    minLength: 8,

    // The UI elements to output messages or other feedback to (can be {String} selectors, {HTMLElement}s or {jQueryObject}s as well as {String} HTML code)
    levelElem: '<div class="ui-passwordcheck-level"><div class="ui-passwordcheck-level-bar"></div></div>',
    infoElem: '<div class="ui-passwordcheck-info"></div>',

    // The level of security the
    levels: [{
      name: 'weak-very',
      label: 'Very weak'
    },{
      name: 'weak',
      label: 'Weak'
    },{
      name: 'medium',
      label: 'Medium'
    },{
      name: 'strong',
      label: 'Strong'
    },{
      name: 'strong-very',
      label: 'Very strong'
    }],

    // Event to fire when an evaluation has completed
    // By default this updates the UI. If you are using custom UI elements to output to you may need to create your own version of this
    onevaluation: function (evaluation) {
      // Set level
      this.$level.find('.ui-passwordcheck-level-bar').css('width', evaluation.levelAmount * 100 + '%')
      this.$elem.addClass('ui-passwordcheck-checked ui-passwordcheck-level-' + evaluation.level.name)

      // Show description of evaluation
      var infoLabel = evaluation.level.label
      var infoMoreHtml = ''
      if (evaluation.info.length > 0) {
        infoLabel += ' <a href="javascript:;"><span class="icon fa-question-circle"></span></a>'
        infoMoreHtml += '<ul class="ui-passwordcheck-messages">'
        for (var i = 0; i < evaluation.info.length; i++) {
          var helpText = (typeof evaluation.info[i].help !== 'undefined' ? evaluation.info[i].help : '')
          var descriptionText = (typeof evaluation.info[i].description !== 'undefined' ? '<strong>' + evaluation.info[i].description + '</strong><br/>' : '')
          if (descriptionText || helpText) infoMoreHtml += '<li>' + descriptionText + helpText + '</li>'
        }
        infoMoreHtml += '</ul>'
      }
      this.$info.html('<div class="ui-passwordcheck-level-label">' + infoLabel + '</div>' + infoMoreHtml)
    }
  },
  // Get any settings/options from the element itself
  ElementAttrsObject(input, {
    minLength: 'data-passwordcheck-minlength',
    levelElem: 'data-passwordcheck-levelelem',
    infoElem: 'data-passwordcheck-infoelem'
  }),
  // Override with function call's options
  options)

  // UI elements
  self.$elem = self.$input.parents('.ui-passwordcheck')
  self.$level = $(self.settings.level)
  self.$info = $(self.settings.info)

  // Setup the UI
  // No main element? Wrap the input with a main element
  if (self.$elem.length === 0) {
    self.$input.wrap('<div class="ui-passwordcheck"></div>')
    self.$elem = self.$input.parents('.ui-passwordcheck')
  }
  // Ensure input has right classes and it's own wrap
  self.$input.addClass('ui-passwordcheck-input')
  if (self.$input.parents('.ui-passwordcheck-input-wrap').length === 0) {
    self.$input.wrap('<div class="ui-passwordcheck-input-wrap"></div>')
  }
  self.$level = $(self.settings.levelElem)
  self.$info = $(self.settings.infoElem)

  // If elements aren't already existing selectors, HTMLElements or objects, add them to the DOM in the correct places
  if (!$.contains(document, self.$level[0])) self.$input.after(self.$level)
  if (!$.contains(document, self.$info[0])) self.$elem.append(self.$info)

  // @debug
  // console.log({
  //   elem: self.$elem,
  //   input: self.$input,
  //   level: self.$level,
  //   info: self.$info
  // })

  // Reset the UI
  self.reset = function (soft) {
    var removeClasses = $.map(self.settings.levels, function (level) {
      return 'ui-passwordcheck-level-' + level.name
    }).join(' ')
    self.$elem.removeClass(removeClasses + ' ui-passwordcheck-checked')
    self.$level.find('.ui-passwordcheck-level-bar').css('width', 0)
    self.$info.html('')

    // Soft reset
    if (!soft) {
      self.$input.val('')
    }

    // Trigger element event in case anything else wants to hook
    self.$input.trigger('PasswordCheck:resetted', [self, soft])
  }

  // Evaluate an input value to see how secure it is
  self.evaluate = function (input) {
    if (typeof input === 'undefined') input = self.$input.val()
    if (typeof input === 'undefined') return false

    var complexity = 0
    var score = 0
    var level = ''
    var levelAmount = 0
    var info = []
    var evaluation = {}

    // The evaluation rules
    var evaluationRules = [{
      re: /[a-z]+/,
      amount: 1
    },{
      re: /[A-Z]+/,
      amount: 1
    },{
      re: /[0-9]+/,
      amount: 1
    },{
      re: /[\u2000-\u206F\u2E00-\u2E7F\\'!"#$%&()*+,\-.\/:;<=>?@\[\]^_`{|}~]+/,
      amount: 1
    },{
      re: /p[a4][s5]+(?:w[o0]+rd)?/i,
      amount: -1,
      description: 'Variations on the word "password"',
      help: 'Avoid using the word "password" or any other variation, e.g. "P455w0rD"'
    },{
      re: /asdf|qwer|zxcv|ghjk|tyiu|jkl;|nm,.|uiop/i,
      amount: -1,
      description: 'Combination matches common keyboard layouts',
      help: 'Avoid using common keyboard layout combinations'
    },{
      re: /([a-z0-9])\1{2,}/i,
      amount: -1,
      description: 'Repeated same character',
      help: 'Avoid repeating the same character. Add in more variation'
    },{
      re: /123(?:456789|45678|4567|456|45|4)?/,
      amount: -1,
      description: 'Incrementing number sequence',
      help: 'Avoid using incrementing numbers'
    },{
      re: /abc|xyz/i,
      amount: -1,
      description: 'Common alphabet sequences',
      help: 'Avoid using combinations like "abc" and "xyz"'
    }]
    if (self.settings.evaluationRules instanceof Array && self.settings.evaluationRules.length > 0) {
      evaluationRules += self.settings.evaluationRules
    }

    // Evaluate the string based on the minLength
    var inputLengthDiff = input.length - self.settings.minLength
    if (input.length < self.settings.minLength) {
      score -= 1
      info.push({
        description: 'Password is too short',
        help: 'Add extra words or characters to lengthen your password'
      })
    } else {
      score += 1
    }
    complexity += (inputLengthDiff > 0 ? Math.floor(inputLengthDiff / 8) : 0)

    // Evaluate the string based on the rules
    for (var i = 0; i < evaluationRules.length; i++) {
      var rule = evaluationRules[i]
      var ruleInfo = {}
      if (rule.hasOwnProperty('re') && rule.re instanceof RegExp) {
        // Positive match
        if (rule.re.test(input)) {
          score += rule.amount
          complexity += 1
          if (rule.hasOwnProperty('description')) ruleInfo.description = rule.description
          if (rule.hasOwnProperty('help')) ruleInfo.help = rule.help
          if (rule.hasOwnProperty('description') || rule.hasOwnProperty('help')) info.push(ruleInfo)
        }
      }
    }

    // Extra checks
    if (complexity < 3) {
      info.push({
        description: 'Password is potentially too simple',
        help: 'Use a combination of upper-case, lower-case, numbers and punctuation characters'
      })
    }

    // Turn score into a level
    levelAmount = (score * complexity) / self.settings.maxScore
    if (score < 0) levelAmount = 0 // Cap minimum
    if (levelAmount > 1) levelAmount = 1 // Cap maximum
    level = self.settings.levels[Math.floor(levelAmount * (self.settings.levels.length - 1))]

    evaluation = {
      score: score,
      complexity: complexity,
      levelAmount: levelAmount,
      level: level,
      info: info
    }

    // Fire the onevaluation event to update the UI
    self.reset(1)
    if (typeof self.settings.onevaluation === 'function') {
      self.settings.onevaluation.apply(self, [evaluation])
    }

    // Trigger element event in case anything else wants to hook
    self.$input.trigger('PasswordCheck:evaluation', [self, evaluation])

    // @debug
    // console.log('PasswordCheck.evaluate', evaluation)

    return evaluation
  }

  // Hook events to the element
  self.$input.on('keyup', function (event) {
    // Evaluate the element's input
    if ($(this).val().length > 0) {
      self.evaluate()

    // Reset the UI
    } else if ($(this).val().length === 0) {
      self.reset()
    }
  })

  // Show/hide the info
  self.$info.on('click', function (event) {
    event.preventDefault()
    self.$elem.toggleClass('ui-passwordcheck-info-open')
  })
}

/*
 * jQuery Plugin
 */
$.fn.uiPasswordCheck = function (options) {
  return this.each(function (i, elem) {
    new PasswordCheck(elem, options)
  })
}

/*
 * jQuery Events
 */
$(document)
  // Auto-assign functionality to elements with [data-passwordcheck] attribute
  .on('ready', function () {
    $('[data-passwordcheck]').uiPasswordCheck()
  })

module.exports = PasswordCheck
