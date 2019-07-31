CHANGELOG
---------

##1.2.4
#### Fix
- SVG detection ( merge request from @undefinedfr )
- Update functions comment
- Update composer required php extensions

##1.2.3
#### Fix
- Yoast SEO sitemap urls


##1.2.2
#### Feature
- new VARNISH_IP env variable for better cache purge


##1.2.1
#### Fix
- cache button


##1.2.0
#### Feature
- Site health is now available by typing `/_site-health` url [README.md](README.md)
- Cache clear button is now available for administrators to remove filecache.
- New API url are available for cache clearing
  - `/_cache/purge` call varnish cache
  - `/_cache/clear` remove filecache and purge varnish cache
#### Fix
- Purge cache button will now clear varnish cache efficiently


##1.1.0
**Post and page supports must now be explicitly declared in `wordpress.yml`**
####Backoffice Feature
- New interface for Wordpress backoffice
- WebP thumbnail generation enhance
- Placeholder for image slots when empty
- Export database and Uploads files are now available in Options pages for easy backuping
- Sup button is available in WYSIWYG
####Advanced custom fields fixes
- ACF Fields are now available in menus
- Capabilities are now supported in menus
- Metadata removal for a cleaner DOM
- Role handling in configuration file with permissions
####Multisite optimization
- Better url management for multisite
- Meta information are no longer global to multisite
#### Fixes
- Maintenance page is no longer active editor and administrator users.
- Data withdrawing optimization in requests for recurring post and ACF fields
- Enhanced image compression to 90%
- Generated thumbnails are now handled with rasterized file names.



##1.0.7
- Core upgrade with a provider and plugin design patterns for maintenability.

##1.0.6
###Features
- WebP image generation
- Auto-resize for images
- Focus point for smart cropping is now available from backoffice media page
- Table prefix is now supported in .env file
- Entity support, check [README.md](README.md)
###Fixes
- Fix SSL errors
- Enhanced form helper
- Better media syncing for multisite
- Better Headless support for API Calls




##1.0.5
- Bugfix: force `$_SERVER['HTTPS']="on"` when not set but `$_SERVER['HTTP_X_FORWARDED_PROTO']` is set to https
- Bugfix: Context->addPosts now return posts ^^
- Doc: Better documentation for ContextTrait functions
- Feature: table prefix now handled by .env using `TABLE_PREFIX` variable

##1.0.4
- Feature: add addSitemap context function

##1.0.3
- Bugfix: Preview button in post now generate a real url

##1.0.2
- Bugfix: Form Helper

##1.0.1
- Support Wordpress 5 by adding classic editor plugin

##1.0.0
- Public release
