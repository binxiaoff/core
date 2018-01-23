<style type="text/css">
    .tab_title {
        display: block;
        background-color: #b20066;
        color: #fff;
        font-size: 16px;
        font-weight: bold;
        text-align: center;
        text-decoration: none;
        padding: 5px;
        margin-top: 15px;
    }
    .tab_title:active,
    .tab_title:focus,
    .tab_title:hover,
    .tab_title:visited {
        color: #fff;
        text-decoration: none;
    }
    .tab_content {border: 2px solid #b20066; padding: 10px;}
    .valid_etape {display: none; text-align: center; font-size: 16px; font-weight: bold; color: #009933;}
</style>

<div id="contenu">
    <h1>Création projet</h1>
    <?php if (false === empty($this->projects->id_project)) : ?>
        <?php $this->fireView('blocs/etape1'); ?>
    <?php else : ?>
        <div class="row">
            <div class="col-md-6">
                <h2>Emprunteur existant</h2>
                <form id="borrower-search-form" action="#" method="post">
                    <div class="form-group">
                        <label for="search">Prénom / nom / SIREN</label>
                        <input type="text" id="search" name="search" class="form-control">
                    </div>
                    <button type="submit" class="btn-primary">Chercher un emprunteur existant</button>
                </form>
            </div>
            <div class="col-md-6">
                <h2>Nouvel emprunteur</h2>
                <a href="<?= $this->lurl ?>/dossiers/add/nouveau" class="btn-primary">Créer un projet avec un nouvel emprunteur</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    $(function () {
        $('#borrower-search-form').on('submit', function (event) {
            event.preventDefault()
            $.colorbox({href: '<?= $this->lurl ?>/dossiers/changeClient/' + encodeURI($('#search').val())})
        })
    })
</script>
