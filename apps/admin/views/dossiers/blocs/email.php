<div class="tab_title" id="title_tab_email">Email</div>
<div class="tab_content" id="tab_email">
    <div class="div-2-columns">
        <div class="div-left-pos">
            <div id="edit_projects_tab_email">
                <h2>Configuration d'envoi d'Email</h2>
                <input type="checkbox" name="stop_relances" id="stop_relances" value="1" <?= $this->projects->stop_relances == 1 ? 'checked' : '' ?>/>
                <label for="stop_relances">Arrêt des relances</label>
                <br/>
                <br/>
                <a href="#" class="btn_link" id="save_projects_tab_email" data-project-id="<?= $this->projects->id_project ?>">Sauvegarder</a>
            </div>
            <br/>
            <div id="tab_email_msg">Données sauvegardées</div>
            <br/>
            <div id="send_cgv">
                <h2>Envoi des CGV</h2>
                <a href="<?= $this->lurl ?>/dossiers/send_cgv_ajax/<?= $this->projects->id_project ?>" class="btn_link thickbox cboxElement">Envoyer</a>
            </div>

            <?php if (in_array($this->projects->status, array(\projects_status::EN_ATTENTE_PIECES, \projects_status::ATTENTE_ANALYSTE, \projects_status::REVUE_ANALYSTE, \projects_status::COMITE, \projects_status::PREP_FUNDING))) { ?>
                <br/>
                <br/>
                <div id="send_completeness" style="height: 50%;">
                    <h2>Complétude - Personnalisation du message</h2>
                    <div class="liwording">
                        <table>
                            <?php
                            /** @var \Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType $attachmentType */
                            foreach ($this->attachmentTypesForCompleteness as $key => $attachmentType) :
                                $year = date('Y');
                                $id = $attachmentType->getId();
                                $translation = 'projet_document-type-' . $id;
                                if ($id == \Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType::DERNIERE_LIASSE_FISCAL) {
                                    $year -= 1;
                                }
                                if ($id == \Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType::LIASSE_FISCAL_N_1) {
                                    $year -= 2;
                                }
                                if ($id == \Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType::LIASSE_FISCAL_N_2) {
                                    $year -= 3;
                                }
                                if ($id == \Unilend\Bundle\CoreBusinessBundle\Entity\AttachmentType::PHOTOS_ACTIVITE) {
                                    $translation = $attachmentType->getLabel() . ' ' . $this->translator->trans('projet_completude-photos');
                                }
                            ?>
                                <tr>
                                <td>
                                    <a class="add_wording" id="add-<?= $key ?>"><img src="<?= $this->surl ?>/images/admin/add.png"></a>
                                </td>
                                <td>
                                    <span class="content-add-<?= $key ?>">
                                        <?= $this->translator->trans($translation, ['%year%', $year]) ?>
                                        <?php if ($id == \attachment_type::PHOTOS_ACTIVITE) : ?>
                                            <?= $this->translator->trans('projet_completude-photos') ?>
                                        <?php endif ?>
                                    </span>
                                </td>
                                </tr>
                            <?php endforeach ?>
                            <td>
                                <a class="add_wording" id="add-<?= count($this->attachmentTypesForCompleteness) + 1 ?>"><img src="<?= $this->surl ?>/images/admin/add.png"></a>
                            </td>
                            <td>
                                <span class="content-add-<?= count($this->attachmentTypesForCompleteness) + 1  ?>"><?= $this->translator->trans('projet_completude-charge-affaires') ?></span>
                            </td>
                        </table>
                    </div>
                    <br/>
                    <h3 class="test">Listes : </h3>
                    <div class="content_li_wording"></div>
                    <fieldset style="width:100%;">
                        <table class="formColor" style="width:100%;">
                            <tr>
                                <td>
                                    <label for="id">Saisir votre message :</label>
                                    <textarea name="content_email_completude" id="content_email_completude"></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <a id="completude_preview" href="<?= $this->lurl ?>/dossiers/completude_preview/<?= $this->projects->id_project ?>" class="btn_link thickbox cboxElement">Prévisualiser</a>
                                </th>
                            </tr>
                        </table>
                    </fieldset>
                </div>
            <?php } ?>
        </div>
        <div class="div-right-pos">
            <h2>Historique</h2>
            <?php if (false === empty($this->aEmails) || false === empty($this->project_cgv->id)) : ?>
                <table class="tablesorter">
                    <tbody>
                    <?php if (false === empty($this->project_cgv->id)) : ?>
                        <tr>
                            <td>
                                CGV envoyées le <?= date('d/m/Y à H:i:s', strtotime($this->project_cgv->added)) ?>
                                (<a href="<?= $this->furl . $this->project_cgv->getUrlPath() ?>" target="_blank">PDF</a>)
                                <?php if (in_array($this->project_cgv->status, array(project_cgv::STATUS_SIGN_UNIVERSIGN, project_cgv::STATUS_SIGN_FO))) : ?>
                                    <br/>
                                    <strong>signées</strong> le <?= date('d/m/Y à H:i:s', strtotime($this->project_cgv->updated)) ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($this->aEmails as $aEmail) : ?>
                        <tr>
                            <td>
                                <?php $this->users->get($aEmail['id_user'], 'id_user'); ?>
                                Envoyé le <?= date('d/m/Y à H:i:s', strtotime($aEmail['added'])) ?> par <?= $this->users->name ?>
                                <br>
                                <?= $aEmail['content'] ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
