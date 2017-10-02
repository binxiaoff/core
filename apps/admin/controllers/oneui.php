<?php

class oneuiController extends bootstrap
{
    public function initialize()
    {
        parent::initialize();

        $this->users->checkAccess();

        $this->menu_admin = 'oneui';
    }

    public function _default()
    {
        $this->render();
    }

    // For demo purposes only
    public function _editable()
    {
        $this->autoFireView = false;
        $this->hideDecoration();

        // If the request value is numeric (e.g. 1000),
        // but we want to keep the formatting
        if ($this->request->isXmlHttpRequest()) {
            $value = $this->request->request->get('value');
            echo json_encode([
                'success' => true,
                'error'   => ['Error 1', 'Error 2'],
                'newValue' => $value . ',00 €'
            ]);
        }
    }

    // For demo purposes only
    public function _editor_table()
    {
        $this->autoFireView = false;
        $this->hideDecoration();

        // Request - Action and ID
        $action = $_POST['action'];
        $id = '';
        $state = '';
        $responseData = [];

        // Add New or Modify
        if ($action === 'create' || $action === 'modify') {
            if ($action === 'create') {
                $id = '4382728'; // Generate new ID
            } elseif ($action === 'modify') {
                $id = $_POST['id']; // Existing ID
            }
            // Data
            $name = $_POST['name']; // Andrew Williams
            $email = $_POST['email']; // client7@example.com
            $position = $_POST['position']; // Director
            $gender = $_POST['gender']; // Male, Female
            $skills = $_POST['skills']; // PHP, Javascript, CSS
            $location = $_POST['location']; // Paris
            $date = $_POST['date']; // 12/02/1983
            if (isset($_FILES['file'])) {
                // Newly uploaded file
                $file = $_FILES['file'];
                $url = '/upload/dir/newFile.jpg';
                $uploadedFile = '<a href="' . $url . '">' . $file['name'] . '</a>';
            } else {
                // No change to file (already exists)
                $url = '/upload/dir/existingFile.jpg';
                $filename = 'Existing file';
                $uploadedFile = '<a href="' . $url . '">' . $filename . '</a>';
            }
            $amount = $_POST['amount'] . ',00  €'; // - Currency formatting 4 000,00 €

            // Response
            $responseData = [$name, $email, $position, $gender, $skills, $location, $date, $uploadedFile, $amount];
        }
        // Toggle
        if ($action === 'activate' || $action === 'deactivate') {
            $id = $_POST['id'];
            if ($action === 'activate') {
                $responseData = 'active';
            }
            if ($action === 'deactivate') {
                $responseData = 'inactive';
            }
        }
        // Delete
        if ($action === 'delete') {
            $id = $_POST['id'];
            $responseData = 'delete';
        }

        // Response - JSON
        if ($this->request->isXmlHttpRequest()) {
            echo json_encode([
                'success' => true, // If false, then errors must be present (line below)
                'error' => ['Error 1', 'Error 2'], // Errors must be an array, even if there's only one
                'id' => $id, // ID must be separate from the data in the response
                'state' => $state, // State must be separate from the data in the response
                'data' => $responseData // Values must be in the same order as in the request
            ]);
        }
    }
}
