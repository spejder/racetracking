<?php
/**
 * @file
 * Main file for the webinterface.
 */

include "db.inc.php";

// Handle actions.
if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'create') {
  list($teamid, $postid, $type, $time)
    = array(
      $_REQUEST['teamid'],
      $_REQUEST['postid'],
      $_REQUEST['type'],
      $_REQUEST['time'],
    );

  if (isset($teamid, $postid, $type) && strlen($time) > 0) {
    if (preg_match("/([0-9]{1,2})\/([0-9]{1,2}) ([0-9]{1,2}):([0-9]{2})/", $time, $matches)) {
      $time_parsed = YEAR . "-" . $matches[1] . "-" . $matches[2] . " " . $matches[3] . ":" . $matches[4] . ":00";
      print ($time_parsed);
      if ($type == "arrival") {
        $field = "a";
      }

      if ($type == "departure") {
        $field = "d";
      }

      if ($stmt = $mysqli->prepare("INSERT INTO visit (teamid, postid, type, time) VALUES (?,?,?,?)")) {
        $stmt->bind_param("iiss", $teamid, $postid, $field, $time_parsed);
        /* execute query */
        $stmt->execute();
        $stmt->close();
      }
    }
  }
}

if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'delete') {
  list($teamid, $postid, $type)
    = array($_REQUEST['teamid'], $_REQUEST['postid'], $_REQUEST['type']);

  // No time == delete.
  if ($type == "arrival") {
    $field = "a";
  }
  if ($type == "departure") {
    $field = "d";
  }

  if ($stmt = $mysqli->prepare("DELETE FROM visit WHERE teamid = ? AND postid = ? AND type = ?")) {
    $stmt->bind_param("iis", $teamid, $postid, $field);
    /* execute query */
    $stmt->execute();
    $stmt->close();
  }
}

if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'straf') {
  list($teamid, $postid, $straf)
    = array($_REQUEST['teamid'], $_REQUEST['postid'], $_REQUEST['straf']);

  if ($straf) {
    if ($stmt = $mysqli->prepare("INSERT INTO visit (teamid, postid, type, time) VALUES (?,?,'x', NOW())")) {
      $stmt->bind_param("ii", $teamid, $postid);
      /* execute query */
      $stmt->execute();
      $stmt->close();
    }
  }
  else {
    if ($stmt = $mysqli->prepare("DELETE FROM visit WHERE teamid = ? AND postid = ? AND type = 'x'")) {
      $stmt->bind_param("ii", $teamid, $postid);
      /* execute query */
      $stmt->execute();
      $stmt->close();
    }
  }
}

$posts = array();
// Get all posts.
if ($stmt = $mysqli->prepare("SELECT p.name, p.postid, p.canceled FROM post p order by name")) {
  $stmt->execute();

  /* bind result variables */
  $stmt->bind_result($name, $id, $canceled);

  /* fetch value */
  while ($stmt->fetch()) {
    $posts[$name]
      = array("name" => $name, "postid" => $id, "canceled" => $canceled);
  }

  /* close statement */
  $stmt->close();
}else{
  die($mysqli->error);
}

// Get all teams, sorted.
$teams = array();
if ($stmt = $mysqli->prepare("select t.name, t.teamid, t.canceled from team t order by t.name")) {
  $stmt->execute();

  // Bind result variables.
  $stmt->bind_result($name, $teamid, $canceled);

  // Fetch value.
  while ($stmt->fetch()) {
    $teams[] = array('name' => $name, 'teamid' => $teamid, 'canceled' => $canceled);
  }

  // Close statement.
  $stmt->close();
}

// Get visit data.
$visits = array();
if ($stmt  = $mysqli->prepare("select t.teamid, v.postid, t.name, UNIX_TIMESTAMP(time), v.type from team t  JOIN visit v on (t.teamid = v.teamid) order by v.time")) {
  // Execute query.
  $stmt->execute();

  // Bind result variables.
  $stmt->bind_result($teamid, $postid, $name, $time, $type);

  // Fetch value.
  while ($stmt->fetch()) {
    $visits[$teamid][$postid][$type] = $time;
  }

  // Close statement.
  $stmt->close();
}


/* close connection */
$mysqli->close();
$count = 0;

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
                <span><input type="radio" name="type" id="arr"  checked="true" value="arrival"></span><label for="arr">Ankomst</label><br />
                <span><input type="radio" name="type" id="dep" value="departure"></span><label for="dep">Afgang</label>
            </td>
            <td align="center">
                Tid
            </td>
            <td rowspan="2" valign="bottom">
                <button name="action" value="create" class="create">Opret</button><br />
                <button name="action" value="delete" class="delete">Slet</button>
            </td>
        </tr>
        <tr>
            <td>
                <select name="postid">
                    <option value="" selected="true">&nbsp;</option>
                <?php foreach($posts as $post) : ?>
                    <option value="<?php echo $post['postid']?>"><?php echo $post['name']?></option>
                <?php endforeach; ?>
                </select>
            </td>
            <td>
                <select name="teamid">
                    <option value="" selected="true">&nbsp;</option>

                <?php foreach($teams as $team) : ?>
                    <option value="<?php echo $team['teamid']?>"><?php echo $team['name']?></option>
                <?php endforeach; ?>
                </select>
            </td>
            <td>
                <input type="text" size="10" name="time" value="<?php echo date("m/d H:i")?>">
            </td>
        </tr>
    </table>
    </form>
    <h1>Status</h1>
    <table border=1 class="maintable">
        <thead>
            <tr>
                <td style="border-top: 0px; border-left: 0px;" class="clear">&nbsp;</td>
            <?php foreach($teams as $team) : ?>
                <td class="headercell <?php echo $team['canceled'] == 1 ? 'canceled' : '';?>"><a href="edit.php?id=<?php echo $team['teamid']?>&t=t"><?php echo $team['name']?></a></td>
            <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach($posts as $post) : ?>
            <?php
              $mod_class = ($count % 2 == 0) ? 'even' : 'odd';
              $count++;
              $hover = '';

              $canc_class = ($post['canceled'] == 1) ? 'canceled' : '';
            ?>

                <tr class="<?php echo $mod_class?> <?php echo $canc_class;?>">
                    <td class="headercell <?php echo $mod_class?> <?php echo $canc_class?>"><a href="edit.php?id=<?php echo $post['postid']?>&t=p"><?php echo $post['name'] ?></a></td>
                    <?php foreach($teams as $team) : ?>
                    <?php
                        $span = "";
                        $team_canc_class = !empty($canc_class) ? $canc_class : ($team['canceled'] == 1 ? 'canceled' : '');
                    ?>
                        <?php if(isset($visits[$team['teamid']][$post['postid']])) : ?>
                            <?php echo render_visit_cell($visits[$team['teamid']][$post['postid']], $hover, "$mod_class $team_canc_class")?>
                        <?php else : ?>
                            <td class="empty <?php echo $mod_class?> <?php echo $team_canc_class?>"><span title="<?php echo $hover;?>"><br><br></span></td>
                        <?php endif ?>
                        <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<br>
    <div style="text-align: center">
      <a href="csv.php">Download som CSV</a><br><br>
      <a href="edit?t=t">Tilføj hold</a><br>
      <a href="edit?t=p">Tilføj post</a><br>
    </div>

    </body>
</html>

<?php
/**
 * Render a single cell.
 *
 * @param $visitdata
 * @param $hover
 * @return string
 */
function render_visit_cell($visitdata, $hover, $additional_class) {
  if (isset($visitdata['a'])) {
    $return = date('H:i', $visitdata['a']) . '<br />';
    $class = "started";
  }
  else {
    $return = "<br />";
  }

  if (isset($visitdata['d'])) {
    $return .= date('H:i', $visitdata['d']) . "<br />";
    $class = "completed";
  }
  else {
    $return .= "<br />";
  }

  // Override if straf.
  if (isset($visitdata['x'])) {
    $class = "straf";
  }

  return '<td class="' . $class . ' '. $additional_class .'"><span title="' . $hover . '">' . $return . '</span></td>';
}
