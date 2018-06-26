<a class="tab_title" id="section-email" href="#section-email">Email</a>
<div class="tab_content" id="tab_email">
    <h2>Envoi des CGV</h2>

    <?php if (false === empty($this->project_cgv->id)) : ?>
        <div>
            <table class="tablesorter">
                <tbody>
                <tr>
                    <td>
                        CGV envoyées le <?= \DateTime::createFromFormat('Y-m-d H:i:s', $this->project_cgv->added)->format('d/m/Y à H:i:s') ?>
                        (<a href="<?= $this->furl . $this->project_cgv->getUrlPath() ?>" target="_blank">PDF</a>)
                        <?php if (\Unilend\Bundle\CoreBusinessBundle\Entity\UniversignEntityInterface::STATUS_SIGNED == $this->project_cgv->status && false === empty($this->project_cgv->updated)) : ?>
                            <strong>signées</strong> le <?= \DateTime::createFromFormat('Y-m-d H:i:s', $this->project_cgv->updated)->format('d/m/Y à H:i:s') ?>
                        <?php endif; ?>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <br>
    <?php endif; ?>

    <a href="<?= $this->lurl ?>/dossiers/send_cgv_ajax/<?= $this->projects->id_project ?>" class="btn-primary thickbox cboxElement">Envoyer</a>
</div>
