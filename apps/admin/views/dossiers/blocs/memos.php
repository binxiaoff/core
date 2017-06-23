<a class="tab_title" id="section-memos" href="#section-memos">Mémos</a>
<style>
    #content_memo {
        display: none;
    }
    .content-memo a {
        text-decoration: underline;
    }
</style>
<script>
    $(function() {
        var $memoContainer = $('#text_memo')
        var $memo = $memoContainer.find('#content_memo')

        var initMemo = function(comment) {
            $memoContainer.slideDown(300, function(){
                CKEDITOR.replace('content_memo', {
                    height: 170,
                    width: '100%',
                    toolbar: 'Basic',
                    removePlugins: 'elementspath',
                    resize_enabled: false
                })

                if (comment !== '') {
                    CKEDITOR.instances['content_memo'].setData(comment)
                }
            })
        }
        var destroyMemo = function() {
            if (CKEDITOR.instances['content_memo']) {
                CKEDITOR.instances['content_memo'].destroy(true)
                $memo.val('')
                $memoContainer.attr('data-comment-id', '')
            }
        }
        var edit = function(comment, commentId, public) {
            $memoContainer.attr('data-comment-id', commentId)
            var $checks =  $memoContainer.find('[name="public_memo"]')
            if (public === true) {
                $checks.each(function(){
                    if ($(this).val() === '1') {
                        $(this).attr('checked', true).prop('checked', true)
                    }
                })
            } else {
                $checks.each(function(){
                    if ($(this).val() === '0') {
                        $(this).attr('checked', true).prop('checked', true)
                    }
                })
            }
            $memo.val(comment)
            initMemo(comment)
        }
        var editMemo = function(comment, commentId, public) {
            destroyMemo()
            if ($memoContainer.is(':visible')) {
                $memoContainer.slideUp(300, function(){
                    edit(comment, commentId, public)
                })
            } else {
                edit(comment, commentId, public)
            }
        }
        var cancelMemo = function(){
            $memoContainer.slideUp(300, function() {
                destroyMemo()
            })
        }
        var submitMemo = function(projectId, commentId) {
            $.ajax({
                url: add_url + '/dossiers/memo',
                method: 'POST',
                dataType: 'html',
                data: {
                    projectId: projectId,
                    commentId: commentId,
                    content: CKEDITOR.instances['content_memo'].getData(),
                    public: $('[name="public_memo"]:checked').val()
                },
                success: function(response) {
                    $('#table_memo').html(response)
                    $memoContainer.slideUp(300, function() {
                        destroyMemo()
                    })
                }
            });
        }
        var deleteMemo = function(projectId, commentId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer le mémo ?')) {
                var memoRows = $('#table_memo .tablesorter tbody tr'),
                    targetedMemoRow = event.target

                $.ajax({
                    url: add_url + '/dossiers/memo/' + projectId + '/' + commentId,
                    method: 'DELETE',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success != undefined && response.success) {
                            if (memoRows.length == 1) {
                                $('#table_memo *').remove()
                            } else {
                                $(targetedMemoRow).closest('tr').remove()
                            }
                        } else {
                            if (response.error != undefined && response.error) {
                                if (response.message != undefined) {
                                    alert(response.message)
                                } else {
                                    alert('Erreur inconnue')
                                }
                            }
                        }
                    }
                })
            }
        }

        $(document).on('click', '#btn_editor', function() {
            initMemo()
        })
        $(document).on('click', '#cancel_memo', function() {
            cancelMemo()
        })
        $(document).on('click', '#submit_memo', function() {
            submitMemo($memoContainer.data('project-id'), $memoContainer.data('comment-id'))
        })
        $(document).on('click', '.btn-edit-memo, .btn-delete-memo', function() {
            var $tr = $(this).closest('tr')
            var projectId = $tr.data('project-id')
            var commentId = $tr.data('comment-id')
            var public = $tr.data('public')
            if ($(this).is('.btn-edit-memo')) {
                var comment = $tr.find('.content-memo').html()
                editMemo(comment, commentId, public);
            } else {
                deleteMemo(projectId, commentId)
            }
        })
    })
</script>
<div class="tab_content expand" id="tab_email">
    <div class="btnDroite">
        <a role="button" id="btn_editor" class="btn btn_link">Ajouter un mémo</a>
    </div>

    <br>
    <div id="text_memo" style="display: none;" data-project-id="<?= $this->params['0'] ?>" data-comment-id="">
        <textarea name="content" id="content_memo" class="textarea memo" style="visibility: hidden"></textarea>
        <br>
        <div class="row">
            <div class="col-md-12 text-right">
                <label style="margin-right: 20px"> <input type="radio" name="public_memo" value="0" checked> Privé</label>
                <label style="margin-right: 20px"><input type="radio" name="public_memo" value="1"> Public</label>
                <a id="cancel_memo" class="btn_link">Annuler</a>
                <a id="submit_memo" class="btn_link">Valider</a>
            </div>
        </div>
        <br>
    </div>
    <div id="table_memo">
        <?php $this->fireView('memo/list'); ?>
    </div>
</div>
