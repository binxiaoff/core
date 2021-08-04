=======
GrumPHP
=======

What's GrumPHP
==============
See official documentation: https://github.com/phpro/grumphp


How to enable or disable GrumPHP
=================================

We don't need to have php installed on our machine to run those commands. We can use a docker image built to run the tests.

The following commands should be launched from the root of the ``application`` repository,
or you should replace the ``$(pwd)`` command with the path of this repo on your machine.

Enable GrumPHP or update the configuration:

.. code-block:: bash

 docker run --rm -it --user $(id -u):$(id -g) -v $(pwd):/app -w /app klstech/php-ci:7.4.16 php ./vendor/bin/grumphp git:init

Disable GrumPHP:

.. code-block:: bash

 docker run --rm -it --user $(id -u):$(id -g) -v $(pwd):/app -w /app klstech/php-ci:7.4.16 php ./vendor/bin/grumphp git:deinit

Run GrumPHP manually:

.. code-block:: bash

  docker run --rm -it --user $(id -u):$(id -g) -v $(pwd):/app -w /app klstech/php-ci:7.4.16 php ./vendor/bin/grumphp run
