<script>
    $(function () {
        $('#process-detection').submit(function (e) {
            e.preventDefault();
            $('#btn-process-detection').remove();

            var form = $(this),
                formData = (window.FormData) ? new FormData(form[0]) : null,
                data = (formData !== null) ? formData : form.serialize();

            $.ajax({
                type: form.attr('method'),
                url: form.attr('action'),
                contentType: false,
                processData: false,
                dataType: 'json',
                data: data,
                success: function (response) {
                    console.log(response['message']);
                    if (response['message']) {
                        $('#success_message').show();
                    } else {
                        $('#params_missing_message').show();
                    }
                    setTimeout(function () {
                        parent.$.fn.colorbox.close();
                        location.reload();
                    }, 3000);
                },
                error: function (response, status) {
                    console.log(response.responseText);
                    $('#error_message').show();

                    setTimeout(function () {
                        parent.$.fn.colorbox.close();
                    }, 3000);
                }
            });
        })
    })
    ;

    $(function () {
        $('#vigilance_status').change(function (e) {

            if ($(this).val() - $('#currentVigilanceStatus').val() > 0) {
                $('#vigilane_rule_list').show()
            } else {
                $('#vigilane_rule_list').hide()
            }
        });
    })

</script>
<div id="popup" style="min-width:500px;">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"/></a>
    <h1 style="text-align: center"><?= $this->title ?></h1>
    <ul>
        <li>
            <b>Client: </b><?= $this->client->getPrenom() . ' ' . $this->client->getNom() ?>
        </li>
        <?php if (isset($this->currentVigilanceStatusId)) : ?>
            <li>
                <b>Statut de vigilance actuel:</b> <?= \Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule::$vigilanceStatusLabel[$this->currentVigilanceStatusId] ?>
            </li>
        <?php endif; ?>
    </ul>
    <hr>
    <form action="<?= $this->processDetectionUrl ?>" id="process-detection" name="process-detection" method="post" enctype="multipart/form-data">
        <?php if ($this->action != 'ack') : ?>
            <label for="vigilance_status"><b>Nouveau statut de vigilance:</b></label>&nbsp;
            <select name="vigilance_status" id="vigilance_status" required>
                <option value=""></option>
                <?php foreach (\Unilend\Bundle\CoreBusinessBundle\Entity\VigilanceRule::$vigilanceStatusLabel as $id => $label) : ?>
                    <option value="<?= $id ?>" <?php if (isset($this->currentVigilanceStatusId) && $this->currentVigilanceStatusId === $id) : ?>selected<?php endif; ?> ><?= $label ?></option>
                <?php endforeach; ?>
            </select>
            <div id="vigilane_rule_list" <?php if (isset($this->currentVigilanceStatusId)) : ?> style="display: none" <?php endif; ?>>
                <label for="vigilance_rule"><b>Règle de vigilance:</b></label>&nbsp;
                <select name="vigilance_rule" id="vigilance_rule">
                    <option value=""></option>
                    <?php foreach ($this->vigilanceRules as $rule) : ?>
                        <option value="<?= $rule->getId() ?>"><?= $rule->getName() ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php else : ?>
            <!--            <label for="sender"><b>Expéditeur:</b></label>-->
            <!--            <br>-->
            <!--            <input style="margin: 5px; width: 200px;" type="email" autocomplete="on" name="sender" id="sender"/>-->
            <!--            <br>-->
            <!--            <label for="receiver"><b>Destinataire:</b></label>-->
            <!--            <br>-->
            <!--            <input style="margin: 5px; width: 200px;" type="email" autocomplete="on" name="receiver" id="receiver"/>-->
            <!--            <br>-->
            <!--            <label for="attachment_1"><b>Pièces jointes:</b></label>&nbsp;-->
            <!--            <br>-->
            <!--            <input style="padding: 5px" type="file" name="attachment_1"/>-->
            <!--            <br>-->
            <!--            <input style="padding: 5px" type="file" name="attachment_2"/>-->
            <!--            <br>-->
            <!--            <input style="padding: 5px" type="file" name="attachment_3"/>-->
        <?php endif; ?>
        <br><br>
        <div>
            <label for="user_comment"><b>Commentaire</b></label><br>
            <textarea id="user_comment" type="text" name="user_comment" rows="6" cols="80" required></textarea>
        </div>
        <br>
        <div style="text-align: center">
            <input type="submit" class="btn" id="btn-process-detection" name="<?= $this->action ?>"/>
            <input type="hidden" id="currentVigilanceStatus" value="<?php if (isset($this->currentVigilanceStatusId)) : ?><?= $this->currentVigilanceStatusId ?> <?php else : ?>-1<?php endif; ?>"/>
            <input type="hidden" name="clientId" id="clientId" value="<?= $this->client->getIdClient() ?>"/>
        </div>
        <br>
        <p id="success_message" style="display: none; color: #00A000">Changement effectué avec succès</p>
        <p id="params_missing_message" style="display: none; color: #f22120">Paramètres obligatoires manquants</p>
        <p id="error_message" style="display: none; color: #f22120">Une erruer est survenue</p>
    </form>
</div>
