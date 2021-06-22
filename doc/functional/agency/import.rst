===============
Project import
===============


The Symfony command ``kls:import:project`` imports project into the database.
It expects two arguments :
1. the shortCode or id of the agent company
2. a path (preferably absolute) to the XLSX file. This file must respect a template available `here <template.xlsx>`_:

Mass import
-----------

For mass import the following script is available:

.. code-block:: bash

    #!/usr/bin/env bash
    mkdir -p ../processed || exit
    for shortCode in *; do
      if [[ -d "$shortCode" ]]
      then
        # Create subshell to go back to root after processing folder
        (
          echo "Processing company $shortCode..."
          cd "$shortCode" || exit
          for import in *
          do
            if [[ -f $import ]] # Take only files
            then
              echo -n "  importing $import... "
              # launch import
              if /var/www/bin/console kls:agency:import "$shortCode" "$import"
              then
                echo "OK"
                mkdir -p "../../processed/$shortCode"
                mv "$import" "../../processed/$shortCode/$import"  # Move successful file
              else
                echo "NOK"
              fi
            fi
          done
        )
      fi
    done


It expects the following folder structure:

::

    . folder containing files to import
    ├── shortCode of company 1
    │   ├── project with company 1 as agent 1.xlsx
    │   ├── project with company 1 as agent 2.xlsx
    │   └── project with company 1 as agent 3.xlsx
    ├── shortCode of company 2
    │   ├── project with company 2 as agent 1.xlsx
    │   ├── project with company 2 as agent 2.xlsx
    │   └── project with company 2 as agent 3.xlsx
    └── script.sh

It will create a ``processed` folder with the same structure as a sibling of folder containing the files to import.
The script will move the files which have been successfully imported into the ``processed`` folder under their agent company folder.
The files which failed import will be kept in place and not moved.
Therefore, the user launching the command should be able to move files and create folders.