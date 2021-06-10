Contributing
============

The notion page for the `development
process <https://www.notion.so/lafabrique/Processus-de-d-veloppement-d6ed6f87a4184ff1ab6908170b38fcde>`__
develops several points described here, you should definitely take a
look there.

Issue tracker
-------------

Bugs and feature requests must be reported in `our issue tracker on
Jira <https://lafabriquebyca.atlassian.net/jira/software/c/projects/CALS/issues/>`__.
Templates exist for user stories and bugs, and are detailed `in
Notion <https://www.notion.so/lafabrique/Processus-de-d-veloppement-d6ed6f87a4184ff1ab6908170b38fcde#ac9a442328914995a14a721dc7735562>`__.

Git workflow
------------

The Git workflow pattern that we adopt is **GitFlow**.

This workflow has lot of commands to type and remember, so you can use
the ``git-flow`` library of git subcommands to help automate some parts
of the flow to make working with it easier. We’re using one of the most
popular ones: `AVH <https://github.com/petervanderdoes/gitflow-avh>`__.
It’s highly recommended to read the `Getting
started <https://github.com/petervanderdoes/gitflow-avh#getting-started>`__
part on **AVH**.

There also exists a plugin for PhpStorm integration: `Git Flow
Integration <https://plugins.jetbrains.com/plugin/7315-git-flow-integration/>`__.
To use the integration, you need to `install
AVH <https://github.com/petervanderdoes/gitflow-avh/wiki/Installing-on-Mac-OS-X>`__
first.

Rebase or merge ?
~~~~~~~~~~~~~~~~~

-  “Merge” is used for merging following branches to ``develop``:
   feature, bugfix, hotfix or release
-  “Rebase” is used for picking up the changes from the base branch
   (develop or master)

   -  if you work alone one your branch, use “rebase”
   -  otherwise, use “merge”

For example, when working on a feature branch, and you need to pick some
newly merged changes from ``develop``, please use ``rebase``. If the
feature branch has already been published (pushed to the remote), you
will need to use the ``force push`` to overwrite the whole history.

Git branch creation
~~~~~~~~~~~~~~~~~~~

The pattern for branch naming is:
``<branch-type>/<jira-ticket-key>-<summary-of-the-ticket>``, where
``<branch-type>`` stands for one of ``feature``, ``bugfix``, ``hotfix``
or ``release``.

For example: ``feature/CALS-233-delete-translation-cache``

The branch must always be created from develop except when:

-  developing on an epic (the base will be the epic branch)
-  creating a hotfix (the base will be master)

Git commit messages:
~~~~~~~~~~~~~~~~~~~~

We are using `conventional commit <https://www.conventionalcommits.org/en/v1.0.0/>`_,
the pattern for commit message summary is:
``<type>(<scope (optional: <jira-ticket-key> commit message``.

The type is defined in the specification and should be from the following list:

- feat
- fix
- build
- chore
- ci
- docs
- style
- refactor
- perf
- test

The scope is defined in the documentation for convential commit linked above,
and the list of accepted scopes is below:

- core
- agency
- arrangement
- fei
- cdc

For example:
``feat(fei): CALS-123 Add method to get eligibility for a program``

And we also define some rules for the message part as follow:

-  Separate subject from body with a blank line
-  Limit the subject line to 50 characters
-  Capitalize the subject line
-  Do not end the subject line with a period
-  Use the imperative in the subject line
-  Wrap the body at 72 characters
-  Use the body to explain what and why vs. how

For more details see:
`How to Write a Git Commit Message <https://chris.beams.io/posts/git-commit/>`_

Version number standard
~~~~~~~~~~~~~~~~~~~~~~~

The version number standard that we use is **SemVer**. Given a version
number MAJOR.MINOR.PATCH, the number that should be incremented is:

-  MAJOR version when you make incompatible API changes,
-  MINOR version when you add functionality in a backwards-compatible
   manner, and
-  PATCH version when you make backwards-compatible bug fixes.

Additional labels for pre-release and build metadata are available as
extensions to the MAJOR.MINOR.PATCH format.

Code review
-----------

All code must go through code review in order to be considered ready.
There are a few rules and processes to follow for developers and
reviewers.

As a developer
~~~~~~~~~~~~~~

A merge request can be created even if the code is not ready, but you
should mark it as draft so that nobody merges it by mistake. This is
done by editing it, and clicking on the link below the title (or
prefixing the title with ``Draft:``).

When you have finished your development, make sure that your branch is
up to date with the latest changes in the destination branch (in most
cases ``develop``, but it can also be an epic branch) by following our
vcs workflow (see above).

-  Create a merge request from your branch to the destination branch (or
   remove the draft status)
-  Assign the reviewer if you already know them.
-  Update the related Jira issue’s status to “Code Review”

Once your branch has been reviewed, you may have some feedbacks to
process. If that feedback implies a big code refactoring or a large code
change, you should set the merge request back to draft and move the
issue back to “In progress” (or “En cours”). Once everything is
processed you can remove the draft status from the merge request.

As a reviewer
~~~~~~~~~~~~~

When you pick up a merge request, you should:

-  Assign the merge request to yourself
-  Read the related Jira issue, including the comments. This will allow
   you to understand the need and maybe the steps in the developer’s
   thinking, avoiding some questions about the “why”
-  Review the code updates. This can be done in Gitlab, or locally using
   the tool of your choice.

   -  make sure the code is up to the coding standards (see below)
   -  make sure the code does what it is supposed to do (check the
      ticket)
   -  make sure the code is robust and well designed
   -  make sure the code is tested (considering the existing state, this
      can be on a best effort basis)
   -  make sure the code is documented (describe methods that are not
      obvious, explain tricky code)

-  Check the git history to make sure it follows the guidelines and is
   explicit enough (it is a form of documentation after all)
-  Test the code locally. You should be able to run it using the `dev
   docker stack <https://gitlab.com/ca-lending-services/docker>`__.

If you have questions, remarks, objections, you can use Gitlab comments.
You can also communicate directly with the developer if necessary,
however any explanations or technical choices should be written down as
Gitlab comments or in the Jira issue. If the changes are major, you
should change the Jira issue status back to “In progress” (or “En
cours”).

Once everything is OK, you can accept the request on Gitlab by clicking
on “Merge”, and change the Jira issue status to “Merged”.

Coding standard
---------------

We have setup a coding standard to ensure that code is readable and
maintainable by everyone. The full description of this standard is in
the `associated documentation file <doc/coding-standard.rst>`__.
