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

$l['gnupg_encrypt_member_title'] = 'PGP Encryption';
$l['gnupg_encrypt_member_public_key'] = 'Public Key';
$l['gnupg_encrypt_member_fingerprint'] = 'Fingerprint';
$l['gnupg_encrypt_member_2fa'] = 'Two-Factor Authentication';
$l['gnupg_encrypt_member_2fa_desc'] = 'Solve the encrypted text below in order to activate 2FA using PGP encryption.';
$l['gnupg_encrypt_member_2fa_activate'] = 'Verify and Activate';
$l['gnupg_encrypt_member_2fa_login'] = 'Verify and Login';
$l['gnupg_encrypt_member_2fa_login_desc'] = 'Solve the encrypted text below in order to login to your account.';
$l['gnupg_encrypt_member_encrypted_text'] = 'Encrypted Text';
$l['gnupg_encrypt_member_encrypted_text_message'] = 'Welcome to my board.
Please verify the link of the board.
Your code is: {1}';
$l['gnupg_encrypt_member_encrypted_pm'] = 'Please visit the website to view the message.';
$l['gnupg_encrypt_member_encrypted_pm_message'] = 'Please verify the link of the board.
Your message is:

{1}';
$l['gnupg_encrypt_member_decrypted_text'] = 'Decrypted Text';

$l['gnupg_encrypt_validate_error_register'] = 'Providing your PGP Public Key and Fingerprint is required to register.';
$l['gnupg_encrypt_validate_error_register_fingerprint'] = 'Your PGP Public Key doesn\' match your Fingerprint.';
$l['gnupg_encrypt_validate_error_activate'] = 'Your confirmation code did not match the encrypted code. You will be redirected back to the UserCP.';

$l['gnupg_encrypt_validate_success_activate'] = '2FA was activated in your account successfully. You will be redirected back to the UserCP.';

$l['gnupg_encrypt_redirect_activate'] = 'You will now be taken to the 2FA confirmation page.';
$l['gnupg_encrypt_redirect_loggedin'] = 'You will now be taken to the 2FA page.';
$l['gnupg_encrypt_redirect_error_loggedin'] = 'Your 2FA code did not match the encrypted message.<br /> You will now be redirect back to the forum home page.';

$l['gnupg_encrypt_task_ran'] = 'The GnuPG Encrypt task ran.';

$l['gnupg_encrypt_validate_error_pm'] = 'The following users do not have a PGP public key to send messages: {1}';