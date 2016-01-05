<?php
if ($this->bLinkExpired === true) : ?>
    <div class="wrapper">
        <div class="shell">
            <p><?= $this->lng['espace-emprunteur']['lien-expire'] ?></p>
        </div>
    </div>
<?php else : ?>
    <div class="main">
        <div class="shell">
            <form action="" method="post" id="form_mdp_question_emprunteur"
                  name="form_mdp_question_emprunteur">
                <div>
                    <div class="row">
                        <span class="pass-field-holder">
                        <input type="password" name="pass" id="pass"
                               title="<?= $this->lng['espace-emprunteur']['mot-de-passe'] ?>"
                               value="" class="field field-large required">
                        </span>

                        <span class="pass-field-holder">
                        <input type="password" name="pass2" id="pass2"
                               title="<?= $this->lng['espace-emprunteur']['confirmation-de-mot-de-passe'] ?>"
                               value=""
                               class="field field-large "
                               data-validators="Confirmation,{ match: 'pass' }">
                        </span>
                        <div><em><?= $this->lng['espace-emprunteur']['info-mdp'] ?></em></div>
                    </div>
                    <div class="row">
                        <input type="text" id="secret-question" name="secret-question"
                               title="<?= $this->lng['espace-emprunteur']['question-secrete'] ?>"
                               placeholder="<?= $this->lng['espace-emprunteur']['question-secrete'] ?>"
                               class="field field-mega required" data-validators="Presence">
                    </div>
                    <div class="row">
                        <input type="text" id="secret-response" name="secret-response"
                               title="<?= $this->lng['espace-emprunteur']['response'] ?>"
                               placeholder="<?= $this->lng['espace-emprunteur']['response'] ?>"
                               class="field field-mega required" data-validators="Presence">
                    </div>
                </div>
                <div class="form-foot row row-cols centered">
                    <input type="hidden" name="form_mdp_question_emprunteur">
                    <button class="btn" type="submit"><?= $this->lng['espace-emprunteur']['valider'] ?><i
                            class="icon-arrow-next"></i></button>
                </div>
            </form>
        </div>
    </div>
    <script>
        $(document).ready(function () {
            $('#pass').keyup(function () {
                controleMdp($(this).val(), 'pass');
            });
            $('#pass2').bind('paste', function (e) {
                e.preventDefault();
            });
        });

        $("#form_mdp_question_emprunteur").submit(function (event) {
            var radio = true;

            if (controleMdp($('#pass').val(), 'pass', false) == false) {
                radio = false;
            }

            if (radio == false) {
                event.preventDefault();
            }
        });
    </script>
<?php endif; ?>

