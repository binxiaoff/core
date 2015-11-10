<?php
session_start();
ini_set('session.gc_maxlifetime',3600); // 1h la session

error_reporting(E_ERROR | E_WARNING);
include('../../core/dispatcher.class.php');
include('../../core/controller.class.php');
include('../../core/command.class.php');
include('../../core/bdd.class.php');
include('../../core/errorhandler.class.php');
include('../../config.php');

$app = 'admin';

if(file_exists('../../config.'.$app.'.php'))
	include('../../config.'.$app.'.php');

$handler = new ErrorHandler($config['error_handler'][$config['env']]['file'],$config['error_handler'][$config['env']]['allow_display'],$config['error_handler'][$config['env']]['allow_log'],$config['error_handler'][$config['env']]['report']);

$dispatcher = new Dispatcher($config,$app);