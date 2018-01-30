$(function(){
    var dt = $('#projects-to-decline').DataTable({
        ajax: '/projets/projets_a_dechoir/',
        language: {
            url: '<?= $this->lurl ?>/oneui/js/plugins/datatables/localisation/fr_FR.json'
        },
        columnDefs: [
            {visible: false, targets: [5]}
        ],
        createdRow: function (row, data, index) {
            var $row = $(row)
            $row.find('td:first-child').html(getProjectUrl(data[5], data[0]))
            var $otherProjectsColumn = $row.find('td:last-child')
            var projectList = ''
            $.each(data[4], function (key, projectId) {
                projectList += getProjectUrl(projectId)
                if (key < data[4].length - 1 ) {
                    projectList += ', '
                }
            })
            $otherProjectsColumn.html(projectList)
        }
    })
    function getProjectUrl(projectId, projectTitle) {
        var linkText = projectTitle !== undefined ? projectTitle : projectId
        return '<a href="/dossiers/edit/' + projectId + '">' + linkText + '</a>'
    }
    $.fn.dataTable.ext.errMode = 'none';
    dt.on('error.dt', function (e, settings, techNote, message) {
        console.log(message)
    })
})