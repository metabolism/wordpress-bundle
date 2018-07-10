# Wordpress Bundle for Symfony 4.0.X

Introduction
------------

Use Wordpress as a backend for a Symfony 4 application

The main idea is to use the power of Symfony for the front / webservices with the ease of Wordpress for the backend.

How does it work ?
--------

When the Wordpress bundle is loaded, it loads a small amount of Wordpress Core files to allow usage of Wordpress functions inside Symfony Controllers.

Wordpress is then linked to the bundle via a plugin located in the mu folder.

Because it's a Symfony bundle, there is no theme management in Wordpress and the entire routing is powered by Symfony.


Features
--------

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

From the bundle itself
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
 
Installation
-----------

    composer require metabolism/wordpress-bundle
    
  register the bundle in the Kernel
  
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
    
  add wordpress permastruct in the routing
  
    _wordpress:
        resource: "@WordpressBundle/Routing/permastructs.php"
        
  add a context service and use context trait from the Wordpress bundle
  
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
      
  inject the context in the controller
  
    public function articleAction(Context $context)
    {
        return $this->render( 'page/article.twig', $context->toArray() );
    }
    

Environment
-----------

The environment configuration ( debug, database, cookie ) is managed via the `.env` file like any other SF4 project, there is a sample file in `doc/sample.env`
We've added an option to handle cookie prefix named `COOKIE_PREFIX`

Wordpress configuration
-----------

When the bundle is installed, a default `wordpress.yml` is copied to `/config/`
This file allow you to manage :
 * Domain name for translation
 * Controller name
 * Keys and Salts
 * Admin page removal
 * Multisite configuration
 * Constants
 * Support
 * Menu
 * Post types
 * Taxonomies
 * Options page
 * Page templates
 * Post templates

        
Roadmap
--------

* Woocommerce Provider rework + samples
* Global maintenance mode for multisite
* Better Symfony 4.1 Support
* Unit tests

       
Why not using Bedrock
--------

Because Bedrock "only" provides a folder organisation with composer dependencies management.
Btw this Bundle comes from years of Bedrock usage + Timber plugin...


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
