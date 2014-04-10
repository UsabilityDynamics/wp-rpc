WordPress XML RPC plugin.


## New Methods
* wp.getSite - Return site (blog) meta.
* wp.getNetwork - Return MultiSite meta.
* wp.getStructure - Return post-type schema, references wp.getPostTypes
* wp.getAccessKeys - Get access 
* wp.getACL - Return roles and capabilities.

## Extended Methods
* wp.getUser - Returns additional user meta to include Access Keys.
* wp.getTerm - Support for term meta.
* wp.getPostTypes - Includes additional information about post types.

## Filters
* xmlrpc_methods
* xmlrpc_blog_options
* xmlrpc_prepare_term
* xmlrpc_prepare_post
* xmlrpc_prepare_post_type
* xmlrpc_default_posttype_fields
* xmlrpc_prepare_post_type
* xmlrpc_prepare_user
* xmlrpc_default_post_fields

## Notes
* wp.getUsersBlogs - Has no filters and returns very limited information.
