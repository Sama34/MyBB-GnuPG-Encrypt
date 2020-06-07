<?php

/***************************************************************************
 *
 *	OUGC GnuPG Encrypt (/inc/languages/english/admin/gnupg_encrypt.php)
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


$l['setting_group_gnupg_encrypt'] = "OUGC GnuPG Encrypt";
$l['setting_group_gnupg_encrypt_desc'] = "Protect an account and private messages using GnuPG encryption.";

// Settings
$l['setting_gnupg_encrypt_groups'] = 'Allowed Groups';
$l['setting_gnupg_encrypt_groups_desc'] = 'Select the groups allowed to use this feature.';
$l['setting_gnupg_encrypt_mods'] = 'Moderator Groups';
$l['setting_gnupg_encrypt_mods_desc'] = 'Select the groups allowed to edit user colors in the Moderator CP.';
$l['setting_gnupg_encrypt_forceonregister'] = 'Force on Registration';
$l['setting_gnupg_encrypt_forceonregister_desc'] = 'Force new registrations to supply a public key and a fingerprint.';
$l['setting_gnupg_encrypt_forcegroups'] = 'Force to Groups';
$l['setting_gnupg_encrypt_forcegroups_desc'] = 'Force these groups to provide their public key and fingerprint in order to use the forum.';
$l['setting_gnupg_encrypt_2fagroups'] = '2FA Allowed Groups';
$l['setting_gnupg_encrypt_2fagroups_desc'] = 'Select the groups allowed to use a verification step in login as a second factor authentication process.';
$l['setting_gnupg_encrypt_force2fagroups'] = 'Force 2FA Groups';
$l['setting_gnupg_encrypt_force2fagroups_desc'] = 'The following groups will be required to activate 2FA before proceeding outside the UserCP.';
$l['setting_gnupg_encrypt_timeout'] = '2FA Session Timeout';
$l['setting_gnupg_encrypt_timeout_desc'] = 'Please insert how many minutes should pass before requesting users to validate their 2FA sessions. Please verify the task by going to <code>Home » Task Manager</code>';
$l['setting_gnupg_encrypt_pms'] = 'Encrypt Private Messages';
$l['setting_gnupg_encrypt_pms_desc'] = 'Turn private message encryption. Please note that encrypted messages will not be reverted if this is turned off.';
$l['setting_gnupg_encrypt_text_message'] = '2FA Encrypted Message';
$l['setting_gnupg_encrypt_text_message_desc'] = 'Please enter the text to be encrypted for 2FA generated codes. Use <code>{1}</code> to output the 2FA code (required). Leave empty to fallback to the language string <code>gnupg_encrypt_member_encrypted_text_message</code>.';

// Admin CP
$l['gnupg_encrypt_pluginlibrary'] = 'This plugin requires <a href="{1}">PluginLibrary</a> version {2} or later to be uploaded to your forum. Please upload the necessary files.';
$l['gnupg_encrypt_pluginlibrary_apply'] = 'Apply Edits';
$l['gnupg_encrypt_pluginlibrary_revert'] = 'Revert Edits';
$l['gnupg_encrypt_pluginlibrary_error_revert'] = 'It wasn\'t possible to revert the core edits.';
$l['gnupg_encrypt_pluginlibrary_success_revert'] = 'The core edits were successfully reverted.';
$l['gnupg_encrypt_pluginlibrary_error_apply'] = 'It wasn\'t possible to apply the core edits.';
$l['gnupg_encrypt_pluginlibrary_success_apply'] = 'The core edits were applied successfully';

$l['gnupg_encrypt_task_ran'] = 'The GnuPG Encrypt task ran.';