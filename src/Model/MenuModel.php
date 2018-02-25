<?php
/**
 * User: Paul Coudeville <paul@metabolism.fr>
 */

namespace Metabolism\WordpressLoader\Model;


class MenuModel {

    public function __construct($name, $slug, $autodeclare = true)
    {
        if ($autodeclare)
        {
            register_nav_menu($slug, __($name, 'wordpress_loader'));
        }
    }
}
