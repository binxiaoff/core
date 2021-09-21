KLS API
=======

This project uses Symphony with PHP 7.4. Before starting any work on
this project, check out the `Contributing guide <CONTRIBUTING.rst>`__.

Deployment
----------

Deployment of the backend of the KLS platform is done using Gitlab. To launch a
deployment, you need to have permissions of Developper or higher. Only maintainers
can deploy in production.

In order to deploy a new version of the code, you must go to the latest pipeline
for this reference (or launch a new one). In Gitlab, you can click on the rocket
icon on the left. There, you can either use the "Run pipeline" button or search
for a pipeline by branch name.

You can then click on the pipeline, and once the test steps have been launched,
you can click on the step named after the environment on which you want to deploy.

GrumPHP
-------

GrumPHP is used as pre-commit hook. It runs inside the ``backend.api`` container of
the docker stack. You need to have this stack up for the hook to run. For more
details about this technical choice, see issue ``CALS-4342``.
