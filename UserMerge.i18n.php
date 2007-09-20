<?php
/** \file
* \brief Internationalization file for the User Merge and Delete Extension.
*/

$allMessages = array(
'en' => array(
		'usermerge' => 'User Merge and Delete',
		'usermerge-badolduser' => 'Invalid Old Username',
		'usermerge-badnewuser' => 'Invalid New User',
		'usermerge-nonewuser' => 'Empty New Username - Assuming merge to $1.<br />Click <u>Merge User</u> to accept.',
		'usermerge-noolduser' => 'Empty Old Username',
		'usermerge-olduser' => 'Old User(Merge From)',
		'usermerge-newuser' => 'New User(Merge To)',
		'usermerge-deleteolduser' => 'Delete Old User?',
		'usermerge-submit' => 'Merge User',
		'usermerge-badtoken' => 'Invalid Edit Token',
		'usermerge-userdeleted' => '$1($2) has been deleted.',
		'usermerge-updating' => 'Updating $1 table ($2 to $3)',
		'usermerge-success' => 'Merge from $1($2) to $3($4) is complete.'
	),
	'de' => array(
		'usermerge' => 'Benutzerkonten zusammenführen und löschen',
	),
	'hsb' => array(
		'usermerge-badolduser' => 'Njepłaćiwe stare wužiwarske mjeno',
		'usermerge-badnewuser' => 'Njepłaćiwe nowe wužiwarske mjeno',
		'usermerge-noolduser' => 'Falowace stare wužiwarske mjeno',
		'usermerge-deleteolduser' => 'Stare wužiwarske mjeno zničić?',
		'usermerge-badtoken' => 'Njepłaćiwe wobdźěłanske znamjo',
		'usermerge-userdeleted' => '$1($2) bu zničeny.',
		'usermerge-updating' => '$1 tabela so aktualizuje ($2 do $3)',
	),
	'nl' => array(
		'usermerge' => 'Gebruikers samenvoegen en verwijderen',
		'usermerge-badolduser' => 'Verkeerde oude gebruiker',
		'usermerge-badnewuser' => 'Verkeerde nieuwe gebruiker',
		'usermerge-nonewuser' => 'Nieuwe gebruiker is niet ingegeven - er wordt aangenomen dat er samengevoegd moet worden naar $1.<br />Klik <u>Gebruiker samenvoegen</u> om te accepteren.',
		'usermerge-noolduser' => 'Oude gebruiker is niet ingegeven',
		'usermerge-olduser' => 'Oude gebruiker (samenvoegen van)',
		'usermerge-newuser' => 'Nieuwe gebruiker (samenvoegen naar)',
		'usermerge-deleteolduser' => 'Verwijder oude gebruiker?',
		'usermerge-submit' => 'Gebruiker samenvoegen',
		'usermerge-badtoken' => 'Ongeldig bewerkingstoken',
		'usermerge-userdeleted' => '$1($2) is verwijderd.',
		'usermerge-updating' => 'Tabel $1 aan het bijwerken ($2 naar $3)',
		'usermerge-success' => 'Samenvoegen van $1($2) naar $3($4) is afgerond.'
	),
	'yue' => array(
		'usermerge' => '用戶合併同刪除',
		'usermerge-badolduser' => '無效嘅舊用戶名',
		'usermerge-badnewuser' => '無效嘅新用戶名',
		'usermerge-nonewuser' => '清除新用戶名 - 假設合併到$1。<br />撳<u>合併用戶</u>去接受。',
		'usermerge-noolduser' => '清除舊用戶名',
		'usermerge-olduser' => '舊用戶 (合併自)',
		'usermerge-newuser' => '新用戶 (合併到)',
		'usermerge-deleteolduser' => '刪舊用戶？',
		'usermerge-submit' => '合併用戶',
		'usermerge-badtoken' => '無效嘅編輯幣',
		'usermerge-userdeleted' => '$1($2) 已經刪除咗。',
		'usermerge-updating' => '更新緊 $1 表 ($2 到 $3)',
		'usermerge-success' => '由 $1($2) 到 $3($4) 嘅合併已經完成。'
	),
	'zh-hans' => array(
		'usermerge' => '用户合并和删除',
		'usermerge-badolduser' => '无效的旧用户名',
		'usermerge-badnewuser' => '无效的新用户名',
		'usermerge-nonewuser' => '清除新用户名 - 假设合并到$1。<br />点击<u>合并用户</u>以接受。',
		'usermerge-noolduser' => '清除旧用户名',
		'usermerge-olduser' => '旧用户 (合并自)',
		'usermerge-newuser' => '新用户 (合并到)',
		'usermerge-deleteolduser' => '删除旧用户？',
		'usermerge-submit' => '合并用户',
		'usermerge-badtoken' => '无效的编辑币',
		'usermerge-userdeleted' => '$1($2) 已删除。',
		'usermerge-updating' => '正在更新 $1 表格 ($2 到 $3)',
		'usermerge-success' => '由 $1($2) 到 $3($4) 的合并已经完成。'
	),
	'zh-hant' => array(
		'usermerge' => '用戶合併和刪除',
		'usermerge-badolduser' => '無效的舊用戶名',
		'usermerge-badnewuser' => '無效的新用戶名',
		'usermerge-nonewuser' => '清除新用戶名 - 假設合併到$1。<br />點擊<u>合併用戶</u>以接受。',
		'usermerge-noolduser' => '清除舊用戶名',
		'usermerge-olduser' => '舊用戶 (合併自)',
		'usermerge-newuser' => '新用戶 (合併到)',
		'usermerge-deleteolduser' => '刪除舊用戶？',
		'usermerge-submit' => '合併用戶',
		'usermerge-badtoken' => '無效的編輯幣',
		'usermerge-userdeleted' => '$1($2) 已刪除。',
		'usermerge-updating' => '正在更新 $1 表格 ($2 到 $3)',
		'usermerge-success' => '由 $1($2) 到 $3($4) 的合併已經完成。'
	),
);

$allMessages['zh'] = $allMessages['zh-hans'];
$allMessages['zh-cn'] = $allMessages['zh-hans'];
$allMessages['zh-hk'] = $allMessages['zh-hant'];
$allMessages['zh-sg'] = $allMessages['zh-hans'];
$allMessages['zh-tw'] = $allMessages['zh-hant'];
$allMessages['zh-yue'] = $allMessages['yue'];
