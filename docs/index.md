# Installation & Configuration

### Add wpackagist.org repository (optional)

Edit `composer.json`

Add https://wpackagist.org repository

```json
"repositories": [
    {
        "type":"composer", "url":"https://wpackagist.org"
    }
]
```

### Configure database and table prefix

edit `.env`

```dotenv
###> metabolism/wordpress-bundle ###
DATABASE_URL=mysql://user:pwd@localhost:3306/dbname
TABLE_PREFIX=wp_
###< metabolism/wordpress-bundle ###
```

### Update gitignore

edit `.gitignore`

```gitignore
/public/uploads/*
!/public/uploads/acf-thumbnails
/public/edition
/public/cache
/public/wp-bundle
```

### Start the server

Configure a vhost mounted to `/public`, or start Symfony server

```shell
symfony serve
```

Accessing the server url will now start the Wordpress Installation

### Add default Wordpress routing

edit `/config/routes.yaml`

```yml
_wordpress:
    resource: "@WordpressBundle/config/routing.php"
```

Clear cache

```shell
php bin/console cache:clear
```

View routes

```shell
php bin/console debug:router
```

if output is empty, double check that Wordpress is installed and you have added WP_INSTALLED=1 in your environment


## Wordpress configuration

When the bundle is installed, a default `wordpress_bundle.yml` is copied to `/config/packages`

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

## Controllers

See our sample [controllers](samples/controller) that you might want to copy to your `/src/Controller` folder.

## Theme

This bundle come without theme, you can start reading the official [documentation](https://symfony.com/doc/current/templates.html)

Also, you can take a look at our sample [template](samples/templates/generic.html.twig) and start writing your own in `/templates`

## Plugin installation

Please use https://wpackagist.org to find your plugin.

edit `composer.json`

```json
"require": {
    //...
    "wpackagist-plugin/classic-editor":"1.*"
    //...
}
```

## ACF Pro installation

Edit `composer.json`

Declare a new repository

```json
"repositories": [
  {
    "type": "package",
    "package": {
      "name": "elliotcondon/advanced-custom-fields-pro",
      "version": "5.9.5",
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

```json
"require": {
   "elliotcondon/advanced-custom-fields-pro": "5.*",
   //...
}
```
      
Edit `.env` to set ACF_PRO_KEY

```dotenv
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
  "menu": [/*...*/],
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
  "portraits": [/*...*/],
  "sitemap": [/*...*/],
  "layout": "default"
}
```   

## Entities

### Post

```php
//BlogController.php

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