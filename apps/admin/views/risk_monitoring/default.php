<style>
    .block {
        margin-bottom: 30px;
    }
    .block-header {
        padding-right: 15px;
        background: #b20066;
    }
    .block-header .table, .block-content .table {
        margin: 0;
    }
    .block-content {
        height: 300px;
        border-bottom: 1px solid #ddd;
        overflow-y: scroll;
        overflow-x: hidden;
    }
    .event-date {
        width: 69px;
    }
    .event-company {
         width: 205px;
     }
    .event-change {
        width: 195px;
        vertical-align: middle!important;
    }
    .event-action {
        width: 56px;
    }
    .event-company a {
        text-decoration: underline;
    }
    .event-action {
        text-align: right;
    }
    .event-action .btn-default {
        padding: 0;
        width: 22px;
    }
    .details {
        list-style: none;
        padding: 0; margin: 0;
    }
    .details li {
        display: inline-block;
        margin-right: 10px;
        color: #777;
    }
    .details li .label {
        color: #bdbdbd;
        font-size: 10px;
        text-transform: uppercase;
    }
    .details li .label:after {
        content: ': '
    }
    .text-success {
        color: #00a453;
    }
    .text-warning {
        color: #d19405;
    }
    .text-error {
        color: #a30a09;
    }
</style>

<div id="contenu">

    <div class="row">
        <div class="col-md-6">
            <h1>Monitoring</h1>
        </div>
    </div>

    <section id="monitoring-events">
        <div class="row">
            <div class="col-md-6">
                <h3>En traitement Commercial</h3>
                <article class="block">
                    <div class="block-header">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th class="event-date">Date</th>
                                    <th class="event-company">Raison Sociale</th>
                                    <th class="event-change">Changement</th>
                                    <th class="event-action">Projet</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                    <div class="block-content">
                        <table class="table table-striped table-events">
                            <tbody>
                                <tr class="event">
                                    <td class="event-date">4/07/17</td>
                                    <td class="event-company">
                                        <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                        <ul class="details">
                                            <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                            <li><span class="label">id</span><span class="value">2349238</span></li>
                                        </ul>
                                    </td>
                                    <td class="event-change"><span class="text-warning">Warning, this is getting risky</span></td>
                                    <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                                </tr>
                                <tr class="event">
                                    <td class="event-date">11/07/17</td>
                                    <td class="event-company">
                                        <a href="https://admin.local.unilend.fr/dossiers">Beatrix et Pascal</a>
                                        <ul class="details">
                                            <li><span class="label">siren</span><span class="value">2349238942</span></li>
                                            <li><span class="label">id</span><span class="value">2349233</span></li>
                                        </ul>
                                    </td>
                                    <td class="event-change"><span class="text-success">Some positive change</span></td>
                                    <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                                </tr>
                                <tr class="event">
                                    <td class="event-date">12/07/17</td>
                                    <td class="event-company">
                                        <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                        <ul class="details">
                                            <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                            <li><span class="label">id</span><span class="value">2349233</span></li>
                                        </ul>
                                    </td>
                                    <td class="event-change"><span class="text-error">Oh boy, too late now</span></td>
                                    <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                                </tr>
                                <tr class="event">
                                    <td class="event-date">4/07/17</td>
                                    <td class="event-company">
                                        <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                        <ul class="details">
                                            <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                            <li><span class="label">id</span><span class="value">2349238</span></li>
                                        </ul>
                                    </td>
                                    <td class="event-change"><span class="text-warning">Warning, this is getting risky</span></td>
                                    <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                                </tr>
                                <tr class="event">
                                    <td class="event-date">11/07/17</td>
                                    <td class="event-company">
                                        <a href="https://admin.local.unilend.fr/dossiers">Beatrix et Pascal</a>
                                        <ul class="details">
                                            <li><span class="label">siren</span><span class="value">2349238942</span></li>
                                            <li><span class="label">id</span><span class="value">2349233</span></li>
                                        </ul>
                                    </td>
                                    <td class="event-change"><span class="text-success">Some positive change</span></td>
                                    <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                                </tr>
                                <tr class="event">
                                    <td class="event-date">12/07/17</td>
                                    <td class="event-company">
                                        <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                        <ul class="details">
                                            <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                            <li><span class="label">id</span><span class="value">2349233</span></li>
                                        </ul>
                                    </td>
                                    <td class="event-change"><span class="text-error">Oh boy, too late now</span></td>
                                    <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                                </tr>
                                <tr class="event">
                                    <td class="event-date">4/07/17</td>
                                    <td class="event-company">
                                        <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                        <ul class="details">
                                            <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                            <li><span class="label">id</span><span class="value">2349238</span></li>
                                        </ul>
                                    </td>
                                    <td class="event-change"><span class="text-warning">Warning, this is getting risky</span></td>
                                    <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                                </tr>
                                <tr class="event">
                                    <td class="event-date">11/07/17</td>
                                    <td class="event-company">
                                        <a href="https://admin.local.unilend.fr/dossiers">Beatrix et Pascal</a>
                                        <ul class="details">
                                            <li><span class="label">siren</span><span class="value">2349238942</span></li>
                                            <li><span class="label">id</span><span class="value">2349233</span></li>
                                        </ul>
                                    </td>
                                    <td class="event-change"><span class="text-success">Some positive change</span></td>
                                    <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                                </tr>
                                <tr class="event">
                                    <td class="event-date">12/07/17</td>
                                    <td class="event-company">
                                        <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                        <ul class="details">
                                            <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                            <li><span class="label">id</span><span class="value">2349233</span></li>
                                        </ul>
                                    </td>
                                    <td class="event-change"><span class="text-error">Oh boy, too late now</span></td>
                                    <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                                </tr>
                                <tr class="event">
                                    <td class="event-date">4/07/17</td>
                                    <td class="event-company">
                                        <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                        <ul class="details">
                                            <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                            <li><span class="label">id</span><span class="value">2349238</span></li>
                                        </ul>
                                    </td>
                                    <td class="event-change"><span class="text-warning">Warning, this is getting risky</span></td>
                                    <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                                </tr>
                                <tr class="event">
                                    <td class="event-date">11/07/17</td>
                                    <td class="event-company">
                                        <a href="https://admin.local.unilend.fr/dossiers">Beatrix et Pascal</a>
                                        <ul class="details">
                                            <li><span class="label">siren</span><span class="value">2349238942</span></li>
                                            <li><span class="label">id</span><span class="value">2349233</span></li>
                                        </ul>
                                    </td>
                                    <td class="event-change"><span class="text-success">Some positive change</span></td>
                                    <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                                </tr>
                                <tr class="event">
                                    <td class="event-date">12/07/17</td>
                                    <td class="event-company">
                                        <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                        <ul class="details">
                                            <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                            <li><span class="label">id</span><span class="value">2349233</span></li>
                                        </ul>
                                    </td>
                                    <td class="event-change"><span class="text-error">Oh boy, too late now</span></td>
                                    <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </article>
            </div>
            <div class="col-md-6">
                <h3>En attente de traitement Commercial</h3>
                <article class="block">
                    <div class="block-header">
                        <table class="table">
                            <thead>
                            <tr>
                                <th class="event-date">Date</th>
                                <th class="event-company">Raison Sociale</th>
                                <th class="event-change">Changement</th>
                                <th class="event-action">Projet</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                    <div class="block-content">
                        <table class="table table-striped table-events">
                            <tbody>
                            <tr class="event">
                                <td class="event-date">4/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                        <li><span class="label">id</span><span class="value">2349238</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-warning">Warning, this is getting risky</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">11/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Beatrix et Pascal</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2349238942</span></li>
                                        <li><span class="label">id</span><span class="value">2349233</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-success">Some positive change</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">12/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                        <li><span class="label">id</span><span class="value">2349233</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-error">Oh boy, too late now</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">4/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                        <li><span class="label">id</span><span class="value">2349238</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-warning">Warning, this is getting risky</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">11/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Beatrix et Pascal</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2349238942</span></li>
                                        <li><span class="label">id</span><span class="value">2349233</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-success">Some positive change</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">12/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                        <li><span class="label">id</span><span class="value">2349233</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-error">Oh boy, too late now</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">4/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                        <li><span class="label">id</span><span class="value">2349238</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-warning">Warning, this is getting risky</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">11/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Beatrix et Pascal</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2349238942</span></li>
                                        <li><span class="label">id</span><span class="value">2349233</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-success">Some positive change</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">12/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                        <li><span class="label">id</span><span class="value">2349233</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-error">Oh boy, too late now</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">4/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                        <li><span class="label">id</span><span class="value">2349238</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-warning">Warning, this is getting risky</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">11/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Beatrix et Pascal</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2349238942</span></li>
                                        <li><span class="label">id</span><span class="value">2349233</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-success">Some positive change</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">12/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                        <li><span class="label">id</span><span class="value">2349233</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-error">Oh boy, too late now</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </article>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <h3>En traitement Risque</h3>
                <article class="block">
                    <div class="block-header">
                        <table class="table">
                            <thead>
                            <tr>
                                <th class="event-date">Date</th>
                                <th class="event-company">Raison Sociale</th>
                                <th class="event-change">Changement</th>
                                <th class="event-action">Projet</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                    <div class="block-content">
                        <table class="table table-striped table-events">
                            <tbody>
                            <tr class="event">
                                <td class="event-date">4/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                        <li><span class="label">id</span><span class="value">2349238</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-warning">Warning, this is getting risky</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">11/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Beatrix et Pascal</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2349238942</span></li>
                                        <li><span class="label">id</span><span class="value">2349233</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-success">Some positive change</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">12/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                        <li><span class="label">id</span><span class="value">2349233</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-error">Oh boy, too late now</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">4/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                        <li><span class="label">id</span><span class="value">2349238</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-warning">Warning, this is getting risky</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">11/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Beatrix et Pascal</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2349238942</span></li>
                                        <li><span class="label">id</span><span class="value">2349233</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-success">Some positive change</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">12/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                        <li><span class="label">id</span><span class="value">2349233</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-error">Oh boy, too late now</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">4/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                        <li><span class="label">id</span><span class="value">2349238</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-warning">Warning, this is getting risky</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">11/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Beatrix et Pascal</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2349238942</span></li>
                                        <li><span class="label">id</span><span class="value">2349233</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-success">Some positive change</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">12/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                        <li><span class="label">id</span><span class="value">2349233</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-error">Oh boy, too late now</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">4/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                        <li><span class="label">id</span><span class="value">2349238</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-warning">Warning, this is getting risky</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">11/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Beatrix et Pascal</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2349238942</span></li>
                                        <li><span class="label">id</span><span class="value">2349233</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-success">Some positive change</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">12/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                        <li><span class="label">id</span><span class="value">2349233</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-error">Oh boy, too late now</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </article>
            </div>
            <div class="col-md-6">
                <h3>En remboursement</h3>
                <article class="block">
                    <div class="block-header">
                        <table class="table">
                            <thead>
                            <tr>
                                <th class="event-date">Date</th>
                                <th class="event-company">Raison Sociale</th>
                                <th class="event-change">Changement</th>
                                <th class="event-action">Projet</th>
                            </tr>
                            </thead>
                        </table>
                    </div>
                    <div class="block-content">
                        <table class="table table-striped table-events">
                            <tbody>
                            <tr class="event">
                                <td class="event-date">4/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                        <li><span class="label">id</span><span class="value">2349238</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-warning">Warning, this is getting risky</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">11/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Beatrix et Pascal</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2349238942</span></li>
                                        <li><span class="label">id</span><span class="value">2349233</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-success">Some positive change</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">12/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                        <li><span class="label">id</span><span class="value">2349233</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-error">Oh boy, too late now</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">4/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                        <li><span class="label">id</span><span class="value">2349238</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-warning">Warning, this is getting risky</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">11/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Beatrix et Pascal</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2349238942</span></li>
                                        <li><span class="label">id</span><span class="value">2349233</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-success">Some positive change</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">12/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                        <li><span class="label">id</span><span class="value">2349233</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-error">Oh boy, too late now</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">4/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                        <li><span class="label">id</span><span class="value">2349238</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-warning">Warning, this is getting risky</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">11/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Beatrix et Pascal</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2349238942</span></li>
                                        <li><span class="label">id</span><span class="value">2349233</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-success">Some positive change</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">12/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                        <li><span class="label">id</span><span class="value">2349233</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-error">Oh boy, too late now</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">4/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                        <li><span class="label">id</span><span class="value">2349238</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-warning">Warning, this is getting risky</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">11/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Beatrix et Pascal</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2349238942</span></li>
                                        <li><span class="label">id</span><span class="value">2349233</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-success">Some positive change</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            <tr class="event">
                                <td class="event-date">12/07/17</td>
                                <td class="event-company">
                                    <a href="https://admin.local.unilend.fr/dossiers">Pascal et Beatrix</a>
                                    <ul class="details">
                                        <li><span class="label">siren</span><span class="value">2832983928</span></li>
                                        <li><span class="label">id</span><span class="value">2349233</span></li>
                                    </ul>
                                </td>
                                <td class="event-change"><span class="text-error">Oh boy, too late now</span></td>
                                <td class="event-action"><a href="/dossiers/edit/82282" class="btn-default"><span>></span></a></td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </article>
            </div>
        </div>
    </section>
</div>



<?php if (count($this->saleTeamEvents) > 0) : ?>
    Projects en traitement Commercial
    <?php foreach ($this->saleTeamEvents as $event) : ?>
        <?php var_dump($event); ?>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (count($this->upcomingSaleTeamEvents) > 0) : ?>
   
    <?php foreach ($this->upcomingSaleTeamEvents as $event) : ?>
        <?php var_dump($event); ?>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (count($this->riskTeamEvents) > 0) : ?>
    Projets en traitement Risque
    <?php foreach ($this->riskTeamEvents as $event) : ?>
        <?php var_dump($event); ?>
    <?php endforeach; ?>
<?php endif; ?>
<?php if (count($this->runningRepayment) > 0) : ?>

    Projects en cours de remboursement
    <?php foreach ($this->runningRepayment as $event) : ?>
        <?php var_dump($event); ?>
    <?php endforeach; ?>
<?php endif; ?>
