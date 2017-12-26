<?php
// enable debug mode
error_reporting(E_ALL); ini_set('display_errors', 'On');

$driver_options = array(
	PDO::ATTR_EMULATE_PREPARES => false, 
	PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
	PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
	PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING );

try {
    $imslu = new PDO("mysql:host=127.0.0.1; dbname=imslu", "imslu", "imslu_password", $driver_options);
}
catch (PDOException $e) {

    //die ('SQL Error');
    echo "Imslu - ".$e->getMessage()."\n"; 
    exit();
}
try {
    $ipacct = new PDO("mysql:host=127.0.0.1; dbname=ipacct", "root", "", $driver_options);
}
catch (PDOException $e) {

    //die ('SQL Error');
    echo "Ipacct - ".$e->getMessage()."\n"; 
    exit();
}
try {
    $ipbill = new PDO("mysql:host=127.0.0.1; dbname=ipbill", "root", "", $driver_options);
}
catch (PDOException $e) {

    //die ('SQL Error');
    echo "Ipbill - ".$e->getMessage()."\n"; 
    exit();
}


echo "Import IP Addresses - STEP 1\n";
$txt = "";
$NETWORKS = array();
$SUBNETS = "";
$STATIC_ROUTES = "";
$DHCPD = "";

$sql = "SELECT id, name, data, macprot FROM addrpool";
$sth = $ipacct->prepare($sql);
$sth->execute();
$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

$addrpool = array();
foreach ($rows as $value) {

  $addrpool[$value['id']] = $value['name'];

  $str = preg_split("/[\s,]+/", $value['data']);
  foreach ($str as $data) {
    if (!empty($data)) {

      $str2 = preg_split("/[\.-]/", $data);
      //print_r($str2);
      $ipaddress_start = "{$str2[0]}.{$str2[1]}.{$str2[2]}.{$str2[3]}";
      $ipaddress_end = "{$str2[0]}.{$str2[1]}.{$str2[2]}.{$str2[4]}";

      $NETWORKS["{$str2[0]}{$str2[1]}"] = "{$str2[0]}.{$str2[1]}.0.0/16 ";
      $SUBNETS .= "{$str2[0]}.{$str2[1]}.{$str2[2]}.0/24 ";

      if ($value['macprot'] == "lmac") {

          $STATIC_ROUTES .= "{$str2[0]}.{$str2[1]}.{$str2[2]}.1/32 ";
      }

      if (filter_var($ipaddress_start, FILTER_VALIDATE_IP)) {

        $ip_start = ip2long($ipaddress_start);
        $ip_end = ip2long($ipaddress_end);
        $ipaddress = '';
        for ($i = $ip_start; $i <= $ip_end; ++$i) {
                
          $ipaddress[$i] = long2ip($i);
        }

        $sql = 'INSERT INTO ip (ip, pool) VALUES (:ip, :pool)';
        $imslu->beginTransaction();
        $sth = $imslu->prepare($sql);
        $sth->bindParam(':ip', $ip, PDO::PARAM_STR);
        $sth->bindParam(':pool', $value['name'], PDO::PARAM_STR);

        foreach ($ipaddress as $ip) {

          $sth->execute();
        }
        $imslu->commit();
      }
    }
  }
}

echo "Import Operators\n";
$sql = "SELECT * FROM users";
$sth = $ipacct->prepare($sql);
$sth->execute();
$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

$operators = array();
$id = 10;
foreach ($rows as $value) {

  if (!isset($operators[$value['login']])) {
    $operators[$value['login']]['id'] = $id;
    $operators[$value['login']]['alias'] = $value['login'];
    $id++;
  }

  switch($value['prop']) {
    case "type":
      switch($value['value']) {
        case "cashier":
          $operators[$value['login']]['type'] = 1;
          break;
        case "tech":
          $operators[$value['login']]['type'] = 2;
          break;
        case "admin":
          $operators[$value['login']]['type'] = 3;
          break;
        case "sysadmin":
          $operators[$value['login']]['type'] = 4;
          break;
      }
      break;
    case "actv":
      $operators[$value['login']]['actv'] = $value['value'];
      break;
    case "pass":
      $operators[$value['login']]['passwd'] = $value['value'];
      break;
    case "rname":
      $operators[$value['login']]['name'] = $value['value'];
      break;
  }
}
//print_r($operators);

if (!empty($operators)) {

  $imslu->beginTransaction();
  $sql = "INSERT IGNORE INTO operators (operid, alias, name, passwd, type) VALUES (:operid, :alias, :name, :passwd, :type)";
  $sql2 = "INSERT IGNORE INTO operators_groups (id, opergrpid, operid) VALUES (:id, :opergrpid, :operid2)";
  foreach ($operators as $value) {

    if ($value['actv'] == "y") {
      $name = !empty($value['name']) ? $value['name'] : '';
      $sth = $imslu->prepare($sql);
      $sth->bindValue(':operid', $value['id']);
      $sth->bindValue(':alias', $value['alias']);
      $sth->bindValue(':name', $name);
      $sth->bindValue(':passwd', MD5($value['passwd']));
      $sth->bindValue(':type', $value['type']);
      $sth->execute();

      $sth = $imslu->prepare($sql2);
      $sth->bindValue(':id', $value['id']);
      $sth->bindValue(':opergrpid', $value['type']);
      $sth->bindValue(':operid2', $value['id']);
      $sth->execute();
    }
  }
  $imslu->commit();
}


echo "Import Services\n";
$sql = "SELECT id, name, tax, d0rate, d0ceil, u0rate, u0ceil, d1rate, d1ceil, u1rate, u1ceil, resetperiod FROM services";
$sth = $ipacct->prepare($sql);
$sth->execute();
$services = $sth->fetchAll(PDO::FETCH_ASSOC);

$serviceid = array();
$imslu->beginTransaction();
$sql = "INSERT INTO services (serviceid, name, price, in_min0, in_max0, out_min0, out_max0, in_min1, in_max1, out_min1, out_max1) VALUES (:serviceid, :name, :price, :in_min0, :in_max0, :out_min0, :out_max0, :in_min1, :in_max1, :out_min1, :out_max1)";

foreach ($services as $value) {

  $serviceid[$value['id']]['name'] = $value['name'];
  $serviceid[$value['id']]['tax'] = $value['tax'];
  $serviceid[$value['id']]['resetperiod'] = $value['resetperiod'];

  $sth = $imslu->prepare($sql);
  $sth->bindValue(':serviceid', $value['id']);
  $sth->bindValue(':name', $value['name']);
  $sth->bindValue(':price', $value['tax']);
  $sth->bindValue(':in_min0', $value['d0rate']);
  $sth->bindValue(':in_max0', $value['d0ceil']);
  $sth->bindValue(':out_min0', $value['u0rate']);
  $sth->bindValue(':out_max0', $value['u0ceil']);
  $sth->bindValue(':in_min1', $value['d1rate']);
  $sth->bindValue(':in_max1', $value['d1ceil']);
  $sth->bindValue(':out_min1', $value['u1rate']);
  $sth->bindValue(':out_max1', $value['u1ceil']);
  $sth->execute();
}
$imslu->commit();
//print_r($services);
//print_r($serviceid);


echo "Import Location\n";
$sql = "SELECT id, name FROM cgroups";
$sth = $ipacct->prepare($sql);
$sth->execute();
$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

if (!empty($rows)) {

  $imslu->beginTransaction();
  $sql = "INSERT IGNORE INTO location (id, name) VALUES (:id, :name)";
  foreach ($rows as $value) {

    $sth = $imslu->prepare($sql);
    $sth->bindValue(':id', $value['id']);
    $sth->bindValue(':name', $value['name']);
    $sth->execute();
  }
  $imslu->commit();
}


echo "Import Users\n";
$sql = "SELECT id, name, tax, adr, pho, phom, valid, serviceid, comment, stdate, cgroup FROM client";
$sth = $ipacct->prepare($sql);
$sth->execute();
$users = $sth->fetchAll(PDO::FETCH_ASSOC);

$userid = array();
$id = 11;
$imslu->beginTransaction();
$sql = "INSERT INTO users (userid, name, locationid, address, phone_number, notes, created, serviceid, pay, free_access, not_excluding, expires) VALUES (:userid, :name, :locationid, :address, :phone_number, :notes, :created, :serviceid, :pay, :free_access, :not_excluding, :expires)";

for ($i=0; $i < count($users); $i++) {

  // In the new version users-->userid is used as a key to generate a tc classid
  // Using sequential numbers for new userid
  $userid[$users[$i]['id']]['id'] = $id;
  $userid[$users[$i]['id']]['name'] = $users[$i]['name'];

  $pay = ("{$users[$i]['tax']}.00" != $serviceid[$users[$i]['serviceid']]['tax']) ? $users[$i]['tax'] : '0.00';
  $free_access = ($users[$i]['valid'] == '0000-00-00') ? 'y' : 'n';
  $not_excluding = ($serviceid[$users[$i]['serviceid']]['resetperiod'] == 'n') ? "y" : "n"; 

  $sth = $imslu->prepare($sql);
  $sth->bindValue(':userid', $id);
  $sth->bindValue(':name', $users[$i]['name']);
  $sth->bindValue(':locationid', $users[$i]['cgroup']);
  $sth->bindValue(':address', $users[$i]['adr']);
  $sth->bindValue(':phone_number', "{$users[$i]['pho']} {$users[$i]['phom']}");
  $sth->bindValue(':notes', $users[$i]['comment']);
  $sth->bindValue(':created', $users[$i]['stdate']);
  $sth->bindValue(':serviceid', $users[$i]['serviceid']);
  $sth->bindValue(':pay', $pay);
  $sth->bindValue(':free_access', $free_access);
  $sth->bindValue(':not_excluding', $not_excluding);
  $sth->bindValue(':expires', $users[$i]['valid']." 23:59:00");
  $sth->execute();

  $id++;
}
$imslu->commit();
//print_r($users);
//print_r($userid);

echo "Import Payments\n";
$sql = "SELECT id, login, tstamp, cid, amt, tick, paid, descr, ptype, tstampb, tstampe FROM cash";
$sth = $ipbill->prepare($sql);
$sth->execute();
$rows = $sth->fetchAll(PDO::FETCH_ASSOC);
//print_r($rows);

$imslu->beginTransaction();
foreach ($rows as $value) {

  if (!empty($userid[$value['cid']]['id'])) {

    $unpaid = ($value['paid'] == 'y') ? 0 : 1;

    if ($value['paid'] == 'y') {
      $sql = "INSERT INTO payments (id, userid, name, unpaid, operator2, date_payment2, expires, sum, notes) VALUES (:id, :userid, :name, :unpaid, :operator2, :date_payment2, :expires, :sum, :notes)";
      $sth = $imslu->prepare($sql);
      $sth->bindValue(':id', $value['id']);
      $sth->bindValue(':userid', $userid[$value['cid']]['id']);
      $sth->bindValue(':name', $userid[$value['cid']]['name']);
      $sth->bindValue(':unpaid', $unpaid);
      $sth->bindValue(':operator2', $value['login']);
      $sth->bindValue(':date_payment2', $value['tstamp']);
      $sth->bindValue(':expires', $value['tstampe']);
      $sth->bindValue(':sum', $value['amt']);
      $sth->bindValue(':notes', $value['descr']);
      $sth->execute();
    }
    else {
      $sql = "INSERT INTO payments (id, userid, name, unpaid, operator1, date_payment1, expires, sum, notes) VALUES (:id, :userid, :name, :unpaid, :operator1, :date_payment1, :expires, :sum, :notes)";
      $sth = $imslu->prepare($sql);
      $sth->bindValue(':id', $value['id']);
      $sth->bindValue(':userid', $userid[$value['cid']]['id']);
      $sth->bindValue(':name', $userid[$value['cid']]['name']);
      $sth->bindValue(':unpaid', $unpaid);
      $sth->bindValue(':operator1', $value['login']);
      $sth->bindValue(':date_payment1', $value['tstamp']);
      $sth->bindValue(':expires', $value['tstampe']);
      $sth->bindValue(':sum', $value['tick']);
      $sth->bindValue(':notes', $value['descr']);
      $sth->execute();
    }
  }
}
$imslu->commit();


echo "Update IP Addresses - STEP 2\n";

$sql = "SELECT iid, mac FROM xmacs";
$sth = $ipacct->prepare($sql);
$sth->execute();
$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

$xmacs = array();
foreach ($rows as $value) {
  $xmacs[$value['iid']] = $value['mac'];
}

$sql = "SELECT net FROM dhcp";
$sth = $ipacct->prepare($sql);
$sth->execute();
$rows = $sth->fetchAll(PDO::FETCH_ASSOC);

$dhcp = array();
if (!empty($rows)) {
    foreach ($rows as $value) {
        $str = preg_split("/[\.]/", $value['net']);
        $dhcp["{$str[0]}{$str[1]}{$str[2]}"] = $value['net'];
        $DHCPD .= "subnet {$str[0]}.{$str[1]}.{$str[2]}.0 netmask 255.255.255.0 {\n  option routers {$str[0]}.{$str[1]}.{$str[2]}.1;\n}\n";
    }
}

$sql = "SELECT id, cid, name, ip, stopped, pass, macprot, autosavemac FROM ips";
$sth = $ipacct->prepare($sql);
$sth->execute();
$rows = $sth->fetchAll(PDO::FETCH_ASSOC);
//print_r($rows);

$imslu->beginTransaction();
foreach ($rows as $value) {

  $uid = (!empty($userid[$value['cid']]['id'])) ? $userid[$value['cid']]['id'] : 0;
  $free_mac = ($value['autosavemac'] == 'y') ? 'n' : 'y';


  if ($value['macprot'] == "lpmac" && !empty($value['name'])) {

    $str = strip_tags($value['name']);
    $username = preg_replace('/\s+/', '_', $str);
    $password = strip_tags($value['pass']);
    $mac = !empty($xmacs[$value['id']]) ? strtolower($xmacs[$value['id']]) : '';

    $sql = 'UPDATE ip SET userid = :userid, mac = :mac, free_mac = :free_mac, username = :username, pass = :pass, protocol = :protocol, stopped = :stopped WHERE ip = :ip';
    $sth = $imslu->prepare($sql);
    $sth->bindValue(':userid', $uid);
    $sth->bindValue(':mac', $mac);
    $sth->bindValue(':free_mac', $free_mac);
    $sth->bindValue(':username', $username);
    $sth->bindValue(':pass', $password);
    $sth->bindValue(':protocol', 'PPPoE');
    $sth->bindValue(':stopped', $value['stopped']);
    $sth->bindValue(':ip', $value['ip']);
    $sth->execute();

    $sql = 'INSERT INTO radcheck ( userid, username, attribute, op, value) VALUES (:userid, :username, :attribute, :op, :value)';
    $sth = $imslu->prepare($sql);
    $sth->bindValue(':userid', $uid, PDO::PARAM_INT);
    $sth->bindValue(':username', $username, PDO::PARAM_STR);
    $sth->bindValue(':attribute', 'Cleartext-Password');
    $sth->bindValue(':op', ':=');
    $sth->bindValue(':value', $password, PDO::PARAM_STR);
    $sth->execute();

    $sql = 'INSERT INTO radcheck (userid, username, attribute, op, value) VALUES (:userid, :username, :attribute, :op, :value)';
    $sth = $imslu->prepare($sql);
    $sth->bindValue(':userid', $uid, PDO::PARAM_INT);
    $sth->bindValue(':username', $username, PDO::PARAM_STR);
    $sth->bindValue(':attribute', 'Calling-Station-Id');
    $sth->bindValue(':value', $mac, PDO::PARAM_STR);
    $sth->bindValue(':op', ':=');
    $sth->execute();

    $sql = 'INSERT INTO radusergroup (username, groupname, userid) VALUES (:username, :groupname, :userid)';
    $sth = $imslu->prepare($sql);
    $sth->bindValue(':username', $username, PDO::PARAM_STR);
    $sth->bindValue(':groupname', 'default', PDO::PARAM_STR);
    $sth->bindValue(':userid', $uid, PDO::PARAM_INT);
    $sth->execute();
    
  }
  elseif ($value['macprot'] == "lmac") {

    $str = preg_split("/[\.]/", $value['ip']);
    $protocol = !empty($dhcp["{$str[0]}{$str[1]}{$str[2]}"]) ? "DHCP" : "IP";
    $mac = !empty($xmacs[$value['id']]) ? $xmacs[$value['id']] : '';
    $sql = 'UPDATE ip SET userid = :userid, mac = :mac, free_mac = :free_mac, protocol = :protocol, stopped = :stopped WHERE ip = :ip';
    $sth = $imslu->prepare($sql);
    $sth->bindValue(':userid', $uid);
    $sth->bindValue(':mac', $mac);
    $sth->bindValue(':free_mac', $free_mac);
    $sth->bindValue(':protocol', $protocol);
    $sth->bindValue(':stopped', $value['stopped']);
    $sth->bindValue(':ip', $value['ip']);
    $sth->execute();
    
  }
  else {
      echo "Unsupported protocol {$value['macprot']} for IP {$value['ip']}\n";
  }
}
$imslu->commit();


$log_file = "/tmp/import-ipacct.log";
$file = fopen("$log_file", "w") or die("Unable to open file!");

$txt .= "IMSLU database ready for use!\nYou can export the database.\n";
$txt .= "Add the following configurations in 'config.sh':\n\n";
if (!empty($NETWORKS)) {

    $NET = "";
    foreach ($NETWORKS as $value) {

        $NET .= "{$value} ";
    }
    $txt .= "NETWORKS=\"{$NET}\"\n";
}

$txt .= "SUBNETS=\"{$SUBNETS}\"\n";
$txt .= "STATIC_ROUTES=\"{$STATIC_ROUTES}\"\n";
$txt .= "Do not forget to add vlans!\n\n";

if (!empty($DHCPD)) {
    $txt .= "Add the following configurations in 'dhcpd.conf':\n\n";
    $txt .= "{$DHCPD} \n";
}

fwrite($file, $txt);
fclose($file);
echo $txt;
echo "See the {$log_file} file for the above message.\n";
?>
