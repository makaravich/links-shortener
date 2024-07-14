# Links Shortener Plugin for WordPress

## Description

The Links Shortener plugin allows authorized users to manage URL shortening directly from the WordPress admin panel.

## Features

1. **Link Management:**
    - Create shortened URLs from the admin panel.
    - Manually specify the new shortened URL.
    - Edit, Delete, Sort, and Search the links.
    - Display a table with all shortened links and the number of clicks for each link.

2. **Link Functionality:**
    - Redirect users to the original URL when they click the shortened link.
    - Track the number of clicks for each shortened link.
    - Track additional information such as the date and time of the click, IP address, and referrer.

3. **Settings and Security:**
    - Override user roles via hooks to control who can manage links.

## Installation

1. Download the plugin from the [GitHub repository](https://github.com/makaravich/links-shortener).
2. Upload the plugin files to the `/wp-content/plugins/links-shortener` directory, or install the plugin through the
   WordPress plugins screen directly.
3. Activate the plugin through the 'Plugins' screen in WordPress.
4. Navigate to the 'Tools' -> 'Links Shortener' page in the admin menu to start managing your links.

## Usage

1. Go to the 'Tools' -> 'Links Shortener' page in the WordPress admin panel.
2. Enter the long URL you want to shorten and specify a custom short URL if desired.
3. The _Short link slug_ field is optional. If you leave it blank, a short link will be generated automatically.
   Please keep in mind that you should only enter URL-friendly characters in this field (letters a-z or numbers 0-9)
4. Click the _'Short the link'_ button to generate the shortened link.
5. The list of all shortened links along with the number of clicks will be displayed in a table below the form.
6. Use the search functionality to find specific links or delete links as needed.

## Development

If you are a WordPress developer, you can use the following hooks:

- _linkssh_allowed_user_roles_ - The hook allows to customize which user roles are permitted to manage the link
  shortener plugin. By default, the roles 'administrator', 'editor', and 'author' have these capabilities.

To modify this list, you can use the following code snippet in your theme's functions.php file or in a custom plugin:

```php
add_filter('linkssh_allowed_user_roles', function($roles) {
// Add the 'contributor' role to the allowed roles
$roles[] = 'contributor';
return $roles;
});
```

This example adds the 'contributor' role to the list of roles that can manage the link shortener capabilities. You can
modify the array as needed to include or exclude any roles based on your requirements.

- _linksh_posts_table_columns_ - allows you to customize the columns displayed in the links management table
  within the WordPress admin panel.

Feel free to fork the repository and make improvements. Pull requests are welcome.

## License

This project is licensed under the GNU GENERAL PUBLIC LICENSE — see the [LICENSE](LICENSE) file for details.

## Contact

If you have any questions, feel free to open an issue or contact me directly
at [d.makarski@gmail.com](mailto:d.makarski@gmail.com).
