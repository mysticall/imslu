<?php
// enable debug mode
 error_reporting(E_ALL); ini_set('display_errors', 'On');
require_once '/usr/share/imslu/include/common.php';
require_once '/usr/share/imslu/include/config.php';

$db = new PDOinstance();

/*
In the new version users-->userid is used as a key to generate a tc classid
$TC class add dev $IFACE_IMQ0 parent 1:2 classid 1:a hfsc sc m1 0bit d 0us m2 32kbit ul m1 0bit d 0us m2 20000kbit
classid 1:a = classid 1:$(printf '%x' ${userid})

For that reason the first 9 positions in the table users->userid must be empty.
*/

$sql = "SELECT AUTO_INCREMENT FROM information_schema.tables WHERE table_name = :table_name";
$sth = $db->dbh->prepare($sql);
$sth->bindValue(':table_name', 'users');
$sth->execute();
$int = $sth->fetch(PDO::FETCH_ASSOC)['AUTO_INCREMENT'];
$AUTO_INCREMENT = ($int < 10) ? 10 : $int;

for ($i = 1; $i < 10; ++$i) {

    $sql = "SELECT name FROM users WHERE userid = :id";
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':id', $i);
    $sth->execute();
    if ($sth->rowCount() > 0) {
        $name = $sth->fetch(PDO::FETCH_ASSOC)['name'];

        $sql = "UPDATE users SET userid = :userid WHERE name = :name";
        $sth = $db->dbh->prepare($sql);
        $sth->bindValue(':userid', $AUTO_INCREMENT);
        $sth->bindValue(':name', $name);
        $sth->execute();

        $sql2 = "SELECT id FROM payments WHERE userid = :userid";
        $sth = $db->dbh->prepare($sql2);
        $sth->bindValue(':userid', $i);
        $sth->execute();

        if ($sth->rowCount() > 0) {

            $id = $sth->fetchAll(PDO::FETCH_ASSOC);

            $db->dbh->beginTransaction();
            $sql = "UPDATE payments SET userid = :userid WHERE id = :id";
            for ($ii = 0; $ii < count($id); ++$ii) {
                
                $sth = $db->dbh->prepare($sql);
                $sth->bindValue(':userid', $AUTO_INCREMENT);
                $sth->bindValue(':id', $id[$ii]['id']);
                $sth->execute();
            }
            $db->dbh->commit();
        }
        ++$AUTO_INCREMENT;
    }
}

# Preparation of new services
$sql = "SELECT * FROM traffic";
$sth = $db->dbh->prepare($sql);
$sth->execute();
$traffic = $sth->fetchAll(PDO::FETCH_ASSOC);

$db->dbh->beginTransaction();
$sql = "INSERT INTO `services` (`serviceid`, `kind_trafficid`, `name`, `price`, `in_max`, `out_max`) VALUES (:serviceid, :kind_trafficid, :name, :price, :in_max, :out_max)";

for ($i = 0; $i < count($traffic); ++$i) {

    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':serviceid', $traffic[$i]['trafficid']);
    $sth->bindValue(':kind_trafficid', 1);
    $sth->bindValue(':name', $traffic[$i]['name']);
    $sth->bindValue(':price', $traffic[$i]['price']);
    $sth->bindValue(':in_max', round($traffic[$i]['local_in']/1024)."mbit");
    $sth->bindValue(':out_max', round($traffic[$i]['local_out']/1024)."mbit");
    $sth->execute();
}
$db->dbh->commit();

foreach ($traffic as $value) {

    $services[$value['trafficid']] = $value['name'];
}

$sql = "SELECT userid, trafficid, free_access, not_excluding FROM users";
$sth = $db->dbh->prepare($sql);
$sth->execute();
$users = $sth->fetchAll(PDO::FETCH_ASSOC);

$db->dbh->beginTransaction();
$sth = $db->dbh->exec("ALTER TABLE users MODIFY free_access enum('n','y') NOT NULL DEFAULT 'n'");
$sth = $db->dbh->exec("ALTER TABLE users MODIFY not_excluding enum('n','y') NOT NULL DEFAULT 'n'");
$db->dbh->commit();

$db->dbh->beginTransaction();
$sql = "UPDATE users SET service = :service, free_access = :free_access, not_excluding = :not_excluding WHERE userid = :userid";

for ($i = 0; $i < count($users); ++$i) {

    $free_access = ($users[$i]['free_access'] == 0) ? 'n' : 'y';
    $not_excluding = ($users[$i]['not_excluding'] == 0) ? 'n' : 'y';
    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':service', $services[$users[$i]['trafficid']]);
    $sth->bindValue(':free_access', $free_access);
    $sth->bindValue(':not_excluding', $not_excluding);
    $sth->bindValue(':userid', $users[$i]['userid']);
    $sth->execute();
}
$db->dbh->commit();

$sql = "SELECT userid, ipaddress, vlan, mac, free_mac, pool_name FROM static_ippool";
$sth = $db->dbh->prepare($sql);
$sth->execute();
$static_ippool = $sth->fetchAll(PDO::FETCH_ASSOC);

$db->dbh->beginTransaction();
$sql = "INSERT INTO ip (userid, ip, mac, free_mac, pool, protocol) VALUES (:userid, :ip, :mac, :free_mac, :pool, :protocol)";

for ($i = 0; $i < count($static_ippool); ++$i) {

    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':userid', $static_ippool[$i]['userid']);
    $sth->bindValue(':ip', $static_ippool[$i]['ipaddress']);
    $sth->bindValue(':mac', $static_ippool[$i]['mac']);
    $sth->bindValue(':free_mac', $static_ippool[$i]['free_mac']);
    $sth->bindValue(':pool', $static_ippool[$i]['pool_name']);
    $sth->bindValue(':protocol', 'IP');
    $sth->execute();
}
$db->dbh->commit();

# FreeRadius
$sql = "SELECT userid, username, value FROM radcheck WHERE attribute = :attribute";
$sth = $db->dbh->prepare($sql);
$sth->bindValue(':attribute', 'Cleartext-Password');
$sth->execute();
$radcheck = $sth->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT framedipaddress FROM radippool";
$sth = $db->dbh->prepare($sql);
$sth->execute();
$radippool = $sth->fetchAll(PDO::FETCH_ASSOC);

$db->dbh->beginTransaction();
$sql = "INSERT INTO ip (userid, ip, username, pass, pool, protocol) VALUES (:userid, :ip, :username, :pass, :pool, :protocol)";

for ($i = 0; $i < count($radippool); ++$i) {

    $userid = (!empty($radcheck[$i]['userid'])) ? $radcheck[$i]['userid'] : 0;
    $username = (!empty($radcheck[$i]['username'])) ? $radcheck[$i]['username'] : '';
    $pass = (!empty($radcheck[$i]['value'])) ? $radcheck[$i]['value'] : '';

    $sth = $db->dbh->prepare($sql);
    $sth->bindValue(':userid', $userid);
    $sth->bindValue(':ip', $radippool[$i]['framedipaddress']);
    $sth->bindValue(':username', $username);
    $sth->bindValue(':pass', $pass);
    $sth->bindValue(':pool', 'PPPoE');
    $sth->bindValue(':protocol', 'PPPoE');
    $sth->execute();
}
$db->dbh->commit();

?>
