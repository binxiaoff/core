===============
Initialise a development environment with Docker (DEV Local)
===============

Step 1: Install the pre-require tools
-------------------------------------
- Docker_ (if you are using MacOS you can download Edge_ for better performances)

Step 2: Clone the repositories
------------------------------
- `Create (if not yet) and add your SSH public key <https://docs.gitlab.com/ee/gitlab-basics/create-your-ssh-keys.html>`_

- Create a folder on your Mac for your projects. For example:

 .. code-block:: bash

  mkdir ~/Projects

- Go to the folder that you created and clone the infra project

 .. code-block:: bash

  git clone git@gitlab.com:ca-lending-services/docker.git
  cd docker
  git checkout develop

- Follow the documentation from the Repository_

Step3: Enable GrumPHP
---------------------
After the code has been deployed to your ``local`` environment, you need to active GrumPHP (you'll have to run the commands from the container using ``docker-compose exec php <your command>``): `How to enable or disable GrumPHP <tools/grumphp.rst>`_

That's it. Now you have your development environment.

.. _Docker: https://www.docker.com/get-started
.. _Edge: https://docs.docker.com/docker-for-mac/edge-release-notes/
.. _Repository: https://gitlab.com/ca-lending-services/docker
