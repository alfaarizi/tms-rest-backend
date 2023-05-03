Task Management System
================================

[![pipeline status](https://gitlab.com/tms-elte/backend-core/badges/develop/pipeline.svg)](https://gitlab.com/tms-elte/backend-core/-/commits/develop)
[![coverage report](https://gitlab.com/tms-elte/backend-core/badges/develop/coverage.svg)](https://gitlab.com/tms-elte/backend-core/-/commits/develop)
[![codecov report](https://codecov.io/gl/tms-elte/backend-core/branch/develop/graph/badge.svg?token=MSA7R9D4DS)](https://codecov.io/gl/tms-elte/backend-core)

<abbr title="Task Management System">TMS</abbr> is an assignment management and plagiarism detection software written in [PHP](http://php.net/) and based on the [Yii 2 Framework](http://www.yiiframework.com/).


REQUIREMENTS
------------

The minimum requirement by this application is that your web server supports PHP 7.4.0. PHP 8 is not yet supported.

A web server supporting PHP is the Apache HTTP Server. You have to [enable `mod_rewrite`](https://gitlab.com/tms-elte/backend-core/-/wikis/Setting-up-the-development-environment#apache-modules) on Apache web server. If you choose a server other than Apache, you may have to make extra configuration.

The application requires MySQL or MariaDB (other databases are not supported).

INSTALLATION
------------

### Dependencies

Yii uses [Composer](http://getcomposer.org/) as dependency manager. If you do not have Composer, you may install it by following the instructions at [getcomposer.org](http://getcomposer.org/doc/00-intro.md#installation-nix).

Simply install the required dependencies:

~~~
composer install --prefer-dist
~~~

**NOTE:** Modern Yii uses the [Asset Packagist](https://asset-packagist.org/) repository, so the global installation of the [Composer Asset Plugin](https://github.com/francoispluchino/composer-asset-plugin) is no longer required.


### Configuration

Create the `config/db.php`, `config/mailer.php` and `config/params.php` configuration files based on the provided samples in that directory.

**NOTE:** Yii won't create the database for you, this has to be done manually before you can access it.

#### Folder permission

Give write permission for the web server to the following folders:
- runtime
- web/assets

**NOTE:** for security considerations the web server should be ONLY able to write the folders above and the following, automatically created directories:
- appdata

#### Git configuration *(optional)*

To enable Git version controller submissions, beside enabling the feature in the `config/params.php` file, you must install Git and allow to serve the Git repositories through your HTTP server with the [smart HTTP protocol](https://git-scm.com/book/en/v2/Git-on-the-Server-The-Protocols), using the [`git-http-backend` binary](https://git-scm.com/docs/git-http-backend).

In case of Apache 2 webserver, the following configuration shall be placed in the main configuration file (`apache2.conf` on Linux, `httpd.conf` on Windows), or preferably in a separate and included config file:

```apacheconf
SetEnv GIT_PROJECT_ROOT "path/to/backend-core/uploadedfiles"
SetEnv GIT_HTTP_EXPORT_ALL
SetEnv REMOTE_USER $REDIRECT_REMOTE_USER

ScriptAlias /git/ "/usr/lib/git-core/git-http-backend/"
# Windows: path/to/git/mingw64/libexec/git-core/git-http-backend.exe/

<Directory "/usr/lib/git-core/">
    # Windows: path/to/git/mingw64/libexec/git-core/
    AllowOverride None
    Options +ExecCGI +FollowSymLinks -Includes
    Require all granted
</Directory>
<Location /git/>
    # Comment out to require SSL connection
    #SSLRequireSSL
</Location>

<LocationMatch "^/git/.*/w.*/git-receive-pack$">
    Options +ExecCGI
    Require all granted
</LocationMatch>
<LocationMatch "^/git/.*/r.*/git-receive-pack$">
    Options +ExecCGI
    Require all denied
</LocationMatch>
```

**NOTE:** the *ScriptAlias* `/git/` must match the `versionControl.basePath` in `config/params.php`.

#### Docker configuration *(optional)*

To enable the automated assignment evaluator, beside enabling the feature in the `config/params.php` file, you must install Docker on the same or a separate computer.

TMS communicates with Docker through the [Docker Engine API](https://docs.docker.com/engine/api/).
- In case Docker was installed on the local Linux machine, this communication can be done through the UNIX socket of Docker, `unix:///var/run/docker.sock`.
- In case Docker was installed on a different Linux machine, or Windows is being used on either of the computers, the communication can be performed over a TCP channel, e.g. `tcp://10.1.2.3:2375`.  
  Docker by default is not configured to listen to TCP connections, so you must enable it explicitly:
  - see configuration for [Linux](https://docs.docker.com/engine/reference/commandline/dockerd/);
  - see configuration for [Docker Desktop for Windows](https://docs.docker.com/desktop/windows/).

**NOTE:** if you are enabling TCP connection for Docker, you shall secure it with TLS or protect that machine with strict firewall rules!

#### CodeChecker *(optional)*

CodeChecker integration is part of the automated assignment evaluator, and it is ready to use for C/C++ programs if the evaluator and Docker daemon is configured.

However, if you want to use an external code analyzer, 
the Docker images for CodeChecker Report Converter tool must be configured in `params.php` (`evaluator.reportConverterImage`).
Then, the images must be pulled by the system administrator with the following command:
~~~
./yii code-checker/pull-report-converter-image (linux|windows)
~~~

#### Plagiarism detection *(optional)*

There are currently two supported plagiarism detectors: Moss and JPlag. Both can be configured or left unconfigured independently of each other. If both services are configured, instructors can choose the service to use before each plagiarism check; if no services are configured, plagiarism detection is unavailable and hidden on the UI.

##### Moss

[Moss](https://theory.stanford.edu/~aiken/moss/) is an online service. After registering as described on the website, all you need to do is setting your user ID (search for `$userid` in the Perl script they sent back) in the `mossId` key in `config/params.php`. As simple it is, it is not uncommon that Moss is unavailable. To reduce the impact of outages, plagiarism check results are automatically downloaded, so merely looking at the results doesn't require the Moss server to be up, but running checks doesn't work during Moss downtime.

##### JPlag

[JPlag](https://github.com/jplag/JPlag) is an offline plagiarism detector. This means that the checks don't depend on third-party services, but it requires more setup. Currently JPlag 4.1.0 is officially supported, but newer versions should also work as long as the command-line API has no breaking changes. You need the following things on the TMS server:
- A Java Runtime Environment capable of running JPlag (as of JPlag 4.1.0, JRE 17 or newer is required)
- The JPlag JAR file, downloadable from [GitHub](https://github.com/jplag/JPlag/releases)
- The JPlag report viewer. The instance operated by the JPlag authors should be sufficient in development environments (although you might run into issues if your dev web server doesn't support HTTPS), but you're urged to use a local instance in production (otherwise you give up the independence on third-party services). The report viewer isn't distributed pre-built; if you decide to use a local instance, you need to check out the JPlag Git repository and build the report viewer (in the `report-viewer` directory) as described by the `README` in that directory.

After preparing the above, you need to configure `jplag` in `params/config.php` as instructed by the comments in the sample file.

### Database migration

TMS has a code-first database model, by performing the database migration, all required tables will be created:

~~~
./yii migrate
~~~

You may seed the database with an initial semester and an administrator user, as instances of these entities are required for TMS to function:

~~~
./yii setup/init
~~~


### Check

Now you should be able to access the application through the following URL, assuming `tms` is the directory directly under the Web root.

~~~
http://localhost/backend-core/web/
~~~

In order to check whether you web server environment fulfills all requirements by Yii, you may visit the following page:

~~~
http://localhost/backend-core/requirements.php
~~~

BACKGROUND JOBS
------------

TMS has various background jobs which has to be performed regularly on the server-side to operate the system properly.
The interval and the arguments of the background jobs can be configured in the `config/params.php` file.

In case of a (Linux based) production environment you shall add a single cronjob to your crontab file to check for background jobs to execute every minute.
This way new background jobs introduced in future versions of TMS will be automatically scheduled on your instance.

~~~
* * * * * php /path/to/backend-core/yii schedule/run --scheduleFile=@app/config/schedule.php
~~~

On Windows you can use the built-in *Task Scheduler* instead.

TESTING
------------

### Set up the environment

Create the `config/test_db.php` configuration file based on the provided sample in that directory.

Initialize a secondary testing database. You should not run tests on production or development databases, as testing will purge your data!

~~~
tests/bin/yii migrate
~~~

### Run the tests


Yii uses [Codeception](https://codeception.com/for/yii) as unit, api, integration, functional and acceptance test framework.

Run the following command to execute TMS's tests:

~~~
vendor/bin/codecept run unit,api
~~~

DOCUMENTATION
------------

### PhpDocumentor

A documentation of the external and internal API of the PHP codebase can be auto-generated with [PhpDocumentor](https://www.phpdoc.org/).
For the latest version on the `develop` branch this documentation is available at
[Gitlab Pages](https://tms-elte.gitlab.io/backend-core/phpdoc/).

### OpenAPI
There are multiple ways to access the OpenAPI documentation for the project:

- For the latest version from the `develop` branch the OpenAPI documentation with `SwaggerUI` is uploaded to
  [Gitlab Pages](https://tms-elte.gitlab.io/backend-core/swagger-ui/).
- Web interface in a local development server:
  - `<baseurl>/common/open-api/json`: get the latest OpenAPI documentation in `json` format
  - `<baseurl>/common/open-api/swagger-ui`: visualize the latest documentation with `SwaggerUI`
- CLI interface in a local development server: the `yii open-api/generate-docs (yaml|json)` command writes the documentation to `stdout` in the desired output format. It also prints warnings to `stderr`.


CONTRIBUTING
------------

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on issue policies, and the process for contributing to the development.

Special thanks to all [project members](CREDITS.md) and contributors.

LICENSE
------------

This project is licensed under the BSD License - see the [LICENSE.md](LICENSE.md) file for details.
