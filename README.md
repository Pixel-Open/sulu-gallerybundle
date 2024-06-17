# Gallery Bundle

![GitHub release (with filter)](https://img.shields.io/github/v/release/Pixel-Developpement/sulu-gallery-bundle?style=for-the-badge)
[![Dependency](https://img.shields.io/badge/sulu-2.5-cca000.svg?style=for-the-badge)](https://sulu.io/)

## Presentation

A bundle for managing image galleries.
The galleries are pages the allows you to display pictures (of a place of interest for instance). 

## Features
* Default image via settings
* Translation
* Search
* Preview of the page in the edit form

## Requirement

* PHP >= 7.4
* Sulu >= 2.4
* Symfony >= 5.4
* Composer

## Installation

### Install the bundle

Execute the following [composer](https://getcomposer.org/) command to add the bundle to the dependencies of your
project:

```bash
composer require pixelopen/sulu-gallerybundle
```

### Enable the bundle

Enable the bundle by adding it to the list of registered bundles in the `config/bundles.php` file of your project:

 ```php
 return [
     /* ... */
     Pixel\GalleryBundle\GalleryBundle::class => ['all' => true],
 ];
 ```

### Update schema
```shell script
bin/console do:sch:up --force
```

## Bundle Config

Define the Admin Api Route in `routes_admin.yaml`
```yaml
gallery.albums_api:
  type: rest
  prefix: /admin/api
  resource: pixel_gallery.albums_route_controller
  name_prefix: gallery.

gallery.settings_api:
  type: rest
  prefix: /admin/api
  resource: pixel_gallery.settings_route_controller
  name_prefix: gallery.
```

## Use
### Add/Edit a gallery
Go to the "Gallery" section in the administration interface. Then, click on "Add".
Fill the fields that needed for your use.

Here is the list of the fields:
* Name (mandatory)
* URL (mandatory and filled automatically according to the name)
* Cover
* Images
* Description
* Location

Once you finished, click on "Save".

Your gallery is not visible on website yet. In order to do that, click on "Activate?". It should be now visible for visitors.

To edit a gallery, simply click on the pencil at the left of the gallery you wish to edit.

## Remove/Restore a gallery

There are two ways to remove a gallery:
* Check every gallery you want to remove and then click on "Delete"
* Go to the detail of a gallrey (see the above section) and click on "Delete".

In both cases, the gallery will be put in the trash.

To access the trash, go to the "Settings" and click on "Trash".
To restore a gallery, click on the clock at the left. Confirm the restore. You will be redirected to the detail of the gallery you restored.

To remove permanently a gallery, check all the galleries you want to remove and click on "Delete".

## Settings

This bundle comes with settings. There is only one setting, it's the configuration of a default image.

To use the settings, you need to call the **gallery_settings** twig function in the template you need to.
This function don't take any parameters

Example of use:

```twig
{% set gallerySettings = gallery_settings() %}
{% if album.cover is not empty %}
    {% set cover = sulu_resolve_media(album.cover.id, 'en' %}
    <img src="{{ cover.thumbnails['991x'] }}" alt="{{ album.name }}">
{% else %}
    {% set default = sulu_resolve_media(gallerySettings.defaultImage.id, 'en' %}
    <img src="{{ default.thumbnails['991x'] }}" alt="Default gallery image">
{% endif %} 
```

## Contributing
You can contribute to this bundle. The only thing you must do is respect the coding standard we implements.
You can find them in the `ecs.php` file.
