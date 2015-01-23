# Laravel Admin Generator

This Laravel package provides a simple and fast way to generate an admin skeleton.

## Installation

Edit your composer.json to add this package

	"require-dev": {
		"sudobat/admin-generator": "dev-master"
	}

Then update Composer from the Terminal, run the following command on the root of your project:

	composer update --dev

When composer is done, add this provider to your providers list in app/config/app.php

	'Sudobat\AdminGenerator\AdminGeneratorServiceProvider'

If all the above went ok, you should see a new command admin:make when you run

	php artisan

## Usage

	php artisan admin:make --resources="..." [--base[="..."]] [--prefix[="..."]] [--namespace[="..."]]

Here is an explanation of the options one by one

	--resources

This is a list of the resources you want to create separated by commas

	--base

This is the path where the folder structure will be created ("app" by default)

	--prefix

This is a sub-folder that will be created inside "controllers, models, views" directories ("admin" by default)

	--namespace

This is the namespace under which all the controllers and models will be created ("" by default)