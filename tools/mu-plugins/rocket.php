<?php
/**
 * Plugin Name: WordpressLoader loader
 * Description: Load wordpress yml configuration
 * Version: 1.0.0
 * Author: Metabolism
 * Author URI: http://www.metabolism.fr
 */

use FrontBundle\Application;

Application::getInstance()->setup();
