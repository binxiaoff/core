<div class="popup" style="background-color: #E3E4E5;">
    <a href="#" class="popup-close">close</a>
    <div class="popup-head">
        <h2><?=$this->lng['espace-emprunteur']['pop-up-nouvelle-demande-de-projet']?></h2>

    </div>
    <div class="popup-cnt">
        <div class="notification-primary">
            <div class="notification-body">
                    <form action="<?= $this->lurl ?>/espace_emprunteur/projets" method="post">
                        <div class="row form-row">
                            <input type="text" name="montant" id="montant"
                                   placeholder="<?= $this->lng['espace-emprunteur']['pop-up-nouveau-projet-montant-souhaite'] ?>"
                                   class="field required"
                                   data-validators="Presence&amp;Numericality, {maximum:<?= $this->sommeMax ?>}&amp;Numericality, {minimum: <?= $this->sommeMin ?>}"
                                   onkeyup="lisibilite_nombre(this.value,this.id);">
                        </div>
                        <div class="row form-row">
                            <select name="duree" id="duree" class="field field-small required custom-select">
                                <option value="0"><?= $this->lng['espace-emprunteur']['duree'] ?></option>
                                <?php foreach ($this->dureePossible as $duree): ?>
                                    <option
                                        value="<?= $duree ?>"<?= $duree == $this->aForm['duree'] ? ' selected' : '' ?>><?= $duree . ' ' . $this->lng['espace-emprunteur']['mois'] ?>
                                    </option>
                                <?php endforeach ?>
                            </select>
                        </div>
                        <div class="row form-row">
                              <textarea name="commentaires" id="commentaires" cols="25" rows="10"
                              placeholder="<?= $this->lng['espace-emprunteur']['pop-up-nouveau-projet-toutes-informations-utiles'] ?>"
                              class="field"></textarea>
                        </div>
                        <input type="submit" class="btn"
                               value="<?= $this->lng['espace-emprunteur']['valider']?>"
                               name="valider_demande_projet">
                    </form>
            </div><!-- /.notification-body -->
        </div><!-- /.notification-primary -->
    </div>
    <!-- /popup-cnt -->
</div>
