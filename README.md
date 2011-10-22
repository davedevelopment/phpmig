Phpmig
======

What is it?
-----------

Phpmig is a (database) migration tool for php, that should be adaptable for use with most PHP 5.3+ projects. It's kind of like [doctrine migrations][doctrinemigrations], without the [doctrine][doctrine]. Although you can use doctrine if you want. And ironically, I use doctrine in my examples.

How does it work?
-----------------

    $ phpmig migrate

Phpmig aims to be vendor/framework independant, and in doing so, requires you to do a little bit of work up front to use it.

Phpmig requires a bootstrap file, that must return an object that implements the ArrayAccess interface with several predefined keys. We recommend returning an instance of [Pimple][pimple], a simple dependency injection container (there's a version bundled at \Phpmig\Pimple\Pimple). This is also an ideal opportunity to expose your own services to the migrations themselves, which have access to the container. 

Getting Started
---------------

The best way to install phpmig is using pear

    $ sudo pear channel-discover pear.atstsolutions.co.uk
    $ sudo pear install atst/phpmig-alpha

Phpmig can do a little configuring for you to get started, go to the root of your project and:

    $ phpmig init
    +d ./migrations Place your migration files in here
    +f ./phpmig.php Create services in here
    $ 

It can generate migrations, but you have to tell it where. Phpmig gets you to supply it with a list of migrations, so it doesn't know where to put them.  Migration files should be named versionnumber_name.php, where version number is made up of 0-9 and name is CamelCase or snake\_case. Each migration file should contain a class with the same name as the file in CamelCase.

    $ phpmig generate AddRatingToLolCats ./migrations
    +f ./migrations/20111018171411_AddRatingToLolCats.php
    $ phpmig status

     Status   Migration ID    Migration Name 
    -----------------------------------------
       down  20111018171929  AddRatingToLolCats


Use the migrate command to run migrations

    $ phpmig migrate
     == 20111018171411 AddRatingToLolCats migrating
     == 20111018171411 AddRatingToLolCats migrated 0.0005s
    $ phpmig status

     Status   Migration ID    Migration Name 
    -----------------------------------------
         up  20111018171929  AddRatingToLolCats

    $

Better Persistence
------------------

The init command creates a bootstrap file that specifies a flat file to use to
track which migrations have been run, which isn't great. You can use the
provided adapters to store this information in your database. For example, to
use Doctrine's DBAL:

``` php
<?php

# phpmig.php

// do some autoloading of Doctrine here

use \Phpmig\Adapter,
    \Phpmig\Pimple\Pimple,
    \Doctrine\DBAL\DriverManager;

$container = new Pimple();

$container['db'] = $container->share(function() {
    return DriverManager::getConnection(array(
        'driver' => 'pdo_sqlite',
        'path'   => __DIR__ . DIRECTORY_SEPARATOR . 'db.sqlite',
    ));
});

$container['phpmig.adapter'] = $container->share(function() use ($container) {
    return new Adapter\Doctrine\DBAL($container['db'], 'migrations');
});

$container['phpmig.migrations'] = function() {
    return glob(__DIR__ . DIRECTORY_SEPARATOR . 'migrations/*.php');
};

return $container;   
```

Writing Migrations
------------------

The migrations should extend the Phpmig\Migration\Migration class, and have
access to the container. For example, assuming you've rewritten your bootstrap
file like above:

``` php
<?php

use Phpmig\Migration\Migration;

class AddRatingToLolCats extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "ALTER TABLE `lol_cats` ADD COLUMN `rating` INT(10) UNSIGNED NULL";
        $container = $this->getContainer(); 
        $container['db']->query($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $sql = "ALTER TABLE `lol_cats` DROP COLUMN `rating`";
        $container = $this->getContainer(); 
        $container['db']->query($sql);
    }
}
```

Todo
----

* Some sort of migration manager, that will take some of the logic out of the commands for calculating which migrations have been run, which need running etc
* Adapters for Zend\_Db and/or Zend\_Db\_Table and others?
* Redo and rollback commands
* Tests!
* Configuration? 
* Someway of protecting against class definition clashes with regard to the symfony dependencies and the user supplied bootstrap?

Contributing
------------

Feel free to fork and send me pull requests, but I don't have a 1.0 release yet, so I may change the API quite frequently. If you want to implement somthing that I might easily break, please drop me an email

Inspiration
-----------

I basically started copying [ActiveRecord::Migrations][activerecordmigrations] in terms of the migration features, the bootstrapping was my own idea, the layout of the code was inspired by [Symfony][symfony] and [Behat][behat]

Copyright
---------

[Pimple][pimple] is copyright Fabien Potencier. Everything I haven't copied from anyone else is Copyright (c) 2011 Dave Marshall. See LICENCE for further details


[pimple]:https://github.com/fabpot/Pimple
[doctrinemigrations]:https://github.com/doctrine/migrations
[doctrine]:https://github.com/doctrine
[behat]:http://behat.org/
[symfony]:http://symfony.com/
[activerecordmigrations]:http://api.rubyonrails.org/classes/ActiveRecord/Migration.html
