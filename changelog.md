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
- ACF Helper can handle lastest posts field type
- Post->get_term function
- Context->getFields($id), return acf field for given id
- Context->addTerm, add current primary term
- update permalink clear cache
- Context->addPagination
### Changed
- Context->addBreadcrumb now add post/term title
- Context->addCurrent to add term for archive

##4.0.0
first official release fr SF 4