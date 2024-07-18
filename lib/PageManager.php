<?php
  require_once("lib/AbstractModule.php");

	class PageManager extends AbstractModule {
		
		var $database = 'page_manager';
		var $moduleManager;
		var $additionalData;
		
		var $delete;
		var $update;
		var $dataError;
		var $titleError;
		var $menuError;
		var $error;



    function __construct($databaseManager) {
      $this->databaseManager = $databaseManager;
    }
		
		
		
		function firstInstall() {
			$query = 'CREATE TABLE IF NOT EXISTS `'.$this->database.'` (
					`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
					`pid` int(11) NOT NULL,
					`module` int(11) NOT NULL,
					`titre` varchar(50) NOT NULL,
					`menu` varchar(50) NOT NULL,
					`data` text NOT NULL,
					`sort` int(11) NOT NULL DEFAULT 0,
					`default_page` tinyint(4) NOT NULL DEFAULT 0,
					PRIMARY KEY (`id`)
				) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;';
			mysqli_query($this->databaseManager->mysqli, $query) OR DIE (mysqli_error());
			
			$query = 'INSERT INTO `'.$this->database.'` (`id`, `pid`, `module`, `titre`, `menu`, `data`, `sort`, `default_page`) VALUES
				(1, 0, 3, "Accueil", "Accueil", \'s:0:"";\', 0, 1);';
			mysqli_query($this->databaseManager->mysqli, $query) OR DIE (mysqli_error());
		}
		
		
		
		function setModuleManager($moduleManager) {
			$this->moduleManager = $moduleManager;
		}
		
		
		
		function setAdditionalData($data = null) {
			$this->moduleManager->loadAllModules();
			$this->additionalData = $this->moduleManager->getAdditionalData();
		}
		
		
		
		function getAdditionalData() {
			$buff = mysqli_query($this->databaseManager->mysqli, 'SELECT id, data FROM '.$this->database);
			$additionaldata = array();
			while ($data = mysqli_fetch_array($buff))
			{
				$additionaldata[$data['id']] = unserialize($data['data']);
			}
			return $additionaldata;
		}
		
		
		
		function getPageData($page) {
			$data['titre'] = '';
			$data['class'] = '';
			if ( intval($page) != 0 )
			{
				$buff = mysqli_query($this->databaseManager->mysqli, 'SELECT titre, menu, pid FROM page_manager WHERE id="'.$page.'"');
				$data = mysqli_fetch_array($buff);
				if ( $data['pid'] == 0 )
				{
					$data['class'] = $data['menu'];
				}
				else
				{
					$buff = mysqli_query($this->databaseManager->mysqli, 'SELECT menu FROM page_manager WHERE id="'.$data['pid'].'"');
					$data2 = mysqli_fetch_array($buff);
					$data['class'] = $data2['menu'];
				}
			}
			elseif ( $page == 'AdminManager' )
			{
				$data = array( 'titre' => 'Connexion', 'class' => '' );
			}
			elseif ( $page == 'PageManager' )
			{
				$data = array( 'titre' => 'G�rer les pages', 'class' => '' );
			}
			return $data;
		}
		
		
		
		function getModules() {
			$buff = mysqli_query($this->databaseManager->mysqli, 'SELECT id, module FROM '.$this->database);
			$modules = array();
			while ($data = mysqli_fetch_array($buff))
			{
				$modules[$data['id']] = $data['module'];
			}
			return $modules;
		}
		
		
		
		function getModule($page) {
			$buff = mysqli_query($this->databaseManager->mysqli, 'SELECT m.module FROM '.$this->moduleManager->database.' m JOIN '.$this->database.' pm ON m.id=pm.module WHERE pm.id="'.$page.'"');
			$data = mysqli_fetch_array($buff);
			return $data['module'];
		}
		
		
		
		function getDefaultPage() {
			$buff = mysqli_query($this->databaseManager->mysqli, 'SELECT id FROM '.$this->database.' WHERE default_page=1');
			$data = mysqli_fetch_array($buff);
			return $data['id'];
		}
		
		
		
		function preProcessAdmin($page, $action) {
			$this->setAdditionalData();
			
			switch ($action)
			{
				case 'add':
					if (isset($_POST['submit']))
					{
						$this->titleError = '';
						$this->menuError = '';
						$this->dataError = '';
						
						$moduleId = intval($_POST['module']);
						$additionalData = $this->additionalData[$moduleId];
						
						$data = '';
						if (is_array($additionalData))
						{
							$data = array();
							foreach ($additionalData as $key => $info)
							{
								if (!empty($_POST[$key]))
								{
									$data[$key] = $_POST[$key];
								}
							}
						}
						if (count($additionalData) == count($data))
						{
							if (!empty($_POST['titre']) && !empty($_POST['menu']))
							{
								$titre = mysqli_real_escape_string($_POST['titre']);
								$menu = mysqli_real_escape_string($_POST['menu']);
								$pid = intval($_POST['pid']);
								$serializedData = mysqli_real_escape_string(serialize($data));
								
								$query = 'INSERT INTO '.$this->database.'(titre, menu, module, pid, data) VALUES("'.$titre.'", "'.$menu.'", '.$moduleId.', '.$pid.', "'.$serializedData.'")';
								mysqli_query($this->databaseManager->mysqli, $query) or die(mysqli_error());
								
								// Ex�cution de la m�thode d'installation du module concern� (installation BDD par exemple)
								$this->moduleManager->install($moduleId, $data);
								$_SESSION[$this->getName()] = 'La nouvelle page � bien �t� ajout�.';
								header('Location: admin.php?page='.$this->getName());
							}
							if (empty($_POST['titre']))
							{
								$this->titleError = 'Veillez remplir ce champ.';
							}
							if (empty($_POST['menu']))
							{
								$this->menuError = 'Veillez remplir ce champ.';
							}
						}
						else
						{
							$this->dataError = 'Les champs suppl�mentaires doivent �tre rempli';
						}
					}
					break;
				
				case 'alter':
					if (isset($_POST['submit']))
					{
						$this->delete = '';
						$this->update = '';
						$this->error = '';
						
						$default = 0;
						$buff = mysqli_query($this->databaseManager->mysqli, 'SELECT id, sort FROM '.$this->database);
						while($data = mysqli_fetch_array($buff)) {
							$id = intval($data['id']);
							if (isset($_POST['delete_'.$id]))
							{
								mysqli_query($this->databaseManager->mysqli, 'DELETE FROM '.$this->database.' WHERE id='.$id) or die(mysqli_error());
								
								// Ex�cution de la m�thode de d�sinstallation du module concern�
								$this->moduleManager->modules[$id]->uninstall();
								$this->delete = 'Suppression(s) effectu�e(s).';
							}
							else
							{
								$update = array();
								if ($data['sort']!=$_POST['sort_'.$id])
								{
									$update[] = 'sort="'.$_POST['sort_'.$id].'"';
								}
								if ($_POST['default_page']==$id)
								{
									$update[] = 'default_page=1';
									$default = $id;
								}
								if (!empty($update))
								{
									$query = 'UPDATE '.$this->database.' SET '.implode(',', $update).' WHERE id='.$id;
									mysqli_query($this->databaseManager->mysqli, $query) or die(mysqli_error());
									$this->update = 'Modification(s) effectu�e(s).';
								}
							}
						}
						if ($default!=0)
						{
							mysqli_query($this->databaseManager->mysqli, 'UPDATE '.$this->database.' SET default_page=0 WHERE id<>'.$default) or die(mysqli_error());
						}
						else
						{
							$this->error = 'Vous devez d�finir la page par d�faut.';
						}
					}
					break;
			}
		}
		
		
		
		function displayPage($page, $action) {
			switch ($action)
			{
				case 'tinymce':
					if ( isset($_GET['plugin']) )
					{
						$module = $_GET['module'];
						$moduleId = $this->moduleManager->getModuleId($module);
						$buff = mysqli_query($this->databaseManager->mysqli, 'SELECT id FROM '.$this->database.' WHERE module='.$moduleId);
						
						$i = 0;
						$moduleInfos = array();
						while ($data = mysqli_fetch_array($buff))
						{
							$moduleInfos[$i] = array();
							$moduleInfos[$i]['dir'] = $this->moduleManager->modules[$data['id']]->rootDir;
							$moduleInfos[$i]['thumbnailSufix'] = $this->moduleManager->modules[$data['id']]->thumbnailSufix;
							$moduleInfos[$i]['extensions'] = $this->moduleManager->modules[$data['id']]->extensions;
							$i++;
						}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" >
<head>
	<title>Images interne</title>
	<script type="text/javascript" src="tiny_mce/tiny_mce_popup.js"></script>
	<script type="text/javascript" src="tiny_mce/plugins/<?php echo $_GET['plugin']; ?>/js/dialog.js"></script>
	<script type="text/javascript" src="tiny_mce/utils/mctabs.js"></script>
	<link rel="stylesheet" media="screen" type="text/css" title="Style" href="tiny_mce/plugins/<?php echo $_GET['plugin']; ?>/css/embed.css" />
</head>
	<body>
		<div class="tabs">
			<ul>
				<li id="general_tab" class="current"><span>Images interne</span></li>
			</ul>
		</div>
		<div class="panel_wrapper">
			<div id="general_panel" class="panel current">
				<ul>
<?php

						foreach ($moduleInfos as $moduleInfo)
						{
							echo '<li><a href="index.php" onclick="mcTabs.displayTab(\'general_tab \',\''.$moduleInfo['dir'].'_panel\');return false;" title="'.$moduleInfo['dir'].'" >'.$moduleInfo['dir'].'</a></li>';
						}

?>
				</ul>
			</div>
<?php

						$media = array();
						foreach ($moduleInfos as $moduleInfo)
						{
							echo '<div id="'.$moduleInfo['dir'].'_panel" class="panel"><ul>';
							echo '<li><a href="index.php" onclick="mcTabs.displayTab(\'general_tab \',\'general_panel\');return false;" title=".." >..</a></li>';
							
							$media[$moduleInfo['dir']] = array();
							
							$dir=opendir($moduleInfo['dir']);
							while ($file=readdir($dir))
							{
								if ($file!='.' && $file!='..')
								{
									$filename = substr($file, 0, strrpos($file, '.'));
									$extension = substr($file, strrpos($file, '.')+1);	
									$thumbnailSufix = substr($filename, -strlen($moduleInfo['thumbnailSufix']), strlen($moduleInfo['thumbnailSufix']));
									if ( $thumbnailSufix!=$moduleInfo['thumbnailSufix'] && in_array($extension, $moduleInfo['extensions'] ) )
									{
										
									}
									elseif ( is_dir($moduleInfo['dir'].'/'.$file) )
									{
										$media[$moduleInfo['dir']][]=$file;
										echo '<li><a href="index.php" onclick="mcTabs.displayTab(\'general_tab \',\''.$moduleInfo['dir'].'_'.$file.'_panel\');return false;" title="'.$file.'" >'.$file.'</a></li>';
									}
								}
							}
							closedir($dir);
							
							echo '</ul><div id="'.$moduleInfo['dir'].'" class="preview" ></div><div class="breaker" ></div></div>';
						}
						
						$i = 0;
						foreach ($media as $dir => $dirList)
						{
							foreach ($dirList as $subDir)
							{
								echo '<div id="'.$moduleInfo['dir'].'_'.$subDir.'_panel" class="panel"><ul>';
								echo '<li><a href="index.php" onclick="mcTabs.displayTab(\'general_tab \',\''.$moduleInfos[$i]['dir'].'_panel\');return false;" title=".." >..</a></li>';
								
								$directory=opendir($dir.'/'.$subDir);
								while ($file=readdir($directory))
								{
									if ($file!='.' && $file!='..')
									{
										$filename = substr($file, 0, strrpos($file, '.'));
										$extension = substr($file, strrpos($file, '.')+1);	
										$thumbnailSufix = substr($filename, -strlen($moduleInfo['thumbnailSufix']), strlen($moduleInfo['thumbnailSufix']));
										if ( $thumbnailSufix!=$moduleInfo['thumbnailSufix'] && in_array($extension, $moduleInfo['extensions'] ) )
										{
											echo '<li><a href="index.php" onclick="InternalImageDialog.insert(\''.$dir.'/'.$subDir.'/'.$file.'\');return false;" onmouseover="InternalImageDialog.preview(\''.$dir.'/'.$subDir.'/'.$filename.$moduleInfos[$i]['thumbnailSufix'].'.'.$extension.'\', \''.$moduleInfo['dir'].'_'.$subDir.'\');" title="'.$file.'" >'.$file.'</a></li>';
										}

									}
								}
								closedir($directory);
								
								echo '</ul><div id="'.$moduleInfo['dir'].'_'.$subDir.'" class="preview" ></div><div class="breaker" ></div></div>';
							}
							$i++;
						}

?>
		</div>
	</body>
</html>
<?php

					}
					break;
			}
		}
		
		
		
		function displayAdmin($page, $action) {
			$modules = $this->moduleManager->getAllModules();
			$buff = mysqli_query($this->databaseManager->mysqli, 'SELECT id, menu, sort FROM '.$this->database.' WHERE pid=0');

?>
			<script type="text/javascript" >
var additionalData = <?php echo json_encode($this->additionalData); ?>;

function loadAdditionalData(select) {
	var bloc = document.getElementById('additionalData');
	var content = '';
	for( var key in additionalData )
	{
		if ( select.value == key && additionalData[key] != '' )
		{
			for ( var element in additionalData[key] )
			{
				content += '<label for="'+element+'" >'+additionalData[key][element].label+' : <label>';
				content += '<input type="'+additionalData[key][element].type+'" name="'+element+'" id="'+element+'" />';
				content += ' <span class="error" ><?php echo $this->dataError; ?></span>';
			}
		}
	}
	bloc.innerHTML = content;
}
			</script>
			<form action="?page=<?php echo $this->getName(); ?>&action=add" method="post">
				<fieldset>
					<legend>Ajouter une page</legend>
					<p>
						<label for="page-titre" >Titre de la page : </label>
						<input type="text" name="titre" id="page-titre" value="<?php echo (isset($_POST['titre']) && !isset($_SESSION[$this->getName()]))? $_POST['titre']: ''; ?>" />
						<span class="error" ><?php echo $this->titleError; ?></span>
					</p>
					<p>
						<label for="page-menu" >Nom du menu : </label>
						<input type="text" name="menu" id="page-menu" value="<?php echo (isset($_POST['menu']) && !isset($_SESSION[$this->getName()]))? $_POST['menu']: ''; ?>" />
						<span class="error" ><?php echo $this->menuError; ?></span>
					</p>
					<p>
						<label for="page-module" >Module : </label>
						<select name="module" onchange="loadAdditionalData(this)" id ="page-module" >
<?php

			foreach($modules as $id => $module) {
				$selected = '';
				if ( isset($_POST['module']) && $_POST['module'] == $id && !isset($_SESSION[$this->getName()]) )
				{
					$selected = 'selected="selected"';
				}
				echo '<option value="'.$id.'" '.$selected.' >'.$module.'</option>';
	        }

?>
						</select>
					</p>
					<p>
						<label for="page-pid" >Menu parent : </label>
						<select name="pid" id="page-pid" >
							<option value="0" >Racine</option>
<?php

			while($data = mysqli_fetch_array($buff)) {
				$selected = '';
				if ( isset($_POST['pid']) && $_POST['pid'] == $data['id'] && !isset($_SESSION[$this->getName()]) )
				{
					$selected = 'selected="selected"';
				}
	            echo '<option value="'.$data['id'].'" '.$selected.' >'.$data['menu'].'</option>';
	        }

?>
						</select>
					</p>
					<div id="additionalData" >
					</div>
					<script type="text/javascript" >
loadAdditionalData(document.getElementById('page-module'));
value = <?php
$value = array();
if ( isset($_POST['module']) )
{
	foreach ($this->additionalData[$_POST['module']] as $element => $info)
	{
		$value[$element] = $_POST[$element];
	}
}
echo json_encode($value);
?>;
for (var element in value)
{
	document.getElementById(element).value=value[element];
}
					</script>
					<p>
						<input type="submit" name="submit" />
						<span class="error" ><?php echo (isset($_SESSION[$this->getName()])? $_SESSION[$this->getName()]: ''); ?></span>
					</p>
				</fieldset>
			</form>
			<form action="?page=<?php echo $this->getName(); ?>&action=alter" method="post">
				<fieldset>
					<legend>G�rer les pages</legend>
					<table>
						<thead>
							<tr>
								<th>Pages</th>
								<th class="option" >Ordre</th>
								<th class="option" >Page par d�faut</th>
								<th class="option" >Supprimer</th>
							</tr>
						</thead>
						<tbody>
<?php

			unset($_SESSION[$this->getName()]);
			$buff = mysqli_query($this->databaseManager->mysqli, 'SELECT id, menu, sort, default_page FROM '.$this->database.' WHERE pid=0 ORDER BY sort');
			
			$i = 1;
			while($data = mysqli_fetch_array($buff)) {
				echo '<tr>';
				echo '<td>'.$i.'. '.$data['menu'].'</td>';
				echo '<td class="option" ><input type="text" size="2" name="sort_'.$data['id'].'" value="'.$data['sort'].'" /></td>';
				if ($data['default_page'])
				{
					$checked = 'checked="checked"';
				}
				else
				{
					$checked = '';
				}
				echo '<td class="option" ><input type="radio" name="default_page" value="'.$data['id'].'" '.$checked.' /></td>';
				echo '<td class="option" ><input type="checkbox" name="delete_'.$data['id'].'" /></td>';
				echo '</tr>';
				$buff2 = mysqli_query($this->databaseManager->mysqli, 'SELECT id, menu, sort, default_page FROM '.$this->database.' WHERE pid='.$data['id'].' ORDER BY sort');
				while($data2 = mysqli_fetch_array($buff2)) {
					echo '<tr>';
					echo '<td><span>'.$data2['menu'].'</span></td>';
					echo '<td class="option" ><input type="text" size="2" name="sort_'.$data2['id'].'" value="'.$data2['sort'].'" /></td>';
					echo '<td class="option" ><input type="radio" name="default_page" value="'.$data2['id'].'" /></td>';
					echo '<td class="option" ><input type="checkbox" name="delete_'.$data2['id'].'" /></td>';
					echo '</tr>';
				}
				echo '<tr class="space" ><td colspan="4" ></td></tr>';
				$i++;
			}

?>
						</tbody>
					</table>
					<p class="center" >
						<input type="submit" name="submit" />
						<span class="error" ><?php echo $this->error; ?> <?php echo $this->delete; ?> <?php echo $this->update; ?></span>
					</p>
				</fieldset>
			</form>
<?php

		}
		
		
		
		function displayMenu($modules, $data = null) {

?>
			<div id="menu">
				<ul>
<?php

			$buff = mysqli_query($this->databaseManager->mysqli, 'SELECT id, menu FROM '.$this->database.' WHERE pid=0 ORDER BY sort');
			while($data = mysqli_fetch_array($buff)) {
				if (!$modules[$data['id']]->displayMenu($modules, $data))
				{
					$buff2 = mysqli_query($this->databaseManager->mysqli, 'SELECT count(id) AS nb_sous_menu FROM '.$this->database.' WHERE pid='.$data['id'].' ORDER BY sort');
					$data2 = mysqli_fetch_array($buff2);
					if ($data2['nb_sous_menu'] == 0)
					{
						$title = htmlentities($data['menu']);
						echo '<li class="'.strtolower($this->sanitize($data['menu'])).'" ><a href="?page='.$data['id'].'" title="'.$title.'" >'.$title.'</a></li>';
					}
					else
					{
						$idMenu = $this->sanitize($data['menu']);
						echo '<li class="'.strtolower($this->sanitize($data['menu'])).'" ><a href="?page='.$data['id'].'" title="'.htmlentities($data['menu']).'" >'.$data['menu'].'</a>';
						echo '<ul>';
						$buff2 = mysqli_query($this->databaseManager->mysqli, 'SELECT id, menu, module FROM '.$this->database.' WHERE pid='.$data['id'].' ORDER BY sort');
						while($data2 = mysqli_fetch_array($buff2)) {
							if (!$modules[$data2['id']]->displayMenu($modules, $data))
							{
								$title = htmlentities($data2['menu']);
								echo '<li><a href="?page='.$data2['id'].'" title="'.$title.'" >'.$title.'</a></li>';
							}
						}
						echo '</ul></li>';
					}
				}
			}

?>
				</ul>
			</div>
<?php

		}
		
		
		
		function displayAdminMenu($modules) {
			$buff = mysqli_query($this->databaseManager->mysqli, 'SELECT id, menu FROM '.$this->database);
			while ($data = mysqli_fetch_array($buff))
			{
				$modules[$data['id']]->displayMenuAdmin($data['id'], $data['menu']);
			}
			$this->displayMenuAdmin();
		}
		
		
		
		function displayMenuAdmin($page = null, $menu = null) {
			if ($_SESSION['connect'])
			{

?>
			<li><a href="?page=<?php echo $this->getName(); ?>" title="G�rer les pages" >G�rer les pages</a></li>
<?php

			}
		}
		
	}