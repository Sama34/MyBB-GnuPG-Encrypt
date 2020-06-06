<?php

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
