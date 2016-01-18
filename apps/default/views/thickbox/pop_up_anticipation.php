<style type="text/css">
    #non:after {
        background-color: #BFBFBF;
    }

    #non:before {
        background-color: #A1A5A7;
    }
</style>
<div class="popup" style="width: 450px;height:250px;">
    <a href="#" class="popup-close">close</a>

    <div class="popup-head">
        <h2><?= $this->lng['espace-emprunteur']['pop-up-confirmation-de-cloture-anticipe-titre'] ?>
        </h2>
    </div>

    <div class="popup-cnt" style="padding:10px;">
        <p><?php printf($this->lng['espace-emprunteur']['pop-up-confirmation-de-cloture-anticipe'], $this->fIR) ?></p>

        <form action="<?= $this->lurl ?>/espace_emprunteur/projets/<?= empty($this->projects->hash) === false ? $this->projects->hash : $this->projects->id_project ?>" method="post"
              class="form_mdp_lost" name="confirm_cloture_anticipation" id="confirm_cloture_anticipation">
            <table border="1" style="margin:auto;">
                <tr>
                    <td colspan="2" style="text-align:center;">
                        <button type="submit" name="oui" class="btn btn-medium"><?= $this->lng['espace-emprunteur']['pop-up-confirmation-de-cloture-anticipe-oui']?></button>
                        <button type="button" id="non" class="btn btn-medium"><?= $this->lng['espace-emprunteur']['pop-up-confirmation-de-cloture-anticipe-non']?></button>
                        <input type="hidden" id="confirm_cloture_anticipation" name="confirm_cloture_anticipation">
                    </td>
                </tr>
            </table>
        </form>
    </div>
</div>
<!-- /popup-cnt -->


<script type="text/javascript">
    $("#non").click(function () {
        $(".popup-close").click()
    });
</script>