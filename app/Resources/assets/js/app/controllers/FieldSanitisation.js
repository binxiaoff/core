/**
 * Field sanitisation
 *
 * If a form field has a specific type of validation, we can sanitise the values here before it's
 * validated.
 */

var $ = require('jquery')
var sanity = require('Sanity')
var $doc = $(document)

$doc
  // Title
  .on('FormValidation:validateInput:beforeValidate', 'input[data-formvalidation-type="title"]', function (event, FormValidation, inputValidation) {
    $(this).val(sanity($(this).val()).sanitiseSimpleText())
  })
  // Siret
  .on('FormValidation:validateInput:beforeValidate', 'input[data-formvalidation-type="siret"]', function (event, FormValidation, inputValidation) {
    $(this).val(sanity($(this).val()).sanitiseSiret())
  })
  // Siret
  .on('FormValidation:validateInput:beforeValidate', 'input[data-formvalidation-type="title"]', function (event, FormValidation, inputValidation) {
    $(this).val(sanity($(this).val()).sanitiseSimpleText())
  })
