<?php
// enable debug mode
 error_reporting(E_ALL); ini_set('display_errors', 'On');
require_once '/usr/share/imslu/include/common.php';
require_once '/usr/share/imslu/include/config.php';

$db = new PDOinstance();

# Update kind_traffic
$sql = "DESCRIBE kind_traffic";
$sth = $db->dbh->prepare($sql);
$sth->execute();
$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

if ($rows[0]['Field'] == 'kind_trafficid') {

  echo "Updating table kind_traffic!\n";

  $sth = $db->dbh->prepare('ALTER TABLE kind_traffic CHANGE COLUMN kind_trafficid id int(11) UNSIGNED NOT NULL AUTO_INCREMENT');
  $sth->execute();
}

# Update services
$sql = "DESCRIBE services";
$sth = $db->dbh->prepare($sql);
$sth->execute();
$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

$found = FALSE;
if (count($rows) == 8) {
  foreach ($rows as $value) {
    if ($value['Field'] == 'kind_trafficid') {
      $found = TRUE;
      break;
    }
  }
}

if ($found) {

  echo "Updating table services!\n";

  $sql = "SELECT * FROM services";
  $sth = $db->dbh->prepare($sql);
  $sth->execute();
  $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

  $services = array();
  foreach ($rows as $value) {
    if ($value['kind_trafficid'] == 1) {
      $services[$value['name']]['name'] = $value['name'];
      $services[$value['name']]['price'] = $value['price'];
      $services[$value['name']]['in_min0'] = $value['in_min'];
      $services[$value['name']]['in_max0'] = $value['in_max'];
      $services[$value['name']]['out_min0'] = $value['out_min'];
      $services[$value['name']]['out_max0'] = $value['out_max'];
    }
    else {
      $services[$value['name']]['in_min1'] = $value['in_min'];
      $services[$value['name']]['in_max1'] = $value['in_max'];
      $services[$value['name']]['out_min1'] = $value['out_min'];
      $services[$value['name']]['out_max1'] = $value['out_max'];
    }
  }

  $db->dbh->beginTransaction();
  $sth = $db->dbh->prepare('ALTER TABLE services DROP kind_trafficid');
  $sth->execute();

  $sth = $db->dbh->prepare("ALTER TABLE services CHANGE COLUMN in_min in_min0 varchar(32) NOT NULL DEFAULT '32kbit'");
  $sth->execute();
  $sth = $db->dbh->prepare('ALTER TABLE services CHANGE COLUMN in_max in_max0 varchar(32) NULL');
  $sth->execute();
  $sth = $db->dbh->prepare("ALTER TABLE services CHANGE COLUMN out_min out_min0 varchar(32) NOT NULL DEFAULT '32kbit'");
  $sth->execute();
  $sth = $db->dbh->prepare('ALTER TABLE services CHANGE COLUMN out_max out_max0 varchar(32) NULL');
  $sth->execute();

  $sth = $db->dbh->prepare("ALTER TABLE services ADD COLUMN IF NOT EXISTS in_min1 varchar(32) NOT NULL DEFAULT '32kbit'");
  $sth->execute();
  $sth = $db->dbh->prepare('ALTER TABLE services ADD COLUMN IF NOT EXISTS in_max1 varchar(32) NULL');
  $sth->execute();
  $sth = $db->dbh->prepare("ALTER TABLE services ADD COLUMN IF NOT EXISTS out_min1 varchar(32) NOT NULL DEFAULT '32kbit'");
  $sth->execute();
  $sth = $db->dbh->prepare('ALTER TABLE services ADD COLUMN IF NOT EXISTS out_max1 varchar(32) NULL');
  $sth->execute();

  $sth = $db->dbh->prepare("ALTER TABLE services ADD COLUMN IF NOT EXISTS in_min2 varchar(32) NOT NULL DEFAULT '32kbit'");
  $sth->execute();
  $sth = $db->dbh->prepare('ALTER TABLE services ADD COLUMN IF NOT EXISTS in_max2 varchar(32) NULL');
  $sth->execute();
  $sth = $db->dbh->prepare("ALTER TABLE services ADD COLUMN IF NOT EXISTS out_min2 varchar(32) NOT NULL DEFAULT '32kbit'");
  $sth->execute();
  $sth = $db->dbh->prepare('ALTER TABLE services ADD COLUMN IF NOT EXISTS out_max2 varchar(32) NULL');
  $sth->execute();

  $sth = $db->dbh->prepare("ALTER TABLE services ADD COLUMN IF NOT EXISTS in_min3 varchar(32) NOT NULL DEFAULT '32kbit'");
  $sth->execute();
  $sth = $db->dbh->prepare('ALTER TABLE services ADD COLUMN IF NOT EXISTS in_max3 varchar(32) NULL');
  $sth->execute();
  $sth = $db->dbh->prepare("ALTER TABLE services ADD COLUMN IF NOT EXISTS out_min3 varchar(32) NOT NULL DEFAULT '32kbit'");
  $sth->execute();
  $sth = $db->dbh->prepare('ALTER TABLE services ADD COLUMN IF NOT EXISTS out_max3 varchar(32) NULL');
  $sth->execute();

  $sth = $db->dbh->prepare("ALTER TABLE services ADD COLUMN IF NOT EXISTS in_min4 varchar(32) NOT NULL DEFAULT '32kbit'");
  $sth->execute();
  $sth = $db->dbh->prepare('ALTER TABLE services ADD COLUMN IF NOT EXISTS in_max4 varchar(32) NULL');
  $sth->execute();
  $sth = $db->dbh->prepare("ALTER TABLE services ADD COLUMN IF NOT EXISTS out_min4 varchar(32) NOT NULL DEFAULT '32kbit'");
  $sth->execute();
  $sth = $db->dbh->prepare('ALTER TABLE services ADD COLUMN IF NOT EXISTS out_max4 varchar(32) NULL');
  $sth->execute();

  $db->dbh->commit();
  $sth = $db->dbh->prepare('DELETE FROM services');
  $sth->execute();

  $db->dbh->beginTransaction();

  $sql = "INSERT INTO services (serviceid, name, price, in_min0, in_max0, out_min0, out_max0, in_min1, in_max1, out_min1, out_max1) VALUES (:serviceid, :name, :price, :in_min0, :in_max0, :out_min0, :out_max0, :in_min1, :in_max1, :out_min1, :out_max1)";
  $serviceid = array();
  $i = 1;
  foreach ($services as $value) {

    $serviceid[$value['name']] = $i;

    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':serviceid', $i, PDO::PARAM_INT);
    $sth->bindValue(':name', $value['name']);
    $sth->bindValue(':price', $value['price']);
    $sth->bindValue(':in_min0', $value['in_min0']);
    $sth->bindValue(':in_max0', $value['in_max0']);
    $sth->bindValue(':out_min0', $value['out_min0']);
    $sth->bindValue(':out_max0', $value['out_max0']);
    $sth->bindValue(':in_min1', $value['in_min1']);
    $sth->bindValue(':in_max1', $value['in_max1']);
    $sth->bindValue(':out_min1', $value['out_min1']);
    $sth->bindValue(':out_max1', $value['out_max1']);
    $sth->execute();

    $i++;
  }
  $db->dbh->commit();
//  print_r($serviceid);
}

# Update users
$sql = "DESCRIBE users";
$sth = $db->dbh->prepare($sql);
$sth->execute();
$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

$expires = 0;
$found = FALSE;
foreach ($rows as $value) {
  if ($value['Field'] == 'service') {
    $found = TRUE;
    break;
  }
  if ($value['Field'] == 'expires') {
    $expires++;
    break;
  }
}

if ($found) {

  echo "Updating table users!\n";

  $sth = $db->dbh->prepare("ALTER TABLE users ADD COLUMN IF NOT EXISTS serviceid int(11) NOT NULL DEFAULT '0' AFTER service");
  $sth->execute();

  $sql = "SELECT userid, service FROM users";
  $sth = $db->dbh->prepare($sql);
  $sth->execute();
  $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

  $db->dbh->beginTransaction();
  $sql = "UPDATE users SET serviceid = :serviceid WHERE userid = :userid";
  foreach ($rows as $value) {

    $sid = !empty($serviceid[$value['service']]) ? $serviceid[$value['service']] : 0;
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':serviceid', $sid, PDO::PARAM_INT);
    $sth->bindValue(':userid', $value['userid'], PDO::PARAM_INT);
    $sth->execute();
  }
  $db->dbh->commit();

  $sth = $db->dbh->prepare('ALTER TABLE users DROP COLUMN service');
  $sth->execute();

}

if ($expires == 0) {

  echo "Updating users payments!\n";

  $sth = $db->dbh->prepare("ALTER TABLE users ADD COLUMN IF NOT EXISTS expires datetime NOT NULL AFTER not_excluding");
  $sth->execute();

  $sql = 'SELECT userid, expires FROM payments';
  $sth = $db->dbh->prepare($sql);
  $sth->execute();
  $rows = $sth->fetchAll(PDO::FETCH_ASSOC);

  $payments = array();
  foreach ($rows as $value) {

    $payments[$value['userid']] = $value['expires'];
  }

  $db->dbh->beginTransaction();
  $sql = "UPDATE users SET expires = :expires WHERE userid = :userid";
  foreach ($payments as $key => $value) {

    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':expires', $value);
    $sth->bindValue(':userid', $key, PDO::PARAM_INT);
    $sth->execute();
  }
  $db->dbh->commit();
}

echo "Database is UP to date.\n";
?>
