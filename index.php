<?php
/*
Plugin Name: MF GitHub Updater
Plugin URI: https://github.com/frostkom/mf_github_updater
Description: A Wordpress plugin to display that there are plugin or theme updates on GitHub
Version: 1.0.12
Licence: GPLv2 or later
Author: Martin Fors
Author URI: https://martinfors.se

Depends: MF Base
GitHub Plugin URI: frostkom/mf_github_updater
*/

if(!function_exists('is_plugin_active') || function_exists('is_plugin_active') && is_plugin_active("mf_base/index.php"))
{
	include_once("include/classes.php");

	$obj_github_updater = new mf_github_updater();

	if(is_admin())
	{
		register_uninstall_hook(__FILE__, 'uninstall_github_updater');

		add_filter('pre_set_site_transient_update_plugins', array($obj_github_updater, 'pre_set_site_transient_update_plugins'), 10, 1);
	}

	function uninstall_github_updater()
	{
		mf_uninstall_plugin(array(
			'options' => array('option_github_updater_last_success', 'option_github_updater_next_try'),
		));
	}
}