# Breadcrumbs for Wordpress developers

## What is this?

This is a PHP class that will allow you to get a list of breadcrumb elements and display them in any html you want.

## Usage Example

### Base usage

Place the LevDBreadcrumbsData.php file in your theme or plugin.

Include the file.

Create an instance of the class and call the build() method.

```php
$crumbs = new LevDBreadcrumbsData();
$crumbs_items = $crumbs->build($args);
```

Output the data

```php
if ( ! empty($crumbs) ) {
    echo '<ul itemscope itemtype="https://schema.org/BreadcrumbList">';
    foreach ($crumbs as $i => $crumb) {
        if ( empty($crumb['url']) ) {
            echo '<li>';
            echo '<span class="current">';
            echo '<span>' . $crumb['title'] . '</span>';
            echo '</span>';
            echo '</li>';
        } else {
            echo '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
            echo '<a itemprop="item" href="' . $crumb['url'] . '">';
            echo '<span itemprop="name">' . $crumb['title'] . '</span>';
            echo '</a>';
            echo '<meta itemprop="position" content="' . ($i+1) . '" />';
            echo '</li>';
        }
    }
    echo '</ul>';
}
```

### Advanced

You can change labels, custom post type archive page or breadcrumbs taxonomy

```php
$args = [
    'show_paged' => 0, // hide breadcrumb with page number
    'labels' => [
        'search' => 'Search: "%s"',
    ],
    'post_terms' => [
         'movie' => 'genre',
    ],
    'archive_pages' => [
        'movie' => 1111, // archive page ID
    ]
];
$crumbs = new LevDBreadcrumbsData();
$crumbs_items = $crumbs->build($args);
```
