# Readme

This is the GitHub repository for the Zee PHP framework. Zee is a barebones framework designed for optimal scalability and adaptability; it has a custom implementation of the MVC standard, automating the model system and simplying the controller-view relationship while still maintaining all the benefits of MVC.

Zee is extremely lightweight and easy to comprehend, but still packs a punch of power.

## Creating  a controller

To create a controller, simply create a file in your desired area, include the `Init.php` file, declare the view and you're good to go! All views must be created under the `views` directory.

Example (With a view called index.php):

    <?php
    
    require 'Init.php';
    
    $page['id'] = 'index';

Example (With a view called users/profile.php) and titled "My Profile":

    <?php
    
    require 'Init.php';
    
    $page['id'] = 'users/profile';
    $page['name'] = 'My Profile';

## Classes

- Zee Namespace
 - __Condition__: Parses expressions that represent statements in SQL queries, used most commonly in conjuction with models.
 - __Config__: Class used to read and write from configuration files found under the `config` directory.
 - __Exception__: Zee exception class
 - __Input__: Provides simple methods of validating POST data and other strings.
 - __Kernel__: Core class of Zee, processes gates, dynamic model creation, output buffering handling, exceptions and more.
 - __Model__: Master model class, all models extend from this.
 - __Output__: Processes instructions and uses PHP's output buffering to render view.
 - __Property__: Used to access objects through a view using a simple volt-like command in the view.

Zee also comes already installed with Composer. Its model class is powered  by PHP's PDO library.

## Creating configuration files

The configuration system of Zee provides an easy way of creating a completely web-based administration suite, it also gives room to develop CLI applications and APIs that can interact directly with Zee.

Just create a file in Zee's `config` directory in order to have Zee parse it, it will then be available via a global function called `Config()`, you may access properties like this:

    Config('File')->Get('Property');

You can also modify properties by using the `Set` method:

    Config('File')->Set('Property', 'Value');

## Using Zee's Model system

Triggering model creation via Zee can be done in a single line; simply call any method using the table's name as the class name in a static context. In order for Zee to access the table, it must be completely lowercase in the MySQL database.

    \Zee\Data\Table::Select('*');

This method will create a file called `Table.class.php` under Zee's `Data` directory. It will only be created once, which provides you with the ability to add additional methods to specific models.

All Zee Models are located under the `Zee\Data` namespace.

## Using Condition
 
Condition is a simple class used to express part of a statement in a SQL statement. It supports __WHERE__, __LIMIT__, __ORDER__ and __LIKE__ statements.
 
It also has a simple implementation of checking whether a value is greater than or less than when doing a __WHERE__ statement. The condition class should be instantiated directly, rather, it should only be accessed by its Model class.

    \Zee\Data\Table::Condition(['w(column, value)'], ['o(date_created, DESC)']);

You can then either access methods from the instantiated object directly or store it in a variable to access the same condition in different queries. Like, so:

    $Admins = \Zee\Data\Table::Condition(['w(is_admin, yes)'], ['li(5)']);

This statement would grab all the rows with the value `is_admin` set to `yes`, it would also only pull the first 5 rows.

You can then invoke the fetch by using the `Select` method:

    $Admins->Select('*');

This same applies to the `Update` and `Delete` methods. The `Delete` method actually __requires__ to be invoked from a  Condition.

## Properties and Lexicon

The Output/Rendering class features three tokens that can be processed in a view:

 - Functions (`{Class::Method()}`);
 - Site Variables (Somewhat like constants) (`%Variable%`)
 - Object Tokens (`[Object->Property]`)

The function token is structured similar as to how it would be structured in PHP. Simply specify the namespace and class, and then provide a method that is present in that class. This token will only work with static methods.

The site variables token in the other hand needs to be accessed directly via the Kernel. You can add global site variables by modifying the `SendBuffer` method in the Kernel or by modifying its `SiteVars` static property.

Lastly, Object Tokens give you a gateway into instantiated methods. It can be used in conjunction with frontend rendering frameworks but is mostly recommended as a simple way of placing dynamic data in a view without having to use PHP.

First, create the object you wish to pass to the Output class:

    $Object = new User('John Doe');

Then, simply register then object with the `Property` class:

    \Zee\Property::Register('User', $Object); // User being the "label" the Output class will give the object

You can then access any of its properties in the controller's respective view by using the `[User->Property]` token. You can also use gates to register view properties.

## Gates

Gates are a simple way of repeating a procedure, running a script, or registering an object across various pages with a single statement.

To build a gate, simply create a file with the format `Gatename.gate.php` in the `gates` directory. Then, proceed to the `config` directory and edit the `Gates.json` configuration file.

In order to specify where each gate will run, simply use `glob`-like expressions. If I made a gate called `MyGate` and wanted it to run on all controllers that end in `-Account`, I would instruct Zee as such:

    {
	    "MyGate" : [
		    "*-Account.php"
	    ]
    }

In the actual file, the function that is ran is accessed via an anonymous function with the name of the variable hosting the function being `[MyGate]GateFunction`, so my file could look like this:

    <?php
    $MyGateGateFunction = function()
    {
	    $User = new User('MyUser'); // Not a Zee object
	    
	    return $User;
    }

The return value of the gate can be accessed via the `$page` global, it is an element called `gate`.

Example: `$page['gate']->Get('Username'); // Not a Zee method...`