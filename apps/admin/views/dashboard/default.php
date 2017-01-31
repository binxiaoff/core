<script>
    $(function() {
        $('[data-toggle="tooltip"]').tooltip({
            // For line breaks
            content: function() {
                return $(this).attr('title');
            }
        })

        $('[data-project]').click(function() {
            var projectId = $(this).data('project')
            if (projectId) {
                $(this).children().css('background-color', '#b20066')
                $(this).children().css('color', '#fff')
                $(location).attr('href', '<?= $this->lurl ?>/dossiers/edit/' + projectId)
            }
        })

        $('body').on('click', '.status-line', function() {
            $(this).toggleClass('expand').nextUntil('.status-line').slideToggle(0)
        })

        $('#sales-projects-selector').change(function() {
            var userId = $(this).val()

            $.ajax({
                method: 'POST',
                url: '<?= $this->lurl ?>/dashboard/salesperson_projects',
                dataType: 'json',
                data: {
                    userId: userId
                }
            }).done(function(response) {
                $('#team-projects-count').text(response.count)
                $('#team-projects').html(response.projects)
            })
        })
    })
</script>
<style>
    table.tablesorter thead tr th {
        font-size: 12px;
    }
    .projects td {
        cursor: pointer;
    }
    .projects .status-line td {
        background-color: #6d1f4f;
        color: #fff;
        font-size: 13px;
    }
    .status-line .sign:after{
        content: '+';
        display: inline-block;
    }
    .status-line.expand .sign:after{
        content: '-';
    }
    .warning {
        background-color: #ffe0f0 !important;
    }
    #user-projects {
        margin-bottom: 25px;
    }
</style>
<div id="contenu">
    <h1>Mes projets (<?= $this->userProjects['count'] ?>)</h1>
    <div id="user-projects">
        <?php $this->templateProjects = $this->userProjects; ?>
        <?php $this->fireView($this->template . 'Projects'); ?>
    </div>
    <h1>
        Mon équipe (<span id="team-projects-count"><?= $this->teamProjects['count'] ?></span>)
        <?php if ('sale' === $this->template) : ?>
            <select id="sales-projects-selector" style="margin-left: 10px">
                <option value=""></option>
                <?php foreach ($this->salesPeople as $salesperson) : ?>
                    <option value="<?= $salesperson['id_user'] ?>"><?= $salesperson['firstname'] ?> <?= $salesperson['name'] ?></option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>
    </h1>
    <div id="team-projects">
        <?php $this->templateProjects = $this->teamProjects; ?>
        <?php $this->fireView($this->template . 'Projects'); ?>
    </div>
</div>
