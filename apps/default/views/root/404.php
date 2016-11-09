<!DOCTYPE html>
<html>
<head>
    <title>Unilend</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.2.1/jquery.min.js"></script>
    <script type="text/javascript">
        jQuery(document).ready(function ($) {
            $.ajax({
                url: '<?=$this->url?>/erreur404',
                dataType: 'html',
                success: function (data) {
                    document.open('text/html', '')
                    document.write(data)
                }
            })
        })
    </script>
</head>
<body></body>
</html>