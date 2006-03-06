<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2001-2006 Kasper Sk�hj <kasper@typo3.org>
*      			 Robert Lemke <robert@typo3.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * TYPO3 Extension Repository
 *
 * Refactored for TER 2.0, July 2005
 *
 * @author	Kasper Sk�hj <kasper@typo3.com>
 * @author	Robert Lemke <robert@typo3.org>
 */

/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *

 *
 * TOTAL FUNCTIONS: 0
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once(PATH_tslib.'class.tslib_pibase.php');

if (t3lib_extMgm::isLoaded('ter')) {	
	require_once(t3lib_extMgm::extPath('ter').'class.tx_ter_helper.php');
	require_once(t3lib_extMgm::extPath('ter').'class.tx_ter_api.php');	
}



/**
 * TYPO3 Extension Repository, frontend plugin for delivery of extensions from repository.
 * Base class for extrep_mgm class as well (frontend management functions)
 * See other extension (extrep_mgm) for all the management functions, documentation hub, translations etc.
 *
 * @author	Kasper Sk�hj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tx_extrep
 */
class tx_extrep extends tslib_pibase {

		// Internal, static:
	var $varPrefix = 'tx_extrep';

		// Internal, dynamic
	var $cObj;							// Standard cObj (parent)
	var $piData = array();				// PI data (input from outside)

	var $dbPageId = 0;					// Loaded internally with the page uid where frontend users AND repository records are stored.
	var $byPassMemberOK = 0;			// For debugging; disable check for extension membership.
	var $ext_feUserSelection = array();	// Loaded with the list of extensions a user has in his selection (from frontend management)


	/**
	 * Extension Categories (static var)
	 * Content must be redundant with the same internal variable as in the Extension Manager module.
	 */
	var $categories = Array(
		'be' => 'Backend',
		'module' => 'Backend Modules',
		'fe' => 'Frontend',
		'plugin' => 'Frontend Plugins',
		'misc' => 'Miscellaneous',
		'services' => 'Services',
		'templates' => 'Templates',
		'example' => 'Examples',
		'doc' => 'Documentation'
	);

	/**
	 * Extension States
	 * Content must be redundant with the same internal variable as in the Extension Manager module!
	 */
	var $states = Array (
		'alpha' => 'Alpha',
		'beta' => 'Beta',
		'stable' => 'Stable',
		'experimental' => 'Experimental',
		'test' => 'Test',
		'obsolete' => 'Obsolete',
	);

	/**
	 * Extension States descriptions
	 * Content must be redundant with the same list in the TYPO3 Core API document / Extension section
	 */
	var $statesDescr = Array (
		'alpha' => 'Alpha state is used for very initial work, basically the state is has during the very process of creating its foundation.',
		'beta' => 'Under current development. Beta extensions are functional but not complete in functionality. Most likely beta-extensions will not be reviewed.',
		'stable' => 'Stable extensions are complete, mature and ready for production environment. You will be approached for a review. Authors of stable extensions carry a responsibility to be maintain and improve them.',
		'experimental' => 'Experimental state is useful for anything experimental - of course. Nobody knows if this is going anywhere yet... Maybe still just an idea.',
		'test' => 'Test extension, demonstrates concepts etc.',
		'obsolete' => 'The extension is obsolete or depreciated. This can be due to other extensions solving the same problem but in a better way or if the extension is not being maintained anymore.',
	);

	/**
	 * Default storage size for extensions (100K)
	 */
 	var $maxStoreSize = 102400;

	/**
	 * WSDL and SOAP URIs for the new TER 2.0
	 */
 	var $WSDLURI;
 	var $SOAPServiceURI;

	/**
	 * Main function
	 *
	 * @param	string		Empty content, ignore.
	 * @param	array		TypoScript for the plugin.
	 * @return	string		HTML content.
	 */
	function main($content,$conf)	{

		$this->WSDLURI = t3lib_div::getIndpEnv('TYPO3_SITE_URL').'wsdl/tx_ter_wsdl.php';
		$this->SOAPServiceURI = t3lib_div::getIndpEnv('TYPO3_SITE_URL').'index.php?id=ter';
		$this->repositoryDir = PATH_site.'fileadmin/ter/';

		$this->getPIdata();

		$this->dbPageId = 1320;

		if ($this->dbPageId<=0)	{
			$content='<p>You must add a reference to a page (called "Starting point") in the "Insert Plugin" content element. That page should be where Frontend Users and all repository records are stored.</p>';
		} else {
				// These four situations returns compiled content back to the EM.
				// So the request is expected to come from the EM inside TYPO3! (Exception applies for "importExtension", see outputSerializedData())
			if (is_array($this->piData['upload']))	{
				$content.=$this->handleUpload();
			} elseif ($this->piData['cmd']=='currentListing')	{
				$content = $this->currentListing($this->piData['debug']);
			} elseif ($this->piData['cmd']=='importExtension')	{
				$content = $this->importExtension($this->piData['uid']);
			} elseif ($this->piData['cmd']=='extensionInfo')	{
				$content = $this->extensionInfo($this->piData['uid']);
			}
		}
		return $content;
	}

	/**
	 * Initializes pi_data
	 *
	 * @return	void
	 */
	function getPIdata() {
		$this->piData = t3lib_div::_GP($this->varPrefix);
	}





	/*********************************************************
	 *
	 * Main functions for interaction with the EM in TYPO3.
	 * These functions are called from the class tx_extrep
	 *
	 *********************************************************/


	/**
	 * Returns compiled information to the EM about an extension.
	 * Revised for TER 2.0
	 *
	 * @param	integer		UID of a record in table tx_ter_extensionkeys
	 * @return	string		Stream of data.
	 */
	function extensionInfo($uid)	{
		global $TYPO3_DB;

		$outArr=array();

			// Fetch requested extension record:
		$res = $TYPO3_DB->exec_SELECTquery (
			'	uid,
				extensionkey AS extension_key,
				tstamp,
				version,
				title AS emconf_title,
				description AS emconf_description,
				category AS emconf_category,
				state AS emconf_state,
				downloadcounter,
				ismanualincluded AS is_manual_included
			',
			'tx_ter_extensions',
			'uid="'.intval($uid).'"'
		);

		if ($outArr = $TYPO3_DB->sql_fetch_assoc($res))	{

			foreach ($outArr as $key => $value) {
				$outArr[$key] = utf8_decode ($value);
			}			
			$outArr = array_merge ($outArr, $this->fetchLegacyExtensionDetails ($outArr['uid']));

				// Fetch other versions with same extension key:
			$res = $TYPO3_DB->exec_SELECTquery (					
				'	uid,
					extensionkey AS extension_key,
					tstamp,
					version,
					title AS emconf_title,
					description AS emconf_description,
					category AS emconf_category,
					state AS emconf_state,
					downloadcounter,
					ismanualincluded AS is_manual_included
				',
				'tx_ter_extensions',
				'extensionkey="'.$outArr['extension_key'].'"'
			);
			while ($otherVersionArr = $TYPO3_DB->sql_fetch_assoc($res))	{
				foreach ($otherVersionArr as $key => $value) {
					$otherVersionArr[$key] = utf8_decode ($value);
				}			
				$otherVersionArr = array_merge ($otherVersionArr, $this->fetchLegacyExtensionDetails ($otherVersionArr['uid']));
				$outArr['_other_versions'][]=$otherVersionArr;
			}

			$outArr['_ICON']= $this->getIconTag($outArr['extension_key'], $outArr['version']);

		} else {
			$outArr['_MESSAGES'][]='Extension was not found!';
		}

		$this->outputSerializedData($outArr);
	}

	/**
	 * Returns a specific version of an extension (in compiled format) to the EM
	 * Revised for TER 2.0
	 *
	 * @param	integer		UID of a record in table tx_ter_extensionkeys
	 * @return	string		Stream of data.
	 */
	function importExtension($uid)	{
		global $TYPO3_DB;

		$outArr = array();
		$outArr['_MESSAGES'][]='Welcome pilgrim, you are proudly served by the TYPO3 Extension Repository version 2.0!';
		$outArr['_MESSAGES'][]=' ';

			// Fetch requested extension record:
		$res = $TYPO3_DB->exec_SELECTquery (
			'	uid,
				extensionkey AS extension_key,
				tstamp,
				version,
				title AS emconf_title,
				description AS emconf_description,
				category AS emconf_category,
				state AS emconf_state,
				downloadcounter,
				ismanualincluded AS is_manual_included
			',
			'tx_ter_extensions',
			'uid="'.intval($uid).'"'
		);

		if ($row = $TYPO3_DB->sql_fetch_assoc($res))	{
			foreach ($row as $key => $value) {
				$row[$key] = utf8_decode ($value);
			}			
			$row = array_merge ($row, $this->fetchLegacyExtensionDetails ($row['uid']));
			$outArr['extKey'] = $row['extension_key'];
			$outArr['EM_CONF'] = $this->getEMCONFarrayForDownload($row);

				// Read T3X file from repository directory:
			$firstLetter = strtolower (substr ($row['extension_key'], 0, 1));
			$secondLetter = strtolower (substr ($row['extension_key'], 1, 1));
			$fullPath = $this->repositoryDir.$firstLetter.'/'.$secondLetter.'/';

			list ($majorVersion, $minorVersion, $devVersion) = t3lib_div::intExplode ('.', $row['version']);
			$t3xFileName = strtolower ($row['extension_key']).'_'.$majorVersion.'.'.$minorVersion.'.'.$devVersion.'.t3x';
		
			$t3xData = @file($fullPath.$t3xFileName);	
			if (is_array($t3xData)) {
				list ($md5Hash, $compressionInfo, $rawData) = split (':', implode ('', $t3xData), 3);
				$t3xDataArr = unserialize($compressionInfo == 'gzcompress' ? gzuncompress ($rawData) : $rawData);
				$outArr['FILES'] = $t3xDataArr['FILES'];
			} 

				// Include SXW manual?
			if ($this->piData['inc_manual'])	{
				$outArr['_MESSAGES'][] = 'Requesting manual send...';
				if (isset($outArr['FILES']['doc/manual.sxw']))	{
					$outArr['_MESSAGES'][] = 'doc/manual.sxw included.';
				} else $outArr['_MESSAGES'][] = 'No manual found.';
			} else unset($outArr['FILES']['doc/manual.sxw']);

				// Add most recent translation?
			if ($this->piData['transl']) {
				$outArr['_MESSAGES'][] = 'Sorry, most recent translation cannot be added, please upgrade to a TER 2.0 compatible Extension Manager.';
			}

				// Increase download counter (manually).
				// NOTE: The new value won't be visible until the extensions.xml.gz is updated by any other action of the true TER2 API. But we accept that.

			$newCounter = intval($row['downloadcounter']) + 1;
			$res = $TYPO3_DB->exec_UPDATEquery (
				'tx_ter_extensions', 
				'uid='.$row['uid'],
				array('downloadcounter' => $newCounter)
			);

			$res = $TYPO3_DB->exec_SELECTquery (
				'uid,downloadcounter',
				'tx_ter_extensionkeys', 
				'extensionkey='.$TYPO3_DB->fullQuoteStr($row['extension_key'],' tx_ter_extensionkeys')
			);
			if ($res) {
				$extensionKeyRow = $TYPO3_DB->sql_fetch_assoc($res);
				if (is_array ($extensionKeyRow)) {
					$newTotalCounter = intval($extensionKeyRow['downloadcounter']) + 1;
					$res = $TYPO3_DB->exec_UPDATEquery (
						'tx_ter_extensionkeys', 
						'uid='.$extensionKeyRow['uid'],
						array('downloadcounter' => $newTotalCounter)
					);						
				}	
			}

			// These lines would be neccessary to update the extensions.xml.gz file. But they would generate quite some load on the server!
			//			$helperObj = new tx_ter_helper ($this);
			//			$helperObj->writeExtensionIndexFile();

			$outArr['_MESSAGES'][]='Extension download was successful!';
			
		} else {
			$outArr['_MESSAGES'][]='Repository Uid "'.$uid.'" was not found!';
		}

		$this->outputSerializedData($outArr);
	}

	/**
	 * Returns compiled list of extensions to the EM (or TER if $internal flag is set)
	 * Revised for TER 2.0
	 *
	 * Note: During the SELECT queries the TER 2.0 field names are mapped with the "AS" keyword
	 *       to match the TER 1.0 standard so all Extension Managers receive the expected field names.
	 *
	 * @return	string 	Returns a stream to the EM
	 */
	function currentListing() {
		global $TYPO3_DB;

		$mode = $this->piData['listmode'];
		$outArr = array();

			// Create search query if neccessary:
		$searchClause = '';
		if (trim($this->piData['search']))	{
			$sW = split('[[:space:]]+',substr(trim($this->piData['search']),0,100),5);
			$searchClause = ' AND '.$GLOBALS['TYPO3_DB']->searchQuery($sW,explode(',','extensionkey'),'tx_ter_extensionkeys');
		}

			// Select the extension key record:
		$resExtensionKeys = $TYPO3_DB->exec_SELECTquery(
			'	extensionkey AS extension_key,
				ownerusername,
				downloadcounter
			',
			'tx_ter_extensionkeys',
			'pid='.intval($this->dbPageId).
				$searchClause
		);

		while ($rowExtensionKey = $TYPO3_DB->sql_fetch_assoc($resExtensionKeys))	{

				// Select the extension record:
			$resExtension = $TYPO3_DB->exec_SELECTquery(
				'	uid,
					tstamp,
					version,
					title AS emconf_title,
					description AS emconf_description,
					category AS emconf_category,
					state AS emconf_state,
					downloadcounter,
					ismanualincluded AS is_manual_included
				',
				'tx_ter_extensions',
				'extensionkey="'.$rowExtensionKey['extension_key'].'"'.
					' AND pid='.intval($this->dbPageId),
				'version DESC'
			);

			if ($rowExtension = $TYPO3_DB->sql_fetch_assoc($resExtension))	{
				$rowExtensionDetails = $this->fetchLegacyExtensionDetails ($rowExtension['uid']);
				$outArr[] = array_merge($rowExtensionKey, $rowExtension, $rowExtensionDetails, array(
					'_STAT_IMPORT'=>array(
						'extension_thisversion'=>$rowExtension['downloadcounter'],
						'extension_allversions'=>$rowExtensionKey['downloadcounter'],
					),
					'_ICON'=> $this->getIconTag ($rowExtensionKey['extension_key'], $rowExtension['version']),
				));
			}
		}

		$this->outputSerializedData($outArr);
	}

	/**
	 * Handles an upload of an extension.
	 * Revised for TER 2.0
	 *
	 * @return		string		HTML error message
	 * @internal	Supports TER WSDL specification 1.0.0
	 */
	function handleUpload()	{

		$decodedData = $this->decodeExchangeData(base64_decode($this->piData['upload']['data']));

			// Render new version number:
		$newVersionBase = $decodedData['EM_CONF']['version'];
		switch((string)$this->piData['upload']['mode'])	{
			case 'new_dev': $cmd='dev'; break;
			case 'new_sub': $cmd='sub'; break;
			case 'new_main': $cmd='main'; break;
			case 'latest':
			default:
				$cmd='';
				$newVersionBase=$decodedData['EM_CONF']['version'];
			break;
		}
		$versionArr = $this->renderVersion($newVersionBase, $cmd);

			// Create dependency / conflict information:
		$dependenciesArr = array ();

		$extKeysArr = t3lib_div::trimExplode (',', $decodedData['EM_CONF']['dependencies']);
		if (is_array ($extKeysArr)) {
			foreach ($extKeysArr as $extKey) {
				if (strlen ($extKey)) {
					$dependenciesArr [] = array (
						'kind' => 'depends',
						'extensionKey' => utf8_encode ($extKey),
						'versionRange' => '',
					);
				}
			}
		}

		$extKeysArr = t3lib_div::trimExplode (',', $decodedData['EM_CONF']['conflicts']);
		if (is_array ($extKeysArr)) {
			foreach ($extKeysArr as $extKey) {
				if (strlen ($extKey)) {
					$dependenciesArr [] = array (
						'kind' => 'conflicts',
						'extensionKey' => utf8_encode ($extKey),
						'versionRange' => '',
					);
				}
			}
		}

		if (isset ($decodedData['EM_CONF']['PHP_version'])) {
				$dependenciesArr [] = array (
					'kind' => 'depends',
					'extensionKey' => 'php',
					'versionRange' => $decodedData['EM_CONF']['PHP_version'],
				);
		}

		if (isset ($decodedData['EM_CONF']['TYPO3_version'])) {
				$dependenciesArr [] = array (
					'kind' => 'depends',
					'extensionKey' => 'typo3',
					'versionRange' => $decodedData['EM_CONF']['TYPO3_version'],
				);
		}		

		if (t3lib_extMgm::isLoaded('ter'))  {
			return $this->handleUpload_directly($decodedData, $dependenciesArr, $versionArr);			
		} else { 
			return $this->handleUpload_soap($decodedData, $dependenciesArr, $versionArr);
		}
	}

	/**
	 * Subfunction of handleUpload() - uploads an extension to TER by writing
	 * the data directly into the database. Only works if the extension "ter"
	 * is also installed and the repository is truly in the same installation.
	 * 
	 * @param		array		$decodedData: The decoded POST data from handleUpload() 
	 * @param		array		$dependenciesArr: The prepared TER2 compatible constraints
	 * @return		string		HTML error message
	 */
	function handleUpload_directly($decodedData, $dependenciesArr, $versionArr) {
		
			// Create objects for calling the uploadFunction in the TER API directly:			
		$accountData = new STDCLASS;
		$accountData->username = utf8_encode (trim ($this->piData['user']['fe_u']));
		$accountData->password = utf8_encode (trim ($this->piData['user']['fe_p']));
		
		$extensionInfoData = new STDCLASS;
		$extensionInfoData->extensionKey = utf8_encode ($decodedData['extKey']);
		$extensionInfoData->version = utf8_encode ($versionArr['version']);
		
		$extensionInfoData->metaData = new STDCLASS;		
		$extensionInfoData->metaData->title = utf8_encode ($decodedData['EM_CONF']['title']);
		$extensionInfoData->metaData->description = utf8_encode ($decodedData['EM_CONF']['description']);
		$extensionInfoData->metaData->category = utf8_encode ($decodedData['EM_CONF']['category']);
		$extensionInfoData->metaData->state = utf8_encode ($decodedData['EM_CONF']['state']);
		$extensionInfoData->metaData->authorName = utf8_encode ($decodedData['EM_CONF']['author']);
		$extensionInfoData->metaData->authorEmail = utf8_encode ($decodedData['EM_CONF']['author_email']);
		$extensionInfoData->metaData->authorCompany = utf8_encode ($decodedData['EM_CONF']['author_company']);

		$extensionInfoData->technicalData = new STDCLASS;
		$extensionInfoData->technicalData->dependencies = $dependenciesArr;
		$extensionInfoData->technicalData->loadOrder = utf8_encode ($decodedData['EM_CONF']['loadOrder']);
		$extensionInfoData->technicalData->uploadFolder = utf8_encode ($decodedData['EM_CONF']['uploadfolder']);
		$extensionInfoData->technicalData->createDirs = utf8_encode ($decodedData['EM_CONF']['createDirs']);
		$extensionInfoData->technicalData->shy = utf8_encode ($decodedData['EM_CONF']['shy']);
		$extensionInfoData->technicalData->modules = utf8_encode ($decodedData['EM_CONF']['module']);
		$extensionInfoData->technicalData->modifyTables = utf8_encode ($decodedData['EM_CONF']['modify_tables']);
		$extensionInfoData->technicalData->priority = utf8_encode ($decodedData['EM_CONF']['priority']);
		$extensionInfoData->technicalData->clearCacheOnLoad = utf8_encode ($decodedData['EM_CONF']['clearCacheOnLoad']);
		$extensionInfoData->technicalData->lockType = utf8_encode ($decodedData['EM_CONF']['lockType']);

		$extensionInfoData->infoData = new STDCLASS;
		$extensionInfoData->infoData->codeLines = intval($decodedData['misc']['codelines']);
		$extensionInfoData->infoData->codeBytes = intval($decodedData['misc']['codebytes']);
		$extensionInfoData->infoData->codingGuidelinesCompliance = utf8_encode ($decodedData['EM_CONF']['CGLcompliance']);
		$extensionInfoData->infoData->codingGuidelinesComplianceNotes = utf8_encode ($decodedData['EM_CONF']['CGLcompliance_note']);
		$extensionInfoData->infoData->uploadComment = utf8_encode ($this->piData['upload']['comment']);
		$extensionInfoData->infoData->techInfo = unserialize ($decodedData['techInfo']);

		$filesData = new STDCLASS;
		$filesData->fileData = array();
		foreach ($decodedData['FILES'] as $filename => $infoArr) {
			$fileData = new STDCLASS;
			$fileData->name = utf8_encode ($infoArr['name']); 
			$fileData->size = intval($infoArr['size']); 
			$fileData->modificationTime = intval($infoArr['mtime']);
			$fileData->isExecutable = intval($infoArr['is_executable']); 
			$fileData->content = base64_encode($infoArr['content']);
			$fileData->contentMD5 = md5($infoArr['content']);
			
			$filesData->fileData[] = $fileData;
		}

			// Set variable $this->extensionsPID because the tx_ter_helper class expects that to get from the pluginObj (which is $this in our case):
		$this->extensionsPID = $this->dbPageId;
	
		$terAPIObj = new tx_ter_api($this);
		try {
			$result = $terAPIObj->uploadExtension (
				$accountData,
				$extensionInfoData,
				$filesData
			);
		} catch (SoapFault $exception) {
			return
				'<strong>Upload was not successful!</strong><br />
				<p>The TER2 SOAP server throwed an exception (<em>'.$exception->faultcode.': '.$exception->faultstring.'</em>). 
				If that seems to be an error, please post a bug report at bugs.typo3.org and mention this error code and description.
			';				
		}

			// Create EM conf array which is delivered back to the EM:
		$backEM_CONF = $decodedData['EM_CONF'];
		$backEM_CONF['version'] = $result['version'];

			// Print info content for Extension Manager frame:
		$content.="
			The extension '".$decodedData['EM_CONF']['title']."' (".$decodedData['extKey'].') version <strong>'.$result['version'].'</strong> was inserted into the repository.<br />
			<br />
			Please finish the upload process by pressing this button to return to your servers Extensions Manager and update the configuration.
			<br />
			<br />
			<form action="'.htmlspecialchars($this->piData['upload']['returnUrl']).'" method="post">
				<input type="hidden" name="TER_CMD[returnValue]" value="'.htmlspecialchars($this->compileOutputData($backEM_CONF)).'" />
				<input type="hidden" name="TER_CMD[extKey]" value="'.htmlspecialchars($decodedData['extKey']).'" />
				<input type="hidden" name="TER_CMD[cmd]" value="EM_CONF" />
				<input type="submit" name="TER_CMD[update]" value="Syncronize EM" />
			</form>
			<br />
			<span style="font-size: 10px;">TER 2.0 (direct mode)</span>
		';
		return $content;
	}

	/**
	 * Subfunction of handleUpload() - uploads an extension to TER by using the
	 * SOAP interface of the extension "ter". You have to use this if the repository
	 * is not located in this TYPO3 installation.
	 *
	 * @param		array		$decodedData: The decoded POST data from handleUpload() 
	 * @param		array		$dependenciesArr: The prepared TER2 compatible constraints
	 * @return		string		HTML error message
	 */
	function handleUpload_soap($decodedData, $dependenciesArr, $versionArr) {

			// Compile data for SOAP call:
		$accountData = array (
			'username' => utf8_encode (trim ($this->piData['user']['fe_u'])),
			'password' => utf8_encode (trim ($this->piData['user']['fe_p']))
		);
		$extensionInfoData = array (
			'extensionKey' => utf8_encode ($decodedData['extKey']),
			'version' => utf8_encode ($versionArr['version']),
			'metaData' => array (
				'title' => utf8_encode ($decodedData['EM_CONF']['title']),
				'description' => utf8_encode ($decodedData['EM_CONF']['description']),
				'category' => utf8_encode ($decodedData['EM_CONF']['category']),
				'state' => utf8_encode ($decodedData['EM_CONF']['state']),
				'authorName' => utf8_encode ($decodedData['EM_CONF']['author']),
				'authorEmail' => utf8_encode ($decodedData['EM_CONF']['author_email']),
				'authorCompany' => utf8_encode ($decodedData['EM_CONF']['author_company']),
			),
			'technicalData' => array (
				'dependencies' => $dependenciesArr,
				'loadOrder' => utf8_encode ($decodedData['EM_CONF']['loadOrder']),
				'uploadFolder' => utf8_encode ($decodedData['EM_CONF']['uploadfolder']),
				'createDirs' => utf8_encode ($decodedData['EM_CONF']['createDirs']),
				'shy' => utf8_encode ($decodedData['EM_CONF']['shy']),
				'modules' => utf8_encode ($decodedData['EM_CONF']['module']),
				'modifyTables' => utf8_encode ($decodedData['EM_CONF']['modify_tables']),
				'priority' => utf8_encode ($decodedData['EM_CONF']['priority']),
				'clearCacheOnLoad' => utf8_encode ($decodedData['EM_CONF']['clearCacheOnLoad']),
				'lockType' => utf8_encode ($decodedData['EM_CONF']['lockType']),
			),
			'infoData' => array(
				'codeLines' => intval($decodedData['misc']['codelines']),
				'codeBytes' => intval($decodedData['misc']['codebytes']),
				'codingGuidelinesCompliance' => utf8_encode ($decodedData['EM_CONF']['CGLcompliance']),
				'codingGuidelinesComplianceNotes' => utf8_encode ($decodedData['EM_CONF']['CGLcompliance_note']),
				'uploadComment' => utf8_encode ($this->piData['upload']['comment']),
				'techInfo' => unserialize ($decodedData['techInfo']),
			),
		);

		$filesData = array();
		foreach ($decodedData['FILES'] as $filename => $infoArr) {
			$filesData['fileData'][] = array (
				'name' => utf8_encode ($infoArr['name']),
				'size' => intval($infoArr['size']),
				'modificationTime' => intval($infoArr['mtime']),
				'isExecutable' => intval($infoArr['is_executable']),
				'content' => base64_encode($infoArr['content']),
				'contentMD5' => md5($infoArr['content']),
			);
		}

			// Upload extension via SOAP:
		$soapClientObj = new SoapClient ($this->WSDLURI, array ('trace' => 1, 'exceptions' => 1, 'soap_version'  => SOAP_1_2));
		try {
			$soapResult = $soapClientObj->uploadExtension (
				$accountData,
				$extensionInfoData,
				$filesData
			);
		} catch (SoapFault $exception) {
			return
				'<strong>Upload was not successful!</strong>
				<p>The TER2 SOAP server throwed an exception (<em>'.$exception->faultcode.': '.$exception->faultstring.'</em>). Please
				visit the teams.typo3.typo3org newsgroup to check if this is a known problem. Otherwise, please report it to our
				bugtracker at <a href="http://bugs.typo3.org" target="_new">bugs.typo3.org</a> and before you do, make sure a similar
				report doesn\'t exist already.</p>
				<p>Sorry for the inconvenience.</p>
				<em>Request:</em><br />
				<pre>' .
					$soapClientObj->__getLastRequestHeaders().'
				</pre>
				<em>Response:</em><br />
				<pre>'.
					$soapClientObj->__getLastResponseHeaders().
				'</pre>'.
				htmlspecialchars($soapClientObj->__getLastResponse());
		}

			// Create EM conf array which is delivered back to the EM:
		$backEM_CONF = $decodedData['EM_CONF'];
		$backEM_CONF['version'] = $soapResult['version'];

			// Print info content for Extension Manager frame:
		$content.="
			The extension '".$decodedData['EM_CONF']['title']."' (".$decodedData['extKey'].') version <strong>'.$soapResult['version'].'</strong> was inserted into the repository.<br />
			<br />
			Please finish the upload process by pressing this button to return to your servers Extensions Manager and update the configuration.
			<br />
			<br />
			<form action="'.htmlspecialchars($this->piData['upload']['returnUrl']).'" method="post">
				<input type="hidden" name="TER_CMD[returnValue]" value="'.htmlspecialchars($this->compileOutputData($backEM_CONF)).'" />
				<input type="hidden" name="TER_CMD[extKey]" value="'.htmlspecialchars($decodedData['extKey']).'" />
				<input type="hidden" name="TER_CMD[cmd]" value="EM_CONF" />
				<input type="submit" name="TER_CMD[update]" value="Syncronize EM" />
			</form>
			<br />
			<span style="font-size: 10px;">TER 2.0 (SOAP mode)</span>
		';
		return $content;
	}





	/*********************************************************
	 *
	 * Miscellaneous helper functions
	 *
	 *********************************************************/


	/**
	 * Based on a x.x.x version number this is converted to some formats returned in an array.
	 *
	 * @param	string		Version number string
	 * @param	string		Keyword to increase the version digit for either 'main', 'sub' or 'dev'
	 * @return	array		Array with various versions of the version number.
	 */
	function renderVersion($v,$raise='')	{
		$parts = t3lib_div::intExplode('.',$v.'..');
		$parts[0] = t3lib_div::intInRange($parts[0],0,999);
		$parts[1] = t3lib_div::intInRange($parts[1],0,999);
		$parts[2] = t3lib_div::intInRange($parts[2],0,999);
		switch((string)$raise)	{
			case 'main':
				$parts[0]++;
				$parts[1]=0;
				$parts[2]=0;
			break;
			case 'sub':
				$parts[1]++;
				$parts[2]=0;
			break;
			case 'dev':
				$parts[2]++;
			break;
		}
		$res=array();
		$res['version']=$parts[0].'.'.$parts[1].'.'.$parts[2];
		$res['version_int']=intval(str_pad($parts[0],3,'0',STR_PAD_LEFT).str_pad($parts[1],3,'0',STR_PAD_LEFT).str_pad($parts[2],3,'0',STR_PAD_LEFT));
		$res['version_main']=$parts[0];
		$res['version_sub']=$parts[1];
		$res['version_dev']=$parts[2];
		return $res;
	}

	/**
	 * Conversion of version numbers to/from doubles
	 *
	 * @param	mixed		Input string, then a version number like "3.6.0" or "3.4.5rc2". If double it will be converted back to string. Version numbers after suffix is not supported higher than "9".
	 * @param	boolean		If set, the conversion is from double to string, otherwise from string to double.
	 * @return	mixed		String or double depending on input.
	 */
	function versionConv($input,$rev=FALSE)	{

			// Initializing translation table:
		$subDecIndex = array(
			'dev' => 1,
			'a' => 4,
			'b' => 5,
			'rc' => 8
		);

			// Direction of conversion:
		if ($rev)	{	// From double to string
			if (!$input)	{
				$result = '';
			} else {
				list($int,$dev) = explode('.',$input);

					// Looking for decimals:
				$suffix = '';
				if (strlen($dev))	{
					$int++;	// Increase integer since that would have been decreased last time.
					$subDecIndex = array_flip($subDecIndex);
					$suffix = $subDecIndex[substr($dev,0,1)];
					if ($suffix)	{
						$suffix.=intval(substr($dev,1));
					}
				}

					// Base part:
				$result = intval(substr($int,0,-6)).'.'.intval(substr($int,-6,-3)).'.'.intval(substr($int,-3)).$suffix;
			}
		} else {	// From string to double
			$result = t3lib_div::int_from_ver($input);
			if (ereg('(dev|a|b|rc)([0-9]*)$',strtolower($input),$reg))	{
				$dec = intval($subDecIndex[$reg[1]]).$reg[2];
				$result = (double)(($result-1).'.'.$dec);
			}
		}

		return $result;
	}

	/**
	 * Converts TER 2.0 dependency information array to a four different TER 1.0 dependency comma-separated lists.
	 *
	 * @param	array		$dependencyArr: TER 2 Dependency array 
	 * @return	string		Comma separated list of extension keys
	 */
	function renderLegacyDependencyListFromArray($dependenciesArr, &$depends, &$conflicts, &$typo3Version, &$phpVersion)	{
		$legacyDependenciesArr = array();
		$legacyConflictsArr = array();
		
		if (is_array ($dependenciesArr)) {
			foreach ($dependenciesArr as $dependencyArr) {
				if (is_array ($dependencyArr)) {
					if ($dependencyArr['extensionKey'] == 'php') {
						$phpVersion = $dependencyArr['versionRange'];
					} elseif ($dependencyArr['extensionKey'] == 'typo3' ) {
						$typo3Version = $dependencyArr['versionRange'];
					} else { 
						switch ($dependencyArr['kind']) {						
							case 'depends':
								$legacyDependenciesArr[] = $dependencyArr['extensionKey'];										
							break;					
							case 'conflicts':
								$legacyConflictsArr[] = $dependencyArr['extensionKey'];		
							break;							
						}
					}
				}
			}	
		}	
		
		$depends = implode (',', $legacyDependenciesArr);
		$conflicts = implode (',', $legacyConflictsArr);
		
	}

	/**
	 * Returns the image tag for an icon of an extension.
	 * Revised for TER 2.0
	 *
	 * @param	string		Extension key
	 * @param	string		Version
	 * @return	string 		Returns the icon image tag, if any
	 */
	function getIconTag($extensionKey, $version)	{
		$firstLetter = strtolower (substr ($extensionKey, 0, 1));
		$secondLetter = strtolower (substr ($extensionKey, 1, 1));
		$fullPath = $this->repositoryDir.$firstLetter.'/'.$secondLetter.'/';

		list ($majorVersion, $minorVersion, $devVersion) = t3lib_div::intExplode ('.', $version);
		$iconFileName = strtolower ($extensionKey).'_'.$majorVersion.'.'.$minorVersion.'.'.$devVersion.'.gif';

		$iconTag = '<img src="'.t3lib_div::getIndpEnv('TYPO3_REQUEST_DIR').substr($fullPath, strlen(PATH_site)).$iconFileName.'" alt="" />';

		return $iconTag;
	}


	/**
	 * Returns the extension details record for an extension instance specified
	 * by $uid
	 * Created for TER 2.0
	 * @param	integer		$uid: UID of an tx_ter_extensions record
	 * @return	array		EMconf compatible data.
	 */
	function fetchLegacyExtensionDetails ($uid)	{
		global $TYPO3_DB;
		
		$resExtensionDetails = $TYPO3_DB->exec_SELECTquery(
			'	datasize,
				datasizecompressed AS datasize_gz,
				dependencies,
				authorname AS emconf_author,
				authoremail AS emconf_author_email,
				authorcompany AS emconf_author_company,
				shy AS emconf_shy,
				createdirs AS emconf_createDirs,
				priority AS emconf_priority,
				modules AS emconf_module,
				uploadfolder AS emconf_uploadfolder,
				modifytables AS emconf_modify_tables,
				clearcacheonload AS emconf_clearCacheOnLoad,			
				locktype AS emconf_lockType,
				loadorder AS emconf_loadOrder
			',
			'tx_ter_extensiondetails',
			'extensionuid='.intval($uid)
		);

		if ($rowExtensionDetails = $TYPO3_DB->sql_fetch_assoc($resExtensionDetails))	{
			$this->renderLegacyDependencyListFromArray (unserialize($rowExtensionDetails['dependencies']), $rowExtensionDetails['emconf_dependencies'], $rowExtensionDetails['emconf_conflicts'], $rowExtensionDetails['emconf_TYPO3_version'], $rowExtensionDetails['emconf_PHP_version']);
			
			foreach ($rowExtensionDetails as $key => $value) {
				$rowExtensionDetails[$key] = utf8_decode ($value);
			}			
		}

		return $rowExtensionDetails;
	}

	/**
	 * Takes a repository record as input and returns the proper emconf-array
	 *
	 * @param	array		Repository record
	 * @return	array		EMconf data.
	 */
	function getEMCONFarrayForDownload($row)	{
		return array(
				'title' => $row['emconf_title'],
				'description' => $row['emconf_description'],
				'category' => $row['emconf_category'],
				'shy' => $row['emconf_shy'],
				'dependencies' => $row['emconf_dependencies'],
				'conflicts' => $row['emconf_conflicts'],
				'priority' => $row['emconf_priority'],
				'loadOrder' => $row['emconf_loadOrder'],
				'TYPO3_version' => $row['emconf_TYPO3_version'],
				'PHP_version' => $row['emconf_PHP_version'],
				'module' => $row['emconf_module'],
				'state' => $row['emconf_state'],
				'internal' => $row['emconf_internal'],
				'uploadfolder' => $row['emconf_uploadfolder'],
				'createDirs' => $row['emconf_createDirs'],
				'modify_tables' => $row['emconf_modify_tables'],
				'clearCacheOnLoad' => $row['emconf_clearCacheOnLoad'],
				'lockType' => $row['emconf_lockType'],
				'author' => $row['emconf_author'],
				'author_email' => $row['emconf_author_email'],
				'author_company' => $row['emconf_author_company'],
				'CGLcompliance' => $row['emconf_CGLcompliance'],
				'CGLcompliance_note' => $row['emconf_CGLcompliance_note'],
				'version' => $row['version'],
			);
	}

	/**
	 * Takes in an array and outputs it in the compiled format readable by the Extension Manager. Then exits.
	 * Notice, this function servers both the EM requesting from TER AND the physical web management-frontend on typo3.org! And there is a DIFFERENCE in encoding, triggered by the parameter "dlFileName" (being set for typo3.org)
	 *
	 * @param	array		Data array to encode an stream
	 * @return	void		(Exits!)
	 */
	function outputSerializedData($outArr)	{
		if ($this->piData['dlFileName'])	{
				// THIS is ONLY meant for direct download of extensions in the extension format.
				// Extension delivered to the EM (if no 'dlFileName' is entered) is totally differently encoded - for instance the hash is of the compressed! value, not the uncompressed. And further, the content is base64 encoded for some reason.
			$mimeType = 'application/octet-stream';
			Header('Content-Type: '.$mimeType);
			Header('Content-Disposition: attachment; filename='.$this->piData['dlFileName']);

				// Compile content
			$serialized = serialize($outArr);
			$md5 = md5($serialized);
			if ($this->piData['gzcompress'])	$serialized = gzcompress($serialized);

			$content=$md5.':';
			$content.=($this->piData['gzcompress']?'gzcompress':'').':';
			$content.=$serialized;

			echo $content;
			exit;
		} else {
			$mimeType = 'application/octet-stream';
			Header('Content-Disposition: attachment; filename=T3X_'.t3lib_div::shortMD5(time()).'.t3x');
			Header('Content-Type: '.$mimeType);
			echo $this->compileOutputData($outArr);
			exit;
		}
	}

	/**
	 * This compiles the $outArr into a string, compression is applied if piVar[gzcompress] was set.
	 * Acceptable output for the EM retrieving content!
	 *
	 * @param	array		Data array to encode an stream
	 * @return	string		The data stream string
	 */
	function compileOutputData($outArr)	{
		$outDat = serialize($outArr);
		if ($this->piData['gzcompress'])	$outDat = gzcompress($outDat);
		return md5($outDat).':'.($this->piData['gzcompress']?'gzcompress':'').':'.base64_encode($outDat).':';
	}

	/**
	 * Returns an array from the compiled input $str
	 *
	 * @param	string		Input data
	 * @return	mixed		If md5-hashes match, returns array with data, otherwise nothing.
	 */
	function decodeExchangeData($str)	{
		$parts = explode(':',$str,3);
		if ($parts[1]=='gzcompress')	{
			$parts[2] = gzuncompress($parts[2]);
		}
		if (md5($parts[2]) == $parts[0])	{
			return unserialize($parts[2]);
		}
	}

	/**
	 * Prints an error (in red font tags)
	 *
	 * @param	string		Message
	 * @return	string		... in red <font> tags
	 */
	function pError($str)	{
		return '<font color="red"><strong>'.$str.'</strong></font>';
	}














	/***************************************
	 *
	 * TRANSLATION FUNCTIONS:
	 *
	 **************************************/

	/**
	 * Generates an array with status statistics of all the labels for translation for a certain extension key uid.
	 * Is also used to merge a translation into a repository record depending on parameters.
	 *
	 * @param	integer		Extension key uid
	 * @param	array		Alternative (already generated) "Most recent extension repository record"
	 * @param	array		Alternative (already generated) language info array (see getLanguagesAndTranslators())
	 * @param	mixed		If set (boolean for all, or array with values for which), new labels are merged in for languages
	 * @return	mixed		If $mergeAndReturnRepository was either true or an array, then the return value is a new version of the $mostRecent_extRepEntry - otherwise statistical information.
	 */
	function makeTranslationStatus($extKeyUid, $mostRecent_extRepEntry='', $langInfo='',$mergeAndReturnRepository=0)	{
#		if (!t3lib_extmgm::isLoaded('extrep_mgm'))	return 'Error: No extrep_mgm extension loaded.';

			// If no rep. record is supplied, we select it:
		if (!is_array($mostRecent_extRepEntry))	{
			$mostRecent_extRepEntry = $this->getLatestRepositoryEntry($extKeyUid);
		}
			// There must be a rep. record...:
		if (is_array($mostRecent_extRepEntry))	{
				// Get the compressed extension data and validate it:
			$datStr = gzuncompress($mostRecent_extRepEntry['datablob']);
			if (md5($datStr)==$mostRecent_extRepEntry['datablob_md5'])	{
				$dB = unserialize($datStr);

					// Finding locallang.php files + manual HTML.
				$LL=array();
				while(list($file)=each($dB))	{
					if ($dB[$file]['LOCAL_LANG'])	{
						$LL[]=$dB[$file];
					}
				}

					// If there are any LOCAL_LANG files:
				if (count($LL))	{
					$translateStat=array();		// Array into which we collect statistics for translation.

						// This is information about which langauges are available and who are in charge of them:
					$langInfo = is_array($langInfo) ? $langInfo : $this->getLanguagesAndTranslators();

						// Caching all already-translated labels:
					$cachedTransl=array();		// Contains new translations for assistants + chief
					$cachedTransl_chief=array();	// Contains only chiefs translation (if any)

						// Walk through all languages in the system, caching the translated values into arrays:
					reset($langInfo[0]);
					while(list($lK,$lR)=each($langInfo[0]))	{
							// Getting
						list($LLarr_comp_users,$temp,$chief_labels) = $this->getCompiledTranslations($langInfo[2][$lK],$lR['auth_translator'],$mostRecent_extRepEntry['extension_key'],$lK);
						$cachedTransl[$lK]=$LLarr_comp_users;	// Combined values
						$cachedTransl_chief[$lK]=$chief_labels;	// Only chief...
					}

						// Traversing all files for translation:
					foreach($LL as $fIdx => $LLr)	{
							// Check, if the locallang file has a lang key before the .php extension (eg. 'locallang_core.da.php' which is then NOT a main file, but a subfile to 'locallang_core.php')
						$reg=array();
						ereg('^locallang_.*\.([a-z]+)\.php',$LLr['name'],$reg);

							// If no lang key extension OR if it is not found in the language records:
						if (!$reg[1] || !isset($langInfo[0][$reg[1]]))	{

								// Get the LOCALLANG array of the file
							$LLarr = unserialize($LLr['LOCAL_LANG'][1]);

								// Importing possible external arrays (eg. from 'locallang_core.dk.php' if the main file was 'locallang_core.php')
								// Requires the language key in the main file to contain NOT an array but a STRING value, 'EXT'!
							foreach($langInfo[0] as $key_rL => $temptemp)	{
								if ($key_rL!='default' && is_string($LLarr[$key_rL]) && $LLarr[$key_rL]=='EXT')		{
									$fParts = t3lib_div::revExplode('.',$LLr['name'],2);
									$fileName = $fParts[0].'.'.$key_rL.'.'.$fParts[1];
									foreach($LL as $tempLL)	{
										if ($tempLL['name']==$fileName)	{
											$tempLLarr = unserialize($tempLL['LOCAL_LANG'][1]);
											$LLarr[$key_rL] = $tempLLarr[$key_rL];
										}
									}
								}
							}

								// Clean up then (basically making sure all keys in translations are also found in the default language)
							$LLarr = $this->cleanUpLLArray($LLarr,$langInfo[0]);

								// Counting how many labels there are to translate - filtering away labels starting with "_": (in old days it was just "count()")
							if (is_array($LLarr['default']))	{
								$baseCount = 0;

								foreach($LLarr['default'] as $tempK => $tempV)	{
									if (substr($tempK,0,1)!='_')	$baseCount++;
								}
							} else {
								$baseCount = -1;
							}


							if ($baseCount>0)	{
									// For each language, traverse this file:
								reset($langInfo[0]);
								while(list($lK,$lR)=each($langInfo[0]))	{
									if (is_array($LLarr[$lR['langkey']]))	{	// There is is an entry in the LLarr for this language:

											// THIS will generate a merged translation for the record
										if ($mergeAndReturnRepository)	{
												// Merging all the chief labels:
											if (is_array($cachedTransl_chief[$lK][$LLr['name']]) && (!is_array($mergeAndReturnRepository) || in_array($lK,$mergeAndReturnRepository)))	{
												$LLarr[$lR['langkey']] = t3lib_div::array_merge($LLarr[$lR['langkey']],$cachedTransl_chief[$lK][$LLr['name']]);
											}
										} else {	// ... OTHERWISE we will have statistics returned.
												// This is the number of missing translations for the current language in the current file compared to default.
											$cur_count=t3lib_div::intInRange($baseCount-count($LLarr[$lR['langkey']]),0);

												// The missing translations is by default equal to this:
											$missing_count=$cur_count;
											$non_chief_count=0;	//.. and there are by default no non-chief translations.
											$chief_count=0;

												// FInding number of missing records to translate from the chiefs perspective:
											if (is_array($cachedTransl_chief[$lK][$LLr['name']]))	{
												$chief_count=count($cachedTransl_chief[$lK][$LLr['name']]);
												$plusChief = t3lib_div::array_merge($LLarr[$lR['langkey']],$cachedTransl_chief[$lK][$LLr['name']]);
												$missing_count=$baseCount-count($plusChief);
											}

											if (is_array($cachedTransl[$lK][$LLr['name']]))	{
													// Finding if there are labels from the full array that the chief translator does not have and if so, increasing a counter.
												$non_chief_count=0;
												reset($cachedTransl[$lK][$LLr['name']]);
												while(list($kkk,$vvv)=each($cachedTransl[$lK][$LLr['name']]))	{
													if (!isset($cachedTransl_chief[$lK][$LLr['name']][$kkk]))	{
														$non_chief_count++;
													}
												}
											}

											$translateStat['files'][$LLr['name']][$lK]=array($cur_count,$missing_count,$non_chief_count,$chief_count);
											$translateStat['lang'][$lK]['cur_count']+=$cur_count;
											$translateStat['lang'][$lK]['missing_count']+=$missing_count;
											$translateStat['lang'][$lK]['non_chief_count']+=$non_chief_count;
											$translateStat['lang'][$lK]['chief_count']+=$chief_count;
										}
									}
								}
							}

								// If we are supposed to return a merged repository record, go ahead here: This will update the locallang files in the rep. record data array!
							if ($mergeAndReturnRepository)	{
								$LLarr = $this->cleanUpLLArray($LLarr,$langInfo[0]);

									// Traverse original array to search for EXT fields ('sub-files', eg. 'locallang_core.dk.php' etc.):
								$LLarr_orig = unserialize($LLr['LOCAL_LANG'][1]);
								foreach($LLarr_orig as $key_orig => $val_orig)	{
									if (is_string($val_orig) && $val_orig=='EXT')	{
											// If found, set sub-locallang files:
										$fParts = t3lib_div::revExplode('.',$LLr['name'],2);
										$fileName = $fParts[0].'.'.$key_orig.'.'.$fParts[1];
										if (is_array($dB[$fileName]))	{
											$dB[$fileName]['content']=$this->makeLocalLangArray(array($key_orig=>$LLarr[$key_orig]),$dB[$fileName]['LOCAL_LANG'][0]);
											$dB[$fileName]['size']=strlen($dB[$fileName]['content']);
											$dB[$fileName]['codelines']=count(explode(chr(10),$dB[$fileName]['content']));
											$dB[$fileName]['LOCAL_LANG'][1]=serialize(array($key_orig=>$LLarr[$key_orig]));
											$dB[$fileName]['content_md5']=md5($dB[$fileName]['content']);
										}

											// Reset this to EXT (so that the main file will contain a proper notice that this language is found externally in a sub-file)
										$LLarr[$key_orig]='EXT';
									}
								}
									// Set main locallang file (eg. 'locallang_core.php'):
								if (isset($dB[$LLr['name']]) && is_array($dB[$LLr['name']]['LOCAL_LANG']) && is_array($LLarr))	{
									$dB[$LLr['name']]['content']=$this->makeLocalLangArray($LLarr,$LLr['LOCAL_LANG'][0]);
									$dB[$LLr['name']]['size']=strlen($dB[$LLr['name']]['content']);
									$dB[$LLr['name']]['codelines']=count(explode(chr(10),$dB[$LLr['name']]['content']));
									$dB[$LLr['name']]['LOCAL_LANG'][1]=serialize($LLarr);
									$dB[$LLr['name']]['content_md5']=md5($dB[$LLr['name']]['content']);
								}
							}
						}
					}
						// Either return the new repository record OR the statistics:
					if ($mergeAndReturnRepository)	{
						$datStr = serialize($dB);
						$mostRecent_extRepEntry['datablob_md5'] = md5($datStr);
						$mostRecent_extRepEntry['datablob'] = gzcompress($datStr);
						$mostRecent_extRepEntry['datasize_gz'] = strlen($mostRecent_extRepEntry['datablob']);
						$mostRecent_extRepEntry['datasize'] = strlen($datStr);

						return $mostRecent_extRepEntry;
					} else return $translateStat;
				} else return 'No language files';
			} else return 'Error: MD5hash didnt match.';
		} else return 'Error: most recent repository entry was not an array';
	}

	/**
	 * Creates the PHP file for a 'locallang.php' file:
	 *
	 * @param	array		The $LOCAL_LANG array to 'print'
	 * @param	string		The original header comment (above $LOCAL_LANG of the file (which should be preserved)
	 * @return	string		PHP file content for 'locallang' file.
	 */
	function makeLocalLangArray($arr,$header='')	{
		$lines=array();
		reset($arr);
		if (!$header)	{
			$lines[]='<?php';
			$lines[]=trim($this->sPS('
				/**
				 * This file is detected by the translation tool.
				 */
			'));
			$lines[]='';
		} else {
			$lines[]=trim($header);
		}
		$lines[]='';
		$lines[]='$LOCAL_LANG = Array (';
		while(list($lK,$labels)=each($arr))	{
			if (is_array($labels))	{
				$lines[]="	'".$lK."' => Array (";
				while(list($l,$v)=each($labels))	{
					if (strcmp($v,''))	{
						$lines[]="		'".$l."' => '".t3lib_div::slashJS($v,1)."',";
					}
				}
				$lines[]='	),';
			} else {	// If string, set string value: (typ "EXT")
				$lines[]="	'".$lK."' => '".t3lib_div::slashJS($labels,1)."',";
			}
		}
		$lines[]=');';
		$lines[]="?>";

		return implode(chr(10),$lines);
	}

	/**
	 * Will return the langInfo array - the list of languages and their translator users (uids)!
	 *
	 * @return	array		Returns an array with three arrays - one with all languages for translation and another with relations between these languages and sub-translators. Finally a third array with all assisting translator uids listed
	 */
	function getLanguagesAndTranslators()	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'*',
					'tx_extrepmgm_langadmin LEFT JOIN tx_extrepmgm_langadmin_sub_translators_mm ON tx_extrepmgm_langadmin.uid = tx_extrepmgm_langadmin_sub_translators_mm.uid_local',
					'tx_extrepmgm_langadmin.pid='.intval($this->dbPageId),
					'',
					'tx_extrepmgm_langadmin.crdate, tx_extrepmgm_langadmin_sub_translators_mm.sorting'
				);

		$languages = array();
		$lang_users = array();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$languages[$row['langkey']] = $row;
			$lang_users[$row['langkey'].'_'.$row['uid_foreign']] = 1;
			$languages_userList[$row['langkey']][] = $row['uid_foreign'];
		}

		return array($languages,$lang_users,$languages_userList);
	}

	/**
	 * This compiles an array with the layers of translation from the assistants and the chief translators
	 *
	 * @param	array		Array of assistant translators UIDs
	 * @param	integer		The chief UID
	 * @param	string		Extension key (used for lookup of translated content)
	 * @param	string		Language key (used for lookup of translated content)
	 * @param	string		File name (used for lookup of translated content)
	 * @return	array		The result arrays
	 */
	function getCompiledTranslations($assistants,$chief_uid,$extKey,$lK,$fileName='')	{
			// INIT output arrays:
		$chief_dat = array();
		$assist_arr = array();
		$LLarr_comp_users=array();

		if (is_array($assistants))	{
				// Add chief UID to the assisting translators and set the order by reversing the array.
			if (intval($chief_uid))	array_unshift($assistants,intval($chief_uid));
			$assistants=array_reverse($assistants);	// Total array of assistants and finally the chief.

				// Traverse assistants + chief users:
			foreach($assistants as $assist_fe_users)	{
				$assist_dat = $this->getDataContent($assist_fe_users,$extKey,$lK);
				if ($fileName)	{
					if (is_array($assist_dat[$fileName]) && count($assist_dat[$fileName]))	{
							// Array with the list of arrays being merged together:
						$assist_arr[$assist_fe_users] = $assist_dat[$fileName];
							// The composite, $LLarr_comp ($LLarr being the original or CURRENT array in the extension) ...
						$LLarr_comp_users=t3lib_div::array_merge($LLarr_comp_users,$assist_dat[$fileName]);
					}
				} else {
					if (intval($chief_uid)==$assist_fe_users)	$chief_dat=$assist_dat;
					$LLarr_comp_users=t3lib_div::array_merge_recursive_overrule($LLarr_comp_users,$assist_dat);
				}
			}
		}
		return array($LLarr_comp_users, $assist_arr, $chief_dat, $assistants);
	}

	/**
	 * Returns an LL-array in and rearranges all labels in the correct order plus removing all labels which is not in the default-key.
	 *
	 * @param	array		$LOCAL_LANG array to clean up
	 * @param	array		Languages to translate (values are the lang records)
	 * @return	array		Result.
	 */
	function cleanUpLLArray($LLarr,$trans_languages)	{
		if (is_array($LLarr['default']))	{
			$newLLarr=array();
				// Setting default labels directly.
			$newLLarr['default']=$LLarr['default'];

				// Going through all translations:
			foreach($trans_languages as $sLr)	{
				$lK=$sLr['langkey'];
				if (strcmp($lK,'default'))	{	// Anything, but the "default" is processed:
					$newLLarr[$lK]=array();		// this line sets the entry to an array (thereby overriding the "EXT" string if set. Should be OK)

					if (!is_string($LLarr[$lK]) && $LLarr[$lK]!='EXT')	{	// Only clean up what is truely an array:
						reset($LLarr['default']);
						while(list($key)=each($LLarr['default']))	{
							if (strcmp($LLarr[$lK][$key],''))	{
								$newLLarr[$lK][$key]=$LLarr[$lK][$key];
							}
						}
					}
				}
			}
			return $newLLarr;
		}
	}

	/**
	 * Get data content from language element table (tx_extrepmgm_langelements).
	 *
	 * @param	integer		The translator user uid (for lookup)
	 * @param	string		The extension key (for lookup)
	 * @param	string		The language key (for lookup)
	 * @return	array		The fetched content (or empty array, if none)
	 */
	function getDataContent($fe_user_uid,$extKey,$langkey)	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'data_content',
					'tx_extrepmgm_langelements',
					'pid='.intval($this->dbPageId).'
						AND deleted_tstamp=0
						AND fe_user='.intval($fe_user_uid).'
						AND extension_key="'.$GLOBALS['TYPO3_DB']->quoteStr($extKey, 'tx_extrepmgm_langelements').'"
						AND langkey="'.$GLOBALS['TYPO3_DB']->quoteStr($langkey, 'tx_extrepmgm_langelements').'"');

		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		$dat = unserialize($row['data_content']);

		if (!is_array($dat))	$dat = array();
		return $dat;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/extrep/pi/class.tx_extrep.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/extrep/pi/class.tx_extrep.php']);
}
?>
