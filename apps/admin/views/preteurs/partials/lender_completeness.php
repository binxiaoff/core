<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\ClientsStatus;

$clientStatus = $this->wallet->getIdClient()->getIdClientStatusHistory()->getIdStatus()->getId();
?>

<?php if (isset($_SESSION['email_completude_confirm']) && $_SESSION['email_completude_confirm']) : ?>
    <div class="row">
        <div class="form-group col-md-6">
            <img src="<?= $this->surl ?>/images/admin/mail.png" alt="email">Votre email a été envoyé
        </div>
        <div class="form-group col-md-6">
            <a href="<?= $this->lurl ?>/preteurs/activation" class="btn_link btnBackListe">Revenir à la liste de contôle</a>
        </div>
    </div>
    <?php unset($_SESSION['email_completude_confirm']); ?>
<?php endif; ?>

<?php if (false === in_array($clientStatus, [ClientsStatus::STATUS_CLOSED_BY_UNILEND, ClientsStatus::STATUS_CLOSED_LENDER_REQUEST])) : ?>
    <div class="row">
        <div class="form-group col-md-6">
            <input type="button" id="completude_edit" class="btn-primary btnCompletude" value="Complétude">
        </div>
    </div>
    <div class="row message_completude" style="display: none;">
        <div class="form-group col-md-6 ">
            <h2>Complétude - Personnalisation du message</h2>
            <div class="liwording">
                <table>
                    <?php foreach ($this->completude_wording as $key => $message) : ?>
                        <tr>
                            <td><img class="add" id="add-<?= $key ?>" src="<?= $this->surl ?>/images/admin/add.png"></td>
                            <td><span class="content-add-<?= $key ?>"><?= $message ?></span></td>
                        </tr>
                        <?php if (substr($key, -1, 1) == 3) : ?>
                            <tr><td colspan="2">&nbsp;</td></tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
        <div class="form-group col-md-6">
            <h3 class="test">Listes : </h3>
            <div class="content_li_wording"></div>
            <fieldset style="width:100%;">
                <table class="formColor" style="width:100%;">
                    <tr>
                        <td>
                            <label for="content_email_completude">Saisir votre message :</label>
                            <textarea name="content_email_completude" id="content_email_completude"><?= isset($_SESSION['content_email_completude'][$this->params[0]]) ? $text = str_replace(array('<br>', '<br />'), '', $_SESSION['content_email_completude'][$this->params[0]]) : '' ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th>
                            <a id="completude_preview" href="<?= $this->lurl ?>/preteurs/completude_preview/<?= $this->client->getIdClient() ?>" class="thickbox"></a>
                            <input type="button" value="Prévisualiser" title="Prévisualiser" name="previsualisation" id="previsualisation" class="btn"/>
                        </th>
                    </tr>
                </table>
            </fieldset>
        </div>
    </div>
<?php endif; ?>
