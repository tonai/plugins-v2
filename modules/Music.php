<?php

	class Music extends AbstractModule {
	
		var $rootDir;
		
		var $save;
		var $delete;
		var $error;
		var $uploadFileError;
		var $deleteFileError;
		var $deleteDirError;
		
		
		
		function install($data) {
			$rootDir = $this->sanitize($data['rootDir']);
			if (!is_dir($rootDir))
			{
				mkdir($rootDir);
			}
		}
		
		
		
		function setAdditionalData($data) {
			if(!empty($data))
			{
				foreach($data as $key => $value)
				{
					$this->$key = $this->sanitize($value);
				}
			}
		}
		
		
		
		function getAdditionalData() {
			return array(
				'rootDir' => array(
					'type' => 'text',
					'label' => htmlentities('Dossier racine des mp3'),
				),
			);
		}
		
		
		
		function preProcessPage($page, $action) {
			switch($action) {
				case 'play':
					if (isset($_GET['mp3']))
					{
						$_SESSION[$page]['mp3'] = $_GET['mp3'];
						header('Location: index.php?page='.$page.'&action=play');
					}
					break;
				
			}
		}
		
		
		
		function preProcessAdmin($page, $action) {
			switch($action) {
				case 'clean':
					$dossier=opendir($this->rootDir);
					$id = 0;
					while ($dir=readdir($dossier))
					{
						if ($dir != '.' && $dir != '..' && is_dir($this->rootDir.'/'.$dir))
						{
							$sousDossier=opendir($this->rootDir.'/'.$dir);
							while ($file=readdir($sousDossier))
							{
								if ($file != '.' && $file != '..')
								{
									$extension = substr($file, strrpos($file, '.')+1);
									$oldName = $this->rootDir.'/'.$dir.'/'.$file;
									if (in_array($extension, $this->extensions))
									{
										$nomFichier = substr($file, 0, strrpos($file, '.'));
										$newName = $this->rootDir.'/'.$dir.'/'.$this->sanitize($nomFichier).'.'.$extension;
										if (is_file($oldName))
										{
											rename($oldName, $newName);
										}
									}
									else
									{
										unlink($oldName);
									}
								}
							}
							closedir($sousDossier);
						}
					}
					closedir($dossier);
					break;
				
				case 'dir':
					if (isset($_POST['submit']))
					{
						$this->save = '';
						$this->error = '';
						if (!empty($_POST['dirname']))
						{
							$dir = $this->sanitize($_POST['dirname']);
							if (!is_dir($this->rootDir.'/'.$dir))
							{
								mkdir($this->rootDir.'/'.$dir);
								$this->save = 'La catégorie a bien été ajouté.';
							}
							else
							{
								$this->error = 'Cette catégorie existe déjà.';
							}
						}
						else
						{
							$this->error = 'Veillez remplir ce champ.';
						}
					}
					break;
				
				case 'file':
					if ( isset($_POST['submit']) )
					{
						$this->save = array();
						$this->delete = array();
						$this->uploadFileError = array();
						$this->deleteFileError = array();
						$this->deleteDirError = '';
						
						$dossier=opendir($this->rootDir);
						$id = 0;
						while ($dir=readdir($dossier))
						{
							if ($dir != '.' && $dir != '..' && is_dir($this->rootDir.'/'.$dir))
							{
								if (isset($_POST[$dir]))
								{
									unlink($this->rootDir.'/'.$dir);
									$this->deleteDirError = 'La(Les) catégorie(s) a(ont) bien été supprimée(s).';
								}
								else
								{
									if ($_FILES[$dir]['error'] != 4)
									{
										$this->erreur = 'Une erreur s\'est produite';
										if ($_FILES[$dir]['error'] > 0)
										{
											$this->uploadFileError[$dir] = 'Erreur lors du tranfsert';
										}
										else
										{
											$fileName = $_FILES[$dir]['name'];
											$nomFichier = substr($fileName, 0, strrpos($fileName, '.'));
											$extension = substr($fileName, strrpos($fileName, '.')+1);
											$nomPropre = $this->sanitize($nomFichier);
											$direction = $this->rootDir.'/'.$dir.'/'.$nomPropre.'.mp3';
											if ('mp3' == $extension)
											{
												if (move_uploaded_file($_FILES[$dir]['tmp_name'],$direction))
												{
													$this->save[$dir] = 'Transfert(s) réussi(s).';
												}
												else
													$this->uploadFileError[$dir] = 'Erreur lors du(des) tranfsert(s).';
											}
											else
											{
												$this->uploadFileError[$dir] = 'Mauvaise(s) extension(s) de fichier.';
											}
										}
									}
								}
								
								$sousDossier=opendir($this->rootDir.'/'.$dir);
								while ($file=readdir($sousDossier))
								{
									$extension=substr($file, strrpos($file, '.'));
									if ($dir != '.' && $dir != '..' && $extension == '.mp3')
									{
										$id++;
										if (isset($_POST['mp3'.$id]))
										{
											if (is_file($this->rootDir.'/'.$dir.'/'.$file))
											{
												unlink($this->rootDir.'/'.$dir.'/'.$file);
												$this->delete[$dir] ='Les(s) Fichier(s) a(ont) bien été supprimé(s).';
											}
											else
											{
												$this->deleteFileError[$dir] ='Ce(s) fichier(s) n\'existe pas.';
											}
										}
									}
								}
								closedir($sousDossier);
							}
						}
						closedir($dossier);
					}
					break;
			}
		}
		
		
		
		function displayPage($page, $action) {
			if (isset($_SESSION[$page]['mp3']))
			{
				$autoplay=0;
				if ($action=='play')
				{
					$autoplay=1;
				}
				$mp3=$_SESSION[$page]['mp3'];
				echo '<div id="player" >';
				echo '<object type="application/x-shockwave-flash" data="dewplayer-mini.swf?mp3='.$this->rootDir.'/'.$mp3.'.mp3&amp;autoplay='.$autoplay.'&amp;bgcolor=000000" width="150" height="20">';
				echo '<param name="bgcolor" value="#000000" />';
				echo '<param name="wmode" value="transparent" />';
				echo '<param name="movie" value="dewplayer-mini.swf?mp3='.$this->rootDir.'/'.$mp3.'.mp3&amp;autoplay='.$autoplay.'&amp;bgcolor=000000" />';
				echo '</object>';
				echo '<strong>'.str_replace('_', ' ', substr($mp3, strpos($mp3, '/')+1)).'</strong>';
				echo '</div>';
			}
			echo '<ul class="center" id="music-list">';

			$dossier=opendir($this->rootDir);
			while ($dir=readdir($dossier))
			{
				if ($dir != '.' && $dir != '..' && is_dir($this->rootDir.'/'.$dir))
				{
					echo '<li class="title">'.str_replace('_', ' ', $dir).'</li>';
					$sousDossier=opendir($this->rootDir.'/'.$dir);
					while ($file=readdir($sousDossier))
					{
						$extension=substr($file, strrpos($file, '.'));
						if ($dir != '.' && $dir != '..' && $extension == '.mp3')
						{
							$nom=substr($file, 0, strrpos($file, '.'));
							echo '<li><a href="?page='.$page.'&action=play&mp3='.$dir.'/'.$nom.'">'.str_replace('_', ' ', $nom).'</a></li>';
						}
					}
					closedir($sousDossier);
				}
			}
			closedir($dossier);
			
			echo '</ul>';
		}
		
		
		
		function displayAdmin($page, $action) {

?>
				<form action="?page=<?php echo $page; ?>&action=dir" method="post" enctype="multipart/form-data" >
					<fieldset class="center" >
						<legend>Ajouter une catégorie</legend>
						<p>
							<label for="dirname" >nom de la catégorie : </label>
							<span class="error" ><?php echo $this->error; ?><?php echo $this->save; ?></span><br/>
							<input type="text" name="dirname" id="dirname" value="<?php echo (isset($_POST['dirname']) && empty($this->save))? $_POST['dirname']: ''; ?>" />
							<input type="submit" name="submit" />
						</p>
					</fieldset>
				</form>
				<form action="?page=<?php echo $page; ?>&action=file" method="post" enctype="multipart/form-data" >
					<fieldset>
						<legend>Ajouter / Supprimer un mp3</legend>
<?php

			$dossier=opendir($this->rootDir);
			$id = 0;
			while ($dir=readdir($dossier))
			{
				if ($dir != '.' && $dir != '..' && is_dir($this->rootDir.'/'.$dir))
				{
					echo '<span class="right">(Supprimer la catégorie : <input type="checkbox" name="'.$dir.'"/>)</span>';
					echo '<h2>'.str_replace('_', ' ', $dir).'</h2>';
					echo '<p class="center" >';
					echo '<label for="'.$dir.'" >ajouter un mp3 : </label>';
					echo '<input type="file" name="'.$dir.'" id="'.$dir.'" />';
					echo (isset($this->save[$dir]))? '<span class="error" >'.$this->save[$dir].'</span>': '';
					echo (isset($this->uploadFileError[$dir]))? '<span class="error" >'.$this->uploadFileError[$dir].'</span>': '';
					echo '</p>';
					
					$sousDossier=opendir($this->rootDir.'/'.$dir);
					while ($file=readdir($sousDossier))
					{
						$extension=substr($file, strrpos($file, '.'));
						if ($file != '.' && $file != '..' && $extension == '.mp3')
						{
							$nom=substr($file, 0, strrpos($file, '.'));
							$id++;
							echo '<p>';
							$checked = (isset($_POST['mp3'.$id.'']) && empty($this->delete))? 'checked="checked"': '';
							echo '<input type="checkbox" name="mp3'.$id.'" id="mp3'.$id.'" '.$checked.'/>';
							echo '<label for="mp3'.$id.'" >Supprimer : '.str_replace('_', ' ', $nom).'</label>';
							echo '</p>';
						}
					}
					closedir($sousDossier);
					echo '<p class="error" >';
					echo (isset($this->delete[$dir]))? $this->delete[$dir]: '';
					echo (isset($this->deleteFileError[$dir]))? $this->deleteFileError[$dir]: '';
					echo '</p>';
				}
			}
			closedir($dossier);
			
			echo '<p class="center" >';
			echo '<input type="submit" name="submit" />';
			echo '<span class="error" >'.$this->deleteDirError.'</span>';
			echo '</p>';

?>
					</fieldset>
				</form>
<?php

		}
		
	}