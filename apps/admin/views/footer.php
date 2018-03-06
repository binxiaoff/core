    </div>
    <?php if ($this->getParameter('kernel.environment') != 'prod') : ?>
        <script type="text/javascript">
            jQuery.ajax(
                { url: "https://unilend.atlassian.net/s/4c6a101758c3c334ffe9cd010c34dc33-T/en_USrrn36f/71001/b6b48b2829824b869586ac216d119363/2.0.10/_/download/batch/com.atlassian.jira.collector.plugin.jira-issue-collector-plugin:issuecollector-embededjs/com.atlassian.jira.collector.plugin.jira-issue-collector-plugin:issuecollector-embededjs.js?locale=en-US&collectorId=f40c1120", type: "get", cache: true, dataType: "script" }
            );

            window.ATL_JQ_PAGE_PROPS = $.extend(window.ATL_JQ_PAGE_PROPS, {
                // ==== default field values ====
                fieldValues: {
                    email: '<?php echo $_SESSION['user']['email'] ?>',
                    fullname: '<?php echo $_SESSION['user']['firstname'] . ' ' . $_SESSION['user']['name'] ?>'
                }
            });
    </script>
    <?php endif; ?>
</body>
</html>
