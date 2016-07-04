/*
 * Borrower Esim Form
 *
 */

// Lib Dependencies
var $ = require('jquery')


var BorrowerEsimForm = function () {
  var self = this


  var step1Datas = {"estimatedRate":4.2,"estimatedMonthlyRepayment":729,"translationComplement":"","remboursementDuration":"18","amountToBorrow":"12588"};

  $(".ui-esim-output-cost").prepend(step1Datas.amountToBorrow+" ");
  $(".ui-esim-output-duration").prepend(step1Datas.remboursementDuration+" ");
  if(true) {
    while($(".ui-esim-output-duration")[0].nextSibling != null){
      $(".ui-esim-output-duration")[0].nextSibling.remove();
    }
    $("#esim2 > fieldset > div:nth-child(2) > div > p:nth-child(1)").append(".");
  }


}

$(document)
  .on("click", "#submit-step-1", function(){
    //BorrowerEsimForm();
  })

module.exports = BorrowerEsimForm
