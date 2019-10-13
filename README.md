# Wordpress & Symfony, with ♥

## Introduction

Use Wordpress 5 as a backend for a Symfony 4 application

The main idea is to use the power of Symfony for the front / webservices with the ease of Wordpress for the backend.


## How does it work ?

When the Wordpress bundle is loaded, it loads a small amount of Wordpress Core files to allow usage of Wordpress functions 
inside Symfony Controllers.

Wordpress is then linked to the bundle via a plugin located in the mu folder.

Because it's a Symfony bundle, there is no theme management in Wordpress and the entire routing is powered by Symfony.


## Features

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
 
 
## Drawbacks

Because of Wordpress design, functions are available in the global namespace, 
it's not perfect but Wordpress will surely change this soon.

Some plugins may not work directly, Woocommerce provider needs some rework 
 
## Demo

A demo is available at https://github.com/wearemetabolism/wordpress-bundle-demo, 

it's an implementation of the Twenty Nineteen theme for Wordpress 5.

## Installation

#### 1 - Start a fresh project

```
symfony new --full my_project
```
or
```
composer create-project symfony/website-skeleton my_project
```

#### 2 - Prepare composer to work with Wordpress

Edit `composer.json`

Add https://wpackagist.org repository

```
"repositories": [
    {
        "type":"composer", "url":"https://wpackagist.org"
    }
],
```

Define installation path for Wordpress related packages

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

#### 3 - Configure database

edit `.env`

```
###> metabolism/wordpress-bundle ###
DATABASE_URL=mysql://user:pwd@host:3306/dbname
TABLE_PREFIX=wp_
###< metabolism/wordpress-bundle ###
```

Only mysql is supported

#### 4 - Require Wordpress Bundle

```
composer require metabolism/wordpress-bundle
```

if you want to use the development version (not recommended), edit `composer.json` before
```
"license": "GPL-3.0-or-later",
...
"prefer-stable": true,
"minimum-stability": "dev",
...
```

then 

```
composer require metabolism/wordpress-bundle:dev-develop
```

#### 5 - Add Wordpress routing

edit `routes.yaml`

```
_wordpress:
    resource: "@WordpressBundle/Routing/permastructs.php"
```

Clear cache

```
./bin/console cache:clear
```

#### 6 - Update gitignore

edit `.gitignore`

```
/public/uploads/*
!/public/uploads/acf-thumbnails
/public/edition
/public/cache
/public/wp-bundle
```

#### 7 - Start the server

Configure a vhost mounted to `/public`, or start the built-in Symfony server

```
./bin/console server:start
```

Accessing the server url will now start the Wordpress Installation, 

#### 8 - Develop your website !

Take a look at `src/Controller/BlogController.php`, `templates/generic.html.twig` and `config/wordpress.yml` and continue to read the doc bellow.

## Wordpress configuration

When the bundle is installed, a default `wordpress.yml` is copied to `/config/`

This file allow you to manage :
 * Keys and Salts
 * Image options
 * Maintenance support
 * Admin pages removal
 * WYSIWYG MCE Editor
 * Feature Support
 * Multi-site configuration
 * Constants
 * ACF configuration
 * Menu
 * Custom Post type
 * Custom Taxonomy
 * Page, post, taxonomy templates
 * Page states
 * Post format
 * External table viewer
 * Roles
 * Optimisations
 * Domain name
 * Controller name

## Plugin installation

Please use https://wpackagist.org to find your plugin.

edit `composer.json`

```
"require": {
    ...
    "wpackagist-plugin/classic-editor":"1.*"
    ...
}
```

## ACF Pro installation

Edit `composer.json`

Declare a new repository

```
"repositories": [
  {
    "type": "package",
    "package": {
      "name": "elliotcondon/advanced-custom-fields-pro",
      "version": "5.8.4",
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

Add ACF

```
"require": {
   "elliotcondon/advanced-custom-fields-pro": "5.*",
   ...
}
```
      
Edit `.env` to set ACF_PRO_KEY

```
ACF_PRO_KEY=Your-Key-Here      
```

## Context service
    
The Context service is a Wordpress data wrapper, it allows to query post, term, pagination, breadcrumb, comments and sitemap.

Critical data are added automatically, such as current post or posts for archive, locale, home url, search url, ...

### Usage

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

## Entities

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

class Guide extends Post
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

## Additional routes

### Site health

You can check site health using `/_site-health`, url
options are:
- output : 1 | json
- full : 0 | 1

        
### Cache

You can purge cache using `/_cache/purge`, url or using purge cache button in backoffice

You can completely remove and purge cache using `/_cache/clear`, url

        
## Roadmap

* Create Symfony recipe
* Provide more samples
* Woo-commerce Provider rework
* Global maintenance mode for multi-site
* Unit tests
       
       
## Why not using Bedrock

Because Bedrock "only" provides a folder organisation with composer dependencies management.
Btw this Bundle comes from years of Bedrock usage + Timber plugin...
       
## Why not using Ekino Wordpress Bundle

The philosophy is not the same, Ekino use Symfony to manipulate Wordpress database.
Plus the last release was in 2015...


## Is Wordpress classic theme bad ?

We don't want to judge anyone, it's more like a code philosophy, once you go Symfony you can't go back.

Plus the security is a requirement for us and Wordpress failed to provide something good because of it's huge usage.


## Licence

GNU AFFERO GPL
    
    
## Maintainers

This project is made by Metabolism ( http://metabolism.fr )

Current maintainers:
 * Jérôme Barbato - jerome@metabolism.fr
 * Paul Coudeville - paul@metabolism.fr
