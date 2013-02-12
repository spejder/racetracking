<?php
/**
 * @file
 * Main file for the webinterface.
 */

include "db.inc.php";

// Initial setup of stuff that will always be in the request.
if (!isset($_REQUEST['id'], $_REQUEST['t'])) {
  die();
}

$id = $_REQUEST['id'] + 0;
$t = $_REQUEST['t'];
$type = $_REQUEST['t'] == 'p' ? "Post" : "Hold";

// Handle actions.
if (isset($_REQUEST['action']['update'])) {
  $data = $_REQUEST['data'];
  $_REQUEST['data']['canceled'] = $_REQUEST['data']['canceled'] == 0 ? 0 : 1;

  if ($t == 't') {
    $stmt = $mysqli->prepare("UPDATE team SET name = ?, smskey = ?, canceled = ? where teamid = ?");
    if (!$stmt) {
      die("error, " . $mysqli->error);
    }
    $stmt->bind_param("ssii", $_REQUEST['data']['name'],
      $_REQUEST['data']['smskey'], $_REQUEST['data']['canceled'], $id);
  }
  elseif ($t == 'p') {
    $stmt = $mysqli->prepare("UPDATE post SET name = ?, smskey = ?, phonenumber = ?, canceled = ? where postid = ?");
    if (!$stmt) {
      die("error, " . $mysqli->error);
    }
    $stmt->bind_param("sssii", $_REQUEST['data']['name'], $_REQUEST['data']['smskey'],
      $_REQUEST['data']['phonenumber'], $_REQUEST['data']['canceled'], $id);
  } else {
    die("unknown type");
  }

  if(!$stmt->execute()){
    die("error, " . $mysqli->error);
  }
  $stmt->close();
} else if (isset($_REQUEST['action']['delete'])) {

  if ($t == 't') {
    $key = "teamid";
    $table = "team";
  }
  elseif ($t == 'p') {
    $key = "postid";
    $table = "post";
  }
  else {
    die("unknown type");
  }

  $stmt = $mysqli->prepare("DELETE FROM $table WHERE $key = ?");
  if (!$stmt) {
    die("error, " . $mysqli->error);
  }
  $stmt->bind_param("i", $id);
  $stmt->execute();

  header("Location: index.php");
}


$data = array();
if ($t == 't') {
  $stmt = $mysqli->prepare("SELECT t.name, t.smskey, t.canceled FROM team t WHERE teamid = ?");
}
elseif ($t == 'p') {
  $stmt = $mysqli->prepare("SELECT p.name, p.smskey, p.phonenumber, p.canceled FROM post p WHERE postid = ?");
}
else {
  die();
}

$stmt->bind_param('i', $id);
$stmt->execute();

$result = $stmt->get_result();
$data =  $result->fetch_assoc();

if ($data === null) {
  die("Could not lookup data");
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
    <h1>Rediger <?php echo $type ?> </h1>
    <form method="POST">
    <table>
      <?php
      foreach($data as $key => $val):
?>
        <tr>
          <td><?php echo $key?></td>
          <td><input type="text" value="<?php echo $val?>" name="data[<?php echo $key ?>]" /></td>
        </tr>
<?php endforeach;?>

      <tr>
        <td colspan="2" align="center">
          <input type="submit" name="action[update]" value="Update" />
          <input type="hidden" name="t" value="<?php echo $t?>" />
          <input type="hidden" name="id" value="<?php echo $id?>" />

          <input type="submit" name="action[delete]" onClick="return confirm('Sikker?')" value="Delete" />
          <input type="button" value="Back" onclick="window.location='index.php'"/>
        </td>
      </tr>
      </table>
    </form>
    </body>
</html>
