<?php

	class AbstractModule {
	
		var $coreSufix = 'Manager';
		var $name = '';
		var $replacement = array(
			'A' => '/&Agrave;|&Aacute;|&Acirc;|&Atilde;|&Auml;|&Aring;/',
			'a' => '/&agrave;|&aacute;|&acirc;|&atilde;|&auml;|&aring;/',
			'C' => '/&Ccedil;/',
			'c' => '/&ccedil;/',
			'E' => '/&Egrave;|&Eacute;|&Ecirc;|&Euml;/',
			'e' => '/&egrave;|&eacute;|&ecirc;|&euml;/',
			'I' => '/&Igrave;|&Iacute;|&Icirc;|&Iuml;/',
			'i' => '/&igrave;|&iacute;|&icirc;|&iuml;/',
			'N' => '/&Ntilde;/',
			'n' => '/&ntilde;/',
			'O' => '/&Ograve;|&Oacute;|&Ocirc;|&Otilde;|&Ouml;/',
			'o' => '/&ograve;|&oacute;|&ocirc;|&otilde;|&ouml;/',
			'U' => '/&Ugrave;|&Uacute;|&Ucirc;|&Uuml;/',
			'u' => '/&ugrave;|&uacute;|&ucirc;|&uuml;/',
			'Y' => '/&Yacute;/',
			'y' => '/&yacute;|&yuml;/',
			'_' => '/\s/'
		);
		
		
		
		function install($data) {
		}
		
		
		
		function uninstall() {
		}
		
		
		
		function setAdditionalData($data) {
			if(!empty($data))
			{
				foreach($data as $key => $value)
				{
					$this->$key = $value;
				}
			}
		}
		
		
		
		function getAdditionalData() {
			return '';
		}
		
		
		
		function preProcessPage($page, $action) {
		}
		
		
		
		function preProcessAdmin($page, $action) {
		}
		
		
		
		function displayPage($page, $action) {
		}
		
		
		
		function displayAdmin($page, $action) {
		}
		
		
		
		function displayMenu($modules, $data) {
			return false;
		}
		
		
		
		function displayMenuAdmin($page, $menu) {
		}
		
		
		
		function getName() {
			if (empty($this->name))
			{
				$this->name = ucfirst(get_class($this));
				if ( substr($this->name, -strlen($this->coreSufix)) == strtolower($this->coreSufix) )
				{
					$this->name = substr($this->name, 0, -strlen($this->coreSufix)).$this->coreSufix;
				}
			}
			return $this->name;
	    }
		
		
		
		function sanitize($name) {
			$name = preg_replace($this->replacement, array_keys($this->replacement), htmlentities($name, ENT_NOQUOTES));
			$name = preg_replace('#[^\w-_]#', '', $name);
			$name = preg_replace('#_{2,}#', '_', $name);
			return $name;
		}
		
	}