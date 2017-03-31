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
                            <?php foreach ($this->completude_wording as $sSlug => $sWording) : ?>
                                <tr>
                                <td>
                                    <a class="add_wording" id="add-<?= $sSlug ?>"><img src="<?= $this->surl ?>/images/admin/add.png"></a>
                                </td>
                                <td>
                                    <span class="content-add-<?= $sSlug ?>"><?= $sWording ?></span>
                                </td>
                                </tr>
                            <?php endforeach ?>
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
    <br><br>
    <?php $this->fireView('../blocs/acceptedLegalDocumentList'); ?>
</div>
