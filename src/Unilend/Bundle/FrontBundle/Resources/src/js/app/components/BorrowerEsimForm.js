/*
 * Borrower Esim Form
 *
 */

// Lib Dependencies
var $ = require('jquery')
var FormatAmounts = require('./FormatAmounts')


var BorrowerEsimForm = {
    StoreEsimDatas : function() {
        var period = $("input[id^='esim-input-duration-']:checked").val(),
            amount = $("#esim-input-amount").val(),
            motiveId = $("#esim-input-reason > option:selected").val();
        if(! $(".form-validation-notifications .message-error").length){
            var datas = {};
            datas.period = period;
            datas.amount = amount;
            datas.motiveId = motiveId;
            $.ajax({
                type: 'POST',
                url: '/esim-step-1',
                data: datas,
                success: function(response) {
                    $(".ui-esim-output-cost").prepend(response.amount+" ");
                    $(".ui-esim-output-duration").prepend(response.period+" ");
                    $(".ui-esim-monthly-output").html(response.estimatedMonthlyRepayment);
                    $(".ui-esim-interest-output").html(response.estimatedRate);
                    if(!response.motiveSentenceComplementToBeDisplayed) {
                        while($(".ui-esim-output-duration")[0].nextSibling != null){
                            $(".ui-esim-output-duration")[0].nextSibling.remove();
                        }
                        $("#esim2 > fieldset > div:nth-child(2) > div > p:nth-child(1)").append(".");
                    }
                    else{
                        var text = $("p[data-bottower-motive]").html();
                        $("p[data-bottower-motive]").html(text.replace('.', ''));
                        $('p[data-bottower-motive]').append(response.translationComplement);
                    }
                },
                error: function() {
                    console.log("error retrieving datas");
                }
            });
            $('a[href*="esim1"]')
                .removeAttr("href data-toggle aria-expanded")
                .attr("nohref", "nohref");
        }
    }
}

$(document)
    .on("click", "#submit-step-1", function(){
        BorrowerEsimForm.StoreEsimDatas();
    })

module.exports = BorrowerEsimForm
