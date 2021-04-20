CHANGELOG
---------

## 1.5.0
#### Bugfix
- psr-4 for helper
- replaced usage php7.3 function
- https in base url sometime failed
- Invalid cookie domain when port is in url
- ACF link return type for term
#### Changed
- adopt symfony 4 folder structure, `web` -> `public`
- php7.1 as min version

## 1.4.12
#### Bugfix
- Prevent image resize on upload when filename end with _hd or _cmjk

## 1.4.11
#### Bugfix
- Better smart resize
- image loading on Windows
- cookie management for host with non default port

## 1.4.10
#### Bugfix
- SVG loading and resize

## 1.4.9
#### Bugfix
- CSS update

## 1.4.8
#### Bugfix
- failed loading when twig service is not installed

## 1.4.7
#### Bugfix
- link and url fallback for File and Image

## 1.4.6
#### Added
- link and url fallback for File and Image

## 1.4.5
#### Bugfix
- changed php 7.3 function
- fixed File entity loading

## 1.4.4
#### Bugfix
- wp-cron.php required to be logged
- removed tag from post copy
#### Added
- File entity
- taxonomy selector in admin now select parent automatically
- Link return format for post
- `wp-bundle/make_post_link_relative` filter
- `wp-bundle/make_attachment_link_relative` filter
- menu `depth` option in wordpress.yml
- wordpress.yml version

### Changed
- menu regsitration in wordpress.yml can be grouped into `register`

## 1.4.3
#### Added
- post->getTerms('', ['hierarchical'=>false]) now accept a 'hierarchical' options to flatten output when multiple taxonimies are requested

## 1.4.2
#### Fixed
- Empty terms/posts returned in QueryHelper now filtered

## 1.4.1
#### Added
- thumbnail return format for Wordpress thumbnail
#### Fixed
- Invalid ternary in ACFHelper

## 1.4.0
#### Added
- new entity acf return format to reduce query
- new mail plugin to send emails from custom SMTP set in .env ( merge request from @undefinedfr )
- more samples
- use RFC 4122 compliant UUID version 5 to generate post guid
- bloginfo in context
- search url redirect when using ?s=
- sidebar management
- translations management
- editor style
- inherit option for role management
- caption and description in image entity
- sticky and class in post entity
- link and getAvatar in user entity
- `ADMIN_IP_WHITELIST` environment variable to prevent backend access
- headless options in wordpress.yml
- `MAPPED_URL` environment variable to support headless
- `login_url`, `search_form`, `dynamic_sidebar`, `comment_form`, `is_active_sidebar`, `_e`, `_x`, `_n`, `__`, `wp_head`, `wp_footer` twig functions
#### Fixed
- project is now a symfony-bundle declared as is in composer.json
- Thumbnail usage in post column
- double `/web` replacement on windows os
- menu depth
- svg support in image entity ( merge request from @undefinedfr )
- GIF issue
- compat-item css
- template_redirect action management
- frontpage pagination in router
- bundle support when loaded from php server
- less agressive search term sanitize
- invalid url when using switch_to_blog
- incorrect processing of gallery in ACFHelper ( merge request from @undefinedfr )
#### Removed
- `classes` in entity
#### Changed
- downgraded PHP version requirement to 5.6.1 to make it comptible with SF3
#### Breaking changes
- page_title renammed as wp_title
- return format in ACF is now used, id will return id, array/object will return entity
- wp_footer and wp_head is no longer available in context as system.head/footer but now as a twig function `{{ wp_head() }}`
- wordpress.yml : post-formats is now integrated in support
- Flexible content no longer skip parent when there is only one child
- moved addComments from context -> getComments in post entity

## 1.3.12
#### Fixed
- Handle sizes on images

## 1.3.11
#### Fixed
- Warning in context

## 1.3.10
#### Fixed
- Debug routes and robots route when maintenance mode is enabled
#### Added
- Template selector for ACF Relationship on Taxonomy

## 1.3.9
#### Fixed
- Preview link not working

## 1.3.8
#### Added
- Template in Term entity

## 1.3.7
#### Added
- Composer.json sample
#### Fix
- Symfony now handle robots.txt virtual route if no robots.txt file is present
- Some Urls in Yoast Sitemap were relative

## 1.3.6
#### Feature
- new image edit function `{{ data.image.edit({resize:[260,224], insert:['/web/newsletter/dots.png','bottom-right', 10, 10]})`

  supported actions: resize / insert / colorize / blur / brightness / gamma / pixelate / greyscale / limitColors / mask / text / rotate / ...

See : http://image.intervention.io/

- new Twig fonctions : Image, Post, User, Term, attachment_url


## 1.3.5
#### Fix
- wp-json rewrite rule was deleted by mistake

## 1.3.4
#### Fix
- msls alternate url used in context

## 1.3.3
#### Feature
- Added support for term templates, can be used as a display rule in acf using `metabolism/acf-extensions`

## 1.3.2
#### Fix
- removed __ function from TwigExtension to allow declaration in App

## 1.3.1
#### Fix
- msls clone link

## 1.3.0
**Category and tag taxonomy supports must now be explicitly declared in `wordpress.yml`**

#### Feature
- `WordpressController` can be loaded on front and back side in `App\Controller`
- added public entity field to image/post/term/user/menu/menu-item
- added `__` function in TwigExtension
- taxonomy tag support for post permalink ( see permalink in BO )
- parent tag support for term permalink ( see permalink in BO )
- Added `output` arg for Context `get_terms` & `get_posts` to allow array without key
- Maintenance mode call its own maintenanceAction
- Added args support to Post getTerms/getTerm
- Added `?debug=image` to display via.placeholder.com image and view requested size
- Added `Cache::get/set/delete` as WP_Object_cache wrapper
- Added `is_tax`, `is_single`,`is_archive` in context

#### Optimisation
- Added rewrite removal rules in config
- Added option in image to remove meta from object
- Entity now implements the magic `__call` function to allow async acf data loading
- Entities can now receive args options
- Added `depth` arg for Entities to prevent acf loading
- Added `depth` arg for Context `get_terms` & `get_posts` to prevent acf loading ( 0, to prevent )
- Better factory cache based on args crc32
- Reduced ACFHelper depth digging to 1
- ACF Message and tab field in options do not trigger sql query to get value anymore
- when publicly_queryable is set to false, it forces rewrite, query_vars, and exclude_from_search to false
- ACF Options are now autoloaded to save queries, note that previously saved options are not affected, you can do a `UPDATE {$table_prefix}_options set autoload = 'yes' WHERE option_name LIKE 'options_%'` to enable autoload

#### Fix
- Better post status management
- Add more check before foreach in ACFHelper
- Added `imagefocus` plugin fallback support
- `term_url` & `post_url` twig functions now return false on error instead of WP_error
- `reset_cache` action
- search rewrite rule loading
- added html entity decode in page title
- addMenu context function called twice
- WPSeo canonical for page and url with query parameters
- Removed non functional `?debug=query`

#### Removed
- Twig `more` function

## 1.2.6
#### Fix
- Add more check before foreach in ACFHelper

## 1.2.5
#### Fix
- Entity->addCustomFields bug on non object ( merge request from @undefinedfr )
- Update function comments


## 1.2.4
#### Feature
- add wpackagist-plugin/redirection plugin, role configuration
#### Fix
- SVG detection ( merge request from @undefinedfr )
- Update functions comments
- Update composer required php extensions


## 1.2.3
#### Fix
- Yoast SEO sitemap urls


## 1.2.2
#### Feature
- new VARNISH_IP env variable for better cache purge


## 1.2.1
#### Fix
- cache button


## 1.2.0
#### Feature
- Site health is now available by typing `/_site-health` url [README.md](../README.md)
- Cache clear button is now available for administrators to remove filecache.
- New API URLs are available for cache clearing
  - `/_cache/purge` call varnish cache
  - `/_cache/clear` remove filecache and purge varnish cache
#### Fix
- Purge cache button will now clear varnish cache efficiently


## 1.1.0
**Post and page supports must now be explicitly declared in `wordpress.yml`**
####Backoffice Feature
- New interface for Wordpress backoffice
- WebP thumbnail generation enhance
- Placeholder for image slots when empty
- Export database and Uploads files are now available in Options pages for easy backup
- Sup button is available in WYSIWYG
####Advanced custom fields fixes
- ACF Fields are now available in menus
- Capabilities are now supported in menus
- Metadata removal for a cleaner DOM
- Role handling in the configuration file with permissions
####Multisite optimization
- Better url management for multisite
- Meta information is no longer global to multisite
#### Fixes
- Maintenance page is no longer active editor and administrator users.
- Data withdrawing optimization in requests for recurring post and ACF fields
- Enhanced image compression to 90%
- Generated thumbnails are now handled with rasterized file names.



## 1.0.7
- Core upgrade with a provider and plugin design patterns for maintainability.

## 1.0.6
###Features
- WebP image generation
- Auto-resize for images
- Focus point for smart cropping is now available from backoffice media page
- Table prefix is now supported in .env file
- Entity support, check [README.md](../README.md)
###Fixes
- Fix SSL errors
- Enhanced form helper
- Better media syncing for multisite
- Better Headless support for API Calls




## 1.0.5
- Bugfix: force `$_SERVER['HTTPS']="on"` when not set but `$_SERVER['HTTP_X_FORWARDED_PROTO']` is set to https
- Bugfix: Context->addPosts now return posts ^^
- Doc: Better documentation for ContextTrait functions
- Feature: table prefix now handled by .env using `TABLE_PREFIX` variable

## 1.0.4
- Feature: add addSitemap context function

## 1.0.3
- Bugfix: Preview button in post now generate a real url

## 1.0.2
- Bugfix: Form Helper

## 1.0.1
- Support Wordpress 5 by adding classic editor plugin

## 1.0.0
- Public release
