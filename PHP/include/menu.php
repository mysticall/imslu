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

$sysadmin_permissions = (OPERATOR_TYPE_LINUX_ADMIN == $_SESSION['data']['type']);
$admin_permissions = (OPERATOR_TYPE_ADMIN == $_SESSION['data']['type']);


$top_menu = array (
    1 => array (
        'name'  => _('logout'),
        'class' => 'logout',
        'link'  => 'logout.php',
        'img'   => 'log-out.png',
        'check_permissions' => TRUE,
    ),				
	2 => array (
        'name'  => _('profile'),
        'class' => 'profile',
        'link'  => 'profile.php',
        'img'   => 'avatar-default.png',
        'check_permissions'=>	TRUE,
    ),
	3 => array (
        'name'  => 	_('ping'),
        'class' => 	'ping',
        'link'  => 	'ping.php',
        'img'   =>	'network-transmit-receive.png',
        'check_permissions' => TRUE,
			),
	4 => array (
        'name'  => 	_('tickets'),
        'class' => 	'tikets',
        'link'  => 	'tickets.php',
        'img'   =>	'dialog-error.png',
        'check_permissions' => TRUE,
    ),
    5 => array (
        'name'  => _('requests'),
        'class' => 'requests',
        'link'  => 'requests.php',
        'img'   => 'emblem-documents.png',
        'check_permissions' => TRUE,
    )
);


$right_menu = array (
	1 => array (
        'name'  => 	_('static IP addresses'),
        'class' => 	'#',
        'link'  => 	'static_ippool.php',
        'img'   =>	'ip_pub.gif',
        'check_permissions' => $sysadmin_permissions,
        'submenu' => array(
            1 => array(
                'name' => _('traffic control'),
                'link' => 'traffic_control.php',
                'img'  => 'wired.png',
                'check_permissions' => TRUE,
            )				
        )			
    ),
	2 => array (
        'name'  => _('freeRadius'),
        'class' => '#',
        'link'  => '#',
        'img'   => 'vpn.png',
        'check_permissions' => $sysadmin_permissions,
        'submenu' => array(
            1 => array(
                'name' => _('nas'),
                'link' => 'freeradius_nas.php',
                'img'  => 'network-server.png',
                'check_permissions' => TRUE,
            ),
            2 => array(
                'name' => _('IP addresses'),
                'link' => 'freeradius_sqlippool.php',
                'img'  => 'ip_pub.gif',
                'check_permissions' => TRUE,
            ),
			3 => array(
                'name' => _('groups'),
                'link' => 'freeradius_groups.php',
                'img'  => 'preferences-desktop-peripherals.png',
                'check_permissions' => TRUE,
            )					
        )			
    ),
	3 => array (
        'name'  => _('administration'),
        'class' => '#',
        'link'  => 'administration.php',
        'img'   => 'system.png',
        'check_permissions' => ($sysadmin_permissions || $admin_permissions),
        'submenu' => array(
            1 => array(
                'name' => _('operators'),
                'link' => 'operators.php',
                'img'  => 'users.png',
                'check_permissions' => TRUE,
            ),
            2 => array(
                'name' => _('the location'),
                'link' => 'user_location.php',
                'img'  => 'weather-clear-night.png',
                'check_permissions' => TRUE,
            ),
            3 => array(
                'name' => _('audit'),
                'link' => 'audit.php',
                'img'  => 'help-faq.png',
					'check_permissions'=>	TRUE,
            ),
            4 => array(
                'name' => _('login attempts'),
                'link' => 'login_attempts.php',
                'img'  => 'lock.png',
                'check_permissions' => TRUE,
            ),
            5 => array(
                'name' => _('freeRadius logs'),
                'link' => 'freeradius_logs.php',
                'img'  => 'accessories-dictionary.png',
                'check_permissions' => TRUE,
            )
        )
    ),
	4 => array (
        'name'  => _('users'),
        'class' => '#',
        'link'  => 'users.php',
        'img'   => 'user.png',
        'check_permissions' => TRUE,
        'submenu' => array(
            1 => array(
                'name' => _('new user'),
                'link' => 'user_new.php',
                'img'  => 'list-add.png',
                'check_permissions' => TRUE,
            )
        )
    ),	
	5 => array (
        'name'  => _('payments'),
        'class' => '#',
        'link'  => 'payments.php',
        'img'   => 'payment.gif',
        'check_permissions' => TRUE,
    )
);

