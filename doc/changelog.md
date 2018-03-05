# Change Log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) 
and this project adheres to [Semantic Versioning](http://semver.org/).

# 1.3.4 - IN DEV
### Changed
* optimize ACF Helper for woocommerce
* folder reorganisation
### Bugfix
* Invalid woocomerce path
### Removed
*debug view moved to kernel

# 1.3.3 - 2017-12-20
### Added
* define constant via YML files
* symlink and folder chmod check
* multisite media synchronisation
* sendForm and uploadFile
### Removed
* wp remote features
* useless uploads folder rewrite path
* Link in response headers
* wp-config.php direct access

# 1.3.2 - 2017-12-11
### Removed
* cache plugin
* clean image filename plugin
### Added
* clean image filename included in core

# 1.3.1 - 2017-12-07
### Fixed
* invalid $base_uri when ssl is active

# 1.3.0 - 2017-11-24
### Changed
* Wordpress app now stored in src/WordpressBundle
### Added
* trackduck_id handling

# 1.2.31 - 2017-11-20
### Added
* Query::wp_query
### Changed
* Query::get_posts now use self::wp_query

# 1.2.30 - 2017-11-17
### Added
* Option to remove generated thumbnails, per site ( Settings > Media ) or globally ( Network Settings )

# 1.2.29 - 2017-11-16
### Fixed
* ACF Relationship no not filter unpublished posts

# 1.2.28 - 2017-11-14
### Fixed
* Route args order was wrong at call
* Query::get_post_term when using primary param now return false instead of array

# 1.2.27 - 2017-11-14
### Added
* WYSIWYG Iframe support for editor
* page name as comment when debugging
### Removed
* Wordpress upgrade notice for non admin

# 1.2.26 - 2017-11-06
### Added
* Internationalization support via mo/po

# 1.2.25 - 2017-10-30
### Fixed
* Invalid Term management

# 1.2.24 - 2017-10-26
### Fixed
* ACF Helper depth

# 1.2.23 - 2017-09-29
### Added
* get_post_term

# 1.2.22 - 2017-09-21
### Fixed
* optimize ACF Helper

# 1.2.21 - 2017-09-16
### Added
* add_ghost_page

# 1.2.20 - 2017-09-15
### Fixed
* Better 404 handling

# 1.2.19 - 2017-09-11
### Fixed
* App initialised twice
* Delayed route initialisation
* Format select in wysiwyg toolbar

### Added
* maintenance mode -> checkbox in settings + wp_maintenance_mode()

# 1.2.18 - 2017-09-06
### Fixed
* CRITICAL : Route detection failed with query string

# 1.2.17 - 2017-09-04
### Changed
* Symlinks are no longer supported

# 1.2.16 - 2017-08-31
### Added
* underline and justify in tynemce
* post type added to main query for custom taxonomy archive page

# 1.2.15 - 2017-08-31
### Fixed
* wp_seo taxonomy bug
### Added
* default term can be false

# 1.2.14 - 2017-08-24
### Added
* Debug bar

# 1.2.13 - 2017-08-24
### Added
* Query::get_post_terms

# 1.2.10 - 2017-08-22
### Added
* Query::get_term_by

# 1.2.8 - 2017-08-17
### Fixed
* Term now return excerpt instead of description
* various minor bugfix

# 1.2.9 - 2017-08-17
### Fixed
* ACF Clean recursion

# 1.2.7 - 2017-08-15
### Added
* Multisite support
### Changed
* edition url now handled as filter

# 1.2.6 - 2017-08-14
### Fixed
* Nginx compatibility issue
### Changed
* no more wp-config.php symlink, included in /web

# 1.2.5 - 2017-08-08
### Added
* WordpressBundle Term model
* allow submenu page removal
### Changed
* ACF depth recursion max to 4

# 1.2.4 - 2017-08-06
### Changed
* set term description to content var to be iso with post

# 1.2.3 - 2017-08-05
### Added
* Added ACF-cleaner plugin
* Added ACF Group support for ACF Helper

# 1.2.2
### Added
* WooCommerce Support
  - see [timber integration](https://github.com/timber/timber/wiki/WooCommerce-Integration) for more details about templates.
* Added Query String support
 
# 1.2.1 - 2017-05-04
### Added
* Route options : value / assert / convert
 
### Fixed 
* double construct

# 1.2.0 - 2017-04-27
### Added
* Files manipulation are now included inside this project
* Route Override from Symfony/Route to raterize Customer Application with Silex syntax
* Added configuration file in documentation as sample
### Changed 
* app/cms to web/app for public folder accessibility protection


### Changed
* Fixed add_to_twig function registration

## 1.1.1 - 2017-02-19
### Added
* post-types-order plugin

## 1.1.0 - 2017-01-20
### Changed ###
* Main folder is now in `web/edition` and configuration in `app/cms`
* Removed johnpbloc/wordpress custom installer

## 1.0.0 - 2016-11-05 ##
### Added ###
* Composer update
* Use of ApplicationTrait

## 0.0.1 - 2016-11-05 ##
### Added ###
* Update rewrite and staging content
* Taxonomies, menus, post types from config
* Mu-plugins

## 0.0.0 - 2016-11-05 ##
### Added ###
* First version
