<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE == 'BE')	{
	t3lib_extMgm::insertModuleFunction(
		'web_info',		
		'tx_webinfocontents_modfunc1',
		t3lib_extMgm::extPath($_EXTKEY).'modfunc1/class.tx_webinfocontents_modfunc1.php',
		'LLL:EXT:webinfo_contents/locallang_db.xml:moduleFunction.tx_webinfocontents_modfunc1'
	);
}
?>