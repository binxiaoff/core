<a class="tab_title" id="section-email" href="#section-email">Email / CGV</a>
<div class="tab_content" id="tab_email">
    <div class="div-2-columns">
        <div class="div-left-pos">
            <div id="send_cgv">
                <h2>Envoi des CGV</h2>
                <a href="<?= $this->lurl ?>/dossiers/send_cgv_ajax/<?= $this->projects->id_project ?>" class="btn_link thickbox cboxElement">Envoyer</a>
            </div>
            <?php if (in_array($this->projects->status, [\projects_status::COMMERCIAL_REVIEW, \projects_status::PENDING_ANALYSIS, \projects_status::ANALYSIS_REVIEW, \projects_status::COMITY_REVIEW, \projects_status::PREP_FUNDING])) : ?>
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
                                    <label for="content_email_completude">Saisir votre message :</label>
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
            <?php endif; ?>
        </div>
    </div>
    <br><br>
    <?php $this->fireView('../blocs/acceptedLegalDocumentList'); ?>
</div>
