<style type="text/css">
    .form-style-10{
        padding:20px;
        background: #FFF;
        border-radius: 10px;
        -webkit-border-radius:10px;
        -moz-border-radius: 10px;
        box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.13);
        -moz-box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.13);
        -webkit-box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.13);
    }
    .form-style-10 .inner-wrap{
        padding: 10px;
        background: #F8F8F8;
        border-radius: 6px;
        margin-bottom: 15px;
    }
    .form-style-10 .section{
        font: normal 20px 'Bitter', serif;
        color: #B20066;
        margin-bottom: 5px;
    }
    .form-style-10 .section span {
        background: #B20066;
        padding: 5px 10px 5px 10px;
        position: absolute;
        border-radius: 50%;
        -webkit-border-radius: 50%;
        -moz-border-radius: 50%;
        border: 4px solid #fff;
        font-size: 14px;
        margin-left: -45px;
        color: #fff;
        margin-top: -3px;
    }
    span.st {
        width: 25%;
    }
    .form-style-10 .add-attachment{
        border-collapse: separate;
        border-spacing: 2px;
    }
    .form-style-10 .add-attachment td{
        padding: 5px;
    }
    .td-greenPoint-status-valid {
        border-radius: 5px; background-color: #00A000; color: white; width: 250px;
    }
    .td-greenPoint-status-warning {
        border-radius: 5px; background-color: #f79232; color: white; width: 250px;
    }
    .td-greenPoint-status-error {
        border-radius: 5px; background-color: #ff0100; color: white; width: 250px;
    }
</style>
<h2>Pièces jointes<span></span></h2>
<div class="form-style-10">
    <?php foreach ($this->attachmentGroups as $key => $attachmentGroup) : ?>
        <div class="section"><span><?= $key + 1 ?></span><?= $attachmentGroup['title'] ?></div>
        <div class="inner-wrap">
            <table id="identity-attachments" class="add-attachment">
                <?php
                /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\Attachment $attachment */
                foreach ($attachmentGroup['attachments'] as $attachment) :
                    $greenpointLabel       = 'Non Contrôlé par GreenPoint';
                    $greenpointColor       = 'error';
                    /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\GreenpointAttachment $greenPointAttachment */
                    $greenPointAttachment  = $attachment->getGreenpointAttachment();
                    if ($greenPointAttachment) {
                        $greenpointLabel = $greenPointAttachment->getValidationStatusLabel();
                        if (0 == $greenPointAttachment->getValidationStatus()) {
                            $greenpointColor = 'error';
                        } elseif (8 > $greenPointAttachment->getValidationStatus()) {
                            $greenpointColor = 'warning';
                        } else {
                            $greenpointColor = 'valid';
                        }
                    }
                    ?>
                    <tr>
                        <th width="25%"><?= $attachment->getType()->getLabel() ?></th>
                        <td width="45%">
                            <a href="<?= $this->url ?>/viewer/client/<?= $this->client->getIdClient() ?>/<?= $attachment->getId() ?>" target="_blank">
                                <?= $attachment->getPath() ?>
                            </a>
                            <?php /* OLD
                            <a href="<?= $this->url ?>/attachment/download/id/<?= $attachment->getId() ?>/file/<?= urlencode($attachment->getPath()) ?>">
                                <?= $attachment->getPath() ?>
                            </a>
                            */ ?>
                        </td>
                        <td class="td-greenPoint-status-<?= $greenpointColor?>" width="25%">
                            <?= $greenpointLabel ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endforeach; ?>
</div>
