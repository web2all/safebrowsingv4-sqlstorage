# Web2All safebrowsingv4-sqlstorage

This package provides a sql storage backend for the `web2all/safebrowsingv4` package. This storage backend has been built with the `web2all/framework`.

The `web2all/safebrowsingv4-sqlstorage` is no longer actively maintained.

## What is in this package ##

It contains a `GoogleSafeBrowsing_Updater_IStorage` and `GoogleSafeBrowsing_Lookup_IStorage` implementation which stores its data in a database. Only tested with MySQL and sqlite (see tests).

## Usage ##

Install using composer (eg. `composer create-project web2all/safebrowsingv4-sqlstorage`).

See documentation of `web2all/safebrowsingv4` for usage and replace the `$storage` by:

    $storage = $web2all->Plugin->Web2All_GoogleSafeBrowsing_SQLStorage_Engine($db);

Where $db is a `ADOConnection` object (see `adodb/adodb-php` package) to the database where the hash prefixes are to be stored.

To initialize the database, import the schema from the `database` directory. Currently there is only a scheme for mysql, but if you convert it to your database brand it will probably work.

## License ##

Web2All safebrowsingv4 is open-sourced software licensed under the MIT license ([https://opensource.org/licenses/MIT](https://opensource.org/licenses/MIT "license")).
