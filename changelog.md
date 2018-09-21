CHANGELOG
---------

##4.0.1
####Removed
- ACF dependency
####Bugfix
- menu children not showing up in tree
- invalid id on archive page
- Google map api key for acf
####Added
- ACF Helper can handle latest posts field type
- Post->get_term, Post->get_terms function
- Context->getFields($id), return acf field for given id
- Context->addTerm, add current primary term
- update permalink clear cache
- Context->addPagination
- Comment entity
- Context->addComments with replies
- Form helper :: post comment
- Template in wordpress.yml for all post_type
- Export on custom table
- Custom table register wp rgpd handler to export and erase data
- get_adjacent_posts in QueryHelper to get post sibling from menu_order
### Changed
- Context->addBreadcrumb now add post/term title
- Context->addCurrent to add term for archive

##4.0.0
first official release fr SF 4
