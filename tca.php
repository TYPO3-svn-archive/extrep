<?php


// ******************************************************************
// Extension Key table
// ******************************************************************
$TCA['tx_extrep_keytable'] = Array (
	'ctrl' => $TCA['tx_extrep_keytable']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'extension_key'
	),
	'columns' => Array (	
		'title' => Array (
			'label' => 'Title',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '256',
				'eval' => 'trim'
			)
		),
		'description' => Array (
			'label' => 'LLL:EXT:extrep/locallang_tca.php:tx_extrep_keytable.description',
			'config' => Array (
				'type' => 'text',
				'cols' => '40',	
				'rows' => '5'
			)
		),
		'extension_key' => Array (
			'label' => 'LLL:EXT:extrep/locallang_tca.php:tx_extrep_keytable.extension_key',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '30',
				'eval' => 'trim,unique,required'
			)
		),
		'upload_password' => Array (
			'label' => 'LLL:EXT:extrep/locallang_tca.php:tx_extrep_keytable.upload_password',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '30',
				'eval' => 'trim'
			)
		),
		'maxStoreSize' => Array (
			'label' => 'LLL:EXT:extrep/locallang_tca.php:tx_extrep_keytable.maxStoreSize',
			'config' => Array (
				'type' => 'input',
				'size' => '10',
				'max' => '10',
				'eval' => 'int'
			)
		),
		'groupmem' => Array (
			'label' => 'LLL:EXT:extrep/locallang_tca.php:tx_extrep_keytable.groupmem',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'fe_users',
				'MM' => 'tx_extrep_groupmem_mm',
				'size' => '20',
				'maxitems' => '100000',
				'minitems' => '0',
				'show_thumbs' => '1'
			)
		),
		'owner_fe_user' => Array (
			'label' => 'LLL:EXT:extrep/locallang_tca.php:tx_extrep_keytable.owner_fe_user',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'fe_users',
				'size' => '1',
				'maxitems' => '1',
				'minitems' => '0',
				'show_thumbs' => '1'
			)
		),
		'hidden' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'members_only' => Array (
			'label' => 'LLL:EXT:extrep/locallang_tca.php:tx_extrep_keytable.members_only',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		)
	),
	'types' => Array (	
		'1' => Array('showitem' => 'hidden;;;;1-1-1, title,description,owner_fe_user,groupmem,members_only,upload_password,maxStoreSize')
	)
);





// ****************************************************************************************************
// Extension Repository table (contains versions of extensions for each key in 'tx_extrep_keytable')
// ****************************************************************************************************
$TCA['tx_extrep_repository'] = Array (
	'ctrl' => $TCA['tx_extrep_repository']['ctrl'],
	'columns' => Array (	
		'backend_title' => Array (
			'label' => 'LLL:EXT:extrep/locallang_tca.php:tx_extrep_repository.backend_title',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '30',
				'eval' => 'trim,required'
			)
		)
	),
	'types' => Array (	
		'1' => Array('showitem' => 'backend_title')
	)
);




?>