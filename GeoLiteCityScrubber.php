<?php
/**
 * Anthony W.
 * ant92083@gmail.com
 * This script will take a path for the GeoLite City Location database and parse the CSV file.
 * Using PDO it will batch transactions into the database and insert them into something that is
 * searchable using our provided class
 */
$start = time();

function displayPDOError(PDO $pdo) {
    echo $pdo->errorCode().':'.$pdo->errorInfo().PHP_EOL;
}

function displayPDOStatementError(PDOStatement $stmt) {
    echo $stmt->errorCode().':'.$stmt->errorInfo().PHP_EOL;
}

/**
 * A word about these configuration settings.  They are examples, please change accordingly
 * to your needs.
 */
$database = 'GeoLiteCityLocations';
$host = 'localhost';
$username = 'admin';
$password = '123456';

if($argc != 2 || count($argv) != 2 || !isset($argv[1])) {
    echo 'You must supply a path to the GeoLite City CSV file.'.PHP_EOL;
    return 0;
}

$file = $argv[1];

/**
 * We can assume at this point that a path has been passed into the script to be used
 */
if(!file_exists($file)) {
    echo "The file specified by path: $file does not exist.".PHP_EOL;
    return 0;
}

/**
 * If the file exists lets open a handle to it.
 */
$resource = fopen($file, 'r');
if($resource == false) {
    echo 'We could not open a handle to the file supplied.'.PHP_EOL;
    return 0;
}

/**
 * If the resource is valid lets open up a database connection
 */
try {
    $db = new PDO("mysql:host=$host;dbname=$database", $username, $password);
} catch (PDOException $e) {
    displayPDOError($db);
    echo 'Could not connect to the database.'.PHP_EOL;
    echo $e->getMessage() . PHP_EOL;
    return 0;
}

/**
 * Create the database if it doesn't exist.
 */
$sql = "CREATE DATABASE IF NOT EXISTS $database;";
if($db->exec($sql) === FALSE) {
    displayPDOError($pdo);
    echo 'Could not execute create database query, please check the SQL query.'.PHP_EOL;
    return 0;
}

/**
 * Create a simple SQL table to hold the data, this can be customzied to your use.
 */
$sql =<<<SQL
    CREATE TABLE IF NOT EXISTS Locations (
      locId INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
      country VARCHAR(6) DEFAULT '',
      region VARCHAR(6) DEFAULT '',
      city VARCHAR(32) DEFAULT '',
      postalCode VARCHAR(6) DEFAULT '',
      latitude DECIMAL(4,4) DEFAULT NULL,
      longitude DECIMAL(4,4) DEFAULT NULL,
      metroCode INT DEFAULT 0,
      areaCode INT DEFAULT 0
    ) ENGINE = InnoDb;
SQL;

if($db->exec($sql) === FALSE) {
    displayPDOError($db);
    echo 'Could not execute create table query, please check the SQL query.'.PHP_EOL;
}

/**
 * The GeoLite City database updates every month.  This may add or remove unique id's,
 * we truncate our table if it already exists.
 */
$sql = "TRUNCATE Locations";
if($db->exec($sql) === FALSE) {
    displayPDOError($pdo);
    echo 'Could not truncate the Locations table.'.PHP_EOL;
    return 0;
}

/**
 * Using PDOStatement insert our data into the database.
 */
$sql =<<<SQL
    INSERT INTO Locations(locId, country, region, city, postalCode, latitude, longitude, metroCode, areaCode) VALUES
    (?,?,?,?,?,?,?,?,?);
SQL;

$statement = $db->prepare($sql);
$rowCount = 0;

/**
 * Begin a transaction, batches of 10000 rows make things a lot easier.
 */
if(!$db->beginTransaction()) {
    displayPDOError($pdo);
    echo 'Could not begin SQL transaction.'.PHP_EOL;
    return 0;
}

/**
 * Process the CSV file.
 */
while($row = fgetcsv($resource, null, ',', '"')) {
    $rowCount+=1;
    if($rowCount > 2) {
        if(!$statement->execute($row)) {
            // something failed, lets back out of the system.
            displayPDOStatementError($statement);
            echo 'Could not execute SQL statement.'.PHP_EOL;
            $db->rollBack();
            return 0;
        }
    }

    if(($rowCount % 50000) == 0) {
        if(!$db->commit()) {
            displayPDOError($db);
            echo 'Could not commit rows to the database.'.PHP_EOL;
            $db->rollBack();
            return 0;
        }

        if(!$db->beginTransaction()) {
            displayPDOError($db);
            echo 'Could not begin SQL transaction.'.PHP_EOL;
            return 0;
        }
    }
}

if(!$db->commit()) {
    displayPDOError($db);
    echo 'Could not commit rows to the database.'.PHP_EOL;
    $db->rollBack();
    return 0;
}

$end = time();
$total = $end - $start;
echo "You have processed $rowCount rows in $total seconds.".PHP_EOL;
?>
