# Wordpress x Symfony

Use Wordpress as a backend for a Symfony application

[![Latest Stable Version](http://poser.pugx.org/metabolism/wordpress-bundle/v)](https://packagist.org/packages/metabolism/wordpress-bundle) [![Total Downloads](http://poser.pugx.org/metabolism/wordpress-bundle/downloads)](https://packagist.org/packages/metabolism/wordpress-bundle) [![Latest Unstable Version](http://poser.pugx.org/metabolism/wordpress-bundle/v/unstable)](https://packagist.org/packages/metabolism/wordpress-bundle) [![License](http://poser.pugx.org/metabolism/wordpress-bundle/license)](https://packagist.org/packages/metabolism/wordpress-bundle)  [![Buy us a tree](https://img.shields.io/badge/Treeware-%F0%9F%8C%B3-lightgreen)](https://plant.treeware.earth/metabolism/wordpress-bundle)

## How does it work ?

When the Wordpress bundle is loaded, it includes the minimal amount of Wordpress Core files to allow usage of Wordpress functions and plugins
inside Symfony.

__Example :__

```php
// src/Controller/BlogController.php

/**
 * @param Post $post
 * @param PostRepository $postRepository
 * @param BreadcrumbService $breadcrumbService
 * @return Response
 */
public function pageAction(Post $post, PostRepository $postRepository)
{
    $context = [];
    
    // get current post
    $context['post'] = $post;
    
    // find 10 "brands" ordered by title
    $context['brands'] = $postRepository->findBy(['post_type'=>'brand'], ['title'=>'ASC'], 10);

    return $this->render('page.html.twig', $context);
}
```

```twig
{# templates/page.html.twig #}

{% extends 'layout.html.twig' %}

{% block body %}
<article id="post-{{ post.ID }}" class="{{ post.class }}">

    {% if post.thumbnail %}
        <img src="{{ post.thumbnail.resize(800, 600) }}" alt="{{ post.thumbnail.alt }}"/>
    {% endif %}

    <div class="entry-content">
        {{ post.content|raw }}
    </div>
    
    <small>{{ post.metafields.mention }}</small>
    
    {% for brand in brands %}
        {% include 'brand.html.twig' %}
    {% endfor %}

</article>
{% endblock body %}
```

## Features

Using Composer :
* Install/update Wordpress via composer
* Install/update plugins via composer

Using Symfony :
* Template engine
* Folder structure
* Http Cache
* Routing
* YML configuration
* DotEnv
* Enhanced Security ( Wordpress is 'hidden' )
* Dynamic image resize
* MVC

Using Wordpress Bundle :
* Post/Term Repository
* Controller argument resolver for post(s), term and user
* Symfony Cache invalidation on update ( Varnish compatible )
* Post/Image/Menu/Term/User/Comment/Site entity
* Wordpress predefined routes
* Site health checker url
* Static site export command

Using [WP Steroids](https://github.com/wearemetabolism/wp-steroids) Wordpress plugin :
* Wordpress YML configuration ( [view sample](samples/config/packages/wordpress.yml) )
* Permalink configuration for custom post type and taxonomy
* Maintenance mode
* Backup download in dev mode
* Build hook
* Disabled automatic update
* Enhanced Security
* Better guid using RFC 4122 compliant UUID version 5
* Multisite images sync ( for multisite as multilangue )
* SVG Support
* Better Performance
* Wordpress Bugfix
* CSS Fix
* Relative urls
* Multisite post deep copy ( with multisite-language-switcher plugin )
* Custom datatable support with view and delete actions in admin

## Migrating from 1.x to 2.0 ?

Check the [migration guide](docs/migration.md)


## Drawbacks

Wordpress functions are available in the global namespace.

Some plugins may not work ( ex : Woocommerce ).

## Recommended / tested plugins

- [Advanced custom fields](https://wordpress.org/plugins/advanced-custom-fields) Customise WordPress with powerful, professional and intuitive fields.
- [ACF extensions](https://github.com/wearemetabolism/acf-extensions) Extensions for ACF
- [Classic editor](https://wordpress.org/plugins/classic-editor) Restores the previous (« classic ») WordPress editor and the « Edit Post » screen.
- [WP smartcrop](https://wordpress.org/plugins/wp-smartcrop) Set the 'focal point' of any image, right from the media library
- [Multisite language switcher](https://wordpress.org/plugins/multisite-language-switcher) Add multilingual support using a WordPress multisite
- [Wordpress seo](https://wordpress.org/plugins/wordpress-seo) The favorite WordPress SEO plugin of millions of users worldwide!
- [Query monitor](https://wordpress.org/plugins/query-monitor) Query Monitor is the developer tools panel for WordPress
- [Redirection](https://wordpress.org/plugins/redirection) Easily manage 301 redirections, keep track of 404 errors
- [Contact form 7](https://wordpress.org/plugins/contact-form-7)  Manage multiple contact forms, plus you can customize the form and the mail contents

## Demo

https://github.com/wearemetabolism/wordpress-bundle-demo

This is an implementation of the Twenty Nineteen Wordpress theme for wordpress-bundle.

Code
[![Screenshot from 2021-05-03 10-08-22](https://user-images.githubusercontent.com/4919596/116854347-d8f2e180-abf7-11eb-9dec-29480cffa720.png)](https://user-images.githubusercontent.com/4919596/116854347-d8f2e180-abf7-11eb-9dec-29480cffa720.png)

Front
[![Screenshot from 2021-05-03 10-09-02](https://user-images.githubusercontent.com/4919596/116854351-d98b7800-abf7-11eb-9843-a57d521a2b7a.png)](https://user-images.githubusercontent.com/4919596/116854351-d98b7800-abf7-11eb-9843-a57d521a2b7a.png)

Backoffice
[![Screenshot from 2021-05-03 10-09-32](https://user-images.githubusercontent.com/4919596/116854353-d98b7800-abf7-11eb-9063-0520125a0e9e.png)](https://user-images.githubusercontent.com/4919596/116854353-d98b7800-abf7-11eb-9063-0520125a0e9e.png)


## Installation

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Create project

```shell
$ symfony new my_project_name
```

or 

```shell
$ composer create-project symfony/skeleton my_project_name
```

### Define installation path for Wordpress core and plugins

Edit composer.json and edit :

```json
"minimum-stability": "dev",
...
"extra": {
    "installer-paths": {
        "public/wp-bundle/mu-plugins/{$name}/": ["type:wordpress-muplugin"],
        "public/wp-bundle/plugins/{$name}/": ["type:wordpress-plugin"],
        "public/edition/": ["type:wordpress-core"]
    }
}
```

### Install the bundle

Open a command console, enter your project directory and execute:

```shell
$ composer require metabolism/wordpress-bundle 2.0.x
```

#### For applications that don't use Symfony Flex

Enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    Metabolism\WordpressBundle\WordpressBundle::class => ['all' => true],
];
```

### Install and configure Wordpress

```shell
$ symfony serve
```

Please read the full [bundle installation guide](docs/index.md) to continue
  
## Why not Bedrock

Because Bedrock "only" provides a folder organisation with composer dependencies management.
This bundle comes from years of Bedrock usage + Timber plugin...

## Why not Ekino Wordpress Bundle

The philosophy is not the same, Ekino use Symfony to manipulate Wordpress database.
Plus the last release was in 2015...

## Is Wordpress classic theme bad ?

It's more like a code philosophy, once you go Symfony you can't go back.

Plus the security is a requirement for us and Wordpress failed to provide something good because of it's huge usage.

## Roadmap

* More samples
* Better documentation
* Global maintenance mode for multi-site
* Unit tests

## Licence

GPL 3.0 or later

This package is [Treeware](https://treeware.earth). If you use it in production, then we ask that you [**buy the world a tree**](https://plant.treeware.earth/metabolism/wordpress-bundle) to thank us for our work. By contributing to the Treeware forest you’ll be creating employment for local families and restoring wildlife habitats.
    
## Maintainers

This project is made by Metabolism ( http://metabolism.fr )

Current maintainers:
 * Jérôme Barbato - jerome@metabolism.fr
