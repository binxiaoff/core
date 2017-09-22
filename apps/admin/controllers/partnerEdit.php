<?php

class partnerEditController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess();

        $this->menu_admin = 'partnerEdit';
    }

    public function _default()
    {
        $this->render();
    }

    public function _organisation() {
        $this->autoFireView = false;
        $this->hideDecoration();

        // Structure
        $structure = $_POST['structure'];

        // New items can be an array or single element
        if (isset($_POST['newItems'])){
            if (is_array($_POST['newItems']))
                $newItems = implode(", ", noescape($_POST['newItems']));
            else
                $newItems = $_POST['newItems'];
        }
        // header("Location: /partnerEdit");
        // die;
    }

    public function _documents()
    {
        $this->autoFireView = false;
        $this->hideDecoration();

        // Request - Action and ID
        $action = $_POST['action'];
        $id = '';
        $responseData = [];

        // Add New or Modify
        if ($action === 'create' || $action === 'modify') {

            if ($action === 'create') {
                $id = '4382728b'; // Generate new ID
            } else if ($action === 'modify') {
                $id = $_POST['id']; // Existing ID
            }

            // Data
            $doc = $_POST['type'];
            $mandatory = $_POST['mandatory'];
            $date = $_POST['date'];
            if (isset($_FILES['file'])) {
                // Newly uploaded file
                $file = $_FILES['file'];
                $url = '/upload/dir/newFile.jpg';
                $uploadedFile = '<a href="' . $url . '">' . $file['name'] . '</a>';
            } else {
                // No change to file (already exists)
                $url = '/upload/dir/existingFile.jpg';
                $name = 'Existing file';
                $uploadedFile = '<a href="' . $url . '">' . $name . '</a>';
            }
            $responseData = [$doc, $mandatory, $date, $uploadedFile];
        }
        // Delete
        if ($action === 'delete') {
            $id = $_POST['id'];
            $responseData = 'delete';
        }

        // Response - JSON
        if ($this->request->isXmlHttpRequest()) {
            echo json_encode([
                'success' => true,
                'error' => ['Error 1', 'Error 2'],
                'id' => $id,
                'data' => $responseData
            ]);
        }
    }

    public function _users()
    {
        $this->autoFireView = false;
        $this->hideDecoration();

        // Request - Action and ID
        $action = $_POST['action'];
        $id = '';
        $responseData = [];

        // Add New or Modify
        if ($action === 'create' || $action === 'modify') {

            if ($action === 'create') {
                $id = '4382728'; // Generate new ID
            } else if ($action === 'modify') {
                $id = $_POST['id']; // Existing ID
            }

            // Data
            $doc = $_POST['type'];
            $mandatory = $_POST['mandatory'];
            $date = $_POST['date'];
            if (isset($_FILES['file'])) {
                // Newly uploaded file
                $file = $_FILES['file'];
                $url = '/upload/dir/newFile.jpg';
                $uploadedFile = '<a href="' . $url . '">' . $file['name'] . '</a>';
            } else {
                // No change to file (already exists), just return the same url
                $url = '/upload/dir/existingFile.jpg';
                $name = 'Existing file';
                $uploadedFile = '<a href="' . $url . '">' . $name . '</a>';
            }
            $responseData = [$doc, $mandatory, $date, $uploadedFile];
        }
        // Delete
        if ($action === 'delete') {
            $id = $_POST['id'];
            $responseData = 'delete';
        }

        // Response - JSON
        if ($this->request->isXmlHttpRequest()) {
            echo json_encode([
                'success' => true,
                'error' => ['Error 1', 'Error 2'],
                'id' => $id,
                'data' => $responseData
            ]);
        }
    }
}