<?php
// enable debug mode
// error_reporting(E_ALL); ini_set('display_errors', 'On');

require_once dirname(__FILE__).'/../include/os.php';
require_once dirname(__FILE__).'/../include/gettextwrapper.php';
require_once $CONFIG_FILE;
require_once $DATABASE_CONFIGURATION;

// Edit here!
// English locale
putenv('LC_MESSAGES=en_US');
setlocale(LC_MESSAGES, 'en_US.UTF-8');
// Bulgarian locale
//putenv('LC_MESSAGES=bg_BG');
//setlocale(LC_MESSAGES, 'bg_BG.UTF-8');
bindtextdomain('frontend', '../locale');
textdomain('frontend');
bind_textdomain_codeset('frontend', 'UTF-8');

$dbserver = !empty($dbserver) ? $dbserver : 'localhost';

$driver_options = array(
	PDO::ATTR_EMULATE_PREPARES => false, 
	PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
	PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
	PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING );

try {
    $dbh = new PDO("{$dbtype}:host={$dbserver}; dbname={$dbname}", "{$dbuser}", "{$dbpass}", $driver_options);
}
catch (PDOException $e) {

    die ('SQL Error');
//    echo $e->getMessage()."\n"; 
    exit();
}

// Mask User Data
function mask_data($var) {

    $data = explode(' ', $var);
    for($i = 0; $i < count($data) ; $i++) {
        $length = mb_strlen(mb_substr($data[$i], 1, -1));
        $data[$i] = ($length > 2) ? mb_ereg_replace(mb_substr($data[$i], 1, -1), str_repeat('*', $length), $data[$i]) : mb_ereg_replace('[\d\w]', '*', $data[$i]);
    }
    return implode(' ', $data);
}


$sql = 'SELECT userid, stopped FROM ip WHERE ip = ? LIMIT 1';
$sth = $dbh->prepare($sql);
$sth->bindParam(1, $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
$sth->bindColumn('userid', $userid);
$sth->bindColumn('stopped', $stopped);
$sth->execute();

if ($sth->rowCount() == 1) {

    $sth->fetch(PDO::FETCH_ASSOC);
    if ($userid != 0) {

        $sql = "SELECT userid, name, address, serviceid, pay, free_access, not_excluding, expires FROM users WHERE userid = ? LIMIT 1";
        $sth = $dbh->prepare($sql);
        $sth->bindParam(1, $userid, PDO::PARAM_INT);
        $sth->execute();
        $user = $sth->fetch(PDO::FETCH_ASSOC);

        // Apply form
        if (!empty($_POST['temporary_access'])) {

          $expires = date("Y-m-d", strtotime("+$TEMPORARY_INTERNET_ACCESS days"))." 23:59:00";

          $sql = 'INSERT INTO payments (userid, name, limited, operator1, date_payment1, expires, sum, notes) 
                  VALUES (:userid, :name, :limited, :operator1, :date_payment1, :expires, :sum, :notes)';
          $sth = $dbh->prepare($sql);
          $sth->bindValue(':userid', $user['userid']);
          $sth->bindValue(':name', $user['name']);
          $sth->bindValue(':limited', 1);
          $sth->bindValue(':operator1', $user['name']);
          $sth->bindValue(':date_payment1', date('Y-m-d H:i:s'));
          $sth->bindValue(':expires', $expires);
          $sth->bindValue(':sum', $user['pay']);
          $sth->bindValue(':notes', 'Activated through warning page.');
          $sth->execute();

          $sql = 'UPDATE users SET expires = :expires WHERE userid = :userid';
          $sth = $dbh->prepare($sql);
          $sth->bindValue(':expires', $expires);
          $sth->bindValue(':userid', $user['userid']);
          $sth->execute();

          // Select user IP Addresses
          $sql = 'SELECT ip FROM ip WHERE userid = :userid';
          $sth = $dbh->prepare($sql);
          $sth->bindValue(':userid', $user['userid'], PDO::PARAM_INT);
          $sth->execute();
          $ip = $sth->fetchAll(PDO::FETCH_ASSOC);

          // Start internet access
          if (!empty($ip)) {
            for ($i = 0; $i < count($ip); ++$i) {

              $cmd = "$SUDO $IMSLU_SCRIPTS/functions-php.sh ip_allow '{$ip[$i]['ip']}' 2>&1";
              shell_exec($cmd);
            }
          }
          header('Location: https://github.com/mysticall/imslu');
          exit;
        }
        // Show Warning Page
        else {

            $obligation = 1;
            if ($stopped == "y") {
                $status = "<label style=\"color: red;\">"._('limited')."</label>";
                $description = "<label style=\"color: red;\">"._('manually stopped')."</label>";
            }
            elseif ($user['free_access'] == "y") {
                $status = "<label style=\"color: green; font-weight: bold;\">"._('active')."</label>";
                $description = "<label style=\"color: green; font-weight: bold;\">"._('free access')."</label>";
            }
            elseif ($user['not_excluding'] == "y") {
                $status = "<label style=\"color: green; font-weight: bold;\">"._('active')."</label>";
                $description = "<label style=\"color: green; font-weight: bold;\">"._('unlimited')."</label>";
            }
            elseif (strtotime($user['expires']) > time()) {
                $status = "<label style=\"color: green; font-weight: bold;\">"._('active')."</label>";
                $description = "<label style=\"color: green; font-weight: bold;\">"._('paid')."</label>"; _('paid');
            }
            else {
                $status = "<label style=\"color: red;\">"._('limited')."</label>";
                $description = "<label style=\"color: red;\">"._('unpaid')."</label>";

                // Checking user payments for obligation
                $sql = 'SELECT * FROM payments WHERE userid = :userid AND (unpaid = 1 OR limited = 1) LIMIT 1';
                $sth = $dbh->prepare($sql);
                $sth->bindParam(':userid', $userid, PDO::PARAM_INT);
                $sth->execute();
                $obligation = ($sth->rowCount() == 1) ? 1 : 0;
            }

            $form =
"<!doctype html>
<html>
  <head>
    <meta http-equiv=\"Pragma\" content=\"no-cache\">
    <meta http-equiv=\"cache-control\" content=\"no-store\">
    <meta http-equiv=\"cache-control\" content=\"no-cache\">
    <meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\">
    <title>IMSLU</title>
    <style>
      body, form, table, input, select {
        font-family: verdana, arial, helvetica, sans-serif;
        font-size: 13px;
        line-height: 1.3em;
        background: #000;
        color: #fff;
      }
      table, tr, td, th {
        padding: 0px 5px;
        border: 2px solid #fff;
        border-collapse: collapse;
        empty-cells: show;
      }
      .button {
        width: 85px;
        background-color: #00AA00;
      }
    </style>
  </head>
  <body>
  <center>
   <form action=\"index.php\" method=\"post\">
   <table>
      <tbody>
        <tr>
          <td style=\"border-right-color:transparent;\">
            <br>
            "._('Status').": {$status}<br>
            "._('Description').": {$description}<br>
            <br>
          </td>
          <td>
            <br>
            <img src=\"logo.png\">
            <br>
          </td>
        </tr>
        <tr>
          <td>
            <br>
            "._('Name').": ".mask_data($user['name'])."<br>
            "._('Address').": ".mask_data($user['address'])."<br>
            "._('Active until').": {$user['expires']}<br>
            <br>
          </td>
          <td>
            <br>
            "._('Contact us')."<br>
            "._('Address').": Edit here!<br>
            "._('Phone number').": Edit here!<br>
            <br>
          </td>
        </tr> \n";

            if ($obligation == 0 && $TEMPORARY_INTERNET_ACCESS > 0) {

      $form .=
"       <tr>
          <td colspan=\"2\">
            <br>
            "._('Activate temporary internet access for')." 
            <input class=\"button\" type=\"submit\" name=\"temporary_access\" value=\""._s('%s days', $TEMPORARY_INTERNET_ACCESS)."\">
            <br><br>
          </td>
        </tr> \n";
            }

    $form .=
"      </tbody>
    </table>
    </form>
    </center>
  </body>
</html>";
            echo $form;
        }
    }
}
?>
