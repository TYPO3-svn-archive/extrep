<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA['tx_extrep_keytable'] = Array (
	'ctrl' => Array (
		'label' => 'extension_key',
		'default_sortby' => 'ORDER BY extension_key',
		'tstamp' => 'tstamp',
		'delete' => 'deleted',
		'crdate' => 'crdate',
		'enablecolumns' => Array (
			'disabled' => 'hidden'
		),
		'title' => 'LLL:EXT:extrep/locallang_tca.php:tx_extrep_keytable',
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'tx_extrep_keytable.gif',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php'
	)
);
$TCA['tx_extrep_repository'] = Array (
	'ctrl' => Array (
		'label' => 'backend_title',
		'default_sortby' => 'ORDER BY backend_title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'title' => 'LLL:EXT:extrep/locallang_tca.php:tx_extrep_repository',
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'tx_extrep_repository.gif',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tca.php'
	)
);
t3lib_extMgm::allowTableOnStandardPages('tx_extrep_keytable');
t3lib_extMgm::addPlugin(Array('Extension Repository', $_EXTKEY));
?>