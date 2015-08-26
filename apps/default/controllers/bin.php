<?php
class binController extends bootstrap
{
    function binController($command, $config)
    {
        parent::__construct($command, $config, 'default');

        $this->autoFireHeader = false;
        $this->autoFireHead = false;
        $this->autoFireFooter = false;
        $this->autoFireView = false;
        $this->autoFireDebug = false;

        // Securisation des acces
        if (isset($_SERVER['REMOTE_ADDR']) && !in_array($_SERVER['REMOTE_ADDR'], $this->Config['ip_admin'][$this->Config['env']])) {
            die;
        }

    }

    function _default()
    {
        die;
    }

    /**
     * Migration of attachment of lenders accounts
     */
    public function _migrateLenderAttachment()
    {
        echo 'Start the data migration for lenders\' attachment...' . PHP_EOL;

        $this->attachment  = $this->loadData('attachment');
        $this->attachment_type = $this->loadData('attachment_type');
        $this->lenders_accounts = $this->loadData('lenders_accounts');
        $lendersAccountsTotal = $this->lenders_accounts->counter();

        $treated = 0;
        $start = 0;
        $limit = 100;
        while(true)
        {
            $lenders = $this->lenders_accounts->select('', '', $start, $limit);
            $start += $limit;

            if(empty($lenders)) {
                break;
            }

            foreach($lenders as $lender)
            {
                $ownerId = $lender['id_lender_account'];
                $ownerType = attachment::LENDER;
                $added = $lender['added'];
                if('' !== $lender['fichier_cni_passeport']) {
                    $this->saveAttachment(attachment_type::CNI_PASSPORTE, $lender['fichier_cni_passeport'], $ownerId, $ownerType, $added, $this->attachment);
                }

                if('' !== $lender['fichier_justificatif_domicile']) {
                    $this->saveAttachment(attachment_type::JUSTIFICATIF_DOMICILE, $lender['fichier_justificatif_domicile'], $ownerId, $ownerType, $added, $this->attachment);
                }

                if('' !== $lender['fichier_rib']) {
                    $this->saveAttachment(attachment_type::RIB, $lender['fichier_rib'], $ownerId, $ownerType, $added, $this->attachment);
                }

                if('' !== $lender['fichier_cni_passeport_dirigent']) {
                    $this->saveAttachment(attachment_type::CNI_PASSPORTE_DIRIGEANT, $lender['fichier_cni_passeport_dirigent'], $ownerId, $ownerType, $added, $this->attachment);
                }

                if('' !== $lender['fichier_extrait_kbis']) {
                    $this->saveAttachment(attachment_type::KBIS, $lender['fichier_extrait_kbis'], $ownerId, $ownerType, $added, $this->attachment);
                }

                if('' !== $lender['fichier_delegation_pouvoir']) {
                    $this->saveAttachment(attachment_type::DELEGATION_POUVOIR, $lender['fichier_delegation_pouvoir'], $ownerId, $ownerType, $added, $this->attachment);
                }

                if('' !== $lender['fichier_statuts']) {
                    $this->saveAttachment(attachment_type::STATUTS, $lender['fichier_statuts'], $ownerId, $ownerType, $added, $this->attachment);
                }

                if('' !== $lender['fichier_autre']) {
                    $this->saveAttachment(attachment_type::CNI_PASSPORTE_VERSO, $lender['fichier_autre'], $ownerId, $ownerType, $added, $this->attachment);
                }

                if('' !== $lender['fichier_document_fiscal']) {
                    $this->saveAttachment(attachment_type::JUSTIFICATIF_FISCAL, $lender['fichier_document_fiscal'], $ownerId, $ownerType, $added, $this->attachment);
                }
                $treated ++;

                echo 'The attachments of lender account id : '.$ownerId.' has been migrated. Treated : '. $treated . '/' . $lendersAccountsTotal . PHP_EOL;
            }

        }
    }

    /**
     * @param integer $attachmentType
     * @param string $path
     * @param integer $ownerId
     * @param integer $ownerType
     * @param string $added
     * @param attachment $attachment
     * @return mixed
     */
    private function saveAttachment($attachmentType, $path, $ownerId, $ownerType, $added, $attachment)
    {
        $attachment->id_type = $attachmentType;
        $attachment->id_owner = $ownerId;
        $attachment->type_owner = $ownerType;
        $attachment->path = $path;
        $attachment->archived = null;
        $attachment->added = $added;

        return $attachment->save();
    }
}