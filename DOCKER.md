Docker Guide
==================

This guide describes how to build and use the Docker image for the *TMS REST Backend* project. This image is intended for production usage.
In case you are interested in setting up a containerized development environment for TMS, please refer to the [TMS Compose](https://gitlab.com/tms-elte/compose) guide instead.

In general, for production, we advise to use the prebuilt images hosted at *DockerHub* (`tmselte/backend-core`) or at the *GitLab Container Registry* (`registry.gitlab.com/tms-elte/backend-core`). Only in special circumstances should you build your own production images.

**Available images:**
 - **Latest stable build:** the `lastest` tag is the newest stable, released version of the *TMS REST Backend*. Preferred to be used in a production environment.
 - **Versioned stable builds:** the versioned tags (e.g. `3.4.0`) contain the respective release of the *TMS REST Backend*. 
 - **Nightly build:** the `nightly` tag is the newest development version of the *TMS REST Backend*. Contains the new features before their release, but has a higher chance to contain bugs.

BUILD IMAGE
------------------

Build the image of the project and name it.

```bash
docker build . -t tms-backend-core
```

RUN CONTAINER
------------------

You can run the `tms-backend-core` image created above. As a bare minimum, you must specify and mount a configuration file to use.
In this example the REST backend will be available over port `8080` on the host machine.

```bash
docker run \
  --name tms-backend-core-container \
  --publish 8080:80 \
  --mount type=bind,source=/path/to/config.yml,target=/var/www/html/backend-core/config.yml,readonly \
  tms-backend-core
```

### Database storage

Keep in mind that there is no RDBMS server installed in the created docker image. You shall either use a [multi-container environment](#multi-container-environment) with a separate database service container; or access a database engine on the host computer.

### Persistent data storage

Additionally, you should also mount a directory (or a Docker volume) for the `appdata` directory, so persistent data is kept separately from the container.

```bash
docker run \
  --name tms-backend-core-container \
  --publish 8080:80 \
  --mount type=bind,source=/path/to/config.yml,target=/var/www/html/backend-core/config.yml,readonly \
  --mount type=bind,source=/path/to/appdata,target=/var/www/html/backend-core/appdata \
  tms-backend-core
```

You may add the `-d` flag to start the container in a detached mode.

### Execute commands in the container

The default command for the image will perform 3 operations when starting a new container:

1. start the cron daemon;
2. apply any new migrations on the database;
3. start the Apache2 webserver.

It is advised to **NOT** override the default command, but of course you may execute additional commands on the running container.

E.g. to seed the sample dataset:  
```bash
docker exec tms-backend-core-container ./yii setup/sample
```

MULTI-CONTAINER ENVIRONMENT
------------------

If you are interested in setting up a multi-container environment to host TMS, please refer to the [TMS Compose](https://gitlab.com/tms-elte/compose) guide instead.