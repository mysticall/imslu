#!/usr/bin/env python

# Copyright (c) 2013 IMSLU Developers

# This program is free software; you can redistribute it and/or modify it 
# under the terms of the GNU General Public License as published by the 
# Free Software Foundation; either version 2 of the License, or (at you option) 
# any later version.

# This program is distributed in the hope that it will be useful, but 
# WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY 
# or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License 
# for more details.

# You should have received a copy of the GNU General Public License along 
# with this program; if not, write to the Free Software Foundation, Inc., 
# 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

from config import USE_VLANS
import functions

if USE_VLANS is True:
    functions.find_mac_in_vlan()
    functions.find_vlan_for_mac()
    functions.find_vlan_for_free_mac()
    functions.find_vlan_mac()
else:
    functions.find_mac()