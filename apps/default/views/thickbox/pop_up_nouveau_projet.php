<style>
    .popup-head h2 {
        background: #b10366 none repeat scroll 0 0;
        border-bottom: 1px solid #fff;
        border-radius: 8px ;
        color: #fff;
        padding: 13px 20px 14px;
        text-align: center;
    }
    .form-row {
        margin-bottom: 5px;
        position: relative;
    }
    .form-row > em {display: inline-block; margin: 5px; font-size: 13px;}

    #commentaires{
        width: 425px;
        margin-top: 50px;
        height: 260px;
    }
    .btn {
        margin-top: 15px;
        margin-left: 170px;
    }

</style>
<div class="popup" style="background-color: #E3E4E5;width: 665px; overflow: hidden; height: 570px;">
    <a href="#" class="popup-close">close</a>
    <div class="popup-head">
        <h2><?=$this->lng['espace-emprunteur']['pop-up-nouvelle-demande-de-projet']?></h2>
    </div>
    <div class="popup-cnt">
            <div class="notification-body">
                    <form action="<?= $this->lurl ?>/espace_emprunteur/projets" method="post">
                        <div class="form-row">
                            <input type="text" name="montant" id="montant"
                                   placeholder="&euro; <?= $this->lng['espace-emprunteur']['pop-up-nouveau-projet-montant-souhaite'] ?>"
                                   class="field field-large required"
                                   data-validators="Presence&amp;Numericality, {maximum:<?= $this->sommeMax ?>}&amp;Numericality, {minimum: <?= $this->sommeMin ?>}"
                                   onkeyup="lisibilite_nombre(this.value,this.id);">
                            <em class="jusqua"><?= $this->lng['espace-emprunteur']['pop-up-nouveau-projet-montant-jusqua'] ?></em>
                        </div>
                        <div class="form-row">
                            <select name="duree" id="duree" class="field field-large required custom-select">
                                <option value="0"><?= $this->lng['espace-emprunteur']['duree'] ?></option>
                                <?php foreach ($this->dureePossible as $duree): ?>
                                    <option
                                        value="<?= $duree ?>"<?= $duree == $this->aForm['duree'] ? ' selected' : '' ?>><?= $duree . ' ' . $this->lng['espace-emprunteur']['mois'] ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="form-row">
                              <textarea name="commentaires" id="commentaires" cols="35" rows="25"
                              placeholder="<?= $this->lng['espace-emprunteur']['pop-up-nouveau-projet-toutes-informations-utiles'] ?>"
                              class="field"></textarea>
                            <em><?= $this->lng['espace-emprunteur']['champs-obligatoires']?></em>
                        </div>
                        <input type="submit" class="btn"
                               value="<?= $this->lng['espace-emprunteur']['valider']?>"
                               name="valider_demande_projet">
                    </form>
            </div><!-- /.notification-body -->
    </div>
    <!-- /popup-cnt -->
</div>
