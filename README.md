# Symfony 4.X Bundle for Wordpress

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
 
 
Drawbacks
-----------

Because of Wordpress design, functions are available in the global namespace, it's not perfect but Wordpress will surely change this soon.

Some plugins may not work directly, Woocommerce provider needs some rework

No support for Gutemberg, activate the Classic Editor until further notice. 
 
Installation
-----------

```
composer require metabolism/wordpress-bundle
```
    
register the bundle in the Kernel
  
```php
public function registerBundles()
{
   $bundles = [
      new \Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
      new \Symfony\Bundle\TwigBundle\TwigBundle(),
      ...
  ];
  	
  $bundles[] = new \Metabolism\WordpressBundle\WordpressBundle();
  	
  return $bundles;
}
```
    
add wordpress permastruct in the routing
  
```json
_wordpress:
    resource: "@WordpressBundle/Routing/permastructs.php"
```
  
add a context service and use context trait from the Wordpress bundle
  
```php
<?php

namespace App\Service;

use Metabolism\WordpressBundle\Traits\ContextTrait as WordpressContext;

class Context
{
	use WordpressContext;

	protected $data;

	/**
	 * Return Context as Array
	 * @return array
	 */
	public function toArray()
	{    	    
	    return is_array($this->data) ? $this->data : [];
	}
}
```
    
inject the context in the controller

```php
public function articleAction(Context $context)
{
    //use wordpress function directly ex:is_user_logged_in()
    if( is_user_logged_in() )
       return $this->render( 'page/article-unlocked.twig', $context->toArray() );
    else   
       return $this->render( 'page/article.twig', $context->toArray() );
}
``` 

Context trait
-----------
    
 Wordpress data wrapper, allow to query :   
 * Post
 * Posts
 * Term
 * Terms
 * Pagination
 * Breadcrumb
 * Comments
 
 
```php
public function articleAction(Context $context)
{
    $context->addPosts(['category__and' => [1,3], 'posts_per_page' => 2, 'orderby' => 'title']);
    return $this->render( 'page/article.twig', $context->toArray() );
}
```
     
To debug context, just add `?debug=context` to any url, it will output a json representation of itself.
     
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
        "web/wp-bundle/mu-plugins/{$name}/": ["type:wordpress-muplugin"],
        "web/wp-bundle/plugins/{$name}/": ["type:wordpress-plugin"],
        "web/edition/": ["type:wordpress-core"]
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

```json
"repositories": [
    {
      "type": "package",
      "package": {
        "name": "elliotcondon/advanced-custom-fields-pro",
        "version": "5.7.10",
        "type": "wordpress-plugin",
        "dist": {
          "type": "zip",
          "url": "https://connect.advancedcustomfields.com/index.php?p=pro&a=download"
        },
        "require": {
          "philippbaschke/acf-pro-installer": "^1.0",
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

```json
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
 * Admin page removal
 * Multi-site configuration
 * Constants
 * Support
 * Menu
 * Post types
 * Taxonomies
 * Options page
 * Post type templates

        
Roadmap
--------

* Woo-commerce Provider rework + samples
* Global maintenance mode for multi-site
* Better Symfony 4.1 Support
* Unit tests
* Better code comments
* Post/Term/User Repository
       
       
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
