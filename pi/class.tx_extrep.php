<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2001-2004 Kasper Skårhøj (kasper@typo3.com)
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
 * @author	Kasper Skårhøj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  103: class tx_extrep extends tslib_pibase
 *  170:     function main($content,$conf)
 *  205:     function getPIdata()
 *
 *              SECTION: Main functions for interaction with the EM in TYPO3.
 *  237:     function extensionInfo($uid)
 *  297:     function importExtension($uid)
 *  371:     function currentListing($debug=0,$internal=0,$listMode=0,$q='',$addWhere='',$addFields='')
 *  447:     function handleUpload()
 *
 *              SECTION: SQL helper functions
 *  664:     function getExtKeyRecord($extKey)
 *  683:     function getLatestRepVersion($extKeyUid)
 *  707:     function getLatestRepositoryEntry($extUid,$fieldList='*')
 *  730:     function exec_getRepList($selList,$addQ,$orderBy)
 *  751:     function getKeyTableRecord($uid)
 *  764:     function insertRepositoryVersion($newRepEntry,$verMode='new_dev')
 *  863:     function updateExtKeyCache($extKeyUid, $mostRecent_extRepEntry='')
 *  923:     function updateDownloadCounters($table,$uid,$count)
 *  934:     function isThisVersionInRep($ver,$extUid)
 *  956:     function insertLog($row,$reqMode,$error=0)
 *  983:     function getStatThisExtRep($mode,$extRepRow)
 *
 *              SECTION: Authentication helper functions
 * 1056:     function checkPrivate_key_password($key,$pass)
 * 1072:     function checkUserAccessToExtension($row,$currentUser)
 * 1094:     function isUserMemberOrOwnerOfExtension($currentUser,$extKeyRow)
 * 1110:     function validateUploadUser()
 *
 *              SECTION: Miscellaneous helper functions
 * 1157:     function renderVersion($v,$raise='')
 * 1192:     function ($input,$rev=FALSE)
 * 1238:     function userField($field)
 * 1249:     function prependFieldsWithTable($table,$fieldList)
 * 1267:     function getIconTag($serDat)
 * 1289:     function getEMCONFarrayForDownload($row)
 * 1328:     function outputSerializedData($outArr)
 * 1363:     function compileOutputData($outArr)
 * 1375:     function decodeExchangeData($str)
 * 1391:     function pError($str)
 *
 *              SECTION: TRANSLATION FUNCTIONS:
 * 1424:     function makeTranslationStatus($extKeyUid, $mostRecent_extRepEntry='', $langInfo='',$mergeAndReturnRepository=0)
 * 1613:     function makeLocalLangArray($arr,$header='')
 * 1655:     function getLanguagesAndTranslators()
 * 1685:     function getCompiledTranslations($assistants,$chief_uid,$extKey,$lK,$fileName='')
 * 1722:     function cleanUpLLArray($LLarr,$trans_languages)
 * 1756:     function getDataContent($fe_user_uid,$extKey,$langkey)
 *
 * TOTAL FUNCTIONS: 37
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

require_once(PATH_tslib.'class.tslib_pibase.php');


/**
 * TYPO3 Extension Repository, frontend plugin for delivery of extensions from repository.
 * Base class for extrep_mgm class as well (frontend management functions)
 * See other extension (extrep_mgm) for all the management functions, documentation hub, translations etc.
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
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
	 * Main function
	 *
	 * @param	string		Empty content, ignore.
	 * @param	array		TypoScript for the plugin.
	 * @return	string		HTML content.
	 */
	function main($content,$conf)	{
		$this->getPIdata();

		$this->dbPageId = intval($this->cObj->data['pages']);
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
			} else {
#				$content.='Temporarily closed down...';
/*
					$content.='<a href="'.$this->pi_getPageLink($GLOBALS['TSFE']->id,'',array(
						$this->varPrefix.'[cmd]'=>'currentListing',
						$this->varPrefix.'[debug]'=>'1'
					)).'">Get extensions listing </a>';
	*/		}
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
	 *
	 * @param	integer		Repository instance uid
	 * @return	string		Stream of data.
	 */
	function extensionInfo($uid)	{
		$currentUser = $this->validateUploadUser();

		$pKey = $this->piData['pKey'];
		$pPass = $this->piData['pPass'];

		$addQ = '';
		$addQ.= ' AND tx_extrep_repository.uid='.intval($uid);
		$addQ.= $this->checkPrivate_key_password($pKey,$pPass);
		$res = $this->exec_getRepList('tx_extrep_repository.*',$addQ,'');

		$outArr = array();
		if ($outArr = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$access = $this->checkUserAccessToExtension($this->getKeyTableRecord($outArr['extension_uid']),$currentUser);
			if ($access)	{
				unset($outArr['datablob']);

				$addQ='';
				$addQ.=' AND tx_extrep_keytable.extension_key="'.$outArr['extension_key'].'"';
				$addQ.=' AND tx_extrep_repository.emconf_private=0';
				$res = $this->exec_getRepList(
						$this->prependFieldsWithTable('tx_extrep_repository','uid,extension_key,tstamp,icondata,version,version_int,datasize,datasize_gz,emconf_description,emconf_title,emconf_category,emconf_dependencies,emconf_state,emconf_modify_tables,emconf_author,emconf_author_company,emconf_CGLcompliance,emconf_CGLcompliance_note,emconf_TYPO3_version_min,emconf_TYPO3_version_max,emconf_PHP_version_min,emconf_PHP_version_max,emconf_loadOrder,upload_typo3_version,upload_php_version,emconf_internal,emconf_uploadfolder,emconf_createDirs,emconf_private,emconf_download_password,emconf_shy,emconf_lockType'),
						$addQ,
						'tstamp DESC'
					);

				$outArr['_other_versions']=array();
				while ($outArr2 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
					$outArr['_other_versions'][]=$outArr2;
				}
#				$outArr['_MESSAGES'][]='Request successful!';
#				$outArr['_MESSAGES'][]='Access-type: '.$access;
#				$outArr['_MESSAGES'][]=$q;

				$outArr['_STAT_INFO']=$this->getStatThisExtRep('info',$outArr);
				$outArr['_STAT_IMPORT']=$this->getStatThisExtRep('import',$outArr);
				$outArr['_ICON']=$this->getIconTag($outArr['icondata']);
				$this->insertLog($outArr,'info');
			} else {
				$outArr=array();
				$outArr['_MESSAGES'][]='Extension is members-only and access was denied.';
				$this->insertLog(array('uid'=>$uid),'info',2);
			}
		} else {
			$outArr['_MESSAGES'][]='Repository Uid "'.$uid.'" was not found!';
			$this->insertLog(array('uid'=>$uid),'info',1);

		}
#$outArr['_MESSAGES'][]=$this->piData;
#$outArr['_MESSAGES'][]=$q;

		$this->outputSerializedData($outArr);
	}

	/**
	 * Returns a specific version of an extension (in compiled format) to the EM
	 *
	 * @param	integer		Repository instance uid
	 * @return	string		Stream of data.
	 */
	function importExtension($uid)	{
		$currentUser = $this->validateUploadUser();

		$pKey = $this->piData['pKey'];
		$pPass = $this->piData['pPass'];
		$transl = $this->piData['transl'];
		$incManual = $this->piData['inc_manual'];

		$addQ = '';
		$addQ.= ' AND tx_extrep_repository.uid='.intval($uid);
		$addQ.= $this->checkPrivate_key_password($pKey,$pPass);

		$res = $this->exec_getRepList(
				'tx_extrep_repository.*',
				$addQ,
				''
			);

		$outArr = array();
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$kTableRec = $this->getKeyTableRecord($row['extension_uid']);
			$access = $this->checkUserAccessToExtension($kTableRec,$currentUser);
			if ($access)	{
					// Add most recent translation?
				if ($transl)	{
					$outArr['_MESSAGES'][] = 'Most recent translation was added.';
					$row = $this->makeTranslationStatus(0,$row,'',1);	// Don't need sending of ext-keytable uid IF $row is FULL!
				}

				$outArr['extKey'] = $row['extension_key'];
				$outArr['EM_CONF'] = $this->getEMCONFarrayForDownload($row);
				$outArr['FILES'] = unserialize(gzuncompress($row['datablob']));

					// Include SXW manual?
				if ($incManual)	{
					$outArr['_MESSAGES'][] = 'Requesting manual send...';
					if (isset($outArr['FILES']['doc/manual.sxw']))	{
						$outArr['_MESSAGES'][] = 'doc/manual.sxw included.';
					} else $outArr['_MESSAGES'][] = 'No manual found.';
				} else unset($outArr['FILES']['doc/manual.sxw']);

				$outArr['_MESSAGES'][] = 'Request successfull!';
				$outArr['_MESSAGES'][] = 'Access-type: '.$access;

				$this->insertLog($row,'import');
				$this->updateDownloadCounters('tx_extrep_repository',$row['uid'],$row['download_counter']);
				$this->updateDownloadCounters('tx_extrep_keytable',$kTableRec['uid'],$kTableRec['download_counter']);
			} else {
				$outArr=array();
				$outArr['_MESSAGES'][]='Extension is members-only and access was denied.';
				$this->insertLog(array('uid'=>$uid),'import',2);
			}
		} else {
			$outArr['_MESSAGES'][]='Repository Uid "'.$uid.'" was not found!';
			$this->insertLog(array('uid'=>$uid),'import',1);
		}

#$outArr['_MESSAGES'][]=$this->piData;
#$outArr['_MESSAGES'][]=$q;

		$this->outputSerializedData($outArr);
	}

	/**
	 * Returns compiled list of extensions to the EM (or TER if $internal flag is set)
	 *
	 * @param	boolean		Tells the function whether to output debug information.
	 * @param	boolean		If set, use input $listMode variable for the mode, otherwise the input piVars[listmode]
	 * @param	integer		Mode
	 * @param	string		Alternative select query
	 * @param	string		For non-alternative query; additional where statements
	 * @param	string		For non-alternative query; additional selected fields
	 * @return	mixed		Can return a stream, an array or just output debug information. Normally it will return a stream to the EM
	 */
	function currentListing($debug=0,$internal=0,$listMode=0,$q='',$addWhere='',$addFields='')	{
		$currentUser = $this->validateUploadUser();
		$mode = $internal ? $listMode : $this->piData['listmode'];

			// Search:
		if (trim($this->piData['search']))	{
			$sW = split('[[:space:]]+',substr(trim($this->piData['search']),0,100),5);
			$searchClause = ' AND '.$GLOBALS['TYPO3_DB']->searchQuery($sW,explode(',','uid,extension_key'),'tx_extrep_keytable');
		} else {
			$searchClause = '';
		}

		$q = $q ? $q : $GLOBALS['TYPO3_DB']->SELECTquery(
							'uid,members_only,owner_fe_user,download_counter,tx_extrepmgm_flags'.$addFields,
							'tx_extrep_keytable',
							'pid='.intval($this->dbPageId).
								$addWhere.
								$searchClause.
								$GLOBALS['TSFE']->sys_page->enableFields('tx_extrep_keytable'),
							'extension_key',
							'title'
						);

		$outArr = array();
		$res = $GLOBALS['TYPO3_DB']->sql(TYPO3_db,$q);

			// Parse remote TYPO3 / PHP versions:
		$remote_TYPO3_ver = $this->versionConv($this->piData['TYPO3_ver']);
		$remote_PHP_ver = $this->versionConv($this->piData['PHP_ver']);

			// Default values set if none supplied (supplied from 3.6.0RC2 of TYPO3):
		if ($remote_TYPO3_ver<=0)	$remote_TYPO3_ver = 3005000;
		if ($remote_PHP_ver<=0)		$remote_PHP_ver = 4000006;

		$versionClause = '';
		$versionClause.= ' AND emconf_TYPO3_version_min<='.$remote_TYPO3_ver.' AND (emconf_TYPO3_version_max=0 OR emconf_TYPO3_version_max>'.$remote_TYPO3_ver.')';
		$versionClause.= ' AND emconf_PHP_version_min<='.$remote_PHP_ver.' AND (emconf_PHP_version_max=0 OR emconf_PHP_version_max>'.$remote_PHP_ver.')';
#debug($versionClause);

#echo $GLOBALS['TYPO3_DB']->sql_error();
#echo($q); exit;
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$access = $this->checkUserAccessToExtension($row,$currentUser);
			if ($access=='all' && $mode==1)	$access = '';

				// If visible only for members/owners:
			$memberOK = 1;
			if ($row['tx_extrepmgm_flags']&1)	{
				$memberOK = $this->isUserMemberOrOwnerOfExtension($currentUser,$row);
			}
			if ($access && ($memberOK||$this->byPassMemberOK))	{
				$res2 = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
							'uid,extension_key,icondata,extension_uid,tstamp,version,version_int,codelines,codebytes,datasize,datasize_gz,emconf_description,emconf_title,emconf_category,emconf_dependencies,emconf_state,emconf_modify_tables,emconf_author,emconf_author_company,emconf_CGLcompliance,emconf_CGLcompliance_note,emconf_TYPO3_version_min,emconf_TYPO3_version_max,emconf_PHP_version_min,emconf_PHP_version_max,emconf_loadOrder,upload_typo3_version,upload_php_version,emconf_internal,emconf_uploadfolder,emconf_createDirs,emconf_private,emconf_download_password,emconf_shy,emconf_module,emconf_priority,emconf_clearCacheOnLoad,emconf_lockType,download_counter,tx_extrepmgm_appr_fe_user,tx_extrepmgm_appr_status,crdate,is_manual_included,upload_comment',
							'tx_extrep_repository',
							'extension_uid='.intval($row['uid']).
								' AND pid='.intval($this->dbPageId).
								' AND emconf_private=0'.
								$versionClause.
								$GLOBALS['TSFE']->sys_page->enableFields('tx_extrep_repository'),
							'',
							'version_int DESC',
							'1'
						);



				if ($row2 = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res2))	{
					$iC = $this->getIconTag($row2['icondata']);
#					unset($row2['emconf_description']);
					unset($row2['icondata']);
					$outArr[]=array_merge($row2,array(
// The stat takes quite some time to render!
#						'_STAT_IMPORT'=>$this->getStatThisExtRep('import',$row2),
						'_STAT_IMPORT'=>array(
							'extension_thisversion'=>$row2['download_counter'],
							'extension_allversions'=>$row['download_counter'],
						),
						'_MEMBERS_ONLY'=>$row['members_only'],
						'_ACCESS'=>$access,
						'_ICON'=>$iC,
						'_EXTKEY_ROW' => $internal?$row:''
					));
				}
			}
		}

		if (!$internal)	$this->insertLog(array(),'listing');

		if ($debug)	{
			debug(strlen(serialize($outArr)));
			debug($outArr);
		} elseif ($internal) {
			return $outArr;
		} else {
			$this->outputSerializedData($outArr);
		}
	}

	/**
	 * Handles an upload of an extension
	 *
	 * @return	string		HTML error message
	 */
	function handleUpload()	{

		if (md5($this->piData['upload']['data'])==$this->piData['upload']['data_md5'])	{
			$decodedData = $this->decodeExchangeData(base64_decode($this->piData['upload']['data']));
			$uploadUser = $this->validateUploadUser();
			if (is_array($uploadUser))	{
				$extKeyRec = $this->getExtKeyRecord($decodedData['extKey']);
				if (is_array($extKeyRec))	{
					if ($extKeyRec['upload_password'])	{
						if ($this->isUserMemberOrOwnerOfExtension($uploadUser, $extKeyRec))	{
							if (!strcmp($extKeyRec['upload_password'], $this->piData['upload']['upload_p']))	{
								$dataArr = $this->renderVersion($decodedData['EM_CONF']['version']);

								$latestVersionRec = $this->getLatestRepVersion($extKeyRec['uid']);
								$newVersionBase = $latestVersionRec['version'];
								switch((string)$this->piData['upload']['mode'])	{
									case 'new_dev':
										$cmd='dev';
									break;
									case 'new_sub':
										$cmd='sub';
									break;
									case 'new_main':
										$cmd='main';
									break;
									case 'latest':
									default:
										$cmd='';
										$newVersionBase=$decodedData['EM_CONF']['version'];
									break;
								}
								$dataArr = $this->renderVersion($newVersionBase,$cmd);

									// If 'latest' was sent and this current version IS captured for review, then the version number is forcibly incremented.
								$existRec = $this->isThisVersionInRep($dataArr['version_int'],$extKeyRec['uid']);
								if ((string)$cmd=='' && is_array($existRec) && $existRec['tx_extrepmgm_appr_fe_user'])	{
									$cmd='dev';
									$dataArr = $this->renderVersion($newVersionBase,$cmd);
									$existRec = $this->isThisVersionInRep($dataArr['version_int'],$extKeyRec['uid']);
								}

								if (!is_array($existRec)  || (string)$this->piData['upload']['mode']=='latest')	{
									$dataArr['backend_title']=$decodedData['extKey'].', '.$dataArr['version'];

									$dataArr['extension_key']=$decodedData['extKey'];
									$dataArr['extension_uid']=$extKeyRec['uid'];
									$dataArr['last_upload_by_user']=$uploadUser['uid'];
									$dataArr['upload_comment']=$this->piData['upload']['comment'];
									$dataArr['last_upload_referer']=t3lib_div::getIndpEnv('HTTP_REFERER');
									$dataArr['last_upload_returnUrl']=$this->piData['upload']['returnUrl'];
									$dataArr['last_upload_date']=time();

									$dataArr['upload_typo3_version']=$this->piData['upload']['typo3ver'];
									$dataArr['upload_php_version']=$this->piData['upload']['phpver'];
									$dataArr['upload_os']=$this->piData['upload']['os'];
									$dataArr['upload_sapi']=$this->piData['upload']['sapi'];
									$dataArr['upload_ext_version']=$decodedData['EM_CONF']['version'];

									$verParts = explode('-',$decodedData['EM_CONF']['PHP_version']);
									$dataArr['emconf_PHP_version_min'] = $this->versionConv($verParts[0]);
									$dataArr['emconf_PHP_version_max'] = $this->versionConv($verParts[1]);

									$verParts = explode('-',$decodedData['EM_CONF']['TYPO3_version']);
									$dataArr['emconf_TYPO3_version_min'] = $this->versionConv($verParts[0]);
									$dataArr['emconf_TYPO3_version_max'] = $this->versionConv($verParts[1]);

									$dataArr['codelines']=$decodedData['misc']['codelines'];
									$dataArr['codebytes']=$decodedData['misc']['codebytes'];

									$dataArr['techinfo']=serialize($decodedData['techInfo']);
									$dataArr['icondata']=is_array($decodedData['FILES']['ext_icon.png']) ?
										$dataArr['icondata']=serialize($decodedData['FILES']['ext_icon.png']) :
										(is_array($decodedData['FILES']['ext_icon.gif'])?serialize($decodedData['FILES']['ext_icon.gif']):'');

									$fieldList=explode(',','title,description,category,shy,dependencies,conflicts,priority,module,state,internal,modify_tables,clearCacheOnLoad,author,author_email,author_company,lockType,uploadfolder,createDirs,CGLcompliance,CGLcompliance_note,loadOrder');
									while(list(,$fN)=each($fieldList))	{
										$dataArr['emconf_'.$fN] = trim($decodedData['EM_CONF'][$fN]);
									}

										// In these cases where $dataArr['xxxxx'] is supposed to hold the value of the incoming data, notice that THIS value is set in the loop above!
										// Thus any added fields here should probably ALSO be added in the $fieldList above!
									$dataArr['emconf_shy'] = $dataArr['emconf_shy']?1:0;
									$dataArr['emconf_clearCacheOnLoad'] = $dataArr['emconf_clearCacheOnLoad']?1:0;
									$dataArr['emconf_lockType'] = substr($dataArr['emconf_lockType'],0,1);
									$dataArr['emconf_internal'] = $dataArr['emconf_internal']?1:0;
									$dataArr['emconf_uploadfolder'] = $dataArr['emconf_uploadfolder']?1:0;
									$dataArr['emconf_private'] = $this->piData['upload']['private']?1:0;
									if ($dataArr['emconf_private'])	{
										$dataArr['emconf_download_password'] = trim($this->piData['upload']['download_password']);
										$dataArr['private_key'] = substr(md5(uniqid(rand(),1)),0,4);
									}

									$dataArr['emconf_createDirs'] = implode(',',t3lib_div::trimExplode(',',$dataArr['emconf_createDirs'],1));
									$dataArr['emconf_dependencies'] = implode(',',t3lib_div::trimExplode(',',$dataArr['emconf_dependencies'],1));
									$dataArr['emconf_modify_tables'] = implode(',',t3lib_div::trimExplode(',',$dataArr['emconf_modify_tables'],1));
									$dataArr['emconf_conflicts'] = implode(',',t3lib_div::trimExplode(',',$dataArr['emconf_conflicts'],1));
									$dataArr['emconf_module'] = $dataArr['emconf_module'];
	#debug($decodedData);
	#debug($dataArr);
									if (!t3lib_div::inList(implode(',',array_keys($this->categories)),$dataArr['emconf_category']))	$dataArr['emconf_category']='';
									if (!t3lib_div::inList(implode(',',array_keys($this->states)),$dataArr['emconf_state']))	$dataArr['emconf_state']='';
									if (!t3lib_div::inList('top,bottom',$dataArr['emconf_priority']))	$dataArr['emconf_priority']='';

									if (is_array($decodedData['FILES']))	{
										reset($decodedData['FILES']);
										$filesA=array();
										while(list($k,$d)=each($decodedData['FILES']))	{
											$d['filename']=$k;
											if ($d['filename']=='doc/manual.sxw')	{
												$dataArr['is_manual_included']=hexdec(substr(md5($d['content_md5']),0,7));
											}

											unset($d['content']);
											unset($d['LOCAL_LANG']);
											unset($d['LOCAL_LANG-conf.php']);

											$filesA[$k]=$d;
#											debug($d);
										}
										$dataArr['files'] = serialize($filesA);
										$rawDat = serialize($decodedData['FILES']);
										$dataArr['datablob_md5'] = md5($rawDat);
										$dataArr['datasize'] = strlen($rawDat);
										$dataArr['datablob'] = gzcompress($rawDat);
										$dataArr['datasize_gz'] = strlen($dataArr['datablob']);
									}

	 								$maxSize = $extKeyRec['maxStoreSize']>0 ? $extKeyRec['maxStoreSize'] :
													($uploadUser['tx_extrepmgm_maxbytes']>0 ? $uploadUser['tx_extrepmgm_maxbytes'] : $this->maxStoreSize);

	 								if ($dataArr['datasize_gz']<$maxSize)	{
										if (!is_array($existRec))	{
											$dataArr['upload_counter']=1;
											$this->cObj->DBgetInsert('tx_extrep_repository', $this->dbPageId, $dataArr, implode(',',array_keys($dataArr)), TRUE);
											$content.="The extension '".$decodedData['EM_CONF']['title']."' (".$dataArr['extension_key'].') was <strong>inserted</strong> into repository.<br /><br />
											The version number is updated to <strong>'.$dataArr['version'].'</strong><br />';
											if ($dataArr['emconf_private']) {
												$content.= '<br />
												Private import key is: <strong>'.$GLOBALS['TYPO3_DB']->sql_insert_id().($dataArr['private_key']?'-'.$dataArr['private_key']:'').'</strong><br />
												';
											}
											$recordUID = $GLOBALS['TYPO3_DB']->sql_insert_id();
										} else {
											$dataArr['upload_counter']=$existRec['upload_counter']+1;
											$this->cObj->DBgetUpdate('tx_extrep_repository', $existRec['uid'], $dataArr, implode(',',array_keys($dataArr)), TRUE);
											$content.="The extension '".$decodedData['EM_CONF']['title']."' (".$dataArr['extension_key'].'),
															version <strong>'.$dataArr['version'].'</strong> was <strong>updated</strong> in repository.<br />
											';
											if ($dataArr['emconf_private']) {
												$content.= '<br />
												Private import key is: <strong>'.$existRec['uid'].($dataArr['private_key']?'-'.$dataArr['private_key']:'').'</strong><br />
												';
											}
											$recordUID = $existRec['uid'];
										}
											// Update extension key cache:
										$this->updateExtKeyCache($extKeyRec['uid']);

											// Print info content:
										$content.= '
		<br />
											Please finish the upload process by pressing this button to return to your servers Extensions Manager and update the configuration.
		<br />
		<br />
										';

										$backEM_CONF = $this->getEMCONFarrayForDownload($dataArr);
										$content.='<form action="'.htmlspecialchars($this->piData['upload']['returnUrl']).'" method="post">
										<input type="hidden" name="TER_CMD[returnValue]" value="'.htmlspecialchars($this->compileOutputData($backEM_CONF)).'" />
										<input type="hidden" name="TER_CMD[extKey]" value="'.htmlspecialchars($dataArr['extension_key']).'" />
										<input type="hidden" name="TER_CMD[cmd]" value="EM_CONF" />
										<input type="submit" name="TER_CMD[update]" value="Syncronize EM" />
										</form>
										';
	//									$content.='<a href="'.$this->piData['upload']['returnUrl'].'">Go back to your Extension Manager</a><br />';
										return $content;
									} else return  $this->pError('The content to be stored had a compressed size of '.t3lib_div::formatSize($dataArr['datasize_gz']).' and thereby exceeded '.t3lib_div::formatSize($maxSize).' which is the limit for this extension!');
								} else return  $this->pError("This version '".$dataArr['version']."' existed in the repository already!");
							} else return  $this->pError('The upload password of the extension did not match your password!');
						} else  return  $this->pError('You are not a member or owner of this extension!');
					} else return  $this->pError('The Extension had no upload password assigned to it, so you cannot upload!');
				} else return  $this->pError('No extension by that key ('.$decodedData['extKey'].') was found!');
			} else return $this->pError($uploadUser);
		} else return $this->pError('The MD5 hash submitted did not match the MD5 hash of the data content!');
	}




















	/*********************************************************
	 *
	 * SQL helper functions
	 *
	 *********************************************************/

	/**
	 * Based on $extKey this returns the extension-key record.
	 *
	 * @param	string		Extension key
	 * @return	array		The extension key row
	 */
	function getExtKeyRecord($extKey)	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'*',
					'tx_extrep_keytable',
					'extension_key="'.$GLOBALS['TYPO3_DB']->quoteStr($extKey, 'tx_extrep_keytable').'"
						AND pid='.intval($this->dbPageId).
						$GLOBALS['TSFE']->sys_page->enableFields('tx_extrep_keytable')
				);
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			return $row;
		}
	}

	/**
	 * Returns most recent repository version record based on extKeyUid
	 *
	 * @param	integer		Extension key UID (!)
	 * @return	mixed		If found, returns the record array. Otherwise false
	 */
	function getLatestRepVersion($extKeyUid)	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'*',
					'tx_extrep_repository',
					'extension_uid='.intval($extKeyUid).'
						AND pid='.intval($this->dbPageId).
						$GLOBALS['TSFE']->sys_page->enableFields('tx_extrep_repository'),
					'',
					'version_int DESC',
					'1'
				);
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			return $row;
		}
	}

	/**
	 * Returns the most recent extension repository record for an extension-keytable uid.
	 * Private repository entries are NOT selected.
	 *
	 * @param	integer		Extension key UID (!)
	 * @param	string		Field list from tx_extrep_repository table.
	 * @return	mixed		If found, returns the record array. Otherwise false
	 */
	function getLatestRepositoryEntry($extUid,$fieldList='*')	{
		$res2 = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					$fieldList,
					'tx_extrep_repository',
					'extension_uid='.intval($extUid).
						' AND emconf_private=0'.
						' AND pid='.intval($this->dbPageId).
						$GLOBALS['TSFE']->sys_page->enableFields('tx_extrep_repository'),
					'',
					'version_int DESC',
					'1'
				);
		return $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res2);
	}

	/**
	 * Returns SELECT statement for selecting extensions/repository entries
	 *
	 * @param	string		List of fields to select
	 * @param	string		Additional WHERE clauses
	 * @param	string		The ORDER BY field for the query
	 * @return	pointer		SQL result pointer
	 */
	function exec_getRepList($selList,$addQ,$orderBy)	{
		return $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					$selList,
					'tx_extrep_repository,tx_extrep_keytable',
					'tx_extrep_keytable.uid=tx_extrep_repository.extension_uid
						AND tx_extrep_keytable.pid='.intval($this->dbPageId).'
						AND tx_extrep_repository.pid='.intval($this->dbPageId).
						$addQ.
						$GLOBALS['TSFE']->sys_page->enableFields('tx_extrep_keytable').
						$GLOBALS['TSFE']->sys_page->enableFields('tx_extrep_repository'),
					'',
					$orderBy
				);
	}

	/**
	 * Returns a record from the key table.
	 *
	 * @param	integer		Extension key table UID
	 * @return	array		Returns the record, if found
	 */
	function getKeyTableRecord($uid)	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,members_only,owner_fe_user,download_counter', 'tx_extrep_keytable', 'uid='.intval($uid).$GLOBALS['TSFE']->sys_page->enableFields('tx_extrep_keytable'));
		return $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
	}

	/**
	 * Inserts the $newRepEntry record in the extension repository. The fields in that array must be available in the repository table.
	 * Some fields are unset before this update happens.
	 *
	 * @param	array		Repository entry record.
	 * @param	string		Mode of inserting: 'new_dev', 'new_sub', 'new_main' or 'latest'/default
	 * @return	void
	 */
	function insertRepositoryVersion($newRepEntry,$verMode='new_dev')	{

			// Finding new version number
		$latestVersionRec = $this->getLatestRepVersion($newRepEntry['extension_uid']);
		$newVersionBase = $latestVersionRec['version'];
		switch($verMode)	{
			case 'new_dev':
				$cmd='dev';
			break;
			case 'new_sub':
				$cmd='sub';
			break;
			case 'new_main':
				$cmd='main';
			break;
			case 'latest':
			default:
				$cmd='';
			break;
		}
		$dataArr = $this->renderVersion($newVersionBase,$cmd);
		$newRepEntry = array_merge($newRepEntry,$dataArr);	// ... better not contain integer keys here... !

			// Setting upload user to -1 because this is made by repository.
		$newRepEntry['last_upload_by_user']=-1;
		$newRepEntry['last_upload_date']=time();

			// Make query:
		$fieldList='
			  pid,
			  extension_key ,
			  extension_uid ,
			  last_upload_by_user,
			  emconf_download_password,
			  emconf_private,
			  private_key,
			  version,
			  version_int,
			  version_main,
			  version_sub,
			  version_dev,
			  last_upload_date,
			  datablob,
			  datablob_md5,
			  datasize,
			  datasize_gz,
			  files,
			  icondata,
			  codelines,
			  codebytes,
			  upload_ext_version,
			  emconf_title,
			  emconf_description,
			  emconf_category,
			  emconf_shy,
			  emconf_dependencies,
			  emconf_createDirs,
			  emconf_conflicts,
			  emconf_priority,
			  emconf_module,
			  emconf_state,
			  emconf_internal,
			  emconf_uploadfolder,
			  emconf_modify_tables,
			  emconf_clearCacheOnLoad,
			  emconf_lockType,
			  emconf_author,
			  emconf_author_email,
			  emconf_author_company,
			  emconf_CGLcompliance,
			  emconf_CGLcompliance_note,
			  emconf_TYPO3_version_min,
			  emconf_TYPO3_version_max,
			  emconf_PHP_version_min,
			  emconf_PHP_version_max,
			  emconf_loadOrder,
			  upload_typo3_version,
			  upload_php_version,
			  upload_os,
			  upload_sapi,
			  backend_title,
			  tx_extrepmgm_appr_status,
			  tx_extrepmgm_appr_comment,
			  tx_extrepmgm_appr_fe_user,
			  is_manual_included
		';

		$this->cObj->DBgetInsert('tx_extrep_repository', $newRepEntry['pid'], $newRepEntry, $fieldList, TRUE);
	}

	/**
	 * This updates the cache-fields in the extension keytable
	 *
	 * @param	integer		Extension key's UID
	 * @param	array		(Currently ignored in function)
	 * @return	void
	 * @see handleUpload()
	 */
	function updateExtKeyCache($extKeyUid, $mostRecent_extRepEntry='')	{
		#if (!is_array($mostRecent_extRepEntry))	{
			$mostRecent_extRepEntry = $this->getLatestRepositoryEntry($extKeyUid,'download_counter,is_manual_included,tx_extrepmgm_appr_status,tx_extrepmgm_appr_comment,tx_extrepmgm_appr_fe_user,emconf_state,extension_key,datablob,datablob_md5');
#		}

		$infoArray=array();
		$updateArray=array();

		$infoArray['download_counter']=$mostRecent_extRepEntry['download_counter'];
		$infoArray['is_manual_included']=$updateArray['tx_extrepmgm_cache_oodoc']=$mostRecent_extRepEntry['is_manual_included'];
			# HERE: More info about manuals...
		$infoArray['tx_extrepmgm_appr_status'] = $updateArray['tx_extrepmgm_cache_review'] = $mostRecent_extRepEntry['tx_extrepmgm_appr_status'];
		$infoArray['tx_extrepmgm_appr_comment'] = $mostRecent_extRepEntry['tx_extrepmgm_appr_comment'];
		$infoArray['tx_extrepmgm_appr_fe_user'] = $mostRecent_extRepEntry['tx_extrepmgm_appr_fe_user'];
		$infoArray['emconf_state'] = $updateArray['tx_extrepmgm_cache_state'] = $mostRecent_extRepEntry['emconf_state'];

			// If the current version is NOT reviewed try to find the previous one.
		if (!$infoArray['tx_extrepmgm_appr_fe_user'])	{
			$res2 = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'tx_extrepmgm_appr_status,tx_extrepmgm_appr_comment,tx_extrepmgm_appr_fe_user',
						'tx_extrep_repository',
						'extension_uid='.intval($extKeyUid).
							' AND emconf_private=0'.
							' AND pid='.intval($this->dbPageId).
							' AND tx_extrepmgm_appr_fe_user>0'.
							$GLOBALS['TSFE']->sys_page->enableFields('tx_extrep_repository'),
						'',
						'version_int DESC',
						'1'
					);

			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res2))	{
				$infoArray['tx_extrepmgm_appr_status'] = $row['tx_extrepmgm_appr_status'];
				$infoArray['tx_extrepmgm_appr_comment'] = $row['tx_extrepmgm_appr_comment'];
				$infoArray['tx_extrepmgm_appr_fe_user'] = $row['tx_extrepmgm_appr_fe_user'];
				$updateArray['tx_extrepmgm_cache_review'] = -$row['tx_extrepmgm_appr_status'];
			}
		}

			// Get translation status:
		$sendCurrent_arr = !(isset($mostRecent_extRepEntry['extension_key']) && isset($mostRecent_extRepEntry['datablob']) && isset($mostRecent_extRepEntry['datablob_md5']));

		$translateStatus = $this->makeTranslationStatus($extKeyUid, $sendCurrent_arr?$mostRecent_extRepEntry:'');
		$infoArray['translation_status'] = is_array($translateStatus) ? $translateStatus['lang'] : $translateStatus;

		$updateArray['tx_extrepmgm_cache_infoarray']=serialize($infoArray);

		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_extrep_keytable', 'uid='.intval($extKeyUid), $updateArray);
#		debug($updateArray);
#		debug($infoArray);
	}

	/**
	 * Updates the download counters in key and repository tables.
	 *
	 * @param	string		table name (either the key or repository table, non else!)
	 * @param	integer		The UID of that table.
	 * @param	integer		Current count (will be inserted as is + 1)
	 * @return	void
	 */
	function updateDownloadCounters($table,$uid,$count)	{
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid='.intval($uid), array('download_counter' => (intval($count)+1)));
	}

	/**
	 * Returns the record for a specific version of a repository record based on the $extKey
	 *
	 * @param	integer		Version as integer
	 * @param	integer		The extension key's UID
	 * @return	mixed		If repository version was found, return array, otherwise void
	 */
	function isThisVersionInRep($ver,$extUid)	{
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'uid,upload_counter,tx_extrepmgm_appr_fe_user',
					'tx_extrep_repository',
					'extension_uid='.intval($extUid).'
						AND pid='.intval($this->dbPageId).'
						AND version_int='.intval($ver).' '.
						$GLOBALS['TSFE']->sys_page->enableFields('tx_extrep_repository')
				);
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			return $row;
		}
	}

	/**
	 * Inserts a stat-entry in the log
	 *
	 * @param	array		Repository record
	 * @param	string		Request mode
	 * @param	string		Error
	 * @return	void
	 */
	function insertLog($row,$reqMode,$error=0)	{
		$pU = parse_url($this->piData['returnUrl']);

		$dataArr = Array(
			'extension_uid' => $row['extension_uid'],
			'extension_rep_uid' => $row['uid'],
			'download_referer' => $this->piData['returnUrl'],
			'download_path_hash' => md5($pU['host'].$pU['path']),
			'download_host' => t3lib_div::getIndpEnv('REMOTE_HOST'),
			'download_addr' => t3lib_div::getIndpEnv('REMOTE_ADDR'),
			'download_server_t3id' => $this->piData['T3instID'],
			'tstamp' => time(),
			'requestMode' => $reqMode,
			'error' => $error
		);

		$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_extrep_downloadstat', $dataArr);
	}

	/**
	 * Returns statistic information for a certain extRepository row / extension-key.
	 * (Slow stuff)
	 *
	 * @param	string		Request mode to filter.
	 * @param	array		Extension repository record
	 * @return	array		Array with statistic numbers
	 */
	function getStatThisExtRep($mode,$extRepRow)	{
		$stat = array();
			// extension rep

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'count(*)',
					'tx_extrep_downloadstat',
					'requestMode="'.$GLOBALS['TYPO3_DB']->quoteStr($mode, 'tx_extrep_downloadstat').'"
						AND extension_uid='.intval($extRepRow['extension_uid'])
				);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
		$stat['extension_allversions'] = $row[0];

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'count(*)',
					'tx_extrep_downloadstat',
					'requestMode="'.$GLOBALS['TYPO3_DB']->quoteStr($mode, 'tx_extrep_downloadstat').'"
						AND extension_uid='.intval($extRepRow['extension_uid']),
					'download_path_hash'
				);
		$stat['extension_allversions_group_path_hash'] = $GLOBALS['TYPO3_DB']->sql_num_rows($res);


		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'count(*)',
					'tx_extrep_downloadstat',
					'requestMode="'.$GLOBALS['TYPO3_DB']->quoteStr($mode, 'tx_extrep_downloadstat').'"
						AND extension_rep_uid='.intval($extRepRow['uid'])
				);
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res);
		$stat['extension_thisversion'] = $row[0];

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'count(*)',
					'tx_extrep_downloadstat',
					'requestMode="'.$GLOBALS['TYPO3_DB']->quoteStr($mode, 'tx_extrep_downloadstat').'"
						AND extension_rep_uid='.intval($extRepRow['uid']),
					'download_path_hash'
				);
		$stat['extension_thisversion_group_path_hash'] = $GLOBALS['TYPO3_DB']->sql_num_rows($res);

		return $stat;
	}

















	/*********************************************************
	 *
	 * Authentication helper functions
	 *
	 *********************************************************/

	/**
	 * Returns a part of WHERE query selecting only non-private extensions or private extensions with the correct password set.
	 *
	 * @param	string		Private key string
	 * @param	string		Download password to check for.
	 * @return	string		Part of query
	 */
	function checkPrivate_key_password($key,$pass)	{
		return " AND
		( tx_extrep_repository.emconf_private=0 OR
			(tx_extrep_repository.private_key='".$GLOBALS['TYPO3_DB']->quoteStr($key, 'tx_extrep_repository')."' AND
				(tx_extrep_repository.emconf_download_password='' OR tx_extrep_repository.emconf_download_password='".$GLOBALS['TYPO3_DB']->quoteStr($pass, 'tx_extrep_repository')."')
			)
		)";
	}

	/**
	 * Input is a extension-key record and the currentUser record. Access type is returned as a string 'all', 'owner', 'member' or '' (if no access)
	 *
	 * @param	array		Extension key record.
	 * @param	array		fe_users record of current user
	 * @return	string		Either 'selected', 'all', 'owner' or 'member' (or false if some failure happend)
	 */
	function checkUserAccessToExtension($row,$currentUser)	{
		if (!$row['members_only'])	{
			return in_array($row['uid'],$this->ext_feUserSelection) ? 'selected' : 'all';
		} elseif(is_array($currentUser) && $currentUser['uid']) {
			if (!strcmp($row['owner_fe_user'],$currentUser['uid']))	{
				return 'owner';
			} elseif (intval($currentUser['uid']) && intval($row['uid'])) {
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid_local', 'tx_extrep_groupmem_mm', 'uid_local='.intval($row['uid']).' AND uid_foreign='.intval($currentUser['uid']));
				if ($GLOBALS['TYPO3_DB']->sql_num_rows($res))	{
					return 'member';
				}
			}
		}
	}

	/**
	 * Validates if a user-record is member or owner of an extension:
	 *
	 * @param	array		fe_users record of current user
	 * @param	array		Extension key record.
	 * @return	string		Result; Either 'owner' or member' or false if none of them is true.
	 */
	function isUserMemberOrOwnerOfExtension($currentUser,$extKeyRow)	{
		if ($currentUser['uid'] && !strcmp($currentUser['uid'],$extKeyRow['owner_fe_user']))	{
			return 'owner';
		} else {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid_local', 'tx_extrep_groupmem_mm', 'uid_local='.intval($extKeyRow['uid']).' AND uid_foreign='.intval($currentUser['uid']));
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res))	{
				return 'member';
			}
		}
	}

	/**
	 * This verifies the fe_users username/password that may possibly be sent with the request to the repository. Either the fe_user row is returned or a text-message describing the authentication error.
	 *
	 * @return	mixed		If success, returns array of fe_users, otherwise error string.
	 */
	function validateUploadUser()	{
		if (trim($this->piData['user']['fe_u']) && trim($this->piData['user']['fe_p']))		{
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'fe_users', 'username="'.$GLOBALS['TYPO3_DB']->quoteStr(trim($this->piData['user']['fe_u']), 'fe_users').'"'.$GLOBALS['TSFE']->sys_page->enableFields('fe_users'));
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
				if (!strcmp($row['password'],trim($this->piData['user']['fe_p'])))	{
					$feUserData = unserialize($row['tx_extrepmgm_selext']);
					$this->ext_feUserSelection = is_array($feUserData['extSelection']) ? array_keys($feUserData['extSelection']) : array();
					return $row;
				} else return "Password was incorrect for user '".$this->piData['user']['fe_u']."'";
			} else return "User '".$this->piData['user']['fe_u']."' not found";
		} else return 'No user or no password submitted';
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
	 * @param	mixed		Input string, then a version number like "3.6.0" or "3.4.5rc2"/"3.4.5rc2-dev" ("-dev" is always stripped off blindly). If double it will be converted back to string. Version numbers after suffix is not supported higher than "9".
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
			$input = strtolower($input);
				// First, remove "-dev" suffix if there:
			if (substr($input,-4)=='-dev')	{
				$input = substr($input,0,-4);
			}
				// Get integer from main version numbers
			$result = t3lib_div::int_from_ver($input);
			if (ereg('(dev|a|b|rc)([0-9]*)$',$input,$reg))	{
				$dec = intval($subDecIndex[$reg[1]]).$reg[2];
				$result = (double)(($result-1).'.'.$dec);
			}
		}

		return $result;
	}

	/**
	 * Returns a field from the fe_user record.
	 *
	 * @param	string		Field name from the fe_users table.
	 * @return	string		Field value.
	 */
	function userField($field)		{
		return $GLOBALS['TSFE']->fe_user->user[$field];
	}

	/**
	 * Having a comma list of fields ($fieldList) this is prepended with the $table."." name
	 *
	 * @param	string		The table name
	 * @param	string		The comma list of fields
	 * @return	string		The result string
	 */
	function prependFieldsWithTable($table,$fieldList)	{
		$list=t3lib_div::trimExplode(',',$fieldList,1);
		$return=array();
		while(list(,$listItem)=each($list))	{
			$return[]=$table.'.'.$listItem;
		}
		return implode(',',$return);
	}

	/**
	 * Returns the image tag for an icon of an extension. Input is a serialized array with information about the icon file.
	 * Writes the icon to the temp-folder on the server.
	 * THIS MEANS that icons displayed in the backend EM is actually shown from the remove server (this server)
	 * This is also a potential security whole if we do not check the extension to be an image extension - could be forged to be a php script written to the server!!!
	 *
	 * @param	array		Serialized icon data.
	 * @return	mixed		Returns the icon image tag, if any
	 */
	function getIconTag($serDat)	{
		$iDat = unserialize($serDat);
		if ($iDat)	{
			$fI=pathinfo($iDat['name']);
			if (t3lib_div::inList('gif,png,jpeg,jpg',strtolower($fI['extension'])))	{
				$tempFileName=PATH_site.'typo3temp/tx_extrep_'.t3lib_div::shortMd5($serDat).'.'.$fI['extension'];
				if (!@is_file($tempFileName))	{
					t3lib_div::writeFile($tempFileName,$iDat['content']);
				}
				$imgInfo=getimagesize($tempFileName);
				$tag ='<img src="'.t3lib_div::getIndpEnv('TYPO3_REQUEST_DIR').substr($tempFileName,strlen(PATH_site)).'" '.$imgInfo[3].' alt="" />';
				return $tag;
			}
		}
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
				'TYPO3_version' => $this->versionConv($row['emconf_TYPO3_version_min'],TRUE).'-'.$this->versionConv($row['emconf_TYPO3_version_max'],TRUE),
				'PHP_version' => $this->versionConv($row['emconf_PHP_version_min'],TRUE).'-'.$this->versionConv($row['emconf_PHP_version_max'],TRUE),
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
				'private' => $row['emconf_private'],
				'download_password' => $row['emconf_download_password'],
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
		if (!t3lib_extmgm::isLoaded('extrep_mgm'))	return 'Error: No extrep_mgm extension loaded.';

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
				 * '.$description.'
				 *
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
		$lines[]='?>';

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
