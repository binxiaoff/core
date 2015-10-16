<div class="contact">
    <div class="contact-form">
        <form action="<?= $_SERVER['REQUEST_URI'] ?>" method="post">
            <?php if (isset($this->confirmation)) { ?>
                <p class="system-message message-positive"><?= $this->confirmation ?></p>
            <?php } ?>
            <div class="row">
                <input type="text" placeholder="<?= $this->lng['contact']['prenom'] ?>" value="<?= $this->demande_contact->prenom ?>" name="prenom" id="prenom" class="field field-small required <?= (isset($this->error_prenom) && $this->error_prenom == 'ok' ? 'LV_valid_field' : (isset($this->error_prenom) && $this->error_prenom == 'nok' ? 'LV_invalid_field' : '')) ?>" data-validators="Presence">
                <input type="text" placeholder="<?= $this->lng['contact']['nom'] ?>" value="<?= $this->demande_contact->nom ?>" id="nom" name="nom" class="field field-small required <?= (isset($this->error_nom) && $this->error_nom == 'ok' ? 'LV_valid_field' : (isset($this->error_nom) && $this->error_nom == 'nok' ? 'LV_invalid_field' : '')) ?>" data-validators="Presence">
            </div>
            <div class="row">
                <input type="text" placeholder="<?= $this->lng['contact']['societe'] ?>" value="<?= $this->demande_contact->societe ?>" name="societe" id="societe" class="field field-large">
            </div>
            <div class="row">
                <input type="text" placeholder="<?= $this->lng['contact']['email'] ?>" value="<?= $this->demande_contact->email ?>" name="email" id="email" class="field field-small required <?= (isset($this->error_email) && $this->error_email == 'ok' ? 'LV_valid_field' : (isset($this->error_email) && $this->error_email == 'nok' ? 'LV_invalid_field' : '')) ?>" data-validators="Email">
                <input type="text" placeholder="<?= $this->lng['contact']['telephone'] ?>" value="<?= $this->demande_contact->telephone ?>" name="telephone" id="phone" class="field field-small <?= (isset($this->error_telephone) && $this->error_telephone == 'ok' ? 'LV_valid_field' : (isset($this->error_telephone) && $this->error_telephone == 'nok' ? 'LV_invalid_field' : '')) ?>">
            </div>
            <div class="row">
                <textarea cols="30" rows="10" placeholder="<?= $this->lng['contact']['message'] ?>" name="message" id="message" class="field field-extra-large required <?= (isset($this->error_message) && $this->error_message == 'ok' ? 'LV_valid_field' : (isset($this->error_message) && $this->error_message == 'nok' ? 'LV_invalid_field' : '')) ?>" data-validators="Presence"><?= $this->demande_contact->message ?></textarea>
            </div>
            <div class="row row-captcha">
                <div class="captcha-holder">
                    <img src="<?= $this->surl ?>/images/default/securitecode.php" alt="captcha">
                </div>
                <input type="text" name="captcha" class="field required <?= (isset($this->error_captcha) && $this->error_captcha == 'ok' ? 'LV_valid_field' : (isset($this->error_captcha) && $this->error_captcha == 'nok' ? 'LV_invalid_field' : '')) ?>" id="captcha" data-validators="Presence" placeholder="<?= $this->lng['contact']['captcha'] ?>">
            </div>
            <div class="form-foot">
                <input type="hidden" name="send_form_contact">
                <input type="hidden" name="demande" value="3">
                <input type="hidden" name="preciser" value="">
                <button type="submit" class="btn">
                    <?= $this->content['call-to-action-40'] ?>
                    <i class="icon-arrow-next"></i>
                </button>
            </div>
        </form>
    </div>
    <div class="contact-block">
        <div class="contact-block-body">
            <h2><?= $this->content['bloc-titre'] ?></h2>
            <h4><i class="icon-place"></i> <?= $this->content['bloc-adresse'] ?></h4>
            <p><i class="icon-phone"></i> <?= $this->lng['contact']['bloc-tel'] ?> : <?= $this->lng['contact']['tel-emprunteur'] ?></p>
            <p><i class="icon-mail"></i> <?= $this->lng['contact']['bloc-email'] ?> : <a href="mailto:<?= $this->content['bloc-email'] ?>"><?= $this->content['bloc-email'] ?></a></p>
        </div>
    </div>
</div>
