<?php
// This file generated by Propel  convert-conf target
// from XML runtime conf file C:\opt\kaltura\app\alpha\config\runtime-conf.xml
return array (
  'datasources' => 
  array (
    'kaltura' => 
    array (
      'adapter' => 'mysql',
      'connection' => 
      array (
        'phptype' => 'mysql',
        'database' => 'kaltura',
        'hostspec' => 'localhost',
        'username' => 'root',
        'password' => 'root',
      ),
    ),
    'default' => 'kaltura',
  ),
  'log' => 
  array (
    'ident' => 'kaltura',
    'level' => '7',
  ),
  'generator_version' => '1.4.2',
  'classmap' => 
  array (
    'SolrLogTableMap' => 'lib/model/map/SolrLogTableMap.php',
    'SolrLogPeer' => 'lib/model/SolrLogPeer.php',
    'SolrLog' => 'lib/model/SolrLog.php',
    'SolrLogServerTableMap' => 'lib/model/map/SolrLogServerTableMap.php',
    'SolrLogServerPeer' => 'lib/model/SolrLogServerPeer.php',
    'SolrLogServer' => 'lib/model/SolrLogServer.php',
  ),
);