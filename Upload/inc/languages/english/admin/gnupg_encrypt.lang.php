<?php

/***************************************************************************
 *
 *	GnuPG Encrypt (/inc/languages/english/admin/gnupg_encrypt.php)
 *	Author: Omar Gonzalez
 *	Author Website: https://ougc.network
 *
 *	Use GnuPG encryption for private messages.
 *
 ***************************************************************************/


$l['setting_group_gnupg_encrypt'] = "GnuPG Encrypt";
$l['setting_group_gnupg_encrypt_desc'] = "Protect an account and private messages using GnuPG encryption.";

// Settings
$l['setting_gnupg_encrypt_groupselect'] = 'Allowed Groups';
$l['setting_gnupg_encrypt_groupselect_desc'] = 'Select the groups allowed to use this feature.';
$l['setting_gnupg_encrypt_mods'] = 'Moderator Groups';
$l['setting_gnupg_encrypt_mods_desc'] = 'Select the groups allowed to edit user colors in the Moderator CP.';
$l['setting_gnupg_encrypt_forceonregister'] = 'Force on Registration';
$l['setting_gnupg_encrypt_forceonregister_desc'] = 'Force new registrations to supply a public key and a fingerprint.';
$l['setting_gnupg_encrypt_forcegroups'] = 'Force to Groups';
$l['setting_gnupg_encrypt_forcegroups_desc'] = 'Force these groups to provide their public key and fingerprint in order to use the forum.';

// Admin CP
$l['gnupg_encrypt_pluginlibrary'] = 'This plugin requires <a href="{1}">PluginLibrary</a> version {2} or later to be uploaded to your forum. Please upload the necessary files.';
$l['gnupg_encrypt_pluginlibrary_apply'] = 'Apply Edits';
$l['gnupg_encrypt_pluginlibrary_revert'] = 'Revert Edits';
$l['gnupg_encrypt_pluginlibrary_error_revert'] = 'It wasn\'t possible to revert the core edits.';
$l['gnupg_encrypt_pluginlibrary_success_revert'] = 'The core edits were successfully reverted.';
$l['gnupg_encrypt_pluginlibrary_error_apply'] = 'It wasn\'t possible to apply the core edits.';
$l['gnupg_encrypt_pluginlibrary_success_apply'] = 'The core edits were applied successfully';