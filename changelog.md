CHANGELOG
---------

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
