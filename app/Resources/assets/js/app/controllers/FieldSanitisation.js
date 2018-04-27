/**
 * Field sanitisation
 *
 * If a form field has a specific type of validation, we can sanitise the values here before it's
 * validated.
 */

var $ = require('jquery')
var Sanity = require('Sanity')
var Utility = require('Utility')
var $doc = $(document)

$doc
  // Sanitise integers
  .on('FormValidation:validateInput:beforeValidate', 'input[data-formvalidation-type="integer"]', function (event, FormValidation, inputValidation) {
    $(this).val(Utility.convertStringToFloat($(this).val()))
  })

  // Sanitise Title (e.g. Raison Sociale)
  .on('FormValidation:validateInput:beforeValidate', 'input[data-formvalidation-type="title"]', function (event, FormValidation, inputValidation) {
    $(this).val(Sanity($(this).val()).sanitiseTitle())
  })

  // Sanitise Siren
  .on('FormValidation:validateInput:beforeValidate', 'input[data-formvalidation-type="siren"]', function (event, FormValidation, inputValidation) {
    $(this).val(Sanity($(this).val()).sanitiseSiren())
  })

  // Sanitise Siret
  .on('FormValidation:validateInput:beforeValidate', 'input[data-formvalidation-type="siret"]', function (event, FormValidation, inputValidation) {
    $(this).val(Sanity($(this).val()).sanitiseSiret())
  })

  // Sanitise Iban
  .on('FormValidation:validateInput:beforeValidate', 'input[data-formvalidation-type="iban"]', function (event, FormValidation, inputValidation) {
    // Strip spaces from Iban
    $(this).val(Sanity($(this).val()).normaliseWhitespace(''))
  })

  // Sanitise Phone/Mobile
  .on('FormValidation:validateInput:beforeValidate', 'input[data-formvalidation-type="tel"], input[data-formvalidation-type="phone"], input[data-formvalidation-type="telephone"], input[data-formvalidation-type="mobile"]', function (event, FormValidation, inputValidation) {
    $(this).val(Sanity($(this).val()).sanitisePhone())
  })
