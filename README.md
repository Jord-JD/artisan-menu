# 📝 Artisan Menu

Use Artisan via an elegant console GUI

<p align="center">
    <img src="assets/images/artisan-menu.gif">
</p>

## Features

* Run built-in and custom Artisan commands from a console GUI
* Prompts for required and optional command arguments
* Presents command-specific options, including flags, values, and repeatable options
* Escape cancels required input without running the command
* Displays most recently used commands

## Installation

To install, just run the following Composer command from the root of your project.

```bash
composer require --dev jord-jd/artisan-menu
```

Artisan Menu supports Laravel 5.1 through 13 and PHP 7.1 or newer.

If you are using Laravel 5.4 or below, you may need to add `JordJD\ArtisanMenu\Providers\ArtisanMenuServiceProvider::class` to the `providers` array in your `config/app.php` file.


## Usage

Artisan menu is designed to be very intuitive to use. Just run the following 
command to start it.

```bash
php artisan menu
```

From then on, just select the commands you wish to run. After the command
has completed, a dialog will appear showing its results.

Required arguments are requested first. Press Escape to cancel without running the
command. Optional arguments and command-specific options follow; leave a prompt blank
to skip it. For repeatable arguments or options, enter comma-separated values.
