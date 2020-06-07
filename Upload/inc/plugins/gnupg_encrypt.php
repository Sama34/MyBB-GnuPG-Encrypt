<?php

/***************************************************************************
 *
 *	OUGC GnuPG Encrypt (/inc/plugins/gnupg_encrypt.php)
 *	Author: Omar Gonzalez
 *	Copyright: © 2020 Omar Gonzalez
 *
 *	Website: https://omarg.me
 *
 *	Protect an account and private messages using GnuPG encryption.
 *
 ***************************************************************************

****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/

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
		global $plugins, $mybb;

		// Tell MyBB when to run the hooks
		if(!defined('IN_ADMINCP'))
		{
			$plugins->add_hook('member_register_end', array($this, 'hook_member_register_end'));
			$plugins->add_hook('usercp_profile_end', array($this, 'hook_usercp_profile_end'));
			$plugins->add_hook('modcp_editprofile_end', array($this, 'hook_modcp_editprofile_end'));
			$plugins->add_hook('datahandler_user_validate', array($this, 'hook_datahandler_user_validate'));
			$plugins->add_hook('datahandler_user_update', array($this, 'hook_datahandler_user_update'));
			$plugins->add_hook('datahandler_user_insert', array($this, 'hook_datahandler_user_insert'));
			$plugins->add_hook('member_profile_end', array($this, 'hook_member_profile_end'));
			$plugins->add_hook('global_start', array($this, 'hook_global_start'));
			$plugins->add_hook('global_end', array($this, 'hook_global_end'));

			if(!empty($mybb->settings['gnupg_encrypt_pms']))
			{
				$plugins->add_hook('datahandler_pm_validate', array($this, 'hook_datahandler_pm_validate'));
				$plugins->add_hook('datahandler_pm_insert', array($this, 'hook_datahandler_pm_insert'));
				$plugins->add_hook('postbit_pm', array($this, 'hook_postbit_pm'));
			}

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
				$templatelist .= ', gnupgencrypt_usercp_2fa, gnupgencrypt_modcp';
			}

			if(THIS_SCRIPT == 'usercp.php')
			{
				$templatelist .= ', gnupgencrypt_usercp_2fa';
			}

			if(THIS_SCRIPT == 'member.php')
			{
				$templatelist .= ', gnupgencrypt_profile';
			}
		}

		$plugins->add_hook('datahandler_login_complete_end', array($this, 'hook_datahandler_login_complete_end'));

		$plugins->add_hook('member_do_login_end', array($this, 'hook_member_do_login_end'));
	}

	// Plugin API
	function _info()
	{
		global $lang, $plugins_cache;

		$this->_lang_load();

		$this->_plugin_edit();

		$info = array(
			"name"			=> 'OUGC GnuPG Encrypt',
			"description"	=> $lang->setting_group_gnupg_encrypt_desc,
			'website'		=> 'https://ougc.network',
			'author'		=> 'Omar G.',
			'authorsite'	=> 'https://ougc.network',
			'version'		=> '1.8.0',
			'versioncode'	=> 1800,
			'compatibility'	=> '18*',
			'codename'		=> 'ougc_gnupg_encrypt',
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

		if(!isset($lang->setting_group_gnupg_encrypt))
		{
			$lang->load('gnupg_encrypt');

			$lang->load('gnupg_encrypt', true);
		}
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
			'groups'			=> array(
			   'title'			=> $lang->setting_gnupg_encrypt_groups,
			   'description'	=> $lang->setting_gnupg_encrypt_groups_desc,
			   'optionscode'	=> 'groupselect',
			   'value'			=> ''
			),
			'mods'				=> array(
			   'title'			=> $lang->setting_gnupg_encrypt_mods,
			   'description'	=> $lang->setting_gnupg_encrypt_mods_desc,
			   'optionscode'	=> 'groupselect',
			   'value'			=> 4
			),
			'forceonregister'	=> array(
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
			'2fagroups'		=> array(
			   'title'			=> $lang->setting_gnupg_encrypt_2fagroups,
			   'description'	=> $lang->setting_gnupg_encrypt_2fagroups_desc,
			   'optionscode'	=> 'groupselect',
			   'value'			=> -1
			),
			'force2fagroups'		=> array(
			   'title'			=> $lang->setting_gnupg_encrypt_force2fagroups,
			   'description'	=> $lang->setting_gnupg_encrypt_force2fagroups_desc,
			   'optionscode'	=> 'groupselect',
			   'value'			=> -1
			),
			'timeout'		=> array(
			   'title'			=> $lang->setting_gnupg_encrypt_timeout,
			   'description'	=> $lang->setting_gnupg_encrypt_timeout_desc,
			   'optionscode'	=> 'numeric',
			   'value'			=> 60
			),
			'pms'		=> array(
			   'title'			=> $lang->setting_gnupg_encrypt_pms,
			   'description'	=> $lang->setting_gnupg_encrypt_pms_desc,
			   'optionscode'	=> 'yesno',
			   'value'			=> 1
			),
			'text_message'		=> array(
			   'title'			=> $lang->setting_gnupg_encrypt_text_message,
			   'description'	=> $lang->setting_gnupg_encrypt_text_message_desc,
			   'optionscode'	=> 'textarea',
			   'value'			=> $lang->gnupg_encrypt_member_encrypted_text_message
			),
		));
	
		// Add template group
		$PL->templates('gnupgencrypt', 'GnuPG Encrypt', array(
			'confirm' => '<html>
	<head>
		<title>{$mybb->settings[\'bbname\']} - {$lang->gnupg_encrypt_member_2fa}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		<table width="100%" border="0" align="center">
			<tr>
				<td valign="top">
					<form action="{$mybb->settings[\'bburl\']}/usercp.php" method="post">
						<input type="hidden" name="action" value="profile" />
						<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
							<tr>
								<td class="thead" colspan="2"><strong>{$lang->gnupg_encrypt_member_2fa}</strong></td>
							</tr>
							<tr>
								<td class="tcat" colspan="2">{$description}</td>
							</tr>
							<tr>
								<td class="trow1"><strong>{$lang->gnupg_encrypt_member_public_key}:</strong></td>
								<td class="trow1">{$gnupg_encrypt_public_key}</td>
							</tr>
							<tr>
								<td class="trow2"><strong>{$lang->gnupg_encrypt_member_fingerprint}:</strong></td>
								<td class="trow2">{$gnupg_encrypt_fingerprint}</td>
							</tr>
							<tr>
								<td class="trow2"><strong>{$lang->gnupg_encrypt_member_encrypted_text}:</strong></td>
								<td class="trow2">{$gnupg_encrypt_encrypted_text}</td>
							</tr>
							<tr>
								<td class="trow2"><strong>{$lang->gnupg_encrypt_member_decrypted_text}:</strong></td>
								<td class="trow2"><input type="number" class="textbox" name="gnupg_encrypt_decrypted_text" id="gnupg_encrypt_decrypted_text" value="{$gnupg_encrypt_decrypted_text}" /></td>
							</tr>
						</table>
						<br class="clear" />
						<div style="text-align: center">
							<input type="submit" class="button" value="{$lang->gnupg_encrypt_member_2fa_activate}" />
						</div>
					</form>
				</td>
			</tr>
		</table>
		{$footer}
	</body>
</html>',
			'login' => '<html>
	<head>
		<title>{$mybb->settings[\'bbname\']} - {$lang->gnupg_encrypt_member_2fa}</title>
		{$headerinclude}
	</head>
	<body>
		{$header}
		<table width="100%" border="0" align="center">
			<tr>
				<td valign="top">
					<form action="{$mybb->settings[\'bburl\']}/member.php" method="post">
						<input type="hidden" name="action" value="login" />
						<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
							<tr>
								<td class="thead" colspan="2"><strong>{$lang->gnupg_encrypt_member_2fa}</strong></td>
							</tr>
							<tr>
								<td class="tcat" colspan="2">{$description}</td>
							</tr>
							<tr>
								<td class="trow1"><strong>{$lang->gnupg_encrypt_member_public_key}:</strong></td>
								<td class="trow1">{$gnupg_encrypt_public_key}</td>
							</tr>
							<tr>
								<td class="trow2"><strong>{$lang->gnupg_encrypt_member_fingerprint}:</strong></td>
								<td class="trow2">{$gnupg_encrypt_fingerprint}</td>
							</tr>
							<tr>
								<td class="trow2"><strong>{$lang->gnupg_encrypt_member_encrypted_text}:</strong></td>
								<td class="trow2">{$gnupg_encrypt_encrypted_text}</td>
							</tr>
							<tr>
								<td class="trow2"><strong>{$lang->gnupg_encrypt_member_decrypted_text}:</strong></td>
								<td class="trow2"><input type="number" class="textbox" name="gnupg_encrypt_decrypted_text" id="gnupg_encrypt_decrypted_text" value="{$gnupg_encrypt_decrypted_text}" /></td>
							</tr>
						</table>
						<br class="clear" />
						<div style="text-align: center">
							<input type="submit" class="button" value="{$lang->gnupg_encrypt_member_2fa_login}" />
						</div>
					</form>
				</td>
			</tr>
		</table>
		{$footer}
	</body>
</html>',
			'modcp' => '<br />
<fieldset class="trow2">
	<legend><strong>{$lang->gnupg_encrypt_member_title}</strong></legend>
	<table cellspacing="0" cellpadding="{$theme[\'tablespace\']}">
		<tr>
			<td>
				<span class="smalltext">{$lang->gnupg_encrypt_member_public_key}</span>
			</td>
		</tr>
		<tr>
			<td>
				<textarea name="gnupg_encrypt_public_key" id="gnupg_encrypt_public_key" style="width: 100%" rows="10" cols="80">{$gnupg_encrypt_public_key}</textarea>
			</td>
		</tr>
		<tr>
			<td>
				<span class="smalltext">{$lang->gnupg_encrypt_member_fingerprint}</span>
			</td>
		</tr>
		<tr>
			<td>
				<input type="text" class="textbox" name="gnupg_encrypt_fingerprint" id="gnupg_encrypt_fingerprint" style="width: 50%" value="{$gnupg_encrypt_fingerprint}" />
			</td>
		</tr>
		{$gnupg_encrypt_usercp_2fa}
	</table>
</fieldset>',
			'profile' => '<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
	<tr>
		<td colspan="2" class="thead"><strong>{$lang->gnupg_encrypt_member_title}</strong></td>
	</tr>
	<tr>
		<td class="trow1" style="width: 20%;"><strong>{$lang->gnupg_encrypt_member_public_key}:</strong></td>
		<td class="trow1">{$gnupg_encrypt_public_key}</td>
	</tr>
	<tr>
		<td class="trow2"><strong>{$lang->gnupg_encrypt_member_fingerprint}:</strong></td>
		<td class="trow2">{$gnupg_encrypt_fingerprint}</td>
	</tr>
</table>
<br />',
			'register' => '<br />
<fieldset class="trow2">
<legend><strong><label for="timezone">{$lang->gnupg_encrypt_member_title}</label></strong></legend>
<table cellspacing="0" cellpadding="{$theme[\'tablespace\']}" width="100%">
	<tr>
		<td>
			<span class="smalltext">{$lang->gnupg_encrypt_member_public_key}</span>
		</td>
	</tr>
	<tr>
		<td>
			<textarea name="gnupg_encrypt_public_key" id="gnupg_encrypt_public_key" style="width: 100%" rows="10">{$gnupg_encrypt_public_key}</textarea>
		</td>
	</tr>
	<tr>
		<td>
			<span class="smalltext">{$lang->gnupg_encrypt_member_fingerprint}</span>
		</td>
	</tr>
	<tr>
		<td>
			<input type="text" class="textbox" name="gnupg_encrypt_fingerprint" id="gnupg_encrypt_fingerprint" style="width: 50%" value="{$gnupg_encrypt_fingerprint}" />
		</td>
	</tr>
</table>
</fieldset>',
			'usercp' => '<br />
<fieldset class="trow2">
	<legend><strong>{$lang->gnupg_encrypt_member_title}</strong></legend>
	<table cellspacing="0" cellpadding="{$theme[\'tablespace\']}">
		<tr>
			<td>
				<span class="smalltext">{$lang->gnupg_encrypt_member_public_key}</span>
			</td>
		</tr>
		<tr>
			<td>
				<textarea name="gnupg_encrypt_public_key" id="gnupg_encrypt_public_key" style="width: 100%" rows="10" cols="80">{$gnupg_encrypt_public_key}</textarea>
			</td>
		</tr>
		<tr>
			<td>
				<span class="smalltext">{$lang->gnupg_encrypt_member_fingerprint}</span>
			</td>
		</tr>
		<tr>
			<td>
				<input type="text" class="textbox" name="gnupg_encrypt_fingerprint" id="gnupg_encrypt_fingerprint" style="width: 50%" value="{$gnupg_encrypt_fingerprint}" />
			</td>
		</tr>
		{$gnupg_encrypt_usercp_2fa}
	</table>
</fieldset>',
			'usercp_2fa' => '<tr>
	<td>
		<span class="smalltext">{$lang->gnupg_encrypt_member_2fa}</span>
	</td>
</tr>
<tr>
	<td>
		<input type="checkbox" class="checkbox" name="gnupg_encrypt_2fa" id="gnupg_encrypt_2fa" value="1" {$gnupg_encrypt_2fa_checked} />
		<label for="gnupg_encrypt_2fa">{$lang->gnupg_encrypt_member_2fa}</label>
	</td>
</tr>',
			'' => '',
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

		$this->_db_verify_tables();
		$this->_db_verify_columns();
		$this->update_task_file();
	
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

		$this->update_task_file(0);
	}

	// This function runs when the plugin is installed.
	function _install()
	{
		global $db, $cache, $gnupg_encrypt;

		$this->_db_verify_tables();
		$this->_db_verify_columns();
		$this->update_task_file();
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
	
		foreach($this->_db_tables() as $name => $table)
		{
			$db->drop_table($name);
		}

		foreach($this->_db_columns() as $table => $columns)
		{
			foreach($columns as $name => $definition)
			{
				!$db->field_exists($name, $table) || $db->drop_column($table, $name);
			}
		}
	
		$PL->settings_delete('gnupg_encrypt');

		$PL->templates_delete('gnupgencrypt');

		$this->update_task_file(-1);
	
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

	// List of tables
	function _db_tables()
	{
		$tables = array(
			'gnupg_encrypt_log'	=> array(
				'lid'			=> "int UNSIGNED NOT NULL AUTO_INCREMENT",
				'uid'			=> "int UNSIGNED NOT NULL",
				'secret'		=> "varchar(20) NOT NULL DEFAULT ''",
				'sid'			=> "varchar(50) NOT NULL DEFAULT ''",
				'code'			=> "varchar(20) NOT NULL DEFAULT ''",
				'dateline'		=> "int(10) NOT NULL DEFAULT '0'",
				'status'		=> "int(1) NOT NULL DEFAULT '0'",
				'primary_key'	=> "lid"
			)
		);

		return $tables;
	}

	// List of columns
	function _db_columns()
	{
		$tables = array(
			'users' => array(
				'gnupg_encrypt_public_key' => "text NULL",
				'gnupg_encrypt_fingerprint' => "varchar(50) NULL DEFAULT ''",
				'gnupg_encrypt_2fa' => "int(1) NOT NULL DEFAULT '1'",
			),
			'sessions'	=> array(
				'gnupg_encrypt_block' => "int(1) NOT NULL DEFAULT '1'",
				'gnupg_encrypt_time' => "int(10) NOT NULL DEFAULT '0'"
			),
		);

		return $tables;
	}

	// Verify DB tables
	function _db_verify_tables()
	{
		global $db;

		$collation = $db->build_create_table_collation();
		foreach($this->_db_tables() as $table => $fields)
		{
			if($db->table_exists($table))
			{
				foreach($fields as $field => $definition)
				{
					if($field == 'primary_key')
					{
						continue;
					}

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
			else
			{
				$query = "CREATE TABLE IF NOT EXISTS `".TABLE_PREFIX."{$table}` (";
				foreach($fields as $field => $definition)
				{
					if($field == 'primary_key')
					{
						$query .= "PRIMARY KEY (`{$definition}`)";
					}
					else
					{
						$query .= "`{$field}` {$definition},";
					}
				}
				$query .= ") ENGINE=MyISAM{$collation};";
				$db->write_query($query);
			}
		}
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
		if(verify_post_check($mybb->input['my_post_key'], true) && isset($mybb->input['gnupg_encrypt']))
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
	
		$gnupg_encrypt_usercp = $gnupg_encrypt_2fa_checked = '';

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

		if(is_member($mybb->settings['gnupg_encrypt_2fagroups']) || is_member($mybb->settings['gnupg_encrypt_force2fagroups']))
		{
			$gnupg_encrypt_2fa = (int)$mybb->user['gnupg_encrypt_2fa'];
	
			if(isset($mybb->input['gnupg_encrypt_2fa']))
			{
				$gnupg_encrypt_2fa = $mybb->get_input('gnupg_encrypt_2fa', MyBB::INPUT_INT);
			}

			if($gnupg_encrypt_2fa)
			{
				$gnupg_encrypt_2fa_checked = ' checked="checked"';
			}

			$gnupg_encrypt_usercp_2fa = eval($templates->render('gnupgencrypt_usercp_2fa'));
		}

		$gnupg_encrypt_usercp = eval($templates->render('gnupgencrypt_usercp'));
	}

	// Hook: usercp_do_profile_end
	function hook_usercp_do_profile_end()
	{
		global $mybb, $userhandler, $lang;

		if((int)$userhandler->user_update_data['gnupg_encrypt_2fa'] !== 0)
		{
			$this->_lang_load();

			redirect('usercp.php?action=profile', $lang->gnupg_encrypt_redirect_activate);
		}
	}

	// Hook: modcp_editprofile_end
	function hook_modcp_editprofile_end()
	{
		global $themes, $mybb, $templates, $theme, $gnupg_encrypt_modcp, $lang, $user;
	
		$gnupg_encrypt_modcp = '';

		if(!is_member($mybb->settings['gnupg_encrypt_mods']))
		{
			return;
		}

		$this->_lang_load();

		$gnupg_encrypt_public_key = $user['gnupg_encrypt_public_key'];

		$gnupg_encrypt_fingerprint = $user['gnupg_encrypt_fingerprint'];

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

		if(is_member($mybb->settings['gnupg_encrypt_2fagroups'], $user) || is_member($mybb->settings['gnupg_encrypt_force2fagroups'], $user))
		{
			$gnupg_encrypt_2fa = (int)$user['gnupg_encrypt_2fa'];
	
			if(isset($mybb->input['gnupg_encrypt_2fa']))
			{
				$gnupg_encrypt_2fa = $mybb->get_input('gnupg_encrypt_2fa', MyBB::INPUT_INT);
			}

			if($gnupg_encrypt_2fa)
			{
				$gnupg_encrypt_2fa_checked = ' checked="checked"';
			}

			$gnupg_encrypt_usercp_2fa = eval($templates->render('gnupgencrypt_usercp_2fa'));
		}

		$gnupg_encrypt_modcp = eval($templates->render('gnupgencrypt_modcp'));
	}

	// Hook: datahandler_user_validate
	function hook_datahandler_user_validate(&$args)
	{
		global $mybb, $lang, $plugins;

		$registration = THIS_SCRIPT == 'member.php';

		$modcp = is_member($mybb->settings['gnupg_encrypt_mods']) && THIS_SCRIPT == 'modcp.php';

		if(!is_member($mybb->settings['gnupg_encrypt_groups']) && !is_member($mybb->settings['gnupg_encrypt_forcegroups']) && !$modcp)
		{
			return;
		}
	
		$this->_lang_load();

		$gnupg_encrypt_public_key = trim($mybb->get_input('gnupg_encrypt_public_key', MyBB::INPUT_STRING));

		$gnupg_encrypt_fingerprint = trim($mybb->get_input('gnupg_encrypt_fingerprint', MyBB::INPUT_STRING));

		$gnupg_encrypt_2fa = $mybb->get_input('gnupg_encrypt_2fa', MyBB::INPUT_INT);

		if((!$gnupg_encrypt_public_key || !$gnupg_encrypt_fingerprint) && (
			($registration && $mybb->settings['gnupg_encrypt_forceonregister']) ||
			(!$modcp && !$registration && is_member($mybb->settings['gnupg_encrypt_forcegroups'], $args->data))
		))
		{
			$args->set_error($lang->gnupg_encrypt_validate_error_register);

			return;
		}

		if($ismod &&(!$gnupg_encrypt_public_key || !$gnupg_encrypt_fingerprint))
		{
			$gnupg_encrypt_public_key = $gnupg_encrypt_fingerprint = '';
		}

		$info = $this->gpg()->import($gnupg_encrypt_public_key);

		/*if((string)$info['fingerprint'] !== $gnupg_encrypt_fingerprint)
		{
			$args->set_error($lang->gnupg_encrypt_validate_error_register_fingerprint);

			return;
		}*/

		$args->data['gnupg_encrypt_public_key'] = $gnupg_encrypt_public_key;

		$args->data['gnupg_encrypt_fingerprint'] = $gnupg_encrypt_fingerprint;

		$args->data['gnupg_encrypt_2fa'] = $gnupg_encrypt_2fa;
	}

	// Hook: datahandler_user_update
	function hook_datahandler_user_update(&$args)
	{
		global $db, $plugins;

		if(!isset($args->data['gnupg_encrypt_public_key']))
		{
			return;
		}

		$args->user_update_data['gnupg_encrypt_public_key'] = $db->escape_string($args->data['gnupg_encrypt_public_key']);

		$args->user_update_data['gnupg_encrypt_fingerprint'] = $db->escape_string($args->data['gnupg_encrypt_fingerprint']);

		$args->user_update_data['gnupg_encrypt_2fa'] = 1;

		$uid = (int)$args->uid;

		$user = get_user($uid);

		$current_status = (int)$user['gnupg_encrypt_2fa'];

		$new_status = (int)$args->data['gnupg_encrypt_2fa'];

		if($current_status === 0 && $new_status !== 0)
		{
			$args->user_update_data['gnupg_encrypt_2fa'] = -1;
		}
		elseif($new_status === 0)
		{
			$db->update_query('sessions', array("gnupg_encrypt_block" => 0), "uid='{$uid}'");

			$args->user_update_data['gnupg_encrypt_2fa'] = 0;
		}

		$plugins->add_hook('usercp_do_profile_end', array($this, 'hook_usercp_do_profile_end'));
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

		$args->user_insert_data['gnupg_encrypt_2fa'] = (int)$args->data['gnupg_encrypt_2fa'];
	}

	// Hook: member_profile_end
	function hook_member_profile_end()
	{
		global $mybb, $gnupg_encrypt_profile, $templates, $lang, $theme, $memprofile, $parser;

		if(!$memprofile['gnupg_encrypt_public_key'] || !$memprofile['gnupg_encrypt_fingerprint'])
		{
			return;
		}
	
		$this->_lang_load();

		$gnupg_encrypt_public_key = trim(htmlspecialchars_uni($memprofile['gnupg_encrypt_public_key']));

		$gnupg_encrypt_fingerprint = trim(htmlspecialchars_uni($memprofile['gnupg_encrypt_fingerprint']));

		if(!($parser instanceof postParser))
		{
			$parser = new postParser;
		}

		$gnupg_encrypt_public_key = $parser->parse_message($gnupg_encrypt_public_key, array('nl2br' => 1));

		$gnupg_encrypt_profile = eval($templates->render('gnupgencrypt_profile'));
	}

	// Hook: global_start
	function hook_global_start()
	{
		global $mybb, $db;

		if(empty($mybb->user['gnupg_encrypt_2fa']) || empty($mybb->user['gnupg_encrypt_public_key']) || empty($mybb->user['gnupg_encrypt_fingerprint']))
		{
			return;
		}
	
		$sid = $db->escape_string($mybb->cookies['sid']);

		$query = $db->simple_select('sessions', 'gnupg_encrypt_block, gnupg_encrypt_time', "sid='{$sid}'");

		$session = $db->fetch_array($query);

		$hours = TIME_NOW - (60 * $mybb->settings['gnupg_encrypt_timeout']);

		$this->blocked = (bool)(int)$session['gnupg_encrypt_block'] || $session['gnupg_encrypt_time'] < $hours;

		if($this->blocked)
		{
			$this->user = $mybb->user;

			$this->user_session = $mybb->session;

			$mybb->session->load_guest();
		}
	}

	// Hook: global_end
	function hook_global_end()
	{
		global $mybb, $db, $headerinclude, $header, $theme, $footer, $templates, $lang, $gobutton;

		$user = !empty($this->user['uid']) ? $this->user : $mybb->user;

		if(!$user['uid'] || !$mybb->user['gnupg_encrypt_2fa'] || (THIS_SCRIPT == 'member.php' && $mybb->get_input('action', MyBB::INPUT_STRING) == 'logout'))
		{
			return;
		}

		$_2fa = (int)$user['gnupg_encrypt_2fa'];

		if($_2fa === 0)
		{
			return;
		}

		if($_2fa === 1 && empty($this->blocked))
		{
			return;
		}

		$login_page = (THIS_SCRIPT == 'member.php' && $mybb->get_input('action', MyBB::INPUT_STRING) == 'login');

		$usercp_page = (THIS_SCRIPT == 'usercp.php' && $mybb->get_input('action', MyBB::INPUT_STRING) == 'profile');

		if($_2fa === -1 && !$usercp_page)
		{
			$mybb->settings['redirects'] = 0;
			$mybb->user['showredirect'] = 0;

			redirect($mybb->settings['bburl'].'/usercp.php?action=profile');
		}

		if($_2fa === 1 && !$login_page)
		{
			$mybb->settings['redirects'] = 0;
			$mybb->user['showredirect'] = 0;

			redirect($mybb->settings['bburl'].'/member.php?action=login');
		}

		$uid = (int)$user['uid'];

		$this->_lang_load();

		$gnupg_encrypt_public_key = trim(htmlspecialchars_uni($user['gnupg_encrypt_public_key']));

		$gnupg_encrypt_fingerprint = trim(htmlspecialchars_uni($user['gnupg_encrypt_fingerprint']));

		if(!($parser instanceof postParser))
		{
			require_once MYBB_ROOT.'inc/class_parser.php';

			$parser = new postParser;
		}
	
		$gnupg_encrypt_public_key = $parser->parse_message($gnupg_encrypt_public_key, array('nl2br' => 1));

		$info = $this->gpg()->import($user['gnupg_encrypt_public_key']);

		$this->gpg()->addencryptkey($user['gnupg_encrypt_fingerprint']);

		$session = !empty($this->user_session) ? $this->user_session : $mybb->session;

		$sid = $db->escape_string($session->sid);

		$session = $this->get_session($session->sid);

		$log = $this->get_log($session['sid'], $user['uid']);

		if($_2fa === -1)
		{
			if(!$session['gnupg_encrypt_block'])
			{
				$db->update_query('sessions', array("gnupg_encrypt_block" => 1), "uid='{$uid}'");
			}
		}

		$lid = (int)$log['lid'];

		if(!$log['secret'])
		{
			$log['secret'] = (int)$this->generate_secret();

			$db->update_query('gnupg_encrypt_log', array('secret' => $log['secret']), "lid='{$lid}'");
		}

		if(empty($mybb->settings['gnupg_encrypt_text_message']))
		{
			$secret = $lang->sprintf($lang->gnupg_encrypt_member_encrypted_text_message, $log['secret']);
		}
		else
		{
			$secret = $lang->sprintf($mybb->settings['gnupg_encrypt_text_message'], $log['secret']);
		}

		$gnupg_encrypt_encrypted_text = $this->gpg()->encrypt($secret);

		$gnupg_encrypt_encrypted_text = $parser->parse_message($gnupg_encrypt_encrypted_text, array('nl2br' => 1));

		$gnupg_encrypt_decrypted_text = '';

		if($mybb->request_method == 'post')
		{
			if(isset($mybb->input['gnupg_encrypt_decrypted_text']))
			{
				$gnupg_encrypt_decrypted_text = $mybb->get_input('gnupg_encrypt_decrypted_text', MyBB::INPUT_INT);
			}

			$update_data = array('code' => $gnupg_encrypt_decrypted_text);

			$valid = (int)$log['secret'] === $gnupg_encrypt_decrypted_text;
		}

		if($_2fa === -1)
		{
			$input_desc = $lang->gnupg_encrypt_member_2fa_desc;
			$description = $lang->gnupg_encrypt_member_2fa_desc;

			if($mybb->request_method == 'post')
			{
				if($valid)
				{
					$update_data['status'] = 1;

					$db->update_query('sessions', array('gnupg_encrypt_block' => 0, 'gnupg_encrypt_time' => TIME_NOW), "sid='{$sid}'");

					$db->update_query('users', array('gnupg_encrypt_2fa' => 1), "uid='{$uid}'");
				}
				else
				{
					$update_data['status'] = -1;

					$db->update_query('sessions', array('gnupg_encrypt_block' => 0, 'gnupg_encrypt_time' => TIME_NOW), "uid='{$uid}'");

					$db->update_query('users', array('gnupg_encrypt_2fa' => 0), "uid='{$uid}'");
				}

				$db->update_query('gnupg_encrypt_log', $update_data, "sid='{$sid}'");

				if($valid)
				{
					redirect($mybb->settings['bburl'].'/usercp.php?action=profile', $lang->gnupg_encrypt_validate_success_activate, $lang->gnupg_encrypt_member_2fa, true);
				}
				else
				{
					redirect($mybb->settings['bburl'].'/usercp.php?action=profile', $lang->gnupg_encrypt_validate_error_activate, $lang->gnupg_encrypt_member_2fa, true);
				}
			}

			$page = eval($templates->render('gnupgencrypt_confirm'));
		}
		else
		{
			$input_desc = $lang->gnupg_encrypt_member_2fa_login;
			$description = $lang->gnupg_encrypt_member_2fa_login_desc;

			if($mybb->request_method == 'post')
			{
				if($valid)
				{
					$update_data['status'] = 1;

					$db->update_query('sessions', array('gnupg_encrypt_block' => 0, 'gnupg_encrypt_time' => TIME_NOW), "sid='{$sid}'");
				}
				else
				{
					$update_data['status'] = -1;

					$db->update_query('sessions', array("gnupg_encrypt_block" => 1, 'gnupg_encrypt_time' => TIME_NOW), "sid='{$sid}'");
				}

				$db->update_query('gnupg_encrypt_log', $update_data, "sid='{$sid}'");

				if($valid)
				{
					isset($lang->redirect_loggedin) || $lang->load('member');

					redirect("index.php", $lang->redirect_loggedin);
				}
				else
				{
					my_unsetcookie('mybbuser');

					my_unsetcookie('sid');
	
					redirect("index.php", $lang->gnupg_encrypt_redirect_error_loggedin);
				}
			}

			$page = eval($templates->render('gnupgencrypt_login'));
		}

		output_page($page);

		exit;
	}

	// Hook: datahandler_pm_validate
	function hook_datahandler_pm_validate(&$dh)
	{
		if(!empty($dh->errors))
		{
			return;
		}

		global $lang, $mybb;

		$this->_lang_load();

		$uids = array($dh->data['fromid'] => $dh->data['fromid']);

		$set_error = false;

		foreach($dh->data['recipients'] as $recipient)
		{
			$uids[$recipient['uid']] = $recipient['uid'];
		}

		$this->encrypted_messages = array();

		$author = get_user($dh->data['fromid']);
	
		$message = $lang->sprintf($lang->gnupg_encrypt_member_encrypted_pm_message, $dh->data['message']);

		foreach($uids as $uid)
		{
			$user = get_user($uid);
	
			$info = $this->gpg()->import($user['gnupg_encrypt_public_key']);
	
			$this->gpg()->addencryptkey($user['gnupg_encrypt_fingerprint']);
	
			$encrypted_message = $this->gpg()->encrypt($message);

			if(empty($encrypted_message))
			{
				$encrypt_error = true;
	
				break;
			}

			$messagelen = strlen($encrypted_message);

			if($messagelen > 65535)
			{
				$message_error = true;
	
				break;
			}
	
			$this->encrypted_messages[$uid] = $encrypted_message;
		}

		if($message_error)
		{
			$dh->set_error('message_too_long', array('65535', $messagelen));

			return false;
		}

		if($encrypt_error)
		{
			if($mybb->user['uid'] == $user['uid'])
			{
				$dh->set_error($lang->gnupg_encrypt_validate_error_pm_author);
			}
			else
			{
				$dh->set_error($lang->sprintf($lang->gnupg_encrypt_validate_error_pm, htmlspecialchars_uni($user['username'])));
			}

			return false;
		}

		$this->decrypted_message = $dh->data['message'];

		$dh->data['message'] = $lang->gnupg_encrypt_member_encrypted_pm;
	}

	// Hook: datahandler_pm_insert
	function hook_datahandler_pm_insert(&$dh)
	{
		global $db, $lang, $mybb;

		if(empty($this->encrypted_messages[$dh->pm_insert_data['uid']]))
		{
			$dh->pm_insert_data['message'] = '';

			return false;
		}

		$dh->pm_insert_data['message'] = $this->encrypted_messages[$dh->pm_insert_data['uid']];
	}

	// Hook: postbit_pm
	function hook_postbit_pm(&$post)
	{
		if(empty($post['message']))
		{
			return;
		}

		global $parser, $lang;

		$this->_lang_load();

		if(!($parser instanceof postParser))
		{
			$parser = new postParser;
		}

		$lang_string = $parser->parse_message($lang->gnupg_encrypt_member_encrypted_pm_message, array('nl2br' => 1));

		$post['message'] = $lang->sprintf($lang_string, $post['message']);
	}

	function gpg()
	{
		defined('GNUPG_WORKING_DIR') || define('GNUPG_WORKING_DIR', MYBB_ROOT.'inc/plugins/gnupg_encrypt');

		if(!($this->gpg instanceof gnupg))
		{
			$this->gpg = new gnupg();

			putenv('GNUPGHOME='.GNUPG_WORKING_DIR.'/.gnupg');

			$this->gpg->seterrormode(GNUPG_ERROR_SILENT);
		}

		return $this->gpg;
	}

	// Hook: datahandler_login_complete_en
	function hook_datahandler_login_complete_end(&$dh)
	{
		/*global $mybb, $db, $headerinclude, $header, $theme, $footer, $templates, $lang, $session;

		$user = get_user($dh->login_data['uid']);

		$uid = (int)$user['uid'];

		if(empty($user['gnupg_encrypt_2fa']))
		{
			return;
		}

		$row = $this->get_log($session->sid, $uid);

		$sid = $db->escape_string($session->sid);

		$this->_lang_load();
	
		$db->update_query('sessions', array("gnupg_encrypt_block" => 1), "sid='{$sid}'");*/
	}

	// Hook: member_do_login_end
	function hook_member_do_login_end()
	{
		global $settings, $loginhandler, $lang;

		if(empty($loginhandler->login_data['gnupg_encrypt_2fa']) || empty($loginhandler->login_data['gnupg_encrypt_public_key']) || empty($loginhandler->login_data['gnupg_encrypt_fingerprint']))
		{
			return;
		}

		$this->_lang_load();

		redirect($settings['bburl'].'/member.php?action=login', $lang->gnupg_encrypt_redirect_loggedin);
	}

	function get_log($sid, $uid)
	{
		global $db;

		$sid = $db->escape_string($sid);

		$uid = (int)$uid;

		$query = $db->simple_select('gnupg_encrypt_log', '*', "uid={$uid} AND sid='{$sid}'");

		$row = $db->fetch_array($query);
	
		if(!$row)
		{
			$row = array(
				'uid'		=> $uid,
				'secret'	=> '',
				'sid'		=> $sid,
				'code'		=> '',
				'dateline'	=> TIME_NOW,
				'status'	=> 0,
			);

			$row['lid'] = $db->insert_query('gnupg_encrypt_log', $row);
		}

		return $row;
	}

	function get_session($sid)
	{
		global $db;

		$sid = $db->escape_string($sid);
	
		$query = $db->simple_select('sessions', '*', "sid='{$sid}'");

		$session = $db->fetch_array($query);

		return $session;
	}

	function generate_secret($length=10)
	{
		$this->secret = '';
		
		for($i = 0; $length > $i; ++$i)
		{
			$this->secret .= rand(0,9);
		}

		return $this->secret;
    }

	// Install/update task file
	function update_task_file($action=1)
	{
		global $db, $lang;

		$this->_lang_load();

		if($action == -1)
		{
			$db->delete_query('tasks', "file='gnupg_encrypt'");

			return;
		}

		$query = $db->simple_select('tasks', '*', "file='gnupg_encrypt'", array('limit' => 1));

		$task = $db->fetch_array($query);

		if($task)
		{
			$db->update_query('tasks', array('enabled' => $action), "file='gnupg_encrypt'");
		}
		else
		{
			include_once MYBB_ROOT.'inc/functions_task.php';

			$_ = $db->escape_string('*');

			$new_task = array(
				'title'			=> $db->escape_string($lang->setting_group_gnupg_encrypt),
				'description'	=> $db->escape_string($lang->setting_group_gnupg_encrypt_desc),
				'file'			=> $db->escape_string('gnupg_encrypt'),
				'minute'		=> '0,15,30,45',
				'hour'			=> $_,
				'day'			=> $_,
				'weekday'		=> $_,
				'month'			=> $_,
				'enabled'		=> 1,
				'logging'		=> 1
			);

			$new_task['nextrun'] = fetch_next_run($new_task);

			$db->insert_query('tasks', $new_task);
		}
	}
}

global $gnupg_encrypt;

$gnupg_encrypt = new GnuPG_Encrypt();