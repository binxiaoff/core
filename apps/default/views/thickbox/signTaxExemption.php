<style>
    .popup-head h2 {
        background: #b10366 none repeat scroll 0 0;
        border-bottom: 1px solid #fff;
        border-radius: 8px;
        color: #fff;
        padding: 13px 20px 14px;
        text-align: center;
    }

    .form-row > em {
        display: inline-block;
        margin: 5px;
        font-size: 13px;
    }

    .input-required {
        background: #ffe8e8;
        color: #c84747;
    }

</style>
<div class="popup" style="background-color: #E3E4E5;width: 600px; overflow: hidden; height: 710px;">
    <a href="#" class="popup-close">close</a>
    <div class="popup-header">
        <h5><?= $this->lng['lender-dashboard']['tax-exemption-request'] ?></h5>
        <h5><?= $this->lng['lender-dashboard']['sworn-statement'] ?></h5>
        <strong><?=
            str_replace(
                ['%nextYear%', '%taxExemptionRequestLimitDate%'],
                [$this->nextYear, $this->taxExemptionRequestLimitDate],
                $this->lng['lender-dashboard']['tax-exemption-request-validity-notice']
            ) ?>
        </strong>
    </div>
    <div class="tax-exemption-details">
        <p><?=
            str_replace(
                ['%rateOfTaxDeductionAtSource%', '%currentYear%', '%lastYear%'],
                [$this->taxType->rate, $this->currentYear, $this->lastYear],
                $this->lng['lender-dashboard']['tax-exemption-info']
            ) ?>
        </p>
        <p><?=
            str_replace(
                '%incomeTaxReferenceSingleAmount%',
                $this->ficelle->formatNumber($this->incomeTaxReferenceSingleAmount),
                $this->lng['lender-dashboard']['income-tax-reference-single-amount']
            ) ?>
            <br>
            <?=
            str_replace(
                '%incomeTaxReferenceCommonAmount%',
                $this->ficelle->formatNumber($this->incomeTaxReferenceCommonAmount),
                $this->lng['lender-dashboard']['income-tax-reference-common-amount']
            ) ?>
        </p>
        <p>
            <strong><?= $this->lng['lender-dashboard']['undersigned'] ?> <?php if ('M.' !== $this->client->civilite): ?> e <?php endif; ?> : </strong>
        </p>
        <?= $this->client->nom . ' ' . $this->client->prenom ?>
        <div class="row">
            <p><?= $this->fiscalAddress['address'] ?><br><?= $this->fiscalAddress['zipCode'] ?>
                <br><?= $this->fiscalAddress['city'] . ' ' . $this->fiscalAddress['country'] ?></p>
        </div>

        <div class="notification-body">
            <form id="tax-exemption-form" action="<?= $this->lurl ?>/profile/requestTaxExemption" method="post">
                <label>
                    <span id="input-attest">
                        <input class="tax-exemption-chbx" type="checkbox" id="h-attest" value="honor-attest" >
                        <strong><?= $this->lng['lender-dashboard']['attest-on-honor'] ?></strong>
                    </span>
                </label>
                <br>
                <label>
                    <span id="input-agree">
                        <input class="tax-exemption-chbx" type="checkbox" id="agree" value="agree-to-be-informed">
                        <strong><?= $this->lng['lender-dashboard']['agree-to-be-informed'] ?>  <?php if ('M.' !== $this->client->civilite): ?> e <?php endif; ?> : </strong>
                    </span>
                </label>
                <ol style="padding: 20px;">
                    <li><?= $this->lng['lender-dashboard']['tax-penalty-risk'] ?></li>
                    <li><?= $this->lng['lender-dashboard']['tax-exemption-limitation'] ?></li>
                </ol>
                <div id="display-msg" style="display: none; text-align: center; width: 100%; box-sizing: border-box;">

                </div>
                <p style="text-align: center;">
                    <button type="submit" class="btn" name="validate-btn" id="validate-btn" style="width: 150px; margin-right: 30px;"><?= $this->lng['lender-dashboard']['tax-exemption-validate'] ?></button>
                    <button type="submit" class="btn" name="cancel-btn" id="cancel-btn" style="width: 150px; margin-right: 30px;"><?= $this->lng['lender-dashboard']['tax-exemption-cancel'] ?></button>
                </p>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(function () {
        $("#validate-btn").click(function (event) {
            event.preventDefault();

            if (true === $("#agree").prop('checked') && true === $("#h-attest").prop('checked')) {
                $(".btn").hide();
                $.ajax({
                    url: $("#tax-exemption-form").attr('action'),
                    type: 'POST',
                    data:{agree: $("#agree").prop('checked'), attest: $("#h-attest").prop('checked')},
                    success: function (data) {
                        $("#display-msg").html(data);
                        $("#display-msg").show();
                        setTimeout(function () {
                            $("a.popup-close").trigger('click');
                        }, 2000);
                        $("div.tax_exemption").remove();
                    },
                    error: function(jqXHR) {
                        $("#display-msg").html(jqXHR.statusText);
                        $("#display-msg").show();
                        setTimeout(function () {
                            $("a.popup-close").trigger('click');
                        }, 2000);
                    }
                });
            } else if (false === $("#agree").prop('checked') && false === $("#h-attest").prop('checked') ) {
                $("#input-agree").addClass('input-required');
                $("#input-attest").addClass('input-required');
            } else if (false === $("#agree").prop('checked')) {
                $("#input-agree").addClass('input-required');
            } else if (false === $("#h-attest").prop('checked')) {
                $("#input-attest").addClass('input-required');
            }
        });
        $("#cancel-btn").click(function () {
            event.preventDefault();
            $("a.popup-close").trigger('click');
        });
        $(".tax-exemption-chbx").click(function () {

            if (true === $("#agree").prop('checked')) {
                $("#input-agree").removeClass('input-required');
            }

            if (true === $("#h-attest").prop('checked')) {
                $("#input-attest").removeClass('input-required');
            }
        });
    });
</script>
