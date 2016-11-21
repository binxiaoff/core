/*
 * Pikaday Datepicker controller
 */

var $ = require('jquery')
var Pikaday = require('pikaday')
require('pikaday.jquery')

var $doc = $(document)

var pikadayOptions = {
  format: 'DD/MM/YYYY'
}

// Language settings for datepicker
var pikadayI18n = {
  fr: {
    previousMonth : 'Précédent Mois',
    nextMonth     : 'Suivant Mois',
    months        : ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'],
    weekdays      : ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'],
    weekdaysShort : ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam']
  },
  it: {
    previousMonth : 'Prec',
    nextMonth     : 'Succ',
    months        : ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'],
    weekdays      : ['Domenica', 'Lunedì', 'Martedì', 'Mercoledì', 'Giovedì', 'Venerdì', 'Sabato'],
    weekdaysShort : ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab']
  }
}
// Use french language
if (/^fr/i.test($('html').attr('lang'))) {
  pikadayOptions.i18n = pikadayI18n.fr
  // Use italian language
} else if (/^it/i.test($('html').attr('lang'))) {
  pikadayOptions.i18n = pikadayI18n.it
}

// Instantiate
$doc.on('ready UI:visible', function (event) {
  var $target = $(event.target)

  $target.find('.ui-has-datepicker, [data-ui-datepicker]').pikaday(pikadayOptions)
    // TMA-1182 Remove has attr/class, add pikaday class to avoid event stuff
    .addClass('ui-pikaday')
    .removeAttr('data-ui-datepicker')
    .removeClass('ui-has-datepicker')
})
