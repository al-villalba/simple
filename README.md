
Motivation
==========

Simple (abreviation of SimpleMVC) is yet another PHP microframework. It's all
about routing, http request, and http response. The porpose of this framework
is to provide programmers the structure of a project following the MVC pattern
with a KISS philosofy (keep-it-stupid-simple).


File System Organigram
======================

Execution Points
----------------

<pre>
bin/www/index.php
bin/cli/appc.php
bin/tests/bootstrap.php
</pre>

Which allow run php with different server interfaces, respectively 'cgi' (or
similar), 'cli', and 'cli' with phpunit. In the third option, you can simulate
a web interface by means of the library `jelix/fakeserverconf` (see
`vendor/jelix/fakeserverconf/README.md`).

All these running points have in commom

- they instantiate the Application
- they make use of the application to achieve their tasks, respectively:
  url rendering, run a command from the shell, or run unit tests

MVC Structure
-------------

<pre>
mvc
    /controllers
    /src
    /views
</pre>

This `mvc` tree can be found either directly under PATH_ROOT or under
`vendor/simple`. Both places are under the namespace `\Simple`. You can
define your own module with your own namespace, you just need to put the 
file structure (like mvc) somewhere below PATH_ROOT (e.g.
`vendor/mymodule/mvc/...`) and register it in the autoload: See
`vendor\simple\mvc\src\bootstrap.php`
    
Executable scripts
------------------

`bin/cli` Folder containing the scripts to be run from the command line interface

`bin/tests` Folder containing unit tests

`bin/www` Folder containing the scripts to be run by the web server. I.e. the
web server's public folder

Other Top Level Paths
---------------------

`/config` Folder containing all config files

`/var` Folder containing variable/temporary files like cache or uploading files

`/vendor` Folder containing all third party tools


The Application Class
=====================

The application class is the main manager that organises the execution of an
action from the start point till the final result. Its duties are:

- Initialise the needed resources (and only those needed) to perform actions
- Organise dependencies
- Provide results to different interfaces (cgi, cli, tests)

The application class is a container which elements are services that are
accessible like an array (see
[ArrayAccess](http://php.net/manual/en/class.arrayaccess.php)). Out of the box,
the application initialises the minimum dependencies to go through an
mvc-roadmap:

- It loads the configuration settings into `$app['config']`
- It defines the pair `controller-action` that will be performed into `$app['routing']`
- It renders the output provided by the `controller-action` into `$app['output']

