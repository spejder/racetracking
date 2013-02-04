<?php
define("YEAR", "2009");
include("db.inc.php");
$mysqli = new mysqli("localhost", DBUSER, DBPASS, DB);

/* check connection */
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}

$posts = array();
// get all posts
if ($stmt = $mysqli->prepare("SELECT p.name, p.postid, p.description FROM post p order by name")) {
    /* execute query */
    $stmt->execute();

    /* bind result variables */
    $stmt->bind_result($name, $id, $description);

    /* fetch value */
    while ($stmt->fetch()) {
        $posts[$name] = array("name" => $name, "postid" => $id, "description" => $description);
    }

    /* close statement */
    $stmt->close();
}

// get all teams, sorted
$teams = array();
if ($stmt = $mysqli->prepare("select t.name, t.teamid from team t order by t.name")) {
    /* execute query */
    $stmt->execute();

    /* bind result variables */
    $stmt->bind_result($name, $teamid);

    /* fetch value */
    while ($stmt->fetch()) {
        $teams[] = array('name' => $name, 'teamid' => $teamid);
    }

    /* close statement */
    $stmt->close();
}

// get visit data
$visits = array();
if ($stmt = $mysqli->prepare("select t.teamid, v.postid, t.name, UNIX_TIMESTAMP(time), v.type from team t  JOIN visit v on (t.teamid = v.teamid) order by v.time")) {
    /* execute query */
    $stmt->execute();

    /* bind result variables */
    $stmt->bind_result($teamid, $postid, $name, $time, $type);

    /* fetch value */
    while ($stmt->fetch()) {
        $visits[$teamid][$postid][$type] = $time;
    }

    /* close statement */
    $stmt->close();
}


/* close connection */
$mysqli->close();

$filename = "ccmr-loeb-status-" . date("YmdHis") . ".csv";

header("Content-type: reapplication/vnd.ms-excel");
header("Content-Disposition: attachment; filename=$filename");


 foreach($teams as $team){
    print(';"' . $team['name'] . '"');
 }
print("\n");
foreach($posts as $post){
    print('"' . $post['name'] . '"');

    foreach($teams as $team){
        // seperator and new line
        print(';"');
        $teamid = $team['teamid'];
        $postid = $post['postid'];

        if(isset($visits[$teamid][$postid]['a'])){
            print("ank " . date( 'H:i',$visits[$teamid][$postid]['a']));
        }

        print("\r\n");
        

        if(isset($visits[$teamid][$postid]['d'])){
            print("afg " . date( 'H:i',$visits[$teamid][$postid]['d']));
        }

        print("\r\n");
	
	if(isset($visits[$teamid][$postid]['x'])){
	    print("X");
	}

        // end quote and new line
        print('"');
    }
    print("\n");
}
?>