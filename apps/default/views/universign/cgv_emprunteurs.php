<?php
use Unilend\Bundle\CoreBusinessBundle\Entity\UniversignEntityInterface;
use Unilend\Bundle\CoreBusinessBundle\Entity\ProjectCgv;
?>
<div class="main">
    <div class="shell">
        <h1>Signature des CGV Unilend</h1>
        <?php if (UniversignEntityInterface::STATUS_SIGNED == $this->status) : ?>
            <div class="row">
                Vous avez bien signé les CGV Unilend. vous pouvez retrouver le document signé en cliquant sur ce <a href="<?= $this->lien_pdf ?>">lien</a>.
                <br/><br/>
                Merci de votre confiance, nous finalisons l'étude de votre demande.
            </div>
        <?php elseif ($this->status == UniversignEntityInterface::STATUS_CANCELED) : ?>
            <div class="row">La signature de vos CGV a bien été annulée, vous pouvez les signer plus tard.</div>
        <?php elseif ($this->status == UniversignEntityInterface::STATUS_FAILED) : ?>
            <div class="row">Une erreur s'est produite lors de la signature des CGV, veuillez réessayer plus tard.</div>
        <?php elseif ($this->status == UniversignEntityInterface::STATUS_PENDING) : ?>
            <div class="row">Vous n'avez pas encore signé vos CGV.</div>
        <?php endif; ?>
    </div>
</div>
