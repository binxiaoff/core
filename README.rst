KLS API
=======

This project uses Symphony with PHP 7.4. Before starting any work on
this project, check out the `Contributing guide <CONTRIBUTING.rst>`__.

GrumPHP
-------

GrumPHP is used as pre-commit hook, you will need to install the vendors for a
first run. You can use docker to set them up without having php installed on
your machine::

    docker run --rm -it --user $(id -u):$(id -g) -v $(pwd):/app -w /app --entrypoint "" webdevops/php:7.4-alpine composer install --no-scripts
