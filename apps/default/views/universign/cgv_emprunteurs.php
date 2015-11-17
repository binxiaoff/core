<div class="main">
    <div class="shell">
        <h1>Signature des CGV Unilend</h1>
        <?php if (in_array($this->status, array(project_cgv::STATUS_SIGN_UNIVERSIGN, project_cgv::STATUS_SIGN_FO))) { ?>
            <div class="row">
                Vous avez bien signé les CGV Unilend. vous pouvez retrouver le document signé en cliquant sur ce <a href="<?= $this->lien_pdf ?>">lien</a>.
                <br/><br/>
                Merci de votre confiance, nous finalisons l'étude de votre demande.
            </div>
        <?php } elseif ($this->status == project_cgv::STATUS_SIGN_CANCELLED) { ?>
            <div class="row">La signature de vos CGV a bien été annulée, vous pouvez les signer plus tard.</div>
        <?php } elseif ($this->status == project_cgv::STATUS_SIGN_FAILED) { ?>
            <div class="row">Une erreur s'est produite lors de la signature des CGV, veuillez réessayer plus tard.</div>
        <?php } elseif ($this->status == project_cgv::STATUS_NO_SIGN) { ?>
            <div class="row">Vous n'avez pas encore signé vos CGV.</div>
        <?php } ?>
    </div>
</div>
