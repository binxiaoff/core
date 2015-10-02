<?php
class attachment_helper
{
    /**
     * @param integer $ownerId
     * @param string $ownerType
     * @param integer $attachmentType
     * @param string $field
     * @param string $path
     * @param string $uploadPath
     * @param upload $upload
     * @param attachment $attachment
     * @param string $sNewName
     * @param array $aFiles
     * @return bool|string
     */
    public function upload($ownerId, $ownerType, $attachmentType, $field, $path, $uploadPath, $upload, $attachment, $sNewName = '', $aFiles = null)
    {
        if (is_null($aFiles)) {
            $aFiles = $_FILES;
        }

        if (false === ($upload instanceof \upload)) {
            return false;
        }

        if (false === ($attachment instanceof \attachment)) {
            return false;
        }

        if (false === isset($aFiles[$field]) || $aFiles[$field]['name'] == '') {
            return ''; // the filed is empty, NOT an error
        }

        $upload->setUploadDir($path, $uploadPath);

        if (false === $upload->doUpload($field, $sNewName, $erase=false, $aFiles)) {
            return false;
        }

        //Supprimer l'ancien fichier
        $attachmentInfo = $attachment->select(
            'id_owner=' . $ownerId
            .' AND type_owner = "' . $ownerType .'"'
            .' AND id_type = ' . $attachmentType
        );

        if (false === empty($attachmentInfo) && $attachmentInfo[0]['path'] != '') {
            @unlink($path. $uploadPath . $attachmentInfo[0]['path']);
        }

        $attachment->id_type = $attachmentType;
        $attachment->id_owner = $ownerId;
        $attachment->type_owner = $ownerType;
        $attachment->path = $upload->getName();
        $attachment->archived = null;

        $attachment_id = $attachment->save();

        if (false === is_numeric($attachment_id)) {
            return false;
        }

        return $attachment_id;
    }
}
