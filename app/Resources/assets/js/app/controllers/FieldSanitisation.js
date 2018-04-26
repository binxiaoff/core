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
  // Sanitise Title (e.g. Raison Sociale)
  .on('FormValidation:validateInput:beforeValidate', 'input[data-formvalidation-type="title"]', function (event, FormValidation, inputValidation) {
    $(this).val(sanity($(this).val()).sanitiseSimpleText())
  })

  // Sanitise Siret
  .on('FormValidation:validateInput:beforeValidate', 'input[data-formvalidation-type="siret"]', function (event, FormValidation, inputValidation) {
    $(this).val(sanity($(this).val()).sanitiseSiret())
  })

  // Sanitise Siren
  .on('FormValidation:validateInput:beforeValidate', 'input[data-formvalidation-type="siren"]', function (event, FormValidation, inputValidation) {
    $(this).val(sanity($(this).val()).sanitiseSiren())
  })

  // Sanitise Phone/Mobile
  .on('FormValidation:validateInput:beforeValidate', 'input[data-formvalidation-type="tel"], input[data-formvalidation-type="phone"], input[data-formvalidation-type="telephone"], input[data-formvalidation-type="mobile"]', function (event, FormValidation, inputValidation) {
    $(this).val(sanity($(this).val()).sanitisePhone())
  })

