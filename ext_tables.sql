# TYPO3 Extension Manager dump 1.0
#
# Host: TYPO3_host    Database: t3_ter
#--------------------------------------------------------


#
# Table structure for table 'tx_extrep_repository'
#
CREATE TABLE tx_extrep_repository (
  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  pid int(11) DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  tstamp int(11) DEFAULT '0' NOT NULL,
  extension_key varchar(30) DEFAULT '' NOT NULL,
  extension_uid int(11) DEFAULT '0' NOT NULL,
  upload_comment text NOT NULL,
  upload_counter int(11) DEFAULT '0' NOT NULL,
  last_upload_referer tinytext NOT NULL,
  last_upload_by_user int(11) DEFAULT '0' NOT NULL,
  last_upload_returnUrl tinytext NOT NULL,
  last_upload_date int(11) DEFAULT '0' NOT NULL,
  emconf_download_password varchar(30) DEFAULT '' NOT NULL,
  emconf_private tinyint(4) DEFAULT '0' NOT NULL,
  private_key varchar(4) DEFAULT '' NOT NULL,
  version varchar(11) DEFAULT '' NOT NULL,
  version_int int(11) DEFAULT '0' NOT NULL,
  version_main int(11) DEFAULT '0' NOT NULL,
  version_sub int(11) DEFAULT '0' NOT NULL,
  version_dev int(11) DEFAULT '0' NOT NULL,
  datablob mediumblob NOT NULL,
  datablob_md5 varchar(32) DEFAULT '' NOT NULL,
  datasize int(11) DEFAULT '0' NOT NULL,
  datasize_gz int(11) DEFAULT '0' NOT NULL,
  files text NOT NULL,
  icondata blob NOT NULL,
  codelines int(11) DEFAULT '0' NOT NULL,
  codebytes int(11) DEFAULT '0' NOT NULL,
  upload_ext_version varchar(11) DEFAULT '' NOT NULL,
  techinfo blob NOT NULL,
  emconf_title tinytext NOT NULL,
  emconf_description text NOT NULL,
  emconf_category varchar(10) DEFAULT '' NOT NULL,
  emconf_shy tinyint(4) DEFAULT '0' NOT NULL,
  emconf_dependencies text NOT NULL,
  emconf_createDirs text NOT NULL,
  emconf_conflicts text NOT NULL,
  emconf_priority varchar(10) DEFAULT '' NOT NULL,
  emconf_module tinytext NOT NULL,
  emconf_state varchar(15) DEFAULT '' NOT NULL,
  emconf_internal tinyint(4) DEFAULT '0' NOT NULL,
  emconf_uploadfolder tinyint(4) DEFAULT '0' NOT NULL,
  emconf_modify_tables tinytext NOT NULL,
  emconf_clearCacheOnLoad tinyint(4) DEFAULT '0' NOT NULL,
  emconf_lockType char(1) DEFAULT '' NOT NULL,
  emconf_author tinytext NOT NULL,
  emconf_author_email tinytext NOT NULL,
  emconf_author_company tinytext NOT NULL,

  emconf_CGLcompliance varchar(10) DEFAULT '' NOT NULL,
  emconf_CGLcompliance_note tinytext NOT NULL,
  emconf_TYPO3_version_min double(9,2) DEFAULT '0.00' NOT NULL,
  emconf_TYPO3_version_max double(9,2) DEFAULT '0.00' NOT NULL,
  emconf_PHP_version_min double(9,2) DEFAULT '0.00' NOT NULL,
  emconf_PHP_version_max double(9,2) DEFAULT '0.00' NOT NULL,
  emconf_loadOrder tinytext NOT NULL,

  upload_typo3_version varchar(10) DEFAULT '' NOT NULL,
  upload_php_version varchar(10) DEFAULT '' NOT NULL,
  upload_os char(3) DEFAULT '' NOT NULL,
  upload_sapi varchar(10) DEFAULT '' NOT NULL,
  backend_title tinytext NOT NULL,
  tx_extrepmgm_appr_status tinyint(3) DEFAULT '0' NOT NULL,
  tx_extrepmgm_appr_comment text NOT NULL,
  tx_extrepmgm_appr_fe_user int(11) unsigned DEFAULT '0' NOT NULL,
  download_counter int(11) DEFAULT '0' NOT NULL,
  is_manual_included int(11) DEFAULT '0' NOT NULL,
  
  PRIMARY KEY (uid),
  KEY extkey (extension_key),
  KEY extcat (emconf_category),
  KEY extension_uid (extension_uid)
  KEY extension_uid (extension_uid,crdate,emconf_private,download_counter)
);


#
# Table structure for table 'tx_extrep_downloadstat'
#
CREATE TABLE tx_extrep_downloadstat (
  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  extension_uid int(11) DEFAULT '0' NOT NULL,
  extension_rep_uid int(11) DEFAULT '0' NOT NULL,
  download_referer tinytext NOT NULL,
  download_path_hash varchar(32) DEFAULT '' NOT NULL,
  download_server_t3id varchar(32) DEFAULT '' NOT NULL,
  download_host text NOT NULL,
  download_addr varchar(15) DEFAULT '' NOT NULL,
  tstamp int(11) DEFAULT '0' NOT NULL,
  requestMode varchar(10) DEFAULT '' NOT NULL,
  error tinyint(4) DEFAULT '0' NOT NULL,
  PRIMARY KEY (uid),
  KEY requestMode (requestMode,extension_uid),
  KEY requestMode_2 (requestMode,extension_rep_uid)
);

#
# Table structure for table 'tx_extrep_groupmem_mm'
#
CREATE TABLE tx_extrep_groupmem_mm (
  uid_local int(11) unsigned DEFAULT '0' NOT NULL,
  uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
  tablenames varchar(30) DEFAULT '' NOT NULL,
  sorting int(11) unsigned DEFAULT '0' NOT NULL,
  KEY uid_local (uid_local),
  KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'tx_extrep_keytable'
#
CREATE TABLE tx_extrep_keytable (
  uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
  pid int(11) unsigned DEFAULT '0' NOT NULL,
  tstamp int(11) unsigned DEFAULT '0' NOT NULL,
  crdate int(11) unsigned DEFAULT '0' NOT NULL,
  deleted tinyint(3) unsigned DEFAULT '0' NOT NULL,
  hidden tinyint(4) unsigned DEFAULT '0' NOT NULL,
  title tinytext NOT NULL,
  description text NOT NULL,
  extension_key varchar(30) DEFAULT '' NOT NULL,
  extension_key_modules varchar(30) DEFAULT '' NOT NULL,
  owner_fe_user int(11) DEFAULT '0' NOT NULL,
  upload_password varchar(30) DEFAULT '' NOT NULL,
  maxStoreSize int(11) DEFAULT '0' NOT NULL,
  groupmem int(11) DEFAULT '0' NOT NULL,
  members_only tinyint(4) DEFAULT '0' NOT NULL,
  download_counter int(11) DEFAULT '0' NOT NULL,
  tx_extrepmgm_flags int(11) unsigned DEFAULT '0' NOT NULL,

  PRIMARY KEY (uid),
  KEY parent (pid),
  KEY extkey (extension_key)
);

