This bundle is in development and is currently not operational

Add a command to generate nelmio api doc

Installation
============

Applications that use Symfony Flex
----------------------------------

Open a command console, enter your project directory and execute:

```console
$ composer require kbunel/nelmio-api-doc-generator --dev
```

Applications that don't use Symfony Flex
----------------------------------------

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require kbunel/nelmio-api-doc-generator --dev
```

This command requires you to have Composer installed globally, as explained
in the [installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `app/AppKernel.php` file of your project:

```php
<?php
// app/AppKernel.php

// ...
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new NelmioApiDocGenerator\NelmioApiDocGeneratorBundle();
        );

        // ...
    }

    // ...
}
```

NelmioApiDocGeneratorBundle configuration
============

```yaml

```

Command
============

To generate the tests, run:

```console
$ php bin/console kbunel:behat:generate-test
```

Available options
----------------------------------

##### Add a tag:

```console
$ php bin/console kbunel:behat:generate-test tag=new
```

##### Generate tests from a specific controller with his namespace:

```console
$ php bin/console kbunel:behat:generate-test namespace='App\Controller\MyController'
```

##### Generate tests for specifics method (separated by a comma):

```console
$ php bin/console kbunel:behat:generate-test methods='put,patch'
```

##### Generate tests from a specific namespace:

This option will get all routes from controllers whose namespace begin by the specified one

```console
$ php bin/console kbunel:behat:generate-test fromNamespace='App\Controller\Users'
```

Available configuration
----------------------------------
```yaml
behat_test_generator:
    fixtures:
        folder: 'path/to/the/fixtures/folder'
    features:
        commonFixtures: 'NameOfTheCommonFileFixtures.yaml' # Path to the file used to add the common fixture with the fixtures generated
        authenticationEmails:
            ^admin: 'super_admin@test.com' # ['route_regex' => 'email_used']
            ^user: 'user@test.com'
            # ...
        httpResponses: # http responses used with test expectations
            get: 200
            put: 204
            patch: 204
            post: 201
            delete: 204
```