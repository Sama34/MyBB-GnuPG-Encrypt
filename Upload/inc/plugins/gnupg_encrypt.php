<?php

/***************************************************************************
 *
 *	GnuPG Encrypt (/inc/plugins/gnupg_encrypt.php)
 *	Author: Omar Gonzalez
 *	Author Website: https://ougc.network
 *
 *	Protect an account and private messages using GnuPG encryption.
 *
 ***************************************************************************/

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

// PLUGINLIBRARY
defined('PLUGINLIBRARY') or define('PLUGINLIBRARY', MYBB_ROOT.'inc/plugins/pluginlibrary.php');

// The information that shows up on the plugin manager
function gnupg_encrypt_info()
{
	global $gnupg_encrypt;

	return $gnupg_encrypt->_info();
}

// This function runs when the plugin is activated.
function gnupg_encrypt_activate()
{
	global $gnupg_encrypt;

	$gnupg_encrypt->_activate();
}

// This function runs when the plugin is deactivated.
function gnupg_encrypt_deactivate()
{
	global $gnupg_encrypt;

	$gnupg_encrypt->_deactivate();
}

// This function runs when the plugin is installed.
function gnupg_encrypt_install()
{
	global $gnupg_encrypt;

	$gnupg_encrypt->_install();
}

// Checks to make sure plugin is installed
function gnupg_encrypt_is_installed()
{
	global $gnupg_encrypt;

	return $gnupg_encrypt->_is_installed();
}

// This function runs when the plugin is uninstalled.
function gnupg_encrypt_uninstall()
{
	global $gnupg_encrypt;

	$gnupg_encrypt->_uninstall();
}

class GnuPG_Encrypt
{
	private $gpg;

	// The information that shows up on the plugin manager
	function __construct()
	{
		global $plugins;

		// Tell MyBB when to run the hooks
		if(defined('IN_ADMINCP'))
		{
		}
		else
		{
			$plugins->add_hook('member_register_end', array($this, 'hook_member_register_end'));
			$plugins->add_hook('usercp_profile_end', array($this, 'hook_usercp_profile_end'));
			/*$plugins->add_hook('modcp_editprofile_end', array($this, 'hook_modcp_editprofile_end'));*/
			$plugins->add_hook('datahandler_user_validate', array($this, 'hook_datahandler_user_validate'));
			$plugins->add_hook('datahandler_user_update', array($this, 'hook_datahandler_user_update'));
			$plugins->add_hook('datahandler_user_insert', array($this, 'hook_datahandler_user_insert'));
			$plugins->add_hook('member_profile_end', array($this, 'hook_member_profile_end'));

			// Neat trick for caching our custom template(s)
			global $templatelist;

			if(isset($templatelist))
			{
				$templatelist .= ',';
			}
			else
			{
				$templatelist = '';
			}
	
			$templatelist .= '';

			if(THIS_SCRIPT == 'modcp.php')
			{
				$templatelist .= ', ';
			}

			if(THIS_SCRIPT == 'usercp.php')
			{
				$templatelist .= ', ';
			}
		}
	}

	// Plugin API
	function _info()
	{
		global $lang, $plugins_cache;

		$this->_lang_load();

		$this->_plugin_edit();

		$info = array(
			"name"			=> 'GnuPG Encrypt',
			"description"	=> $lang->setting_group_gnupg_encrypt_desc,
			'website'		=> 'https://ougc.network',
			'author'		=> 'Omar G.',
			'authorsite'	=> 'https://ougc.network',
			'version'		=> '1.8.0',
			'versioncode'	=> 1800,
			'compatibility'	=> '18*',
			'codename'		=> 'gnupg_encrypt',
			'pl'			=> array(
				'version'	=> 13,
				'url'		=> 'https://community.mybb.com/mods.php?action=view&pid=573'
			)
		);

		if($this->_is_installed() && !empty($plugins_cache['active']) && !empty($plugins_cache['active']['gnupg_encrypt']))
		{
			global $PL, $mybb;

			$status = '';

			$this->_pluginlibrary();

			$edits = array();

			// Check edits to core files.
			if($this->_edits_apply() !== true)
			{
				$apply = $PL->url_append('index.php', array(
					'module' => 'config-plugins',
					'gnupg_encrypt' => 'apply',
					'my_post_key' => $mybb->post_code,
				));

				$edits['warning'] = "<a href=\"{$apply}\">{$lang->gnupg_encrypt_pluginlibrary_apply}</a>";
			}
		
			if($this->_edits_revert() !== true)
			{
				$revert = $PL->url_append('index.php', array(
					'module' => 'config-plugins',
					'gnupg_encrypt' => 'revert',
					'my_post_key' => $mybb->post_code,
				));

				$edits['tick'] = "<a href=\"{$revert}\">{$lang->gnupg_encrypt_pluginlibrary_revert}</a>";
			}

			if(count($edits))
			{
				foreach($edits as $image => $edit)
				{
					$status .= "<li style=\"list-style-image: url(styles/default/images/icons/{$image}.png)\">{$edit}</li>\n";
				}
			}

			$info['description'] = $info['description'].$status;
		}

		return $info;
	}

	// Load language
	function _lang_load()
	{
		global $lang;
	
		isset($lang->setting_group_gnupg_encrypt) or $lang->load("gnupg_encrypt");
	}

	// Load PluginLibrary
	function _pluginlibrary()
	{
		if($file_exists = file_exists(PLUGINLIBRARY))
		{
			global $PL;
		
			$PL or require_once PLUGINLIBRARY;
		}
	
		if(!$file_exists || $PL->version < $info['pl']['version'])
		{
			global $lang;
	
			$this->_lang_load();	

			$i = _info();

			flash_message($lang->sprintf($lang->gnupg_encrypt_pluginlibrary, $i['pl']['ulr'], $i['pl']['version']), 'error');

			admin_redirect('index.php?module=config-plugins');
		}
	}

	// This function runs when the plugin is activated.
	function _activate()
	{
		global $db, $PL, $cache, $lang, $gnupg_encrypt;
	
		$this->_lang_load();
	
		$this->_pluginlibrary();
	
		// Add settings group
		$PL->settings('gnupg_encrypt', $lang->setting_group_gnupg_encrypt, $lang->setting_group_gnupg_encrypt_desc, array(
			'groups'		=> array(
			   'title'			=> $lang->setting_gnupg_encrypt_groupselect,
			   'description'	=> $lang->setting_gnupg_encrypt_groupselect_desc,
			   'optionscode'	=> 'groupselect',
			   'value'			=> ''
			),
			'mods'		=> array(
			   'title'			=> $lang->setting_gnupg_encrypt_mods,
			   'description'	=> $lang->setting_gnupg_encrypt_mods_desc,
			   'optionscode'	=> 'groupselect',
			   'value'			=> '3,4'
			),
			'forceonregister'		=> array(
			   'title'			=> $lang->setting_gnupg_encrypt_forceonregister,
			   'description'	=> $lang->setting_gnupg_encrypt_forceonregister_desc,
			   'optionscode'	=> 'yesno',
			   'value'			=> 1
			),
			'forcegroups'		=> array(
			   'title'			=> $lang->setting_gnupg_encrypt_forcegroups,
			   'description'	=> $lang->setting_gnupg_encrypt_forcegroups_desc,
			   'optionscode'	=> 'groupselect',
			   'value'			=> -1
			),
		));
	
		// Add template group
		$PL->templates('gnupgencrypt', 'GnuPG Encrypt', array(
			'' => '',
		));
	
		// Insert/update version into cache
		$plugins = $cache->read('ougc_plugins');

		if(!$plugins)
		{
			$plugins = array();
		}
	
		$info = $this->_info();
	
		if(!isset($plugins['gnupg_encrypt']))
		{
			$plugins['gnupg_encrypt'] = $info['versioncode'];
		}
	
		$this->_db_verify_columns();
	
		/*~*~* RUN UPDATES START *~*~*/
	
		/*~*~* RUN UPDATES END *~*~*/
	
		$plugins['gnupg_encrypt'] = $info['versioncode'];
	
		$cache->update('ougc_plugins', $plugins);
	
		include MYBB_ROOT."/inc/adminfunctions_templates.php";

		find_replace_templatesets('member_register', '#'.preg_quote('{$boardlanguage}').'#i', '{$gnupg_encrypt_register}{$boardlanguage}');
		find_replace_templatesets('member_profile', '#'.preg_quote('{$signature}').'#i', '{$gnupg_encrypt_profile}{$signature}');
		find_replace_templatesets('usercp_profile', '#'.preg_quote('{$contactfields}').'#i', '{$gnupg_encrypt_usercp}{$contactfields}');
		find_replace_templatesets('modcp_editprofile', '#'.preg_quote('{$customfields}').'#i', '{$gnupg_encrypt_modcp}{$customfields}');
	}

	// This function runs when the plugin is deactivated.
	function _deactivate()
	{
		include MYBB_ROOT."/inc/adminfunctions_templates.php";
	
		find_replace_templatesets("member_register", "#".preg_quote('{$gnupg_encrypt_register}')."#i", '', 0);
		find_replace_templatesets("member_profile", "#".preg_quote('{$gnupg_encrypt_profile}')."#i", '', 0);
		find_replace_templatesets("usercp_profile", "#".preg_quote('{$gnupg_encrypt_usercp}')."#i", '', 0);
		find_replace_templatesets("modcp_editprofile", "#".preg_quote('{$gnupg_encrypt_modcp}')."#i", '', 0);
	}

	// This function runs when the plugin is installed.
	function _install()
	{
		global $db, $cache, $gnupg_encrypt;
	
		$this->_db_verify_columns();
	}

	// Checks to make sure plugin is installed
	function _is_installed()
	{
		global $db;
	
		static $is_installed = null;

		if($is_installed === null)
		{
			global $cache;

			$is_installed = false;

			foreach($this->_db_columns() as $table => $columns)
			{
				foreach($columns as $name => $definition)
				{
					$is_installed = $db->field_exists($name, $table);

					break;
				}
			}
	
			$plugins = $cache->read('ougc_plugins');
	
			if(!$plugins)
			{
				$plugins = array();
			}
		
			$is_installed = $is_installed && isset($plugins['gnupg_encrypt']);
		}
	
		return $is_installed;
	}

	// This function runs when the plugin is uninstalled.
	function _uninstall()
	{
		global $db, $cache, $PL;

		$this->_pluginlibrary();
	
		foreach($this->_db_columns() as $table => $columns)
		{
			foreach($columns as $name => $definition)
			{
				!$db->field_exists($name, $table) || $db->drop_column($table, $name);
			}
		}
	
		$PL->settings_delete('gnupg_encrypt');

		$PL->templates_delete('gnupgencrypt');
	
		// Delete version from cache
		$plugins = (array)$cache->read('ougc_plugins');
	
		if(isset($plugins['gnupg_encrypt']))
		{
			unset($plugins['gnupg_encrypt']);
		}
	
		if(!empty($plugins))
		{
			$cache->update('ougc_plugins', $plugins);
		}
		else
		{
			$cache->delete('ougc_plugins');
		}

		// Revert edits.
		if($this->_edits_revert(true) !== true)
		{
			flash_message($lang->gnupg_encrypt_pluginlibrary_error_revert, 'error');
	
			admin_redirect('index.php?module=config-plugins');
		}
	}

	// List of columns
	function _db_columns()
	{
		$tables = array(
			'users' => array(
				'gnupg_encrypt_public_key' => "text NULL",
				'gnupg_encrypt_fingerprint' => "text NULL",
			),
		);

		return $tables;
	}

	// Verify DB columns
	function _db_verify_columns()
	{
		global $db;

		foreach($this->_db_columns() as $table => $columns)
		{
			foreach($columns as $field => $definition)
			{
				if($db->field_exists($field, $table))
				{
					$db->modify_column($table, "`{$field}`", $definition);
				}
				else
				{
					$db->add_column($table, $field, $definition);
				}
			}
		}
	}

	function _plugin_edit()
	{
		global $mybb, $lang;

		// Check for core file edit action
		if($mybb->input['my_post_key'] == $mybb->post_code && isset($mybb->input['gnupg_encrypt']))
		{
			if($mybb->input['gnupg_encrypt'] == 'apply')
			{
				if($this->_edits_apply(true) === true)
				{
					flash_message($lang->gnupg_encrypt_pluginlibrary_success_apply, 'success');

					admin_redirect('index.php?module=config-plugins');
				}
	
				else
				{
					flash_message($lang->gnupg_encrypt_pluginlibrary_error_apply, 'error');
	
					admin_redirect('index.php?module=config-plugins');
				}
			}
	
			if($mybb->input['gnupg_encrypt'] == 'revert')
			{
				if($this->_edits_revert(true) === true)
				{
					flash_message($lang->gnupg_encrypt_pluginlibrary_success_revert, 'success');

					admin_redirect('index.php?module=config-plugins');
				}
	
				else
				{
					flash_message($lang->gnupg_encrypt_pluginlibrary_error_revert, 'error');

					admin_redirect('index.php?module=config-plugins');
				}
			}
		}
	}

	function _edits_apply($apply=false)
	{
		global $PL;

		$this->_pluginlibrary();
	
		$edits = array(/*array(
			'search' => array('static $formattednames = array();'),
			'after' => array(
				'global $gnupg_encrypt;',
				'if($gnupg_encrypt instanceof gnupg_encrypt)',
				'{',
				'$gnupg_encrypt->format_name($formattednames, $username, $usergroup, $displaygroup);',
				'}'
			),
		)*/);
	
		return $PL->edit_core('gnupg_encrypt', 'inc/functions.php', $edits, $apply);
	}
	
	function _edits_revert($apply=false)
	{
		global $PL;
	
		$this->_pluginlibrary();
	
		return $PL->edit_core('gnupg_encrypt', 'inc/functions.php', array(), $apply);
	}

	// Hook: member_register_end
	function hook_member_register_end()
	{
		global $templates, $gnupg_encrypt_register, $lang, $mybb;
	
		$this->_lang_load();

		$gnupg_encrypt_fingerprint = $gnupg_encrypt_public_key = '';

		if(isset($mybb->input['gnupg_encrypt_fingerprint']))
		{
			$gnupg_encrypt_fingerprint = htmlspecialchars_uni($mybb->get_input('gnupg_encrypt_fingerprint', MyBB::INPUT_STRING));
		}

		if(isset($mybb->input['gnupg_encrypt_public_key']))
		{
			$gnupg_encrypt_public_key = htmlspecialchars_uni($mybb->get_input('gnupg_encrypt_public_key', MyBB::INPUT_STRING));
		}

		$gnupg_encrypt_register = eval($templates->render('gnupgencrypt_register'));
	}

	// Hook: usercp_options_end
	function hook_usercp_profile_end()
	{
		global $themes, $mybb, $templates, $theme, $gnupg_encrypt_usercp, $lang;
	
		$gnupg_encrypt_usercp = '';

		if(!is_member($mybb->settings['gnupg_encrypt_groups']))
		{
			return;
		}

		$this->_lang_load();

		$gnupg_encrypt_public_key = $mybb->user['gnupg_encrypt_public_key'];

		$gnupg_encrypt_fingerprint = $mybb->user['gnupg_encrypt_fingerprint'];

		if(isset($mybb->input['gnupg_encrypt_public_key']))
		{
			$gnupg_encrypt_public_key = $mybb->get_input('gnupg_encrypt_public_key', MyBB::INPUT_STRING);
		}

		if(isset($mybb->input['gnupg_encrypt_fingerprint']))
		{
			$gnupg_encrypt_fingerprint = $mybb->get_input('gnupg_encrypt_fingerprint', MyBB::INPUT_STRING);
		}

		$gnupg_encrypt_public_key = htmlspecialchars_uni($gnupg_encrypt_public_key);

		$gnupg_encrypt_fingerprint = htmlspecialchars_uni($gnupg_encrypt_fingerprint);

		$gnupg_encrypt_usercp = eval($templates->render('gnupgencrypt_usercp'));
	}

	// Hook: modcp_editprofile_end
	function hook_modcp_editprofile_end()
	{
		global $themes, $mybb, $templates, $theme, $gnupg_encrypt_modcp_profile, $lang, $user;
	
		$gnupg_encrypt_modcp_profile = '';

		if(!is_member($mybb->settings['gnupg_encrypt_mods']))
		{
			return;
		}

		$this->_lang_load();
	
		$lang->colorucp_desc = $lang->sprintf($lang->colorucp_desc, $mybb->settings['bburl']);

		if(isset($mybb->input['gnupg_encrypt']))
		{
			$value = htmlspecialchars_uni($mybb->get_input('gnupg_encrypt', MyBB::INPUT_STRING));
		}
		else
		{
			$value = htmlspecialchars_uni($user['gnupg_encrypt']);
		}

		if(!empty($value))
		{
			$value = '#'.$value;
		}

		$username = htmlspecialchars_uni($user['username']);

		$username = format_name($username, $user['usergroup'], $user['displaygroup']);

		$js = eval($templates->render('gnupg_encrypt_js'));

		$gnupg_encrypt_modcp_profile = eval($templates->render('gnupg_encrypt_modcp_profile'));
	}

	// Hook: datahandler_user_validate
	function hook_datahandler_user_validate(&$args)
	{
		global $mybb, $lang, $plugins;

		$registration = THIS_SCRIPT == 'member.php';

		$modcp = THIS_SCRIPT == 'modcp.php';

		if(!is_member($mybb->settings['gnupg_encrypt_groups']) && !$modcp)
		{
			return;
		}
	
		$this->_lang_load();

		$gnupg_encrypt_public_key = trim($mybb->get_input('gnupg_encrypt_public_key', MyBB::INPUT_STRING));

		$gnupg_encrypt_fingerprint = trim($mybb->get_input('gnupg_encrypt_fingerprint', MyBB::INPUT_STRING));

		if((!$gnupg_encrypt_public_key || !$gnupg_encrypt_fingerprint) && (
			($registration && $mybb->settings['gnupg_encrypt_forceonregister']) ||
			(!$registration && is_member($mybb->settings['gnupg_encrypt_forcegroups'], $args->data))
		))
		{
			$args->set_error($lang->gnupg_encrypt_valdiate_error_register);

			return;
		}

		$info = $this->gpg()->import($gnupg_encrypt_public_key);

		if($info['fingerprint'] !== $gnupg_encrypt_fingerprint)
		{
			$args->set_error($lang->gnupg_encrypt_valdiate_error_register_fingerprint);

			return;
		}

		$args->data['gnupg_encrypt_public_key'] = $gnupg_encrypt_public_key;

		$args->data['gnupg_encrypt_fingerprint'] = $gnupg_encrypt_fingerprint;
	}

	// Hook: datahandler_user_update
	function hook_datahandler_user_update(&$args)
	{
		global $db;

		if(!isset($args->data['gnupg_encrypt_public_key']))
		{
			return;
		}

		$args->user_update_data['gnupg_encrypt_public_key'] = $db->escape_string($args->data['gnupg_encrypt_public_key']);

		$args->user_update_data['gnupg_encrypt_fingerprint'] = $db->escape_string($args->data['gnupg_encrypt_fingerprint']);
	}

	// Hook: datahandler_user_insert
	function hook_datahandler_user_insert(&$args)
	{
		global $db;

		if(!isset($args->data['gnupg_encrypt_public_key']))
		{
			return;
		}

		$args->user_insert_data['gnupg_encrypt_public_key'] = $db->escape_string($args->data['gnupg_encrypt_public_key']);

		$args->user_insert_data['gnupg_encrypt_fingerprint'] = $db->escape_string($args->data['gnupg_encrypt_fingerprint']);
	}

	function hook_member_profile_end()
	{
		global $mybb, $gnupg_encrypt_profile, $templates, $lang, $theme, $memprofile, $parser;

		if(!is_member($mybb->settings['gnupg_encrypt_groups'], $memprofile))
		{
			return;
		}
	
		$this->_lang_load();

		$gnupg_encrypt_public_key = htmlspecialchars_uni($memprofile['gnupg_encrypt_public_key']);

		$gnupg_encrypt_fingerprint = htmlspecialchars_uni($memprofile['gnupg_encrypt_fingerprint']);

		if(!($parser instanceof postParser))
		{
			$parser = new postParser;
		}

		$gnupg_encrypt_public_key = $parser->parse_message($gnupg_encrypt_public_key, array('nl2br' => 1));

		$gnupg_encrypt_profile = eval($templates->render('gnupgencrypt_profile'));
	}

	function gpg()
	{
		defined('GNUPG_WORKING_DIR') || define('GNUPG_WORKING_DIR', MYBB_ROOT.'inc/plugins/gnupg_encrypt');

		if(!($this->gpg instanceof gnupg))
		{
			$this->gpg = new gnupg();

			putenv('GNUPGHOME='.GNUPG_WORKING_DIR.'/.gnupg');

			//$gpg->seterrormode(GNUPG_ERROR_SILENT);
		}

		return $this->gpg;

		//$addencryptkey = $gpg->addencryptkey($gnupg_encrypt_fingerprint);

		//$encrypted =  $gpg->encrypt('message..!');

		//_dump($info, $addencryptkey, $encrypted);
	}
}

global $gnupg_encrypt;

$gnupg_encrypt = new GnuPG_Encrypt();