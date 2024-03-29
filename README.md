# Roadiz *Headless Edition* CMS

[![Join the chat at https://gitter.im/roadiz/roadiz](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/roadiz/roadiz?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

Roadiz is a modern CMS based on a polymorphic node system which can handle many types of services and contents.
Its back-office has been developed with a high sense of design and user experience.
Its theming system is built to live independently of back-office allowing easy switching
and multiple themes for one content basis. For example, it allows you to create one theme
for your desktop website and another one for your mobile, using the same node hierarchy.
Roadiz is released under MIT license, so you can reuse
and distribute its code for personal and commercial projects.

- [Documentation](#documentation)
- [Headless edition](#headless-edition)
- [Usage](#usage)
    * [Develop with *Docker*](#develop-with-docker)
        - [Issue with Solr container](#issue-with-solr-container)
    * [Develop with *PHP* internal server](#develop-with-php-internal-server)
        + [On Linux](#on-linux)
- [Update Roadiz sources](#update-roadiz-sources)
- [Maximize performances for production](#maximize-performances-for-production)
    * [Optimize class autoloader](#optimize-class-autoloader)
    * [Increase PHP cache sizes](#increase-php-cache-sizes)
- [Build a docker image with Gitlab Registry](#build-a-docker-image-with-gitlab-registry)

## Documentation

* *Roadiz* website: http://www.roadiz.io
* *Read the Docs* documentation can be found at http://docs.roadiz.io
* *API* documentation can be found at http://api.roadiz.io
* *Forum* can be found at https://ask.roadiz.io

## Headless edition

This is the **API-ready edition** for Roadiz. It is meant to set up your *Apache/Nginx* server root 
to the `web/` folder, keeping your app sources secure, and all your business logic into `src/` folder
AKA `\App` PHP namespace.

**Headless edition** does not need any *themes*, so you can build your API schema right into the backoffice
and use REST API entry points without any code. A built-in *tree-walker* is configured automatically to walk
your node-types children fields to create a JSON graph when requesting a single node (by *id* or by *slug*).

**Headless edition** is heavily based on `roadiz/abstract-api-theme` features, you will find additional information about registered routes and API entry points on its [readme](https://github.com/roadiz/AbstractApiTheme/blob/develop/README.md).
*AbstractApiTheme* is already registered for you so you can begin creating your data structure right away. Any additional configuration is available in your `src/AppServiceProvider.php` container service-provider.

Automatic node-source controller resolution is disabled and any request on a node-source path will end up in `src/Controller/NullController.php`, so your application clients have to use your secure API end-points.

### Boilerplate for exposing content API for static-site generators

Headless edition has been built to work with a *NuxtJS* application and minimizing API calls.

The most important call you'll make is the *Get single node-source by path* which combine searching a node-source by its path **and** fetching its content
in **single** data context.

To retrieve homepage, you can execute `GET /api/1.0/nodes-sources/by-path?path=/`:

```json
{
    "slug": "homepage",
    "@type": "Page",
    "node": {
        "nodeName": "homepage",
        "home": true,
        "visible": true,
        "tags": [],
        "attributeValues": []
    },
    "translation": {
        "locale": "en"
    },
    "urlAliases": [],
    "title": "Homepage",
    "metaTitle": "Homepage – Headless",
    "metaKeywords": "",
    "metaDescription": "Homepage – Headless",
    "url": "/",
    "@id": "http://headless.test/api/1.0/page/2/en",
    "head": {
        "siteName": "Headless",
        "homePageUrl": "/"
    }
}
```

This is way more efficient than executing :

- `/api/1.0/nodes-sources?path=/` which is a *hydra:Collection* response
- and then once you know `@id`, you can request a single node-source response: `/api/1.0/page/2/en`

---

You'll find some boilerplate models and controller for serving *common contents* for building menus and finding social links.
For example: `/api/1.0/common` will expose `CommonContentResponse` model which can be customized to expose some useful data that could be painful to fetch
through *NodesSources* API endpoints, such as hierarchical menu views, or *Roadiz* settings.

```json
{
    "mainMenuWalker": {
        "children": [],
        "item": {
            "slug": "main-menu",
            "@type": "Neutral",
            "node": {
                "nodeName": "main-menu",
                "visible": false,
                "tags": []
            },
            "title": "Main menu",
            "@id": "http://headless.test/api/1.0/neutral/1/en"
        },
        "@type": "MenuNodeSourceWalker"
    },
    "head": {
        "siteName": "Headless",
        "homePageUrl": "/"
    }
}
```

## Usage

### Use ready-to-go Docker image
If you do not need any custom code or to version your content schema, you can launch a Roadiz headless with our
Docker standalone image and our `docker-compose.standalone.yml` example stack.

Override ./app/conf/config.yaml file if necessary (for Solr configuration or custom monolog handler)

```bash
docker-compose up -d --force-recreate
docker-compose exec -u www-data app bin/roadiz migration:migrate --allow-no-migration -n
docker-compose exec -u www-data app bin/roadiz install -n --env=install 
docker-compose exec -u www-data app bin/roadiz generate:private-key
docker-compose up -d --force-recreate --no-deps app varnish
docker-compose exec -u www-data app bin/roadiz users:create -m johndoe@roadiz.io -b -s -p "supersecretpassword" johndoe 
```

Then browse to `https://headless.test/rz-admin` and build your headless API.

Standalone code is configured to create a `CommonContentResponse` from a `main-menu` node. If you need to customize
common content responses, we invite you to create a custom project.

### Create a new custom project
For custom projects we recommend starting from a dedicated repository:

```bash
# Create a new Roadiz project on develop branch
composer create-project roadiz/headless-edition;
# Navigate into your project dir
cd headless-edition;
```

Composer will automatically create a new project based on Roadiz and download every dependency. 

Composer script will copy a default configuration file and your entry-points in `web/` folder automatically
and a `.env` file in your project root to set up your *Docker* development environment.

### Develop with *Docker*

*Docker* on Linux will provide awesome performances, and a production-like environment 
without bloating your development machine:

```bash
# Copy sample environment variables
# and adjust them against your needs.
nano .env;
# Build PHP image
docker-compose build;
# Create and start containers
docker-compose up -d;
# Initialize database and base content
docker-compose exec -u www-data app bin/roadiz migration:migrate --allow-no-migration -n
docker-compose exec -u www-data app bin/roadiz install -n --env=install 
# Restart to empty caches
docker-compose up -d --force-recreate --no-deps app varnish
```

##### Issue with Solr container

*Solr* container declares its volume in `.data/solr` in your project folder. After first launch this 
folder may be created with `root` owner causing *Solr* not to be able to populate it. Just run: \
`sudo chown -R $USER_UID:$USER_UID .data` (replacing `$USER_UID` with your local user *id*).

### Develop with *PHP* internal server

````bash
# Edit your Makefile "DEV_DOMAIN" variable to use a dedicated port
# to your project and your theme name.
nano Makefile;

# Launch PHP server
make dev-server;
````

#### On Linux

Pay attention that *PHP* is running with *www-data* user. You must update your `.env` file to 
reflect your local user **UID** during image build.

```shell script
# Type id command in your favorite terminal app
id
# It should output something like
# uid=1000(toto)
```

So use the same uid in your `.env` file **before** starting and building your docker image.
```dotenv
USER_UID=1000
```

## Update Roadiz sources

Simply call `composer update` to upgrade Roadiz packages. 
You’ll need to execute regular operations if you need to migrate your database.

## Maximize performances for production

You can follow the already [well-documented article on *Performance* tuning for Symfony apps](http://symfony.com/doc/current/performance.html).

### Optimize class autoloader

```bash
composer dump-autoload --optimize --no-dev --classmap-authoritative
```

### Increase PHP cache sizes

```ini
; php.ini
opcache.max_accelerated_files = 20000
realpath_cache_size=4096K
realpath_cache_ttl=600
```

## Build a docker image with Gitlab Registry

You can create a standalone *Docker* image with your Roadiz project thanks to our `roadiz/php80-nginx-alpine` base 
image, a continuous integration tool such as *Gitlab CI* and a private *Docker* registry. 
All your theme assets will be compiled in a controlled environment, and your production website 
will have a minimal downtime at each update.

Make sure you don’t ignore `package.lock` or `yarn.lock` in your themes not to get dependency errors when your 
CI system will compile your theme assets. You may do the same for your project `composer.lock` to make sure 
you’ll use the same dependencies' version in dev as well as in your CI jobs.

*Headless Edition* provides a basic configuration set with a `Dockerfile`:

1. Customize `.gitlab-ci.yml` file to reflect your *Gitlab* instance configuration and your *theme* path and your project name.
2. Enable *Registry* and *Continuous integration* on your repository settings.
3. Push your code on your *Gitlab* instance. An image build should be triggered after a new **tag** has been pushed and your test and build jobs succeeded.
