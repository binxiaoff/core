<script>
    $(function () {
        displayNotificationForm = function (projectId) {
            $('#selectedProjectId').val(projectId)
            $('#add_notifiaction').show();
        }

        $('#addNotification').click(function (event) {
            event.preventDefault();
            var form = $('#add_notification_form');
            $.ajax({
                method: form.attr('method'),
                url: form.attr('action'),
                data: form.serialize(),
                dataType: 'json'
            }).done(function (response) {
                console.log(response);
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
        <h2>Choisir un projet</h2>
        <form method="post" name="project_form" id="project_form" enctype="multipart/form-data" action="<?= $this->lurl ?>/preteurs/notifications" target="_parent">
            <fieldset name="project">
                <p><label for="projectId">ID projet</label></p>
                <input type="text" name="projectId" id="projectId" class="input_large">
                <br>
                <p><label for="projectTitle">Raison sociale</label></p>
                <input type="text" name="projectTitle" id="projectTitle" class="input_large">
            </fieldset>
            <br>
            <fieldset>
                <input type="submit" title="valider" value="Valider" id="searchProject" name="searchProject" class="btn">
            </fieldset>
        </form>
        <?php if (false === empty($this->projectList)) : ?>
            <br>
            <div id="project_list">
                <table class="tablesorter">
                    <thead>
                    <tr>
                        <th class="header">ID</th>
                        <th class="header">Raison sociale</th>
                        <th class="header">Statut</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $i = 1; ?>
                    <?php foreach ($this->projectList as $project) : ?>
                        <tr <?= ($i % 2 === 1 ? '' : ' class="odd"') ?> onclick="displayNotificationForm(<?= $project['id_project'] ?>)">
                            <td><?= $project['id_project'] ?></td>
                            <td><?= $project['name'] ?></td>
                            <td><?= $project['label'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <? endif; ?>
    </div>
    <br>
    <div id="add_notifiaction" style="display: none">
        <h2>Détails de la notification</h2>
        <form method="post" name="add_notification_form" id="add_notification_form" enctype="multipart/form-data" action="<?= $this->lurl ?>/preteurs/addNotification" target="_parent">
            <fieldset name="notifications">
                <p><label for="notificationDate">Date de la notification</label></p>
                <input type="text" id="notificationDate" class="input_large" name="notificationDate" value="">
                <br/>
                <p><label for="notificationSubject">Sujet</label></p>
                <input type="text" id="notificationSubject" class="input_large" name="notificationSubject" value="">
                <br>
                <p><label for="notificationContent">Contenu de la notification</label></p>
                <textarea id="notificationContent" name="notificationContent"></textarea>

                <input type="hidden" id="selectedProjectId" name="selectedProjectId" value="">
            </fieldset>
            <fieldset>
                <input type="submit" title="valider" value="Valider" id="addNotification" name="addNotification" class="btn">
            </fieldset>
        </form>
    </div>
    <br>
    <div id="add_notification_result">

    </div>
</div>
