<?php

/***************************************************************************
 *
 *	OUGC GnuPG Encrypt (/inc/tasks/gnupg_encrypt.php)
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

function task_gnupg_encrypt($task)
{
	global $db, $mybb, $gnupg_encrypt;

	$minutes = TIME_NOW - (60 * $mybb->settings['gnupg_encrypt_timeout']);

	$query = $db->simple_select('sessions', 'sid', "gnupg_encrypt_block!='1' AND gnupg_encrypt_time<'{$minutes}'");

	$sids = array();

	while($sid = $db->fetch_field($query, 'sid'))
	{
		$sids[] = $db->escape_string($sid);
	}

	$sids = implode("','", array_values($sids));

	$db->update_query('sessions', array('gnupg_encrypt_time' => TIME_NOW, 'gnupg_encrypt_block' => 1), "sid IN ('{$sids}')");

	$gnupg_encrypt->_lang_load();

	add_task_log($task, $lang->gnupg_encrypt_task_ran);
}
