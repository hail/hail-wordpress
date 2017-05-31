# Hail-Wordpress
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A Wordpress plugin to allow importing of Hail content and the use of shortcodes for displaying of Hail content. https://get.hail.to

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

## FAQ

## Changelog
