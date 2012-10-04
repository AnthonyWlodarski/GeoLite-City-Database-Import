GeoLite-City-Database-Import
============================

This script will import the GeoLite City CSV file into a usable MySQL table in your database.
To use the script open a terminal and enter the following command:

	php GeoLiteCityScrubber.php /path/to/CSV/file.csv

The script will do it's best to open the file, create a handle to the data inside the file
and process each row.  Please see the section inside the file to customize your database
configuration settings.
