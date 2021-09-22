Task Management System
================================

<abbr title="Task Management System">TMS</abbr> is an assignment management and plagiarism detection software written in [PHP](http://php.net/) and based on the [Yii 2 Framework](http://www.yiiframework.com/).


REQUIREMENTS
------------

The minimum requirement by this application is that your web server supports PHP 7.3.0.


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


### Database migration

TMS has a code-first database model, by performing the database migration, all required tables will be created:

~~~
yii migrate
~~~

You may seed the database with an initial semester and a course, as managing these entities has no GUI yet:

~~~
yii setup/seed
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


CONTRIBUTING
------------

Please read [CONTRIBUTING.md](CONTRIBUTING.md) for details on issue policies, and the process for contributing to the development.

Special thanks to all [project members](CREDITS.md) and contributors.

LICENSE
------------

This project is licensed under the BSD License - see the [LICENSE.md](LICENSE.md) file for details.
