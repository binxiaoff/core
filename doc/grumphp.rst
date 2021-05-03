=======
GrumPHP
=======

What's GrumPHP
==============
See official documentation: https://github.com/phpro/grumphp


How to enable or disable GrumPHP
=================================

Since our code runs in Docker and committing on the host using PHPStorm, we need to strictly manipule GrumPHP in the host machine.

The following command should be launched in **HOST machine**.

Enable the GrumPHP:

.. code-block:: bash

 php ./vendor/bin/grumphp git:init

Disable the GrumPHP:

.. code-block:: bash

 php ./vendor/bin/grumphp git:deinit


How to trigger GrumPHP git commit hook within container
=======================================================

Since our code runs with php from Docker container, our **HOST machine** should not have PHP in order to avoid issues with host and container php version possible differences.
All PHP command have to be done on the container only.

Retrieve Docker PHP API container name. For MacOS users, this can be done with Docker Desktop dashboard.

 .. image:: images/grumphp_docker-desktop.png
    :align: center
    :alt: PHP Docker container

.. code-block:: bash

 user@my_host_machine $ docker ps
 CONTAINER ID   IMAGE    COMMAND                  CREATED        STATUS      PORTS     NAMES
 4ca4759aaaff   api_php  "docker-php-entrypoi…"   30 hours ago   Up 8 hours  9000/tcp  api_php_1

Exec **git commit** commands from container to trigger pre-commit hook with container PHP version. Keep in mind that working directory inside the container is **/var/www**.
Git add command can be launched outside container on **host machine**.

Git shouldn't be on php container. In order to avoid launch git commit within the container, the pre-commit hook has to be updated to run grumphp within the php container.

If you want **git add** from your **host machine**, use the next docker exec command.

.. code-block:: bash

 relative path within the container ─────────────────────────┐
 container name ─────────────────────────────┐               │
                                             ↓               ↓
 user@my_host_machine $ docker exec -ti api_php_1 git add src/Core/Security/UserChecker.php

 user@my_host_machine $ docker exec -ti api_php_1 git commit -m "CALS-XXXX : bug fix"
  GrumPHP detected a pre-commit command.
  GrumPHP is sniffing your code!
  
  Running tasks with priority 0!
  ==============================
  Running task 1/4: git_blacklist... ✔
  Running task 2/4: phpcsfixer... ✔
  Running task 3/4: phpcs... ✔
  Running task 4/4: phpstan... ✔
  GrumPHP detected a commit-msg command.
  GrumPHP is sniffing your code!
               ▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄▄
             ▄▄▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▌
           ▄▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▌
          ▐▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▌
          ▐▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▌
    ▄▐▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▌
   ▐▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▌
   ▐█▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▌
     ▀█▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▌
       ▀▀▓▓▓▓▓▓▓▓▓▓▓▓█▀▀▀▀▀▀▀▀▀▀▀▀▀▀████████████▄
        ▄████████▀▀▀▀▀                 ██████████
       ███████▀                         ██████▀
        ▐████      ██▌          ██       ████▌
          ▐█▌                            ███
           █▌           ▄▄ ▄▄           ▐███
          ███       ▄▄▄▄▄▄▄▄▄▄▄▄       ▐███
           ██▄ ▐███████████████████████████
          █▀█████████▌▀▀▀▀▀▀▀▀▀██████████▌▐
            ███████████▄▄▄▄▄▄▄███████████▌
           ▐█████████████████████████████
            █████████████████████████████
             ██ █████████████████████▐██▀
              ▀ ▐███████████████████▌ ▐▀
                  ████▀████████▀▐███
                   ▀█▌  ▐█████  ▐█▌
                          ██▀   ▐▀
         _    _ _                         _ _
        / \  | | |   __ _  ___   ___   __| | |
       / _ \ | | |  / _` |/ _ \ / _ \ / _` | |
      / ___ \| | | | (_| | (_) | (_) | (_| |_|
     /_/   \_\_|_|  \__, |\___/ \___/ \__,_(_)
                  |___/

Nevertheless, git push has to be done on your **host machine** because docker container do not share host ssh keys.
