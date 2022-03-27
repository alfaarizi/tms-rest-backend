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
* on all branches: code style check with PHPCS; generate PhpDoc and OpenAPI documentation; unit and api tests.
* *develop* branch: continuous deployment to staging environment; deploy the generated documentation to Gitlab Pages.
* *master* branch: continuous deployment to production.

DOCUMENTATION
--------------

All *structural elements* (classes, methods, etc.) in the source code must be documented with the 
[DocBlock](https://docs.phpdoc.org/guide/getting-started/what-is-a-docblock.html) 
format, so that an API documentation of codebase can be generated with [PhpDocumentor](https://www.phpdoc.org/).

The documentation is auto-generated in the CI pipeline and can be downloaded as an artifact if needed to be analyzed.
To generate the documentation locally for yourself during development, simply download the 
[PHAR file](https://phpdoc.org/phpDocumentor.phar) and execute it, as instructed in the *Usage* section 
on the website of [PhpDocumentor](https://www.phpdoc.org/).

### OpenAPI

Writing OpenAPI documentation is required for new contributions.
This project contains several tools for writing and visualizing the specification:
- `zircote/swagger-php`: generates OpenAPI documentation from doctrine annotations
- `light/yii2-swagger`: integrates `zircote/swagger-php` and `swagger-api/swagger-ui` with Yii2.
- A custom tool that generates schemas from model and resource classes and passes them to the `zircote/swagger-php` library.

#### Write annotations for action methods

You should write annotations for all action methods.
[See zircote/swagger-php documentation for more information.](https://zircote.github.io/swagger-php/)

- The annotations should contain all possible responses with the corresponding status codes.
- You should write `operationId`s with the following convention: `moduleName::controllerName:actionName`
- The tags should contain the module name and controller name.
- Use the generated schemas when possible.
- Reuse the provided definitions when possible.

#### Reusable definitions

The reusable definitions are placed in the `components/openapi/definitions` directory. 

- `apiInfo`: API and server information. It reads information from global constants.
- `intId`: number format for all ids. Usage: `@OA\Schema(ref="#/components/schemas/int_id")`
- `intIdList`: number format for comma-separated lists of IDs. Usage: `@OA\Schema(ref="#/components/schemas/int_id_list")`
- `responses`: the most common responses with status codes.
Usage: `@OA\Response(response=<code>, ref="#/components/responses/<code>")`
- `security`: security schemes
- `yii2Error`: schema of the Yii2 error responses. Usage: `ref="#/components/schemas/Yii2Error"`
- `yii2Params`: definition of the optional parameters provided by Yii2: `sort`, `fields`, `extraFields`.
Usage: `@OA\Parameter(ref="#/components/parameters/yii2_expand")`


#### Generate schemas from model and resource classes

`OA\Schema` annotations are automatically generated, if a model or resource class implements the `IOpenApiFieldTypes` interface.
The `fieldTypes` method returns an associative array that contains all necessary information to generate annotation for the fields.

Currently, it supports the following annotations:
- `OAProperty`: contains the type of the field, ref, constrains or other annotation classes.
  Examples:
    - string field with examples: `'description' => new OAProperty(['type' => 'string', 'example' => 'example']),`
    - use a ref: `'id' => new OAProperty(['ref' => '#/components/schemas/int_id'])`
- `OAItems`: describes an item of an array property. Example:
  - string array: `'stringArray' =>  new OAProperty(['type' => 'array', new OAItems(['ref' => '#/components/schemas/int_id'])])`
  - use a ref: `'ids' =>  new OAProperty(['type' => 'array', new OAItems(['type' => 'string'])])`
- `OAList`: generates list of items for annotations. Example:
  - Possible values of an enum: `'os' => new OAProperty(['type' => 'string', 'enum' => new OAList(['linux', 'windows')])]),`

The tool generates `OA\Schema` annotations for all scenarios and read requests with the following names
`<prefix>_<resource-name>_Scenario<scenario-name>` and `<prefix>_<resource-name>_Read`.
The generated files can be found in the `runtime/openapi-schemas` directory.

Currently, the following prefixes are used:
- `Common`: for the `resources` directory
- `Student`: for the `student/resources` directory 
- `Instructor`: for the `instructor/resources` directory 
- `Admin`: for the `admin/resources` directory 

The schemas can be used in documentation via refs:
`#/components/schemas/schema_name`
