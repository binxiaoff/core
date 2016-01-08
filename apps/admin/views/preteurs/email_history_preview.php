<div id="popup" style="width:800px;">
    <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?=$this->surl?>/images/admin/delete.png" alt="Fermer" /></a>
        <table class="formMail">
            <tr>
                <th>From : <?= str_replace('"', '', $this->oMail->from) ?> </th>
            </tr>
            <tr>
                <th>To : <?= $this->oMail->email_nmp ?></th>
            </tr>
            <tr>
                <th><?= str_replace("_"," ", utf8_encode(mb_decode_mimeheader($this->oMail->subject))) ?></th>
            </tr>
            <tr>
                <td><iframe src="<?=$this->lurl?>/preteurs/email_history_preview_iframe/<?=$this->params[0]?>" width="760px" height="400px"></iframe></td>
            </tr>
        </table>
</div>