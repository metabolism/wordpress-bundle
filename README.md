# Wordpress Bundle for Symfony 4.0.X

INTRODUCTION
------------

Use Wordpress as a backend for a Symfony 4 application

        
FEATURES
--------
- configure Wrdpress using yml
- to complete...
- support : ACF Pro 5, Multisite, Woocommerce
 
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
    
   open `config/wodpress.yml` to edit wordpress configuration ( post type, taxonomy etc...) 

        
ROADMAP
--------
//todo 

        
MAINTAINERS
-----------

This project is made by Metabolism ( http://metabolism.fr )

Current maintainers:
 * Jérôme Barbato - jerome@metabolism.fr
 * Paul Coudeville - paul@metabolism.fr
