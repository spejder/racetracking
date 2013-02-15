<?php
/**
 * @file
 * Interface to the sms system.
 */

require_once 'db.inc.php';

report($_REQUEST['message'], $mysqli);

$mysqli->close();

/**
 * Processes the input from the sms gateway.
 *
 * @param string $input
 *   String sent in via SMS.
 *
 * @param object $mysqli
 *   DB connection.
 */
function report($input, $mysqli) {
  // TODO support matching both with an without post specification,
  // if spec is missing, determine post via number associated to the post.
  preg_match("/spejder ([a-z0-9]+) (p[0-9]{1,2}) (h[0-9]{1,2}) ([0-9]{3,4}) ([0-9]{3,4})?/i", $input, $matches);

  $keyword = $matches[1];
  $post_smskey = $matches[2];
  $team_smskey = $matches[3];

  $arrival_time = $matches[4];
  $departure_time = $matches[5];

  // Get teamid.
  $teamid = get_team_id($team_smskey, $mysqli);
  $postid = get_post_id($post_smskey, $mysqli);

  $year = date("Y");
  $month = date("n");
  $day = date("j");


  if ($teamid && $postid) {
    if (strlen($arrival_time) == 3) {
      // Eg 01:00 as 100.
      $arrival_time = mktime(substr($arrival_time, 0, 1), substr($arrival_time, 1), 0, $month, $day, $year);
    }
    else {
      // Eg 01:00 as 0100 or 23:00 as 2300
      $arrival_time = mktime(substr($arrival_time, 0, 2), substr($arrival_time, 2), 0, $month, $day, $year);
    }

    // Same handling as above.
    if (strlen($departure_time) == 3) {
      $departure_time = mktime(substr($departure_time, 0, 1), substr($departure_time, 1), 0, $month, $day, $year);
    }
    else {
      $departure_time = mktime(substr($departure_time, 0, 2), substr($departure_time, 2), 0, $month, $day, $year);
    }

    if ($departure_time < $arrival_time) {
      // we've crossed into a new day, subtract a day.
      $arrival_time -= 60 * 60 * 24;
    }

    // Unix-timestamps created, now format them.
    $departure_time = date("Y-m-d H:i:00", $departure_time);
    $arrival_time = date("Y-m-d H:i:00", $arrival_time);

    // And insert the visit into the database.
    if ($stmt = $mysqli->prepare("INSERT INTO visit (teamid, postid, type, time) VALUES (?,?,?,?)")) {
      $type = "a";
      $stmt->bind_param("iiss", $teamid, $postid, $type, $arrival_time);
      $stmt->execute();

      $type = "d";
      $stmt->bind_param("iiss", $teamid, $postid, $type, $departure_time);
      $stmt->execute();

      $stmt->close();

      print ("Modtaget, hold $teamid ankommet til post $postid kl $arrival_time afgang kl $departure_time");
    }
  }
  else {
    // TODO: Describe the usage without post name
    print ("Ugyldig kommando, syntax: spejder $keyword p<postnr> h<holdnr> <ankomst> <afgang> [x]\n");
    print ("Eksempel 1: spejder $keyword p1 h2 2200 2300\n");
    print ("Eksempel 2: spejder $keyword h4 2300 0200");
  }
}

/**
 * Git a teams id via its smskey.
 *
 * @param string $team_smskey
 *   The short smskey identifier.
 *
 * @param object $mysqli
 *   An initialised database connection.
 *
 * @return int
 *   The id.
 */
function get_team_id($team_smskey, $mysqli) {
  if ($stmt = $mysqli->prepare("SELECT teamid FROM team where smskey = ?")) {
    // Execute query.
    $stmt->bind_param('s', $team_smskey);
    $stmt->execute();

    // Bind result variables.
    $stmt->bind_result($teamid);

    if ($stmt->fetch()) {
      $return = $teamid;
    }
    else {
      $return = FALSE;
    }
    $stmt->close();

    return $return;
  }
  else {
    return FALSE;
  }
}

/**
 * Git a posts id via its smskey.
 *
 * @param string $post_smskey
 *   The short smskey identifier.
 *
 * @param object $mysqli
 *   An initialised database connection.
 *
 * @return int
 *   The id.
 */
function get_post_id($post_smskey, $mysqli) {
  if ($stmt = $mysqli->prepare("SELECT postid FROM post where smskey = ?")) {
    // Execute query.
    $stmt->bind_param('s', $post_smskey);
    $stmt->execute();

    // Bind result variables.
    $stmt->bind_result($teamid);

    if ($stmt->fetch()) {
      $return = $teamid;
    }
    else {
      $return = FALSE;
    }
    $stmt->close();

    return $return;
  }
  else {
    return FALSE;
  }
}
