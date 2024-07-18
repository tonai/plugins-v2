<?php

	session_start();
    session_regenerate_id();
    session_name(md5('B.R.C-E4ever'));
	
	if (!isset($_SESSION['connect']))
		$_SESSION['connect']=0;
	if (!isset($_SESSION['admin']))
		$_SESSION['admin']=0;

	require_once("lib/DatabaseManager.php");
	require_once("lib/DisplayManager.php");

	$databaseManager = new DatabaseManager();
	
	$databaseManager->connexion();
	
	$displayManager = new DisplayManager();
	$displayManager->init('admin');
	$displayManager->display();

	$databaseManager->deconnexion();