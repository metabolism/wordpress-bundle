# Wordpress & Symfony, with ♥

Introduction
------------

Use Wordpress 5 as a backend for a Symfony 4 application

The main idea is to use the power of Symfony for the front / webservices with the ease of Wordpress for the backend.


How does it work ?
--------

When the Wordpress bundle is loaded, it loads a small amount of Wordpress Core files to allow usage of Wordpress functions inside Symfony Controllers.

Wordpress is then linked to the bundle via a plugin located in the mu folder.

Because it's a Symfony bundle, there is no theme management in Wordpress and the entire routing is powered by Symfony.


Features
--------

From Composer :
* Install/update Wordpress via composer
* Install/update plugin via composer

From Symfony :
* Template engine
* Folder structure
* Http Cache
* Routing
* Installation via Composer for Wordpress Core and plugins
* YML configuration ( even for Wordpress )
* DotEnv
* Enhanced Security ( Wordpress is hidden )
* Dynamic image resize

From the bundle itself :
* YML configuration for Wordpress (see bellow )
* Permalink settings for custom post type and taxonomy
* ACF data cleaning
* SF Cache invalidation ( Varnish compatible )
* Post/Image/Menu/Term/User/Comment/Query entities
* Download backup ( uploads + bdd ) from the admin
* Maintenance mode
* Multisite images sync ( for multisite as multilangue )
* SVG Support
* Edit posts button in toolbar on custom post type archive page
* Wordpress predefined routes via permastruct
* Context helpers
* Relative urls
* Terms bugfix ( sort )
* Form helpers ( get / validate / send )
* Multisite post deep copy ( with multisite-language-switcher plugin )
* Image filename clean on upload
* Custom datatable support with view and delete actions in admin
* Extensible, entities, controller and bundle plugins can be extended in the app
* Site health checker
 
 
Drawbacks
-----------

Because of Wordpress design, functions are available in the global namespace, it's not perfect but Wordpress will surely change this soon.

Some plugins may not work directly, Woocommerce provider needs some rework

No support for Gutenberg, activate the Classic Editor until further notice. 
 
 
Using the boilerplate
-----------

See : https://github.com/wearemetabolism/boilerplate-symfony
```
git clone git@github.com:wearemetabolism/boilerplate-symfony.git myproject
cd myproject
composer install
```

Manual installation
-----------

#### 1 - Edit your composer.json, add wpackagist repository

See `doc/composer.json`

```
 "repositories": [
        {
            "type":"composer", "url":"https://wpackagist.org"
        }
    ],
```

Define installation path for wordpress related packages

```
"extra": {
        ...
        "installer-paths": {
            "public/wp-bundle/mu-plugins/{$name}/": ["type:wordpress-muplugin"],
            "public/wp-bundle/plugins/{$name}/": ["type:wordpress-plugin"],
            "public/edition/": ["type:wordpress-core"]
        }
        ...
    }
```

Use optimized autoloader

```
    "config": {
        ...
        "optimize-autoloader": true,
        "apcu-autoloader": true,
        ...
    }
```

#### 2 - Add Wordpress Bundle using composer
```
composer require metabolism/wordpress-bundle
```

#### 3 - Edit your `.env` file 

Specify the database url, should be mysql

See `doc/.env`

```
DATABASE_URL=mysql://user:pwd@host:3306/dbname
```

#### 4 - Start Wordpress installation

Configure a vhost mounted to `/public`, or start the built-in Symfony server

```
./bin/console server:run
```

You should now be able to access `http://127.0.0.1:8000/edition` to start Wordpress installation

#### 5 - Register the bundle

edit `config/bundles.php`

```php
    ...
    Metabolism\WordpressBundle\WordpressBundle::class => ['all' => true]
    ...
```
    
#### 6 - Add Wordpress routing

edit `services.yaml`

```
_wordpress:
    resource: "@WordpressBundle/Routing/permastructs.php"
```


Context trait
-----------
    
Wordpress data wrapper, allow to query post, term, pagination, breadcrumb, comments and sitemap.

Critical data are added automatically, such as current post or posts for archive, locale, home url, search url, ...
 
```php
public function articleAction(Context $context)
{
    $context->addPosts(['category__and' => [1,3], 'posts_per_page' => 2, 'orderby' => 'title'], 'portraits');
    $context->addSitemap();
    return $this->render( 'page/article.twig', $context->toArray() );
}
```
### Preview

To preview/debug context, just add `?debug=context` to any url, it will output a json representation of itself.

```json
{
  "debug": false,
  "environment": "prod",
  "locale": "fr",
  "language": "fr-FR",
  "languages": [],
  "is_admin": false,
  "home_url": "http://brilliant-wordpress-site.fr/",
  "search_url": "/search",
  "privacy_policy_url": "",
  "maintenance_mode": false,
  "tagline": "Un site utilisant WordPress",
  "posts_per_page": "10",
  "body_class": "fr-FR home page-template-default page page-id-38",
  "page_title": "Home",
  "system": "-- Removed from debug --",
  "menu": [...],
  "post": {
    "excerpt": "",
    "thumbnail": "",
    "link": "/",
    "template": "",
    "ID": 38,
    "comment_status": "closed",
    "menu_order": 0,
    "comment_count": "0",
    "author": "1",
    "date": "15 January 2019",
    "date_gmt": "2019-01-15 11:45:59",
    "content": "",
    "title": "Home",
    "status": "publish",
    "password": "",
    "name": "home",
    "modified": "17 January 2019",
    "modified_gmt": "2019-01-17 14:07:13",
    "parent": 0,
    "type": "page",
    "splashscreen": {
      "text": "La France et le Japon partagent les valeurs ...",
      "partner": {
        "link": "http://www.japon.fr"
      }
    }
  },
  "portraits": [...],
  "sitemap": [...],
  "layout": "default"
}
```   

Entities
-----------

### Post

```php
//MainController.php

public function articleAction(Context $context)
{
    $article = $context->addPost(12, 'article');
    return $this->render( 'page/article.twig', $context->toArray() );
}
```

```twig
{# page/article.twig #}

<h1>{{ article.title }}</h1>
{% set next_article = article.next() %}
{% if next_article %}
    <a href="{{ next_article.link }}">next</a>
{% endif %}
```

ACF fields are directly available so let say you've added a `copyright` text field :

```twig
<h1>{{ post.title }}</h1>
<small>{{ post.copyright }}</small>
```

Available functions :
- next($in_same_term = false, $excluded_terms = '', $taxonomy = 'category')
- prev($in_same_term = false, $excluded_terms = '', $taxonomy = 'category')
- getTerm( $tax='' )
- getTerms( $tax='' )
- getParent()

### Image

Image entity provide a nice on the fly resize function, add width and height to crop-resize, set width or height to 0 to resize

To debug images, just add `?debug=image` to any url, it will replace images with placeholder.

[wp-smartcrop](https://wordpress.org/plugins/wp-smartcrop/) plugin is supported

```twig
<h1>{{ post.title }}</h1>
<img src="{{ post.thumbnail.resize(800, 600) }}" alt="{{ post.thumbnail.alt }}">
<img src="{{ post.thumbnail.resize(0, 600) }}" alt="{{ post.thumbnail.alt }}">
<img src="{{ post.thumbnail.resize(800, 0, 'webp') }}" alt="{{ post.thumbnail.alt }}">
```

Generate picture element ( width, height, media queries ), it use wepb if enabled in PHP
```twig
data.image.toHTML(664, 443, {'max-width: 1023px':[438,246]})|raw 
```

Edit image : 

resize / insert / colorize / blur / brightness / gamma / pixelate / greyscale / limitColors / mask / text / rotate
See : http://image.intervention.io/

```twig
data.image.edit({resize:[260,224], insert:['/newsletter/dots.png','bottom-right', 10, 10]})
```

Use a placeholder if the image doesn't exists
```twig
<img src="{{ (post.thumbnail|default|placeholder).resize(800, 0) }}" alt="{{ post.thumbnail.alt }}">
```

### Custom posts

Custom posts can extend the `Post` entity to add some preprocess or new functions,
in the `/src` folder, add an `Entity` folder, then create a new class for the post_type using Pascal case

```php
namespace App\Entity;

use Metabolism\WordpressBundle\Entity\Post;
use Metabolism\WordpressBundle\Entity\Image;

class Keyfact extends Post
{
	public function __construct($id = null)
	{
		parent::__construct($id);

		if( isset($this->picto) && $this->picto instanceof Image)
			$this->picto = $this->picto->getFileContent();
	}
}
```

### Other entities

Menu, Comment, MenuItem, Product, Term and User can be extended by creating the same file in the `/src/Entity` folder. 

Wordpress core and plugin installation
-----------

Plugin have to be declared to your composer.json, but first you must declare wpackagist.org as a replacement repository to your composer.json

Then define install paths, for mu-plugin, plugin and core
 
```json
{
    "name": "acme/brilliant-wordpress-site",
    "description": "My brilliant WordPress site",
    "repositories":[
        {
            "type":"composer",
            "url":"https://wpackagist.org"
        }
    ],
    "require": {
        "wpackagist-plugin/wordpress-seo":">=7.0.2"
    },
    "extra": {
      "installer-paths": {
        "public/wp-bundle/mu-plugins/{$name}/": ["type:wordpress-muplugin"],
        "public/wp-bundle/plugins/{$name}/": ["type:wordpress-plugin"],
        "public/edition/": ["type:wordpress-core"]
      }
    },
    "autoload": {
        "psr-0": {
            "Acme": "src/"
        }
    }
}
```
    
Wordpress ACF PRO installation
-----------

You must declare a new repository like bellow

```
"repositories": [
    {
      "type": "package",
      "package": {
        "name": "elliotcondon/advanced-custom-fields-pro",
        "version": "5.7.10",
        "type": "wordpress-plugin",
        "dist": {"type": "zip", "url": "https://connect.advancedcustomfields.com/index.php?p=pro&a=download&k={%ACF_PRO_KEY}&t={%version}"},
        "require": {
          "ffraenz/private-composer-installer": "^2.0",
          "composer/installers": "^1.0"
        }
      }
    },
    {
      "type":"composer", "url":"https://wpackagist.org"
    }
  ]
```

Still in composer.json, add ACF to the require section

```
"require": {
     "elliotcondon/advanced-custom-fields-pro": "5.*",
}
```
      
Set the environment variable ACF_PRO_KEY to your ACF PRO key. Add an entry to your .env file:

```
ACF_PRO_KEY=Your-Key-Here      
```

Environment
-----------

The environment configuration ( debug, database, cookie ) is managed via the `.env` file like any other SF4 project, there is a sample file in `doc/sample.env`

We've added an option to handle cookie prefix named `COOKIE_PREFIX` and table prefix named `TABLE_PREFIX`


Wordpress configuration
-----------

When the bundle is installed, a default `wordpress.yml` is copied to `/config/`
This file allow you to manage :
 * Domain name for translation
 * Controller name
 * Keys and Salts
 * Admin pages removal
 * Multi-site configuration
 * Constants
 * Support
 * Menu
 * Post types
 * Taxonomies
 * Options page
 * Post type templates

        
Site health
-----------

You can check site health using `/_site-health`, url
options are:
- output : 1 | json
- full : 0 | 1

        
Cache
-----------

You can purge cache using `/_cache/purge`, url or using purge cache button in backoffice

You can completely remove and purge cache using `/_cache/clear`, url

        
Roadmap
--------

* Woo-commerce Provider rework + samples
* Global maintenance mode for multi-site
* Unit tests
       
       
Why not using Bedrock
--------

Because Bedrock "only" provides a folder organisation with composer dependencies management.
Btw this Bundle comes from years of Bedrock usage + Timber plugin...
       
Why not using Ekino Wordpress Bundle
--------

The philosophy is not the same, Ekino use Symfony to manipulate Wordpress database.
Plus the last release was in 2015...


Is Wordpress classic theme bad ?
--------

We don't want to judge anyone, it's more like a code philosophy, once you go Symfony you can't go back.

Plus the security is a requirement for us and Wordpress failed to provide something good because of it's huge usage.


Licence
----------

GNU AFFERO GPL
    
    
Maintainers
-----------

This project is made by Metabolism ( http://metabolism.fr )

Current maintainers:
 * Jérôme Barbato - jerome@metabolism.fr
 * Paul Coudeville - paul@metabolism.fr
