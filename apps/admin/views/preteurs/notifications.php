<?php

use Unilend\Bundle\CoreBusinessBundle\Entity\Projects;

?>
<style>
    #project_form {
        margin-bottom: 30px;
    }
    #project_form label {
        display: block;
        margin-bottom: 5px;
    }
    .table-clickable tr td {
        cursor: pointer;
    }
    .table-hover tr:hover > td {
        background: #cecece;
    }
    #add_notification_form {
        display: none;
    }
    .send_project {
        display: inline-block;
        font-size: 12px;
        margin-left: 104px;
        color: #75757d;
    }
    #project_list {
        background: #fff;
        margin-top: 15px;
        padding: 15px;
    }
</style>
<script>
    $(function () {
        $("#notificationDate").datepicker({
            changeMonth: true,
            changeYear: true,
            yearRange: '<?=(date('Y') - 1)?>:<?=(date('Y') + 16)?>'
        });
        $("#notificationDate").datepicker('setDate', new Date());

        $('#project_list table tr').on('click', function () {
            var projectName = $(this).data('project-name')
            var projectId = $(this).data('project-id')
            var form = $('#add_notification_form')
            $('#selectedProjectId').val(projectId)
            form.find('.send_project .name').text(projectName)
            form.find('.send_project .id').text(projectId)
            form.show();
            $('html, body').animate({
                scrollTop: 0
            }, 450);
        });
        $('#addNotification').on('click', function (e) {
            e.preventDefault();
            var form = $('#add_notification_form');
            $.ajax({
                method: form.attr('method'),
                url: form.attr('action'),
                data: form.serialize(),
                dataType: 'json'
            }).done(function (response) {
                $('#add_notification_result').text(response.message)
                if (response.status == 'ok') {
                    $('#add_notification_result').css('color', 'green')
                } else {
                    $('#add_notification_result').css('color', 'red')
                }
            })
        })
    })
</script>
<div id="contenu">
    <div id="search_project">
        <table style="width: 100%">
            <tr>
                <td style="width: 30%">
                    <h2>Choisir un projet</h2>
                    <br>
                    <form method="post" name="project_form" id="project_form" enctype="multipart/form-data" action="<?= $this->lurl ?>/preteurs/notifications" target="_parent">
                        <fieldset name="project">
                            <p>
                                <label for="projectId">ID projet</label>
                                <input type="text" name="projectId" id="projectId" class="input_large">
                            </p>
                            <p>
                                <label for="projectTitle">Raison sociale</label>
                                <input type="text" name="projectTitle" id="projectTitle" class="input_large">
                            </p>
                        </fieldset>
                        <fieldset>
                            <input type="submit" title="valider" value="Valider" id="searchProject" name="searchProject" class="btn-primary" style="float: right">
                        </fieldset>
                    </form>
                </td>
                <td style="width: 10%"></td>
                <td style="width: 60%">
                    <form method="post" name="add_notification_form" id="add_notification_form" enctype="multipart/form-data" action="<?= $this->lurl ?>/preteurs/addNotification" target="_parent">
                        <h2>Détails de la notification <span class="send_project"><span class="name"></span>  <span class="id"></span></span></h2>
                        <br>
                        <table style="width: 100%">
                            <tr>
                                <td style="width: 33%; vertical-align: top;" class="add_notification">
                                    <p>
                                        <label for="notificationDate">Date de la notification</label>
                                        <input type="text" id="notificationDate" class="input_large" name="notificationDate" value="">
                                    </p>
                                    <p>
                                        <label for="notificationSubject">Sujet</label>
                                        <input type="text" id="notificationSubject" class="input_large" name="notificationSubject" value="">
                                    </p>
                                </td>
                                <td style="width: 63%; vertical-align: top; padding-left: 3%; padding-right: 10px">
                                    <p>
                                        <label for="notificationContent">Contenu de la notification</label>
                                        <textarea id="notificationContent" name="notificationContent" class="input_large" style="height: 87px; width: 100%;"></textarea>
                                        <input type="hidden" id="selectedProjectId" name="selectedProjectId" value="">
                                    </p>
                                    <div>
                                        <div id="add_notification_result" style="float: left;"></div>
                                        <input type="submit" title="envoyer" value="Envoyer" id="addNotification" name="addNotification" class="btn-primary" style="float: right;">
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </form>
                 </td>
            </tr>
        </table>
    </div>
</div>

<?php if (isset($this->projectList)) : ?>
    <div id="project_list">
        <?php if (empty($this->projectList)) : ?>
            <h2>Aucun résultat</h2>
        <?php else : ?>
            <h2><?= count($this->projectList) ?> résultat<?= 1 === count($this->projectList) ? '' : 's' ?></h2>
            <table class="tablesorter table-clickable table-hover">
                <thead>
                <tr>
                    <th class="header">ID</th>
                    <th class="header">Raison sociale</th>
                    <th class="header">Statut</th>
                </tr>
                </thead>
                <tbody>
                <?php $i = 1; ?>
                <?php /** @var Projects $project */ ?>
                <?php foreach ($this->projectList as $project) : ?>
                    <tr <?= ($i % 2 === 1 ? '' : ' class="odd"') ?> data-project-id="<?= $project->getIdProject() ?>"  data-project-name="<?= htmlentities($project->getIdCompany()->getName()) ?>">
                        <td><a href="<?= $this->lurl ?>/dossiers/edit/<?= $project->getIdProject() ?>"><?= $project->getIdProject() ?></a></td>
                        <td><a href="<?= $this->lurl ?>/emprunteurs/edit/<?= $project->getIdCompany()->getIdClientOwner()->getIdClient() ?>"><?= $project->getIdCompany()->getName() ?></a></td>
                        <td><?= $this->projectStatusRepository->findOneBy(['status' => $project->getStatus()])->getLabel() ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
<?php endif; ?>
