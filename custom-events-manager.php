<?php
/*
@package Custom_Events_manager
@version 1.0.0

Plugin Name: Custom Events Manager
Plugin URI: http://www.reticent.net
Description: This plugin allows for creation of custom event registration pages.
Version: 1.0.0
Author: Silas Montgomery
Author URI: http://reticent.net/
License: GPL2

Copyright 2012  Silas Montgomery  (email : nomsalis@reticent.net)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define( 'CEM_PATH', plugin_dir_path(__FILE__) );
define( 'CEM_JS_PATH', plugins_url('/js/', __FILE__) );
define( 'CEM_CSS_PATH', plugins_url('/css/', __FILE__) );
define( 'CEM_VERSION', '1.0.0' );

require_once( CEM_PATH . 'lib/class-registration-page.php' );
require_once( CEM_PATH . 'lib/class-administration-page.php' );
require_once( CEM_PATH . 'lib/class-cem-installation.php');

register_activation_hook( __FILE__,  array('CEM_Installation', 'cem_install') );

$registration = new Registration_Page();
$administration = new Administration_Page();

?>