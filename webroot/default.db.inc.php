<?php
/**
 * @file
 * DB-include, should be named db.inc.php.
 */

define('YEAR', '2013');
define('DB', 'dbname');
define('DBUSER', 'dbuser');
define('DBPASS', 'dbpass');

$mysqli = new mysqli("localhost", DBUSER, DBPASS, DB);

/* check connection */
if (mysqli_connect_errno()) {
  printf("Connect failed: %s\n", mysqli_connect_error());
  exit();
}
