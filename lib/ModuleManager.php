<?php

	class ModuleManager {
		
		var $database = 'modules';
		var $coreSufix = 'Manager';
		var $modules = array();
		var $module;
		
		var $install;
		var $uninstall;
		


		function __construct($databaseManager) {
      $this->databaseManager = $databaseManager;
			require_once('lib/AbstractModule.php');
    }
		

		
		function firstInstall() {
			$query = 'CREATE TABLE IF NOT EXISTS `'.$this->database.'` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`module` varchar(50) NOT NULL,
					`page` tinyint(4) NOT NULL,
					PRIMARY KEY (`id`)
				) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=4 ;';
			mysqli_query($this->databaseManager->mysqli, $query) OR DIE (mysqli_error());
			
			$query = 'INSERT INTO `'.$this->database.'` (`id`, `module`, `page`) VALUES
				(1, "AdminManager", 0),
				(2, "PageManager", 0),
				(3, "ContentManager", 1);';
			mysqli_query($this->databaseManager->mysqli, $query) OR DIE (mysqli_error());
		}
		
		
		
		function addModule ($module) {
			$this->modules[$module->getName()] = $module;
	    }
		
		
		
		function getModuleId ($module) {
			$buff = mysqli_query($this->databaseManager->mysqli, 'SELECT id FROM '.$this->database.' WHERE module="'.$module.'"');
			$data = mysqli_fetch_array($buff);
			return $data['id'];
		}
		
		
		
		function getAllModules () {
			$modules = array();
			$buff = mysqli_query($this->databaseManager->mysqli, 'SELECT id, module FROM '.$this->database.' WHERE page=1');
			while ($data = mysqli_fetch_array($buff))
			{
				$modules[$data['id']] = $data['module'];
			}
			return $modules;
	    }
		
		
		
		function loadModules ($pages) {
			$modules = $this->getAllModules();
			foreach ($pages as $pageId => $moduleId)
			{
				$moduleName = $modules[$moduleId];
				if (substr($moduleName, -strlen($this->coreSufix)) == $this->coreSufix)
				{
					include_once('lib/'.$moduleName.'.php');
				}
				else
				{
					include_once('modules/'.$moduleName.'.php');
				}
				$this->modules[$pageId] = new $moduleName($this->databaseManager);
			}
	    }
		
		
		
		function loadAllModules () {
			$modules = $this->getAllModules();
			foreach ($modules as $moduleName)
			{
				if (substr($moduleName, -strlen($this->coreSufix)) == $this->coreSufix)
				{
					include_once('lib/'.$moduleName.'.php');
				}
				else
				{
					include_once('modules/'.$moduleName.'.php');
				}
			}
	    }
		
		
		
		function install($moduleId, $installData) {
			$buff = mysqli_query($this->databaseManager->mysqli, 'SELECT module FROM '.$this->database.' WHERE id='.$moduleId);
			$data = mysqli_fetch_array($buff);
			$module = new $data['module']();
			$module->install($installData);
		}
		
		
		
		function setAdditionalData($data) {
			foreach ($data as $pageId => $additionaldata)
			{
				$this->modules[$pageId]->setAdditionalData($additionaldata);
			}
		}
		
		
		
		function getAdditionalData() {
			$modules = $this->getAllModules();
			$additionalData = array();
			foreach ($modules as $moduleId => $moduleName)
			{
				$module = new $moduleName();
				$additionalData[$moduleId] = $module->getAdditionalData();
			}
			return $additionalData;
		}
		
		
	    
	    function preProcessPage ($page, $action) {
			$this->modules[$page]->preProcessPage($page, $action);
	    }
		
		
		
		function preProcessAdmin ($page, $action) {
			if ($page==$this->getName())
			{
				switch ($action)
				{
					case 'install':
						if (isset($_POST['submit']))
						{
							$buff = mysqli_query($this->databaseManager->mysqli, 'SELECT module FROM '.$this->database.' WHERE page=1');
							$modules = array();
							while($data = mysqli_fetch_array($buff)) {
								$installedModules[$data['module']]=false;
							}
							
							$path='modules';
							$dossier=opendir($path);
							while ($file=readdir($dossier))
							{
								if ($file!='.' && $file!='..')
								{
									$nomFichier = substr($file, 0, strrpos($file, '.'));
									if (isset($installedModules[$nomFichier]))
									{
										$installedModules[$nomFichier]=true;
									}
								}
							}
							closedir($dossier);
							
							if (isset($_POST['delete']))
							{
								foreach ($_POST['delete'] as $module => $checked)
								{
									$installed=false;
									foreach($installedModules as $installedModule => $uninstall)
									{
										if ($installedModule==$module)
										{
											$installed=true;
											$installedModules[$module]=false;
										}
									}
									
									if ($installed==false)
									{
										$query = 'INSERT INTO `'.$this->database.'` (`module`, `page`) VALUES ("'.$module.'", 1);';
										mysqli_query($this->databaseManager->mysqli, $query) OR DIE (mysqli_error());
										$this->install = 'Installation(s) effectu�e(s).';
									}
								}
							}
							
							foreach($installedModules as $installedModule => $uninstall)
							{
								if ($uninstall)
								{
									$query = 'DELETE FROM `'.$this->database.'` WHERE module = "'.$installedModule.'"';
									mysqli_query($this->databaseManager->mysqli, $query) OR DIE (mysqli_error());
									$this->uninstall = 'D�sinstallation(s) effectu�e(s).';
								}
							}
						}
						break;
				}
			}
			else
			{
				$this->modules[$page]->preProcessAdmin($page, $action);
			}
	    }
		
		
		
		function displayPage ($page, $action)
		{
			if ($page==$this->getName())
			{
				switch ($action)
				{
				}
			}
			else
			{
				$this->modules[$page]->displayPage($page, $action);
			}
		}
		
		
		
		function displayAdmin ($page, $action) {
			if ($page==$this->getName())
			{
				switch ($action)
				{
					case 'install':

?>
			<form action="?page=<?php echo $this->getName(); ?>&action=install" method="post">
				<fieldset>
					<legend>Installer / D�sinstaller un module</legend>
<?php

						$buff = mysqli_query($this->databaseManager->mysqli, 'SELECT module FROM '.$this->database);
						$modules = array();
						while($data = mysqli_fetch_array($buff)) {
							$installedModules[] = $data['module'];
						}
						
						$path='modules';
						$dossier=opendir($path);
						while ($file=readdir($dossier))
						{
							if ($file!='.' && $file!='..')
							{
								$nomFichier = substr($file, 0, strrpos($file, '.'));
								$checked = '';
								foreach($installedModules as $installedModule) {
									if ($installedModule==$nomFichier)
									{
										$checked = 'checked="checked"';
									}
								}
								echo '<p>';
								echo '<input type="checkbox" name="delete['.$nomFichier.']" '.$checked.' id="module'.$nomFichier.'" /> ';
								echo '<label for="module'.$nomFichier.'" >'.$nomFichier.'</label>';
								echo '</p>';						}
						}
						closedir($dossier);

?>
					<p>
						<input type="submit" name="submit" />
						<span class="error" ><?php echo $this->install; ?> <?php echo $this->uninstall; ?></span>
					</p>
				</fieldset>
			</form>
<?php
					
						break;
				}
			}
			else
			{
				$this->modules[$page]->displayAdmin($page, $action);
			}
	    }
		
		
		
		function displayMenuAdmin() {
			if ($_SESSION['connect'])
			{

?>
			<li><a href="?page=<?php echo $this->getName(); ?>&action=install" title="Installer / D�sinstaller un module" >Installer / D�sinstaller un module</a></li>
<?php

			}
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
		
	}