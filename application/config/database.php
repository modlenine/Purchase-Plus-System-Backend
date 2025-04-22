<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$active_group = 'default';
$query_builder = TRUE;

if($_SERVER['HTTP_HOST'] == "localhost"){
	$mysqlServer = "192.168.20.22";
	// $mssql = "192.168.10.60";
	// $mssqlDB_sln = "SLC_STD_TEST";
	// $mssqlDB_tbb = "APPLY_STD_TEST";

	$mssql = "192.168.10.54";
	$mssqlDB_sln = "SLC_STD";
	$mssqlDB_tbb = "APPLY_STD";
}else{
	$mysqlServer = "localhost";
	$mssql = "192.168.10.54";
	$mssqlDB_sln = "SLC_STD";
	$mssqlDB_tbb = "APPLY_STD";
}

$db['default'] = array(
	'dsn'	=> '',
	'hostname' => $mysqlServer,
	'username' => 'ant',
	'password' => 'Ant1234',
	'database' => 'purchaseplus',
	'dbdriver' => 'mysqli',
	'dbprefix' => '',
	'pconnect' => FALSE,
	'db_debug' => (ENVIRONMENT !== 'production'),
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8',
	'dbcollat' => 'utf8_general_ci',
	'swap_pre' => '',
	'encrypt' => FALSE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => TRUE
);

$db['compare_vendor'] = array(
	'dsn'	=> '',
	'hostname' => $mysqlServer,
	'username' => 'ant',
	'password' => 'Ant1234',
	'database' => 'compare_vendor',
	'dbdriver' => 'mysqli',
	'dbprefix' => '',
	'pconnect' => FALSE,
	'db_debug' => (ENVIRONMENT !== 'production'),
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8',
	'dbcollat' => 'utf8_general_ci',
	'swap_pre' => '',
	'encrypt' => FALSE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => TRUE
);

$db['saleecolour'] = array(
	'dsn'	=> '',
	'hostname' => $mysqlServer,
	'username' => 'ant',
	'password' => 'Ant1234',
	'database' => 'saleecolour',
	'dbdriver' => 'mysqli',
	'dbprefix' => '',
	'pconnect' => FALSE,
	'db_debug' => (ENVIRONMENT !== 'production'),
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8',
	'dbcollat' => 'utf8_general_ci',
	'swap_pre' => '',
	'encrypt' => FALSE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => TRUE
);


$db['mssql'] = array(
	'dsn' => '',
	// 'hostname' => '192.168.10.54',
	'hostname' => $mssql,
	'username' => 'dataconnector',
	'password' => 'Admin1234',
	// 'database' => 'SLC_STD',
	'database' => $mssqlDB_sln,
	'dbdriver' => 'sqlsrv',
	'dbprefix' => '',
	'pconnect' => FALSE,
	'db_debug' => TRUE,
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8',
	'dbcollat' => 'utf8_general_ci',
	'swap_pre' => '',
	'encrypt' => FALSE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => TRUE
   );


   $db['mssql2'] = array(
	'dsn' => '',
	// 'hostname' => '192.168.10.54',
	'hostname' => $mssql,
	'username' => 'dataconnector',
	'password' => 'Admin1234',
	// 'database' => 'APPLY_STD',
	'database' => $mssqlDB_tbb,
	'dbdriver' => 'sqlsrv',
	'dbprefix' => '',
	'pconnect' => FALSE,
	'db_debug' => TRUE,
	'cache_on' => FALSE,
	'cachedir' => '',
	'char_set' => 'utf8',
	'dbcollat' => 'utf8_general_ci',
	'swap_pre' => '',
	'encrypt' => FALSE,
	'compress' => FALSE,
	'stricton' => FALSE,
	'failover' => array(),
	'save_queries' => TRUE
   );
