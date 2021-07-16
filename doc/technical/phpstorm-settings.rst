`Return to index <../index.rst>`_

.. _phpstorm-settings:
======================
PhpStorm Settings
======================

To ensure that all the members in the team have the same PhpStorm settings (especially for the code styling), we use the PhpStorm `Settings Repository <https://www.jetbrains.com/help/idea/sharing-your-ide-settings.html#settings-repository>`_ to synchronise the settings.

Configure the Settings repository
=================================
1. Open Preferences dialog (``⌘,``)
#. Go to Tools > Settings Repository
#. Add to "Read-only Sources" the git repository: ``git@gitlab.com:ca-lending-services/phpstorm-settings.git``

Add French dictionary for spellcheck
====================================
1. Open Preferences dialog (``⌘,``)
#. Go to Editor > Spelling
#. Download the `dictionary file <https://intellij-support.jetbrains.com/hc/en-us/community/posts/206844865-Spelling-Use-a-French-dictionary>`_
#. Add it to "Custom Dictionaries"

Setup unique line at end of file
================================
1. Open Preferences dialog (``⌘,``)
#. Go to Editor > General and the "On save" section
#. Check the option to always add a new line at the end of the file
#. Check the option to remove multiple empty lines at the end of the file
