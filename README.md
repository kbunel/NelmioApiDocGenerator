This bundle is in development and is currently not operational

Add a command to generate nelmio api doc

Installation
============

Applications that use Symfony Flex
----------------------------------

Open a command console, enter your project directory and execute:

```console
$ composer require kbunel/nelmio-api-doc-generator --dev --update-with-dependencies
```

Applications that don't use Symfony Flex
----------------------------------------

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require kbunel/nelmio-api-doc-generator --dev --update-with-dependencies
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
        if (in_array($this->getEnvironment(), array('dev', 'test'))) {
            // ...
            $bundles[] = new NelmioApiDocGenerator\NelmioApiDocGeneratorBundle();
            $bundles[] = new FileAnalyzer\FileAnalyzerBundle();
        }

        // ...
    }

    // ...
}
```

NelmioApiDocGeneratorBundle configuration
============

The generator will parse files and try to get informations.
If you use custom functions in the controtller's action to serialize your datas and have specific groups,
function that handle http responses or a return function with data's variable inside.

serialization_groups: Function that handle the serialization's group
http_responses: function that handle the http response
return: function that handle the datas returned

```yaml
nelmio_api_doc_generator:
    functions:
        serialization_groups:
            - customFunction
        http_responses:
            - customFunction
        return:
            - customFunction
```

Command
============

To generate the tests, run:

```console
$ php bin/console kbunel:nelmioApiDoc:generate
```

Available options
----------------------------------

##### Generate documentation for a specific route:

namespace: The controller Namespace.
action: The function name for the route in the controller.

```console
$ php bin/console kbunel:nelmioApiDoc:generate route=namespace::action
```
