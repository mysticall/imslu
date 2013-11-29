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

$sysadmin_rights = (OPERATOR_TYPE_LINUX_ADMIN == CWebOperator::$data['type']);
$admin_rights = (OPERATOR_TYPE_ADMIN == CWebOperator::$data['type']);


$menu_top = array (
	1 =>	array (
			'name'		=> 	_('Logout'),
			'class'		=> 	'logout',
			'link'		=> 	'logout.php',
			'img'		=>	'log-out.png',
			'check_anyright'=>	TRUE,
			),				
	2 =>	array (
			'name'		=> 	_('Profile'),
			'class'		=> 	'profile',
			'link'		=> 	'profile.php',
			'img'		=>	'avatar-default.png',
			'check_anyright'=>	TRUE,
			)
	);


$menu_right = array (
	1 =>	array (
			'name'		=> 	_('Static IP addresses'),
			'class'		=> 	'#',
			'link'		=> 	'static_ippool.php',
			'img'		=>	'ip_pub.gif',
			'check_anyright'=>	$sysadmin_rights,
			'submenu' => array(
			
			1 =>	array(
					'name' 		=> _('Traffic control'),
					'link' 		=>'traffic_control.php',
					'img'		=>	'wired.png',
					'check_anyright'=>	TRUE,
					)				
				)			
			),
	2 =>	array (
			'name'		=> 	_('FreeRADIUS'),
			'class'		=> 	'#',
			'link'		=> 	'#',
			'img'		=>	'vpn.png',
			'check_anyright'=>	$sysadmin_rights,
			'submenu' => array(
			
			1 =>	array(
					'name' 		=> _('NAS'),
					'link' 		=>'freeradius_nas.php',
					'img'		=>	'network-server.png',
					'check_anyright'=>	TRUE,
					),
			2 =>	array(
					'name'		=> _('IP addresses'),
					'link' 		=>'freeradius_sqlippool.php',
					'img'		=>	'ip_pub.gif',
					'check_anyright'=>	TRUE,
					),
			3 =>	array(
					'name'		=> _('Groups'),
					'link' 		=>'freeradius_groups.php',
					'img'		=>	'preferences-desktop-peripherals.png',
					'check_anyright'=>	TRUE,
					)					
				)			
			),
	3 =>	array (
			'name'		=> 	_('Administration'),
			'class'		=> 	'#',
			'link'		=> 	'administration.php',
			'img'		=>	'system.png',
			'check_anyright'=>	($sysadmin_rights || $admin_rights),
			'submenu' => array(
			
			1 =>	array(
					'name' 		=> _('Operators'),
					'link' 		=>'operators.php',
					'img'		=>	'users.png',
					'check_anyright'=>	TRUE,
					),
			2 =>	array(
					'name' 		=> _('The location'),
					'link' 		=>'user_location.php',
					'img'		=>	'weather-clear-night.png',
					'check_anyright'=>	TRUE,
					),
			3 =>	array(
					'name' 		=> _('Audit'),
					'link' 		=>'audit.php',
					'img'		=>	'help-faq.png',
					'check_anyright'=>	TRUE,
					),
			4 =>	array(
					'name' 		=> _('Login attempts'),
					'link' 		=>'login_attempts.php',
					'img'		=>	'lock.png',
					'check_anyright'=>	TRUE,
					),
            5 =>    array(
                    'name'      => _('FreeRADIUS logs'),
                    'link'      =>'freeradius_logs.php',
                    'img'       =>  'accessories-dictionary.png',
                    'check_anyright'=>  TRUE,
                    )
				)
			),
	4 =>	array (
			'name'		=> 	_('Users'),
			'class'		=> 	'#',
			'link'		=> 	'users.php',
			'img'		=>	'user.png',
			'check_anyright'=>	TRUE,
			'submenu' => array(
		
			1 =>	array(
					'name'		=> _('New user'),
					'link' 		=>'user_new.php',
					'img'		=>	'list-add.png',
					'check_anyright'=>	TRUE,
					)
				)
			),	
	5 =>	array (
			'name'		=> 	_('Payments'),
			'class'		=> 	'#',
			'link'		=> 	'payments.php',
			'img'		=>	'payment.gif',
			'check_anyright'=>	TRUE,
			),
	6 =>	array (
			'name'		=> 	_('Monitoring'),
			'class'		=> 	'#',
			'link'		=> 	'#',
			'img'		=>	'utilities-system-monitor.png',
			'check_anyright'=>	FALSE,
			),
	7 =>	array (
			'name'		=> 	_('Map'),
			'class'		=> 	'#',
			'link'		=> 	'#',
			'img'		=>	'applications-internet.png',
			'check_anyright'=>	FALSE,
			),
	);


