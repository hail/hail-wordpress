# Hail-Wordpress

A Wordpress plugin to allow importing of Hail content and the use of shortcodes for displaying of Hail content. https://get.hail.to

License: [GPLv2](http://www.gnu.org/licenses/gpl-2.0.html) or later

## Requirements

* Wordpress
* PHP >=5.3.2
* Access to the Hail API (see below)

## Installation

### Via composer

If you're using composer (https://roots.io/using-composer-with-wordpress/) then you can add the following requirement to your composer.json:
```json
"require": {
  "hail/hail-wordpress": "1.*"
}
```
The plugin isn't registered on the wordpress.org plugin repository (or wpackagist.org) so you also need to tell composer where to find the repository, by adding the following to your composer.json repositories section:
```json
"repositories": [
  {
    "type": "vcs",
    "url": "https://github.com/hail/hail-wordpress"
  }
],
```

### Via zip file

You can install the plugin via .zip file available [here](https://s3.amazonaws.com/hail-static/wordpress/hail-wordpress.zip)

## Configuration

### Register an OAuth client in Hail

After activating the plugin a page named Hail will become available under settings.
In order to connect to Hail and start bringing in content you need to register an OAuth client in Hail. This can be done under your Hail user account developer settings here: https://hail.to/app/user/applications

The generated client_id and client_secret can now be entered into the plugin settings page. The redirect URI displayed on that page must also be added into Hail.

Follow the steps on the plugin page to authorize and go through the OAuth flow.

The helper functions and shortcodes can be used at this point, but you probably want to enter a primary tag ID for the import process.

### The import process

Articles are imported into a custom post type named "hail_article". This content can't be edited within Wordpress, as it would just be overwritten by the Hail content on import.

The import process requests from the Hail API all articles which are tagged with the private tag specified in the configuration. The modification timestamps are compared with that has previously been imported, and only changed content is updated.

The plugin registers a WP Cron task which is set on an hourly schedule which runs the import process.

Most attributes of the imported content are stored as post_meta.

### Viewing imported articles / CPT template.

After you've imported some Hail articles, you'll probably notice that they look like crap. This is likely because the custom post type doesn't have a template associated with it.

As is standard practice in Wordpress, custom post types require a theme template file named appropriately. In this case you'll need to create a file named single-hail_article.php in your theme (or child theme) folder.

In order to keep the design of that page consistent with the rest of your site it's easiest just to copy any containing markup / sidebars / widget inclusions etc. from one of the other theme templates.

To actually show the content within the template, here is an example of accessing and displaying the data which has been imported for a given article:

```php
<?php

get_header();

// get an instance of the Hail Helper which allows you to request any additional data from the API
$hail = Hail_Helper::getInstance();

// most of the data is stored in post_meta, so let's grab that
$post_id = get_the_ID();
$hail_id = get_post_meta($post_id, 'hail_id', true);
$title = get_the_title();
$lead = get_post_meta($post_id, 'lead', true);
$body = get_post_meta($post_id, 'body', true);
$hero_id = get_post_meta($post_id, 'hero_id', true);
$hero_type = get_post_meta($post_id, 'hero_type', true);

$hero_image = $hero_video = null;

// the data for the actual article hero image isn't imported so let's fetch that
if ($hero_type == 'image') {
  $hero_image = $hail->getImage($hero_id);
// heroes can also be videos
} else if ($hero_type == 'video') {
  $hero_video = $hail->getVideo($hero_id);
}

?>

<!-- dummy markup -->
<div class="page-header">
  <h1><?php echo $title ?></h1>
</div>

<div class="site-content">
  <article>

    <?php if ($hero_image) { ?>

      <!-- show the hero image that we fetched -->
      <div class="featured-image">
        <img src="<?php echo $hero_image['file_1000_url'] ?>" class="img-responsive wp-post-image">
        <div class="caption"><?php echo $hero_image['caption'] ?></div>
      </div>

    <?php } else if ($hero_video) { ?>

      <!-- if the hero was a video then just show the preview image -->
      <div class="featured_image">
        <img src="<?php echo $hero_video['preview']['file_1000_url'] ?>" class="img-responsive wp-post-image">
        <div class="caption"><?php echo $hero_video['caption'] ?></div>
      </div>

    <?php } ?>

    <div class="entry-content">
      <!-- show the lead and body (body contains paragraph tags, but lead doesn't) -->
      <p><?php echo $lead; ?></p>
      <?php echo $body; ?>
    </div>

  </article>
</div>
```

### The Hail Helper

Helper functions for requesting Hail API data are provided by the Hail_Helper class.

As per the example above you can get an instance of this class like so:
```php
<?php
$hail = Hail_Helper::getInstance();
?>
```
The following helper functions are available:
```php
<?php
// get an article by it's ID
$hail->getArticle($article_id);

// get an image by it's ID
$hail->getImage($image_id);

// get a video by it's ID
$hail->getVideo($video_id);

// get array of articles by private tag ID, with optional caching
$hail->getArticlesByPrivateTag($private_tag_id, true);

// get array of an article's attached images by article ID
$hail->getArticleImages($article_id, true);

// get array of publications by private tag ID, with optional caching
$hail->getPublicationsByPrivateTag($private_tag_id, true);
?>
```

Caching defaults to true on the functions accept it, and utilizes set_transient for caching of API JSON results. Cache times can't currently be edited and are set at 3 minutes.

### Shortcodes

Basic shortcode functionality is provided:

```[hail_content columns=2 display_hero=true orderby=date order=asc]```

## FAQ?
