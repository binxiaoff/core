<?php
ob_start();
foreach ($this->aRejectedBids as $aBid) :
echo
'<div class="row bid">
    <span class="' . ((false === empty($aBid['id_autobid']) && $this->bIsAllowedToSeeAutobid) ? 'autobid' : 'no_autobid') . '">A</span>
    <span class="amount">' . $this->ficelle->formatNumber($aBid['amount'] / 100, 0) . ' €</span>
    <span class="rate">' . $this->ficelle->formatNumber($aBid['rate'], 1) . ' %</span>
    <span class="circle_rejected"></span>
    <span class="rejected">' . $this->lng['preteur-synthese']['label-rejected-bid'] . '
        <a href="' . $this->furl . '/projects/detail/' . $this->oProject->slug . '">' . $this->lng['preteur-synthese']['label-new-offer'] . '</a>
    </span>
</div>';
endforeach;

$sHtmlBody = ob_get_contents();
ob_clean();
echo json_encode(array('html' => $sHtmlBody));
