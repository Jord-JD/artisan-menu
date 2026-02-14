# 📝 Artisan Menu

Use Artisan via an elegant console GUI

<p align="center">
    <img src="assets/images/artisan-menu.gif">
</p>

## Features

* Run built-in and custom Artisan commands from a console GUI
* Prompts to enter required command arguments
* Displays most recently used commands

## Installation

To install, just run the following Composer command from the root of your project.

```bash
composer require --dev jord-jd/artisan-menu
```

If you using Larvel 5.4 or below, you may need to add `DivineOmega\ArtisanMenu\Providers\ArtisanMenuServiceProvider::class` to the `providers` array in your `config/app.php` file.


## Usage

Artisan menu is designed to be very intuitive to use. Just run the following 
command to start it.

```bash
php artisan menu
```

From then on, just select the commands you wish to run. After the command
has completed, a dialog will appear showing its results.
