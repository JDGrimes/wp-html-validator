WP HTML Validator
=================

WordPress plugin that validates your site's HTML and reports any errors. Works for
local sites too!

# Installation

Install and activate it as you would any other plugin. Then visit any webpage on your
site (on the front end or the administration panels). You can scroll down to the
footer and see whether the HTML is valid.

# Configuration

The results are cached per-URL using transients, and the default expiration is 2
minutes. You can change this by defining `WP_HTML_VALIDATOR_CACHE_EXPIRATION` in your
`wp-config.php`:

```php
define( 'WP_HTML_VALIDATOR_CACHE_EXPIRATION', 15 * 60 ); // 15 minutes
```
