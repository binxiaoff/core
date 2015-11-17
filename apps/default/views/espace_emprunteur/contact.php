<div class="main">
    <div class="shell">
        <div class="contact">
            <h1><?=$this->lng['espace-emprunteur']['nous-contacter'] ?></h1>
            <div class="contact-form">
                <form action="<?= $_SERVER['REQUEST_URI'] ?>" method="post" enctype="multipart/form-data">
                    <div class="row">
                        <input type="text"
                               placeholder="<?= $this->lng['espace-emprunteur']['contact-siren'] ?>"
                               value="<?= $this->aContactRequest['siren'] ?>"
                               name="siren" id="siren"
                               class="field field-large required <?= isset($this->aErrors['siren']) ? ' LV_invalid_field' : '' ?>"
                               data-validators="Presence">
                    </div>
                    <div class="row">
                        <input type="text"
                               placeholder="<?= $this->lng['espace-emprunteur']['contact-societe'] ?>"
                               value="<?= $this->aContactRequest['company'] ?>"
                               id="company" name="company"
                               class="field field-large required <?= isset($this->aErrors['company']) ? ' LV_invalid_field' : '' ?>"
                               data-validators="Presence">
                    </div>
                    <div class="row">
                        <input type="text"
                               placeholder="<?= $this->lng['espace-emprunteur']['contact-prenom'] ?>"
                               value="<?= $this->aContactRequest['prenom'] ?>"
                               name="prenom" id="prenom"
                               class="field field-small required <?=isset($this->aErrors['prenom']) ? ' LV_invalid_field' : '' ?>"
                               data-validators="Presence&amp;Format,{pattern:/^([^0-9]*)$/}">
                        <input type="text"
                               placeholder="<?= $this->lng['espace-emprunteur']['contact-nom'] ?>"
                               value="<?= $this->aContactRequest['nom'] ?>"
                               id="nom" name="nom"
                               class="field field-small required <?= isset($this->aErrors['nom']) ? ' LV_invalid_field' : '' ?>"
                               data-validators="Presence&amp;Format,{pattern:/^([^0-9]*)$/}">
                    </div>
                    <div class="row">
                        <input type="text"
                               placeholder="<?= $this->lng['espace-emprunteur']['contact-portable'] ?>"
                               value="<?= $this->aContactRequest['telephone'] ?>"
                               name="telephone" id="phone"
                               class="field field-small <?= isset($this->aErrors['telephone']) ? ' LV_invalid_field' : '' ?>"
                               data-validators="Presence&amp;Numericality&amp;Length,{minimum: 9, maximum: 14}">

                        <input type="text"
                               placeholder="<?= $this->lng['espace-emprunteur']['contact-email'] ?>"
                               value="<?= $this->aContactRequest['email'] ?>"
                               name="email" id="email"
                               class="field field-small required <?= isset($this->aErrors['email']) ? ' LV_invalid_field' : '' ?>"
                               data-validators="Presence&amp;Email">

                    </div>
                    <div class="row">
                        <select class="custom-select required field field-large" name="demande" id="demande">
                            <option value=""><?= $this->lng['espace-emprunteur']['contact-selectionnez-un-sujet'] ?></option>
                            <?php foreach ($this->aRequestSubjects as $aRequestSubject) { ?>
                                <option value="<?= $aRequestSubject['id_contact_request_subject'] ?>"><?= $aRequestSubject['label'] ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="row">
                        <textarea cols="30" rows="10" placeholder="<?= $this->lng['espace-emprunteur']['contact-message'] ?>"
                                  name="message" id="message"
                                  class="field field-extra-large required <?= isset($this->aErrors['message']) ? ' LV_invalid_field' : '' ?>"
                                  data-validators="Presence"><?= $this->aContactRequest['message'] ?></textarea>
                    </div>
                    <div class="row">
                        <div class="row row-upload">
                            <div class="uploader">
                                <input type="text" id="attachement"
                                       value="<?= $this->lng['espace-emprunteur']['contact-aucun-fichier-selectionne'] ?>"
                                       class="field required"
                                       readonly="readonly">
                                <div class="file-holder">
                            <span class="btn btn-small">
                                <?= $this->lng['espace-emprunteur']['contact-parcourir'] ?>
                                <span class="file-upload">
                                    <input type="file" class="file-field" name="attachement">
                                </span>
                            </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-foot">
                        <input type="hidden" name="send_form_contact">
                        <button type="submit" class="btn">
                            <?= $this->content['call-to-action-40'] ?>
                            <i class="icon-arrow-next"></i>
                        </button>
                    </div>
                </form>
            </div>
            <div class="contact-block" style="width: 400px;">
                <div class="contact-block-body">
                    <h2><?= $this->content['bloc-titre'] ?></h2>
                    <h4><i class="icon-place"></i> <?= $this->content['bloc-adresse'] ?></h4>
                    <?php $this->settings->get('Téléphone emprunteur', 'type'); ?>
                    <p><i class="icon-phone"></i> <?= $this->lng['espace-emprunteur']['bloc-tel'] ?>
                        : <?= $this->settings->value ?></p>

                    <p><i class="icon-mail"></i> <?= $this->lng['espace-emprunteur']['bloc-email'] ?> : <a
                            href="mailto:<?= $this->lng['espace-emprunteur']['bloc-email'] ?>"><?= $this->lng['espace-emprunteur']['bloc-email-contact-emprunteur'] ?></a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
        $(document).on('change', 'input.file-field', function() {
        var val = $(this).val();

        if (val.length != 0 || val != '') {
            val = val.replace(/\\/g, '/').replace(/.*\//, '');
            $(this).closest('.uploader').find('input.field').val(val).addClass('LV_valid_field').addClass('file-uploaded');
        }
    });
</script>