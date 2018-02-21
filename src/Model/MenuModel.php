<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Metabolism\WordpressLoader\Model;


use Metabolism\WordpressLoader\Application;

class MenuModel {

    public function __construct($name, $slug, $autodeclare = true)
    {
        if ($autodeclare)
        {
            register_nav_menu($slug, __($name, Application::$bo_domain_name));
        }
    }
}
