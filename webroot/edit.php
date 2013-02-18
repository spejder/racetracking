<?php
/**
 * @file
 * Main file for the webinterface.
 */

include "db.inc.php";

// Initial setup of stuff that will always be in the request.
if (!isset($_REQUEST['t'])) {
  die();
}

$id = (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) ? $_REQUEST['id'] + 0 : FALSE;
$t = $_REQUEST['t'];
$type = $_REQUEST['t'] == 'p' ? "Post" : "Hold";

// Handle actions.
if (isset($_REQUEST['action']['execute'])) {
  $data = $_REQUEST['data'];
  $_REQUEST['data']['canceled'] = $_REQUEST['data']['canceled'] == 0 ? 0 : 1;

  if ($t == 't') {
    if ($id !== FALSE) {
      // Update
      $stmt = $mysqli->prepare("UPDATE team SET name = ?, smskey = ?, canceled = ? where teamid = ?");
      if (!$stmt) {
        die("error, " . $mysqli->error);
      }
      $stmt->bind_param("ssii", $_REQUEST['data']['name'],
        $_REQUEST['data']['smskey'], $_REQUEST['data']['canceled'], $id);
    }
    else {
      // Create
      $stmt = $mysqli->prepare("INSERT INTO team (name, smskey, canceled) VALUES (?, ?, ?)");
      if (!$stmt) {
        die("error, " . $mysqli->error);
      }
      $stmt->bind_param("ssi", $_REQUEST['data']['name'],
        $_REQUEST['data']['smskey'], $_REQUEST['data']['canceled']);
    }
  }
  elseif ($t == 'p') {
    if ($id !== FALSE) {
      // Update
      $stmt = $mysqli->prepare("UPDATE post SET name = ?, smskey = ?, phonenumber = ?, canceled = ? where postid = ?");
      if (!$stmt) {
        die("error, " . $mysqli->error);
      }
      $stmt->bind_param("sssii", $_REQUEST['data']['name'], $_REQUEST['data']['smskey'],
        $_REQUEST['data']['phonenumber'], $_REQUEST['data']['canceled'], $id);
    }
    else {
      // Create
      $stmt = $mysqli->prepare("INSERT INTO post (name, smskey, phonenumber, canceled) VALUES (?, ?, ?, ?)");
      if (!$stmt) {
        die("error, " . $mysqli->error);
      }
      $stmt->bind_param("sssi", $_REQUEST['data']['name'], $_REQUEST['data']['smskey'],
        $_REQUEST['data']['phonenumber'], $_REQUEST['data']['canceled']);
    }

  } else {
    die("unknown type");
  }

  if(!$stmt->execute()){
    die("error, " . $mysqli->error);
  }
  $stmt->close();
  header("Location: index.php");

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
if ($id !== FALSE) {
  if ($t == 't') {
    $result = $mysqli->query("SELECT t.name, t.smskey, t.canceled FROM team t WHERE teamid = $id");
  }
  elseif ($t == 'p') {
    $result = $mysqli->query("SELECT p.name, p.smskey, p.phonenumber, p.canceled FROM post p WHERE postid = $id");
  }
  else {
    die();
  }

  $data =  $result->fetch_array(MYSQLI_ASSOC);

  if ($data === null) {
    die("Could not lookup data");
  }

  /* close connection */
  $mysqli->close();

}
else {
  if ($t == 't') {
    $data = array(
      'name' => '',
      'smskey' => '',
      'canceled' => '0',
    );
  }
  elseif ($t == 'p') {
    $data = array(
      'name' => '',
      'smskey' => '',
      'phonenumber' => '',
      'canceled' => '0',
    );
  }
}

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
    <h1><?php echo $id !== FALSE ? 'Rediger' : 'Opret'?> <?php echo $type ?> </h1>
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
          <input type="submit" name="action[execute]" value="Execute" />
          <input type="hidden" name="t" value="<?php echo $t?>" />
          <input type="hidden" name="id" value="<?php echo $id !== FALSE ? $id : ''?>" />

          <input type="submit" name="action[delete]" onClick="return confirm('Sikker?')" value="Delete" />
          <input type="button" value="Go Back" onclick="window.location='index.php'"/>
        </td>
      </tr>
      </table>
    </form>
    </body>
</html>
