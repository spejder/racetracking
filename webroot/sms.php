
<?php
require_once('db.inc.php');

$mysqli = new mysqli("localhost", DBUSER, DBPASS, DB);

/* check connection */
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}

report($_REQUEST['message'], $mysqli);

$mysqli->close();


function report($input, $mysqli){
    preg_match("/spejder ([a-z0-9]+) (p[0-9]{1,2}) (h[0-9]{1,2}) ([0-9]{3,4}) ([0-9]{3,4})( x)?/i", $input, $matches);


    $keyword = $matches[1];
    $post_smskey = $matches[2];
    $team_smskey = $matches[3];

    $arrival_time = $matches[4];
    $departure_time = $matches[5];

    $straf = false;
    if(strtolower(trim($matches[6])) == "x"){
	   $straf = true;
    }

    // get teamid
    $teamid = get_team_id($team_smskey, $mysqli);
    $postid = get_post_id($post_smskey, $mysqli);

    $year = date("Y");
    $month = date("n");
    $day = date("j");


    if($teamid && $postid){
        if(strlen($arrival_time) == 3){
            $arrival_time = mktime(substr($arrival_time,0,1), substr($arrival_time, 1), 0, $month, $day, $year);
        }else{
            $arrival_time = mktime(substr($arrival_time,0,2), substr($arrival_time, 2), 0, $month, $day, $year);
        }

        if(strlen($departure_time) == 3){
            $departure_time = mktime(substr($departure_time,0,1), substr($departure_time, 1), 0, $month, $day, $year);
        }else{
            $departure_time = mktime(substr($departure_time,0,2), substr($departure_time, 2), 0, $month, $day, $year);
        }

        if($departure_time < $arrival_time){
            // we've crossed into a new day, subtract a day
            $arrival_time -= 60*60*24;
        }

        $departure_time = date("Y-m-d H:i:00",$departure_time);
        $arrival_time = date("Y-m-d H:i:00",$arrival_time);

        if ($stmt = $mysqli->prepare("INSERT INTO visit (teamid, postid, type, time) VALUES (?,?,?,?)")) {
            $type = "a";
            $stmt->bind_param("iiss", $teamid, $postid, $type, $arrival_time);
            $stmt->execute();

            $type = "d";
            $stmt->bind_param("iiss", $teamid, $postid, $type, $departure_time);
            $stmt->execute();

	    $stmt->close();
	    $stmt = $mysqli->prepare("INSERT INTO visit (teamid, postid, type, time) VALUES (?,?,?,NOW())");
            if($straf){
		$type ="x";
		$stmt->bind_param("iis", $teamid, $postid, $type);
		$stmt->execute();
	    }

	    $stmt->close();

            print("Modtaget, hold $teamid ankommet til post $postid kl $arrival_time afgang kl $departure_time");
	    if($straf){
		print(" (+strafpost)");
	    }
        }


    }else{
        print("Ugyldig kommando, syntax: spejder $keyword p<postnr> h<holdnr> <ankomst> <afgang> [x]\n");
        print("Eksempel 1: spejder $keyword p1 h2 2200 2300\n");
        print("Eksempel 2: spejder $keyword p3 h4 2300 0200 x");
    }

}


function get_team_id($team_smskey, $mysqli){
    if ($stmt = $mysqli->prepare("SELECT teamid FROM team where smskey = ?")) {
    /* execute query */
        $stmt->bind_param('s', $team_smskey);
        $stmt->execute();

    /* bind result variables */
        $stmt->bind_result($teamid);

        if($stmt->fetch()){
            $return = $teamid;
        }else{
            $return = false;
        }
        $stmt->close();

        return $return;
    }else{
        return false;
    }
}

function get_post_id($post_smskey, $mysqli){
    if ($stmt = $mysqli->prepare("SELECT postid FROM post where smskey = ?")) {
    /* execute query */
        $stmt->bind_param('s', $post_smskey);

        $stmt->execute();

    /* bind result variables */
        $stmt->bind_result($teamid);

        if($stmt->fetch()){
            $return = $teamid;
        }else{
            $return = false;
        }
        $stmt->close();

        return $return;
    }else{
        return false;
    }
}




