# avatar_overlay_generator

A tool to place overlays over social media avatars.

## Purpose

The software in this repository lets end-users add an optical overlay to their social media pictures or photos. This
makes sense to advertise events such as the [AugustRiseUp in Berlin](https://augustriseup.de/), for which I created this
software.

## Hosted version

This repo’s software runs at https://klimainitiativen.info/avatar_generator

## Installation

This repository forms a [Drupal](https://www.drupal.org/) module. To use it, you need a working instance of Drupal 8 or higher.

You can install the module using Composer by executing the following in your server's shell (e.g., Bash):

```bash
composer require fonata/avatar_overlay_generator
drush en avatar_overlay_generator
```

Now, the URLs defined in `avatar_overlay_generator.routing.yml` will be served by your Drupal instance.

## Configuration for Drupal

There are no configuration options.

## Extending the module

The most important task is to add more overlays

1. Create an `.svg` file and a `.png` file with the same name. The .svg will be used as the overlay and the .png as a
   preview.
2. Place both files in proper `overlays` subdirectory of your server.
3. If you want to give the overlays back to the community, create a pull request
   at https://github.com/gogowitsch/avatar_overlay_generator/pulls

To serve multiple URLs with different sets of overlays, these steps are needed:

1. Create a new class that extends `GenericAvatarForm.php`.
2. Create a route that links the new class with a new URL (see `avatar_overlay_generator.routing.yml`)
3. Create a directory with one or more png & svg file pairs and reference the directory in the new class.

## Bugs/Features/Patches:

If you want to report bugs, feature requests, or submit a patch, please do so at the project page on the GitHub website:
https://github.com/gogowitsch/avatar_overlay_generator/issues

## Author

Christian Bläul:

- 📧 [christian@blaeul.de](mailto:christian@blaeul.de)
- 📑 [linkedin.com/in/blaeul](https://www.linkedin.com/in/blaeul)

The author can also be contacted for paid work on Drupal or other web applications.
