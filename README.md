![](http://i.imgur.com/8SZWa2h.gif)


#Installation
* Make a MySQL database and import init.sql.
* Copy `includes/config.example.php` to `includes/config.php` and get editing.
* Edit `.htaccess`

On a fresh Ubuntu/Debian installation you will likely want `apt-get install apache2 php5 php5-curl php5-mysql mysql-server mysql-client` and configure your `php.ini` as follows:

````
display_errors = On
display_startup_errors = On
error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE
````

#"Support"
If you have any questions you can try your luck on the issue tracker or `##minichan @ irc.freenode.net`. Note that development is primarily geared towards running a functional [http://minichan.org](http://minichan.org) so your feature requests may not be a priority. Pull requests are welcome if they are discussed on the issue tracker or IRC (with r04r) first.

And yes. The code is shit. :-)
