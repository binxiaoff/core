<style>
    #preview-keywords-iframe-container > iframe {
        border: 1px solid #b1adb2;
        width: 100%;
    }
</style>
<script>
    $(function() {
        var $keywords = $('#preview-keywords')
        var $iframeContainer = $('#preview-keywords-iframe-container')

        $('#preview-button').on('click', function () {
            var matched
            var regex = /\[EMV DYN\]([^\[]*)\[EMV \/DYN\]/g
            var content = $('#content').val()
            var form = ''
            var keywords = {
                staticUrl: '<?= $this->surl ?>',
                frontUrl: '<?= $this->furl ?>',
                adminUrl: '<?= $this->url ?>',
                facebookLink: '<?= $this->facebookUrl ?>',
                twitterLink: '<?= $this->twitterUrl ?>',
                year: '<?= date('Y') ?>'
            }

            do {
                matched = regex.exec(content);
                if (matched && !(matched[1] in keywords)) {
                    keywords[matched[1]] = ''
                }
            } while (matched)

            for (keyword in keywords) {
                form +=
                    '<div class="form-group">' +
                    '<label for="keyword-' + keyword + '">' +
                    keyword +
                    '</label>' +
                    '<input type="text" id="keyword-' + keyword + '" name="keywords[' + keyword + ']" value="' + keywords[keyword] + '" data-keyword="' + keyword + '" class="form-control">' +
                    '</div>'
            }

            $keywords.html(form)
            $iframeContainer.html('')

            $.colorbox({
                inline: true,
                href: '#preview-content',
                width: '90%',
                height: '90%'
            })
        })

        $('#preview-keywords-form').on('submit', function (event) {
            event.preventDefault()

            var $form = $(this)

            $form.find('[name=title]').val($('#title').val())
            $form.find('[name=content]').val($('#content').val())
            $form.find('[name=header]').val($('#header-select').val())
            $form.find('[name=footer]').val($('#footer-select').val())

            $.ajax({
                url: $form.prop('action'),
                method: $form.prop('method').toUpperCase(),
                data: $form.serialize(),
                success: function (response) {
                    if (response.success && response.data && response.data.content) {
                        var $iframe = $('<iframe></iframe>')
                            .attr('src', 'data:text/html;charset=utf-8,' + encodeURI(response.data.content))
                            .height($('#cboxLoadedContent').height() - 45)

                        $iframeContainer.empty().append($iframe)
                    }
                }
            })
        })
    })
</script>
<div style="display: none;">
    <div id="preview-content">
        <a onclick="parent.$.fn.colorbox.close();" title="Fermer" class="closeBtn"><img src="<?= $this->surl ?>/images/admin/delete.png" alt="Fermer"></a>
        <div class="row" style="margin: 40px 15px 0 15px;">
            <form id="preview-keywords-form" method="post" action="<?= $this->url ?>/mails/preview" class="col-md-3">
                <div id="preview-keywords"></div>
                <div class="form-group">
                    <input type="hidden" name="title">
                    <input type="hidden" name="content">
                    <input type="hidden" name="header">
                    <input type="hidden" name="footer">
                    <button type="submit" class="form-control btn-default pull-right">Prévisualiser</button>
                </div>
            </form>
            <div id="preview-keywords-iframe-container" class="col-md-9"></div>
        </div>
    </div>
</div>
