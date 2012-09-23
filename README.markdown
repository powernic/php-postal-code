PHP Postal (Zip) Code Range and Distance Calculation
====================================================

**Calculate the distance between U.S. zip codes and find all zip codes within a
given distance of a known zip code.**

This project was started as a port to PHP 5 of a zip code class I wrote in 2005
for PHP 4. It also provides improvements based on suggestions from users of
the original code.

This fork has been modified to be compatible with PHP 5.3+ as well as the [GeoNames.org][1]
postal code database.


Postal Code Database
--------------------

The `PostalCode` class is based on a MySQL table or view with the following fields:

    country_code    char(2)
    postal_code     varchar(20)
    place_name      varchar(180)
    admin_name1     varchar(100)
    admin_code1     varchar(20)
    admin_name2     varchar(100)
    admin_code2     varchar(20)
    admin_name3     varchar(100)
    admin_code3     varchar(20)
    latitude        float
    longitude       float
    accuracy        tinyint(1)


While the name of this table can be specified by the `mysql_table` class property,
the default table name is `postal_codes`.

**Data Source**

The class has been modified to work with the [GeoNames.org postal code database][2], which
is licensed under a Creative Commons Attribution 3.0 License. This database can be
downloaded and imported into a MySQL database using the included schema.

### Composer

[Composer](http://getcomposer.org/) is an easy way to manage dependencies in your PHP projects. The PHP PostalCode Class can be found in the default [Packagist](http://packagist.org/) repository.

After installing Composer into your project, the PHP PostalCode Class can be installed by adding the following lines to your `composer.json` file and running the Composer command line tool:

```json
{
  "require": {
    "rubberneck/php-postal-code": "2.*"
  }
}
```

License
-------

[GNU General Public License v3][3]

[1]: http://www.geonames.org/export/
[2]: http://download.geonames.org/export/zip/
[3]: http://opensource.org/licenses/gpl-3.0.html

