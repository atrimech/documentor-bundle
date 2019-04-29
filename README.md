# MTrimechDocumentorBundle

The MTrimechDocumentorBundle adds a single task that parse all registered bundles.
The Goal is to write some README files under each parsed bundle that contains a simple documentation of routing, models and 
commands.

    * Resources/docs/Command/README.md
    * Resources/docs/Models/README.md
    * Resources/docs/Routers/README.md
    
**NOTE:** _This bundle has no dependencies, it can be installed on every symfony project version 3._

**Used components:**

    * FileSystem
    * Finder
    * Twig
    * Routing
    * Console
   
## Installation

    composer require mtrimech/documentor
    
## Configuration
app/Kernel.php

    $bundles[] = new MTrimech\DocumentorBundle\MTrimechDocumentorBundle();
    
## Screenshots

![Routers](/mtrimech/documentor-bundle/blob/master/Resources/public/img/routers.png?raw=true)


![Models](/mtrimech/documentor-bundle/blob/master/Resources/public/img/models.png?raw=true)

## Usage

    bin/console mtrimech:documentor:generator
    
## Enjoy !