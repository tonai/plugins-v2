<?php

	class DisplayManager {
	
		var $enteteHTML;
		var $pageManager;
		var $moduleManager;
		var $adminManager;
		
		var $mode;
		var $action;
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
		
		function __construct($databaseManager) {
      $this->databaseManager = $databaseManager;
    }
		
		function init( $mode = false ) {
			$this->mode = $mode;
			$this->page = ((isset($_GET['page']))? $_GET['page']: ((isset($_POST['page']))? $_POST['page']: false));
			$this->action = ((isset($_GET['action']))? $_GET['action']: ((isset($_POST['action']))? $_POST['action']: false));
			
			require_once('lib/ModuleManager.php');
			$this->moduleManager = new ModuleManager($this->databaseManager);
			
			require_once('lib/PageManager.php');
			$this->pageManager = new PageManager($this->databaseManager);
			$this->moduleManager->addModule($this->pageManager);
			
			if ( $this->mode=='admin' )
			{
				require_once('lib/AdminManager.php');
				$this->adminManager = new AdminManager($this->databaseManager);
				$this->moduleManager->addModule($this->adminManager);
			}
			
			$this->moduleManager->loadModules($this->pageManager->getModules());
			$this->pageManager->setModuleManager($this->moduleManager);
			
			if( empty($this->page) )
			{
				if ( $this->mode=='admin' && !$_SESSION['connect'] )
				{
					$this->page = 'AdminManager';
				}
				else
				{
					$this->page = $this->pageManager->getDefaultPage();
				}
			}
			
			if ( intval($this->page) )
			{
				$this->moduleManager->module = $this->pageManager->getModule($this->page);
			}
			else
			{
				$this->moduleManager->module = $this->page;
			}
			
			$this->moduleManager->setAdditionaldata($this->pageManager->getAdditionalData());
			
			if ($_SESSION['connect'])
			{
				$this->moduleManager->preProcessAdmin($this->page, $this->action);
			}
			else
			{
				$this->moduleManager->preProcessPage($this->page, $this->action);
			}
			
			require_once("include/EnteteHTML.php");
			$this->enteteHTML = new EnteteHTML();
		}
	
	
	
		function display() {
			$data = $this->pageManager->getPageData($this->page);
			
			if ( $this->mode == 'ajax' )
			{
				header('Content-Type: text/html; charset=ISO-8859-1');
				$this->moduleManager->displayPage($this->page, $this->action);
			}
			else
			{
				$this->enteteHTML->display($data['titre'], $this->moduleManager->getAllModules(), $this->moduleManager->module);
				if ($this->mode == 'admin')
				{
					echo '<body id="admin" >';
				}
				else
				{
					echo '<body>';
				}

?>
		<div id="page">
			<div id="right">
				<div id="header">
<?php

				$this->pageManager->displayMenu($this->moduleManager->modules);

?>
				</div>
			</div>
			<div id="body">
<?php

				if ($this->mode == 'admin')
				{
					echo '<div id="admin-menu" ><ul>';
					$this->adminManager->displayMenuAdmin();
					$this->pageManager->displayAdminMenu($this->moduleManager->modules);
					$this->moduleManager->displayMenuAdmin();
					echo '</ul></div>';
				}
				echo '<div id="'.strtolower($this->moduleManager->module).'" class="'.strtolower($this->sanitize($data['class'])).'" >';
				echo '<h1>'.htmlentities($data['titre']).'</h1>';
				if ($_SESSION['connect'])
				{
					$this->moduleManager->displayAdmin($this->page, $this->action);
				}
				else
				{
					$this->moduleManager->displayPage($this->page, $this->action);
				}

?>
				</div>
			</div>
		</div>
	</body>
</html>
<?php

			}
		}
		
		
		
		function sanitize($name) {
			$name = preg_replace($this->replacement, array_keys($this->replacement), htmlentities($name, ENT_NOQUOTES));
			$name = preg_replace('#[^\w-_]#', '', $name);
			$name = preg_replace('#_{2,}#', '_', $name);
			return $name;
		}
		
	}