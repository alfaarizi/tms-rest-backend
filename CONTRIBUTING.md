Contribution Guide
==================

<abbr title="Task Management System">TMS</abbr> is a university project. Here you will find all relevant information on how to contribute to the project. Don't hesitate to contact the project *Owners* or *Maintainers* if you have questions.

You reserve all right, title, and interest in and to your contributions. All contributions are subject to the BSD License - see the [LICENSE.md](LICENSE.md) file for details.


ISSUE POLICIES
--------------

Either you are reporting a ~"Kind: Bug", making a ~"Kind: Enhancement" or plan to join the development, you should always create an issue with detailed decription to log and track the tasks and their process.

Example use cases or bug reports should never contain sensitive personal data, e.g. the grading results of a student. Always make sure the remove all sensitive personal information from the samples and screenshots you provide. (*Name and NEPTUN code pairs are allowed to include, as these are openly searchable information for all university citizens.*)


DIRECTORY STRUCTURE
-------------------

      assets/             contains assets definition
      commands/           contains Terminal controller classes
      components/         contains components (e.g. helpers, widgets)
      config/             contains application configurations
      controllers/        contains Web controller classes
      mail/               contains view files for e-mails
      messages/           contains localization translations
      migrations/         contains database migration classes
      models/             contains model classes
      modules/            contains submodule applications
      runtime/            contains files generated during runtime
      tests/              contains various tests for the application
      vendor/             contains dependent 3rd-party packages
      views/              contains view files for the Web application
      web/                contains the entry script and Web resources


CODING GUIDLINES
----------------

### PHP source files

The PHP codebase strictly follows the [PSR-12](https://www.php-fig.org/psr/psr-12/) coding style. Make sure you match your editor's settings to it before you submit your code for review. If you are not familiar with PSR-12, you advised to use an editor which offers code style checking according to this standard, like [PhpStorm](https://www.jetbrains.com/phpstorm/), for which the appropriate configuration is already contained in the repository's `.idea` folder.

There is also an `.editorconfig` file in the root of the project containing the basic styling rules, which is supported and auto-recognized by [many IDEs](http://editorconfig.org/) either native or through a plugin.

### Other source files

JavaScript and CSS source files *MUST* use 4 spaces for indenting, not tabs; and follow a consistent formatting style.

### Automated linting

The project integrated <abbr title="PHP CodeSniffer">[PHPCS](https://github.com/squizlabs/PHP_CodeSniffer)</abbr> linter is also useful to show and in many cases even fix styling problems to you early on. You may run it after you [installed](README.md#installation) the dependencies with Composer.
~~~bash
vendor/bin/phpcs  # check code style
vendor/bin/phpcbf # fix code style
~~~


DEVELOPMENT
-----------

### Workflow

The project follows the [Git Feature Branch Workflow](http://nvie.com/posts/a-successful-git-branching-model/), therefore development for all issues should be carried out on a feature branch with following a naming convention that enables Gitlab to join the issues with the appropriate branches. This convention is `<issue id>-<custom name>`.  

The default development branch for the project is **develop**. Only project members with at least *Maintainer* role are allowed to push to this branch, *Developers* shall submit a merge request for code review in order to merge their feature branch back.  
The stable branch of the project is **master**. Only project members with at least *Maintainer* role are allowed to push or merge into this branch.

### Testing

TMS comes with unit, integration and functional tests implemented with the [PHPUnit](https://phpunit.de/) and the [Codeception](https://codeception.com/for/yii) test frameworks. You should always perform these tests as described in the [README](README.md#testing) before you submit your code for review.

Currently code coverage level of tests for the project is low. If you fix a bug with an existing feature or add a new feature, please also provide the appropriate tests for them.

### Continuous integration

Upon pushing or merging to the server, the following tests are performed on the last commit:
*  on all branches: code style check with PHPCS (backend) and ESLint (frontend); unit and api tests.
*  on master, develop2 branches: continuous deployment to production / staging environment.
