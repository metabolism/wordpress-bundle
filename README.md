# Wordpress x Symfony

Use Wordpress as a backend for a Symfony application

[![Latest Stable Version](http://poser.pugx.org/metabolism/wordpress-bundle/v)](https://packagist.org/packages/metabolism/wordpress-bundle)
[![Total Downloads](http://poser.pugx.org/metabolism/wordpress-bundle/downloads)](https://packagist.org/packages/metabolism/wordpress-bundle)
[![Latest Unstable Version](http://poser.pugx.org/metabolism/wordpress-bundle/v/unstable)](https://packagist.org/packages/metabolism/wordpress-bundle)
[![License](http://poser.pugx.org/metabolism/wordpress-bundle/license)](https://packagist.org/packages/metabolism/wordpress-bundle)
[![Buy us a tree](https://img.shields.io/badge/Treeware-%F0%9F%8C%B3-lightgreen)](https://plant.treeware.earth/metabolism/wordpress-bundle)
[![Doc - Gitbook](https://img.shields.io/badge/Doc-Gitbook-346ddb?logo=gitbook&logoColor=fff)](https://metabolism.gitbook.io/symfony-wordpress-bundle/)

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

## Documentation

Full documentation is available on [Gitbook](https://metabolism.gitbook.io/symfony-wordpress-bundle/)

[![Doc - Gitbook](https://img.shields.io/badge/Doc-Gitbook-346ddb?style=for-the-badge&logo=gitbook&logoColor=fff)](https://metabolism.gitbook.io/symfony-wordpress-bundle/)

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
* Post/Image/Menu/Term/User/Comment/Blog entity
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

## Installation

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Creating a new project

```shell
$ composer create-project metabolism/wordpress-skeleton my_project_directory
```

Please read the full [bundle installation guide](https://metabolism.gitbook.io/symfony-wordpress-bundle/getting-started/wordpress) to continue

### Setting up an existing Symfony project

Define installation path for Wordpress core and plugins in `composer.json`

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

```shell
$ composer require metabolism/wordpress-bundle 2.0.x
```

#### For applications that don't use Symfony Flex

Register the bundle

```php
// config/bundles.php

return [
    // ...
    Metabolism\WordpressBundle\WordpressBundle::class => ['all' => true],
];
```

Please read the full [bundle installation guide](https://metabolism.gitbook.io/symfony-wordpress-bundle/getting-started/wordpress) to continue

## Demo

https://github.com/wearemetabolism/wordpress-bundle-demo

This is an implementation of the Twenty Nineteen Wordpress theme for wordpress-bundle.

[![Screenshot from 2021-05-03 10-08-22](https://user-images.githubusercontent.com/4919596/116854347-d8f2e180-abf7-11eb-9dec-29480cffa720.png)](https://user-images.githubusercontent.com/4919596/116854347-d8f2e180-abf7-11eb-9dec-29480cffa720.png)

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

## Roadmap

* More samples
* Global maintenance mode for multi-site
* Unit tests

## Drawbacks

Wordpress functions are available in the global namespace.

Some plugins may not work ( ex : Woocommerce ).

## Licence

GPL 3.0 or later

This package is [Treeware](https://treeware.earth). If you use it in production, then we ask that you [**buy the world a tree**](https://plant.treeware.earth/metabolism/wordpress-bundle) to thank us for our work. By contributing to the Treeware forest you’ll be creating employment for local families and restoring wildlife habitats.
    
## Maintainers

This project is made by Metabolism ( http://metabolism.fr )

Current maintainers:
 * Jérôme Barbato - jerome@metabolism.fr
