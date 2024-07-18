<?php

	class Galery extends AbstractModule {
		
		var $rootDir;
		var $extensions = array('jpg', 'jpeg', 'png', 'gif');
		var $thumbnailSufix = '_petit';
		var $privateSufix = '_private';
		
		var $save;
		var $delete;
		var $error;
		var $dirSave;
		var $dirDelete;
		var $dirUpdate;
		var $dirError;
		
		
		
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
					'label' => htmlentities('Dossier racine des photos'),
				),
			);
		}
		
		
		
		function preProcessAdmin($page, $action) {
			switch($action) {
				case 'clean':
					$dossier=opendir($this->rootDir);
					$j=0;
					$media=array();
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
									$oldPath = $this->rootDir.'/'.$dir.'/'.$file;
									if ( in_array($extension, $this->extensions) )
									{
										$fileName = substr($file, 0, strrpos($file, '.'));
										$newPath = $this->rootDir.'/'.$dir.'/'.$this->sanitize($fileName).'.'.$extension;
										if (is_file($oldPath))
										{
											rename($oldPath, $newPath);
											$media[$j]=$newPath;
											$j++;
										}
									}
									else
									{
										unlink($oldPath);
									}
								}
							}
							closedir($sousDossier);
						}
					}
					closedir($dossier);
					
					foreach ($media as $file)
					{
						$path = substr($file, 0, strrpos($file, '/'));
						$fileName = substr($file, strrpos($file, '/')+1);
						$baseName = substr($fileName, 0, strrpos($fileName, '.'));
						$extension = substr($fileName, strrpos($fileName, '.')+1);
						$thumbnailSufix = substr($baseName, -strlen($this->thumbnailSufix), strlen($this->thumbnailSufix));
						if ( $thumbnailSufix!=$this->thumbnailSufix )
						{
							$thumbnailPath = $path.'/'.$baseName.$this->thumbnailSufix.'.'.$extension;
							if (!file_exists($thumbnailPath))
							{
								$this->redimensionnement($path, $baseName, $extension);
							}
						}
					}
					break;
					
				case 'delete':
					if ( isset($_POST['submit']) )
					{
						$this->delete = '';
						
						$path = $this->rootDir.'/'.$_GET['cat'];
						$dossier=opendir($path);
						$j=0;
						$media=array();
						while ($file=readdir($dossier))
						{
							if ($file!='.' && $file!='..')
							{
								$nomFichier = substr($file, 0, strrpos($file, '.'));
								$thumbnailSufix = substr($nomFichier, -strlen($this->thumbnailSufix), strlen($this->thumbnailSufix));
								if ( $thumbnailSufix!=$this->thumbnailSufix )
								{
									$media[$j]=$file;
									$j++;
								}
							}
						}
						closedir($dossier);
						
						foreach ($media as $id => $file)
						{
							if ( isset($_POST['media'.$id]) )
							{
								$nomFichier = substr($file, 0, strrpos($file, '.'));
								$extension = substr($file, strrpos($file, '.')+1);
								if ( in_array($extension, $this->extensions) )
								{
									$fileName = $path.'/'.$file;
									$miniName = $path.'/'.$nomFichier.$this->thumbnailSufix.'.'.$extension;
									if (file_exists($miniName))
									{
										unlink($miniName);
									}
									if (file_exists($fileName))
									{
										unlink($fileName);
									}
									$this->delete = 'La(Les) photo(s) a(ont) bien été supprimée(s).';
								}
							}
						}
					}
					break;
					
				case 'upload':
					$path=$this->rootDir.'/'.$_GET['cat'];
					if ( isset($_POST['submit']) )
					{
						$this->save = '';
						$this->error = '';
						
						if (isset($_FILES['media']['error']))
						{
							if ($_FILES['media']['error'] != 4)
							{
								$this->erreur = 'Une erreur s\'est produite';
								if ($_FILES['media']['error'] > 0)
								{
									$this->uploadFileError[$dir] = 'Erreur lors du tranfsert';
								}
								else
								{
									$nomFichier = substr($_FILES['media']['name'], 0, strrpos($_FILES['media']['name'], '.'));
									$extension = strtolower( substr($_FILES['media']['name'], strrpos($_FILES['media']['name'], '.')+1) );
									$nomPropre = $this->sanitize($nomFichier);
									$direction = $path.'/'.$nomPropre.'.'.$extension;
									if ( in_array($extension, $this->extensions ) )
									{
										if (move_uploaded_file($_FILES['media']['tmp_name'],$direction))
										{
											$this->redimensionnement($path, $nomPropre, $extension);
											$this->save = 'Transfert réussi';
										}
										else
											$this->error = 'Erreur lors du tranfsert';
									}
									else
									{
										$this->error = 'Mauvaise extension de fichier';
									}
								}
							}
						}
					}
					break;
				
				case 'add_dir':
					if (isset($_POST['submit']))
					{
						$this->dirSave = '';
						$this->dirError = '';
						
						if ( !empty($_POST['dir']) )
						{
							$dir = $this->sanitize($_POST['dir']);
							if (!is_dir($this->rootDir.'/'.$dir))
							{
								mkdir($this->rootDir.'/'.$dir);
								$this->dirSave = 'La catégorie a bien été ajouté.';
							}
							else
							{
								$this->dirError = 'Cette catégorie existe déjà';
							}
						}
						else
						{
							$this->dirError = 'Veillez remplir ce champ.';
						}
					}
					break;
				
				case 'update_dir':
					if (isset($_POST['submit']))
					{
						$this->dirDelete = '';
						$this->dirUpdate = '';
						
						$dossier=opendir($this->rootDir);
						while ($dir=readdir($dossier))
						{
							if ($dir != '.' && $dir != '..' && is_dir($this->rootDir.'/'.$dir))
							{
								$private = false;
								if ( substr($dir, -strlen($this->privateSufix)) == $this->privateSufix )
								{
									$private = true;;
								}
								
								if (isset($_POST['delete'][$dir]))
								{
									unlink($this->rootDir.'/'.$dir);
									$this->dirDelete = 'La(Les) catégorie(s) a(ont) bien été supprimée(s)';
								}
								elseif (isset($_POST['private'][$dir]))
								{
									rename($this->rootDir.'/'.$dir, $this->rootDir.'/'.$dir.$this->privateSufix);
									$this->dirUpdate = 'La(Les) catégorie(s) a(ont) bien été mise(s) à jour';
								}
								elseif ($private && !isset($_POST['private'][$dir]))
								{
									$newDir = substr($dir, 0, -strlen($this->privateSufix));
									rename($this->rootDir.'/'.$dir, $this->rootDir.'/'.$newDir);
									$this->dirUpdate = 'La(Les) catégorie(s) a(ont) bien été mise(s) à jour';
								}
							}
						}
					}
					break;
			}
		}
		
		
		
		function displayPage($page, $action) {
			if (!empty($_GET['cat']))
			{
				$path=$this->rootDir.'/'.$_GET['cat'];
				if ( substr($_GET['cat'], -strlen($this->privateSufix)) != $this->privateSufix && is_dir($path) )
				{
					echo '<h2>'.str_replace('_', ' ', $_GET['cat']).'</h2>';
					
					if (is_dir($path))
					{
						$pager = (isset($_GET['pager'])? $_GET['pager']: '1');
						$multiple = 5;
						$imageParPage = 15;
						$media = array();
						$j = 0;
						
						$dossier=opendir($path);
						while ($file=readdir($dossier))
						{
							if ($file!='.' && $file!='..')
							{
								$nomFichier = substr($file, 0, strrpos($file, '.'));
								$extension = substr($file, strrpos($file, '.')+1);	
								$thumbnailSufix = substr($nomFichier, -strlen($this->thumbnailSufix), strlen($this->thumbnailSufix));
								if ( $thumbnailSufix!=$this->thumbnailSufix && in_array($extension, $this->extensions ) )
								{
									$media[$j]=$file;
									$j++;
								}
							}
						}
						closedir($dossier);
						
						$detail=0;
						if (isset($_GET['photo']))
						{
							if (is_file($path.'/'.$_GET['photo']))
							{
								$i=0;
								while ($media[$i]!=$_GET['photo'] && $i!=count($media))
								{
									$i++;
								}
								if ($i!=count($media) || $media[$i]==$_GET['photo'])
								{
									echo '<p class="center" >';
									if ($i!=0)
									{
										$previousPager = intval(($i-1)/$imageParPage=16)+1;
										echo '<a href="?page='.$page.'&cat='.$_GET['cat'].'&photo='.$media[$i-1].'&pager='.$previousPager.'" class="left" >Image précédante </a>';
									}
									if ($i!=(count($media)-1))
									{
										$nextPager = intval(($i+1)/$imageParPage=16)+1;
										echo '<a href="?page='.$page.'&cat='.$_GET['cat'].'&photo='.$media[$i+1].'&pager='.$nextPager.'" class="right" >Image suivante</a>';
									}
									echo '<a href="?page='.$page.'&cat='.$_GET['cat'].'&pager='.$pager.'" >Retour à la galerie</a>';
									echo '<div class="clear"></div>';
									echo '</p>';
									
									$taille=getimagesize($path.'/'.$_GET['photo']);
									echo '<p class="center" >';
									if ($taille[0]<750)
									{
										echo '<img src="'.$path.'/'.$_GET['photo'].'" />';
									}
									else
									{
										echo '<img src="'.$path.'/'.$_GET['photo'].'" width="750" />';
									}
									echo '</p>';
									
									echo '<p class="center" >';
									if ($i!=0)
									{
										echo '<a href="?page='.$page.'&cat='.$_GET['cat'].'&photo='.$media[$i-1].'&pager='.$previousPager.'" class="left" >Image précédante </a>';
									}
									if ($i!=(count($media)-1))
									{
										echo '<a href="?page='.$page.'&cat='.$_GET['cat'].'&photo='.$media[$i+1].'&pager='.$nextPager.'" class="right" >Image suivante</a>';
									}
									echo '<a href="?page='.$page.'&cat='.$_GET['cat'].'&pager='.$pager.'" >Retour à la galerie</a>';
									echo '<div class="clear"></div>';
									echo '</p>';
								}
								$detail=1;
							}
							else
							{
								$detail=0;
							}
						}
						
						if ($detail==0)
						{						
							if (!isset($_GET['pager']))
							{
								$image=0;
								$pageActuelle=1;
							}
							else
							{
								$image=$imageParPage*($_GET['pager']-1);
								$pageActuelle=$_GET['pager'];
							}
							
							/********affichage des images********/
							if (!empty($media))
							{
								echo '<table>';
								$j=0;
								$k=0;
								for ($i=$image; $i<($image+$imageParPage); $i++)
								{
									if ( $j%$multiple == 0 )
									{
										echo '<tr>';
									}
									if (isset($media[$i]))
									{
										$nomFichier = substr($media[$i], 0, strrpos($media[$i], '.'));
										$extension = substr($media[$i], strrpos($media[$i], '.')+1);
										$miniName = $nomFichier.$this->thumbnailSufix.'.'.$extension;
										echo '<td><a href="?page='.$page.'&cat='.$_GET['cat'].'&photo='.$media[$i].'&pager='.$pager.'"><img src="'.$path.'/'.$miniName.'" /></a>';
										echo '</td>';
									}
									if ( ($j+1)%$multiple == 0 )
									{
										echo '</tr>';
									}
									$j++;
								}
								echo '</table>';
							}
							
							/********ré-affichage du choix des pages********/
							if (count($media) > $imageParPage)
							{
								$pagesTotales=ceil(count($media)/$imageParPage);
								$pages=$pagesTotales;
								echo '<p class="pages" >';
								if ($pageActuelle!=1)
								{
									$pagePrec=$pageActuelle-1;
									echo '<a href="?page='.$page.'&cat='.$_GET['cat'].'&pager='.$pagePrec.'" title="page précédante"><</a>&nbsp&nbsp;';
								}
								echo '<a href="?page='.$page.'&cat='.$_GET['cat'].'&pager=1" title="première page">1..</a>&nbsp&nbsp;';
								$i=2;
								if ($pageActuelle<=5)
								{
									$i=2;
									if ($pages>9)
									{
										$pages=9;
									}
								}
								elseif ($pageActuelle>=($pagesTotales-4) and $pageActuelle>5)
								{
									if ($pagesTotales>=6)
									{
										$i=$pagesTotales-7;
									}
								}
								else
								{
									$i=$pageActuelle-3;
									$pages=$pageActuelle+3;
								}
								for ($i;$i<$pages;$i++)
								{
									echo '<a href="?page='.$page.'&cat='.$_GET['cat'].'&pager='.$i.'">'.$i.'</a>&nbsp&nbsp;';
								}
								if ($pagesTotales!=1)
								{
									echo '<a href="?page='.$page.'&cat='.$_GET['cat'].'&pager='.$pagesTotales.'" title="dernière page">..'.$pagesTotales.'</a>&nbsp&nbsp;';
								}
								if ($pageActuelle!=$pagesTotales)
								{
									$pageSuiv=$pageActuelle+1;
									echo '<a href="?page='.$page.'&cat='.$_GET['cat'].'&pager='.$pageSuiv.'" title="page suivante">></a>';
								}
								echo '</p>';
							}
						}
					}
				}
				else
				{
					header('Location: index.php');
				}
			}
			else
			{
				header('Location: index.php');
			}
		}
				
		
		
		function displayAdmin($page, $action) {
			switch ($action)
			{
				case 'add_dir':
				case 'update_dir':
					echo '<form action="?page='.$page.'&action=add_dir" method="post" >';

?>
				<fieldset>
					<legend>Ajouter une catégorie</legend>
					<p class="center" >
						<input type="text" name="dir" value="<?php echo (isset($_POST['dir']) && empty($this->dirSave))? $_POST['dir']: ''; ?>" />
						<input type="submit" name="submit" />
						<span class="error" ><?php echo $this->dirSave; ?> <?php echo $this->dirError; ?></span>
					</p>
				</fieldset>
			</form>
<?php

					echo '<form action="?page='.$page.'&action=update_dir" method="post" >';
					echo '<fieldset>';
					echo '<legend>supprimer une catégorie</legend>';
					
					$sousDossier=opendir($this->rootDir);
					while ($dir=readdir($sousDossier))
					{
						if ($dir != '.' && $dir != '..' && is_dir($this->rootDir.'/'.$dir))
						{
							$updateChecked = '';
							if ( substr($dir, -strlen($this->privateSufix)) == $this->privateSufix )
							{
								$dir = substr($dir, 0, -strlen($this->privateSufix));
								$updateChecked = 'checked="checked"';
							}
							$deleteChecked = (isset($_POST[$dir]) && empty($this->dirDelete))? 'checked="checked"': '';
							
							echo '<p>';
							echo '<strong>'.str_replace('_', ' ', $dir).'</strong>';
							
							echo '<label for="delete_'.$dir.'" >Supprimer : </label>';
							echo '<input type="checkbox" name="delete['.$dir.']" id="delete_'.$dir.'" '.$deleteChecked.' /> ';
							
							echo '<label for="private_'.$dir.'" >Privé : </label>';
							echo '<input type="checkbox" name="private['.$dir.']" id="private_'.$dir.'" '.$updateChecked.' /> ';
							echo '</p>';
						}
					}
					closedir($sousDossier);

?>
					<p class="center" >
						<input type="submit" name="submit" />
						<span class="error" ><?php echo $this->dirDelete; ?> <?php echo $this->dirUpdate; ?></span>
					</p>
				</fieldset>
			</form>
<?php

					break;
				
				default:
					if (!empty($_GET['cat']))
					{
						$path=$this->rootDir.'/'.$_GET['cat'];
						if (is_dir($path))
						{
							$pager = (isset($_GET['pager'])? $_GET['pager']: '1');
							$multiple = 5;
							$imageParPage = 15;
							$media = array();
							$j = 0;
							
							$dossier=opendir($path);
							while ($file=readdir($dossier))
							{
								if ($file!='.' && $file!='..')
								{
									$nomFichier = substr($file, 0, strrpos($file, '.'));
									$extension = substr($file, strrpos($file, '.')+1);	
									$thumbnailSufix = substr($nomFichier, -strlen($this->thumbnailSufix), strlen($this->thumbnailSufix));
									if ( $thumbnailSufix==$this->thumbnailSufix && in_array($extension, $this->extensions ) )
									{
										$media[$j]=str_replace($this->thumbnailSufix, '', $file);
										$j++;
									}
								}
							}
							closedir($dossier);

?>
					<form action="?page=<?php echo $page; ?>&cat=<?php echo $_GET['cat']; ?>&action=upload&pager=<?php echo $pager; ?>" method="post" enctype="multipart/form-data" >
						<fieldset class="center" >
							<legend>Ajouter un fichier</legend>
							<input type="file" name="media" value="<?php echo (isset($_POST['media']) && empty($this->save))? $_POST['media']: ''; ?>" />
							<span class="error" ><?php echo $this->save; ?> <?php echo $this->error; ?></span>
							<input type="submit" name="submit" />
						</fieldset>
					</form>
					<form action="?page=<?php echo $page; ?>&cat=<?php echo $_GET['cat']; ?>&action=delete&pager=<?php echo $pager; ?>" method="post" enctype="multipart/form-data" >
<?php

							if (!isset($_GET['pager']))
							{
								$image=0;
								$pageActuelle=1;
							}
							else
							{
								$image=$imageParPage*($_GET['pager']-1);
								$pageActuelle=$_GET['pager'];
							}
							
							/********affichage des images********/
							if (!empty($media))
							{
								echo '<table>';
								$j=0;
								$k=0;
								for ($i=$image; $i<($image+$imageParPage); $i++)
								{
									if ( $j%$multiple == 0 )
									{
										echo '<tr>';
									}
									if (isset($media[$i]))
									{
										$nomFichier = substr($media[$i], 0, strrpos($media[$i], '.'));
										$extension = substr($media[$i], strrpos($media[$i], '.')+1);
										$miniName = $nomFichier.$this->thumbnailSufix.'.'.$extension;
										$checked = (isset($_POST['media'.$i]) && empty($this->delete))? 'checked="checked"': '';
										echo '<td><input type="checkbox" name="media'.$i.'" '.$checked.' />';
										echo '<img src="'.$path.'/'.$miniName.'" />';
										echo '</td>';
									}
									if ( ($j+1)%$multiple == 0 )
									{
										echo '</tr>';
									}
									$j++;
								}
								echo '</table>';
							}
							
							/********affichage du choix des pages********/
							if (count($media) > $imageParPage)
							{
								$pagesTotales=ceil(count($media)/$imageParPage);
								$pages=$pagesTotales;
								echo '<p class="pages" >';
								if ($pageActuelle!=1)
								{
									$pagePrec=$pageActuelle-1;
									echo '<a href="?page='.$page.'&cat='.$_GET['cat'].'&pager='.$pagePrec.'" title="page précédante"><</a>&nbsp&nbsp;';
								}
								echo '<a href="?page='.$page.'&cat='.$_GET['cat'].'&pager=1" title="première page">1..</a>&nbsp&nbsp;';
								$i=2;
								if ($pageActuelle<=5)
								{
									$i=2;
									if ($pages>9)
									{
										$pages=9;
									}
								}
								elseif ($pageActuelle>=($pagesTotales-4) and $pageActuelle>5)
								{
									if ($pagesTotales>=6)
									{
										$i=$pagesTotales-7;
									}
								}
								else
								{
									$i=$pageActuelle-3;
									$pages=$pageActuelle+3;
								}
								for ($i;$i<$pages;$i++)
								{
									echo '<a href="?page='.$page.'&cat='.$_GET['cat'].'&pager='.$i.'">'.$i.'</a>&nbsp&nbsp;';
								}
								if ($pagesTotales!=1)
								{
									echo '<a href="?page='.$page.'&cat='.$_GET['cat'].'&pager='.$pagesTotales.'" title="dernière page">..'.$pagesTotales.'</a>&nbsp&nbsp;';
								}
								if ($pageActuelle!=$pagesTotales)
								{
									$pageSuiv=$pageActuelle+1;
									echo '<a href="?page='.$page.'&cat='.$_GET['cat'].'&pager='.$pageSuiv.'" title="page suivante">></a>';
								}
								echo '</p>';
							}
							
							/********affichage bas du corps********/
							echo '<p class="center" >';
							if (!empty($media))
								echo '<input type ="submit" value="supprimer les photos sélectionnées" name="submit" />';
							echo '<span class="error" >'.$this->delete.'</span></p>';
							echo '</form>';
						}
					}
					break;
			}
		}
		
		
		
		function displayMenu($modules, $data) {
			if ($_SESSION['connect'])
			{
				$idMenu = $this->sanitize($data['menu']);
				echo '<li class="'.strtolower($this->sanitize($data['menu'])).'" ><span title="'.$data['menu'].'" >'.$data['menu'].'</span>';
				echo '<ul>';
				
				$dossier=opendir($this->rootDir);
				$i=0;
				while ($file=readdir($dossier))
				{
					if ($file != '.' && $file != '..' && is_dir($this->rootDir.'/'.$file))
					{
						$title = str_replace('_', ' ', $file);
						echo '<li><a href="?page='.$data['id'].'&cat='.$file.'" title="'.$title.'" >'.$title.'</a></li>';
					}
					$i++;
				}
				
				echo '</ul></li>';
				return true;
			}
			else
			{
				$idMenu = $this->sanitize($data['menu']);
				echo '<li class="'.strtolower($this->sanitize($data['menu'])).'" ><span title="'.$data['menu'].'" >'.$data['menu'].'</span>';
				echo '<ul>';
				
				$dossier=opendir($this->rootDir);
				$i=0;
				while ($file=readdir($dossier))
				{
					if ($file != '.' && $file != '..' && is_dir($this->rootDir.'/'.$file))
					{
						if ( substr($file, -strlen($this->privateSufix)) != $this->privateSufix )
						{
							$title = str_replace('_', ' ', $file);
							echo '<li><a href="?page='.$data['id'].'&cat='.$file.'" title="'.$title.'" >'.$title.'</a></li>';
						}
					}
					$i++;
				}
				
				echo '</ul></li>';
				return true;
			}
		}
		
		
		
		function displayMenuAdmin($page, $menu) {
			if ($_SESSION['connect'])
			{

?>
			<li><a href="?page=<?php echo $page; ?>&action=add_dir" >Gérer les catégories pour <?php echo $menu; ?></a></li>
<?php

			}
		}
		
		
		
		function redimensionnement($path, $filename, $extension) {
			$fond = imagecreatetruecolor(150, 150);
			$background = imagecolorallocate($fond, 0, 0, 0);
			imagefill($fond, 0, 0, $background);
			if ( $extension == 'jpg' )
				$endFunction = 'jpeg';
			else
				$endFunction = $extension;
			$functionName = 'imagecreatefrom'.$endFunction;
			$source = $functionName($path.'/'.$filename.'.'.$extension);
			$largeur_source = imagesx($source);
			$hauteur_source = imagesy($source);
			
			if($largeur_source>$hauteur_source)
			{
				$largeur_destination = 150;
				$hauteur_destination = ceil($largeur_destination*$hauteur_source/$largeur_source);
				$position_X=0;
				$position_Y=(150-$hauteur_destination)/2;
			}
			else
			{
				$hauteur_destination = 150;
				$largeur_destination = ceil($hauteur_destination*$largeur_source/$hauteur_source);
				$position_Y=0;
				$position_X=(150-$largeur_destination)/2;
			}
			$destination = imagecreatetruecolor($largeur_destination, $hauteur_destination);

			imagecopyresampled($destination, $source, 0, 0, 0, 0, $largeur_destination, $hauteur_destination, $largeur_source, $hauteur_source);
			imagecopy($fond, $destination, $position_X, $position_Y, 0, 0, $largeur_destination, $hauteur_destination);
			$nom= $path.'/'.$filename.$this->thumbnailSufix.'.'.$extension;
			$functionName = 'image'.$endFunction;
			$functionName($fond, $nom);
		}
		
	}