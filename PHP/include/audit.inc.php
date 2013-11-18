<?php
/*
 * IMSLU version 0.1-alpha
 *
 * Copyright © 2013 IMSLU Developers
 * 
 * Please, see the doc/AUTHORS for more information about authors!
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */
 
function add_audit($db, $actionid, $resourceid, $details, $oldvalue = null, $newvalue = null) {

	$values = array(
		'actionid' => $actionid,
		'resourceid' => $resourceid,
		'operid' => CWebOperator::$data['operid'],
		'oper_alias' => CWebOperator::$data['alias'],
		'date_time' => date('Y-m-d H:i:s'),
		'ip' => CWebOperator::$data['operip'],
		'details' => $details,
		'oldvalue' => $oldvalue,
		'newvalue' => $newvalue	
	);

	$sql = 'INSERT INTO auditlog ('.implode(',', array_keys($values)).')'.
			" VALUES ('".implode("', '", array_values($values))."')";
	$sth = $db->dbh->prepare($sql);
	$sth->execute();
}

