CHANGELOG
---------

##1.1.0
####Backoffice Feature
- New interface for Wordpress backoffice
- WebP thumbnail generation enhance
- Placeholder for image slots when empty
- Export database and Uploads files are now availables in Options pages for easy backuping
- Sup button is available in WYSIWYG
####Advanced custom fields fixes
- ACF Fields are now available in menus
- Capabilities are now supported in menus
- Metadata removal for a cleaner DOM
- Role handling in configuration file with permissions
####Multisite optimization
- Better url management for multisites
- Meta informations are no longer global to multisites
#### Fixes
- Maintenance page is no longer active editor and administrator users.
- Data withdrawing optimization in requests for recurring post and ACF fields
- Enhanced image compression to 90%
- Generated thumbnails are now handled with raterized file names.



##1.0.7
- Core upgrade with a provider and plugin design patterns for maintenability.

##1.0.6
- WebP thumbnail generation
- Fix SSL errors
- enhanced form helper
- better media syncing for multisite



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
