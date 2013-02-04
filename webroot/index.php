<?php
define("YEAR", "2013");
include("db.inc.php");
$mysqli = new mysqli("localhost", DBUSER, DBPASS, DB);

/* check connection */
if (mysqli_connect_errno()) {
    printf("Connect failed: %s\n", mysqli_connect_error());
    exit();
}

// handle actions
if($_REQUEST['action'] == 'create'){
    list($teamid, $postid, $type, $time) = array($_REQUEST['teamid'], $_REQUEST['postid'], $_REQUEST['type'], $_REQUEST['time']);
    if(isset($teamid, $postid, $type) && strlen($time) > 0){
        if(preg_match("/([0-9]{1,2})\/([0-9]{1,2}) ([0-9]{1,2}):([0-9]{2})/", $time, $matches)){
            $time_parsed = YEAR . "-" . $matches[1] . "-" . $matches[2] . " " . $matches[3] . ":" . $matches[4] . ":00";
            print($time_parsed);
            if($type == "arrival") $field = "a";
            if($type == "departure") $field = "d";
            if ($stmt = $mysqli->prepare("INSERT INTO visit (teamid, postid, type, time) VALUES (?,?,?,?)")) {
               $stmt->bind_param("iiss", $teamid, $postid, $field, $time_parsed);
               /* execute query */
                $stmt->execute();
                $stmt->close();
            }
        }
    }
}

if($_REQUEST['action'] == 'delete'){
        list($teamid, $postid, $type) = array($_REQUEST['teamid'], $_REQUEST['postid'], $_REQUEST['type']);

        // no time == delete
        if($type == "arrival") $field = "a";
        if($type == "departure") $field = "d";
         if ($stmt = $mysqli->prepare("DELETE FROM visit WHERE teamid = ? AND postid = ? AND type = ?")) {
               $stmt->bind_param("iis", $teamid, $postid, $field);
               /* execute query */
                $stmt->execute();
                $stmt->close();
            }   
}

if($_REQUEST['action'] == 'straf'){
    list($teamid, $postid, $straf) = array($_REQUEST['teamid'], $_REQUEST['postid'], $_REQUEST['straf']);

    if($straf){
	if ($stmt = $mysqli->prepare("INSERT INTO visit (teamid, postid, type, time) VALUES (?,?,'x', NOW())")) {

                   $stmt->bind_param("ii", $teamid, $postid);
                   /* execute query */
                    $stmt->execute();
                    $stmt->close();
	}
    }else{
	if ($stmt = $mysqli->prepare("DELETE FROM visit WHERE teamid = ? AND postid = ? AND type = 'x'")) {
	                   $stmt->bind_param("ii", $teamid, $postid);
	                   /* execute query */
	                    $stmt->execute();
	                    $stmt->close();
	}
    }
   
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
if ($stmt  = $mysqli->prepare("select t.teamid, v.postid, t.name, UNIX_TIMESTAMP(time), v.type from team t  JOIN visit v on (t.teamid = v.teamid) order by v.time")) {
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

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>CCMR Post-rapporterings system</title>
        <style type="text/css" title="currentStyle" media="screen">
            @import "style.css";
        </style>
    </head>
    <body>
    <h1>Indrapporter</h1>
    <form method="POST">
    <table>
        <tr>
            <td align="center">Post</td>
            <td align="center">Hold</td>
            <td rowspan="2">
                <span><input type="radio" name="type" id="arr"  checked="true" value="arrival"></span><label for="arr">Ankomst</label><br>
                <span><input type="radio" name="type" id="dep" value="departure"></span><label for="dep">Afgang</label>
            </td>
            <td align="center">
                Tid
            </td>
            <td rowspan="2" valign="bottom">
                <button name="action" value="create" class="create">Opret</button><br>
                <button name="action" value="delete" class="delete">Slet</button>
            </td>
        </tr>
        <tr>
            <td>
                <select name="postid">
                    <option value="" selected="true">&nbsp;</option>
                <?php foreach($posts as $post): ?>
                    <option value="<?=$post['postid']?>"><?=$post['name']?></option>
                <?php endforeach; ?>
                </select>
            </td>
            <td>
                <select name="teamid">
                    <option value="" selected="true">&nbsp;</option>

                <?php foreach($teams as $team): ?>
                    <option value="<?=$team['teamid']?>"><?=$team['name']?></option>
                <?php endforeach; ?>
                </select>
            </td>
            <td>
                <input type="text" size="10" name="time" value="<?=date("m/d H:i")?>">
            </td>
        </tr>
    </table>
    </form>
	<form method="POST">
	<table>
	  <tr>
	            <td align="center">Post</td>
	            <td align="center">Hold</td>
	            <td align="center">Straf</td>
	            <td align="center" valign="middle" rowspan="2">
		<button name="action" value="straf">S&aelig;t Straf</button>
	
	</td>
	
       
	</tr>
	
	<tr>
		    <td>
	                <select name="postid">
	                    <option value="" selected="true">&nbsp;</option>
                        <?php foreach($posts as $post): ?>
	                    <option value="<?=$post['postid']?>"><?=$post['name']?></option>
	                <?php endforeach; ?>
	                </select>
	            </td>
	            <td>
	                <select name="teamid">
	                    <option value="" selected="true">&nbsp;</option>
	
                       <?php foreach($teams as $team): ?>
	                    <option value="<?=$team['teamid']?>"><?=$team['name']?></option>
	                <?php endforeach; ?>
	                </select>
	            </td>
		    <td align="center">
		<input type="checkbox" name="straf" value="true" checked="true">
	</td>
	</tr>
	</table>

    <h1>Status</h1>
    <table border=1 class="maintable">
        <thead>
            <tr>
                <td style="border-top: 0px; border-left: 0px;" class="clear">&nbsp;</td>
            <?php foreach($teams as $team): ?>
                <td class="headercell"><?=$team['name']?></td>
            <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach($posts as $post): ?>
                <tr>
                    <td class="headercell"><?=$post['name'] ?></td>
                    <?php foreach($teams as $team): ?>
                    <?php
                        $span = "";
                    ?>
                        <?php if(isset($visits[$team['teamid']][$post['postid']])): ?>
                            <?=render_visit_cell($visits[$team['teamid']][$post['postid']], $hover)?>
                        <?php else: ?>
                            <td class="empty"><span title="<?=$hover?>"><br><br></span></td>
                        <?php endif ?>
                        <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<br>
<center>
<a href="csv.php">Download som CSV</a>
</center>
    </body>
</html>
<?php
// functions

function render_visit_cell($visitdata, $hover){
    if(isset($visitdata['a'])){
        $return .= date( 'H:i',$visitdata['a']) . '<br>';
        $class = "started";
    }else{
        $return .= "<br>";
    }

    if(isset($visitdata['d'])){
        $return .= date('H:i',$visitdata['d']) . "<br>";
        $class = "completed";
    }else{
        $return .= "<br>";
    }
    
    // override if straf
    if(isset($visitdata['x'])){
	$class ="straf"; 
    }
    
    return '<td class="' . $class . '"><span title="'. $hover .'">' . $return . '</span></td>';

}
