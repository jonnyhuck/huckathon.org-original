# huckathon.org
A simple, quick and dirty task manager interface for OSM Mapathons.

## Setup
To use this, you must have a [PostGIS](http://postgis.net/) database containing a set of squares covering your area of interest in a table called `grid`.

This is the structure of my `grid` table:

    Column    |          Type           |                       Modifiers                        
| ------------- | ------------- | ------------- |
 `ogc_fid`      | `integer`                 | `not null default nextval('grid_ogc_fid_seq'::regclass)`
 `id`           | `numeric(10,0)`           | 
 `wkb_geometry` | `geometry(Polygon,21096)` | 
 `status`       | `integer`                 | `not null default 0`

Indexes |
| ------------- |
"grid_pkey" PRIMARY KEY, btree (ogc_fid) |
"grid_wkb_geometry_geom_idx" gist (wkb_geometry) |


I created it automatically using [ogr2ogr](http://www.gdal.org/ogr2ogr.html), having created my squares using the [Vector Grid](https://docs.qgis.org/2.6/en/docs/user_manual/processing_algs/qgis/vector_creation_tools/vectorgrid.html) tool in [QGIS](http://www.qgis.org/en/site/).

You will also require the [PDO-pgsql](http://php.net/manual/en/ref.pdo-pgsql.connection.php) extension for [PHP](http://php.net/). This is trivially installed with:

* Ubuntu: `sudo apt install php-pgsql`
* Mac (Homebrew): `brew install php56-pdo-pgsql`

Finally, you will need to add a file called `connection.php` to the `/scripts` directory of this repository that contains the connection string for your repository in this form (replace the sections in `[]` with your own information):

```php
<?php
	$connstr = 'pgsql:dbname=[DBNAME];host=localhost;user=[USRNAME];password=[PASSWORD]';
```