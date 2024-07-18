<?php

	class Calendar extends AbstractModule {
	
		var $tableauMois=array('janvier','f�vrier','mars','avril','mai','juin','juillet','ao�t','septembre','octobre','novembre','d�cembre');
		var $tableauJour=array('lundi','mardi','mercredi','jeudi','vendredi','samedi','dimanche');
		var $database;
		
		var $nameError;
		var $dateError;
		var $insert;
		var $delete;



    function __construct($databaseManager) {
      $this->databaseManager = $databaseManager;
    }
		
		
		
		function install($data) {
			$query = 'CREATE TABLE IF NOT EXISTS `'.$this->sanitize($data['database']).'` (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`nom` varchar(50) NOT NULL DEFAULT \'\',
				`date` date NOT NULL DEFAULT \'0000-00-00\',
				`description` text NOT NULL,
				`passage` time NOT NULL DEFAULT \'00:00:00\',
				`lieu` varchar(50) NOT NULL DEFAULT \'\',
				`adresse` text NOT NULL,
				`lien` varchar(100) NOT NULL DEFAULT \'\',
				PRIMARY KEY (`id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;';
			mysqli_query($this->databaseManager->mysqli, $query) OR DIE (mysqli_error());
		}
		
		
		
		function uninstall() {
			$query = 'DROP TABLE IF EXISTS `'.$this->database.'`;';
			mysqli_query($this->databaseManager->mysqli, $query) OR DIE (mysqli_error());
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
				'database' => array(
					'type' => 'text',
					'label' => htmlentities('Base de donn�es'),
				),
			);
		}
		
		
		
		function preProcessAdmin($page, $action) {
			switch($action)
			{
				case 'addEvent':
					$this->nameError = '';
					$this->dateError = '';
					
					$jour=1;
					$mois=1;
					$an=date('Y');
					$annee=$an;
					$heure=0;
					$minute=0;
					if (isset($_POST['submit']))
					{
						$jour = intval($_POST['jour']);
						$mois = intval($_POST['mois']);
						$annee = intval($_POST['annee']);
						$heure = intval($_POST['heure']);
						$minute = intval($_POST['minute']);
						if( !empty($_POST['nom']) )
						{
							$nom = htmlentities($_POST['nom'], ENT_QUOTES);
							$date = $annee.'-'.$mois.'-'.$jour;
							$reponse = mysqli_query($this->databaseManager->mysqli, 'SELECT date FROM '.$this->database);
							$i = 0;
							while ($donnees = mysqli_fetch_array($reponse))
							{
								$dateBase = explode('-',$donnees['date']);
								$anneeBase = $dateBase[0];
								$moisBase = $dateBase[1];
								$jourBase = $dateBase[2];
								if ($anneeBase == $annee && $moisBase==$mois && $jourBase==$jour)
									$i=1;
							}
							$description = mysqli_real_escape_string($_POST['description']);
							if (isset($_POST['horaire']))
							{
								$horaire = $_POST['horaire'];
								if ($horaire == 'on')
									$passage = '00:00:01';
							}
							else
							{
								$passage = $heure.':'.$minute.':00';
							}
							$lieu = mysqli_real_escape_string($_POST['lieu']);
							$adresse = mysqli_real_escape_string($_POST['adresse']);
							$lien = mysqli_real_escape_string($_POST['lien']);
							if ($i == 0)
							{
								mysqli_query($this->databaseManager->mysqli, 'INSERT INTO '.$this->database.' VALUES("", "'.$nom.'", "'.$date.'", "'.$description.'", "'.$passage.'", "'.$lieu.'", "'.$adresse.'", "'.$lien.'")');
								$this->insert = 'L\'�v�nement a bien �t� ajout�.';
							}
							else
							{
								$this->dateError = 'Un �v�nement � cette date existe d�j�.';
							}
						}
						else
						{
							$this->nameError = 'Veillez remplir ce champ.';
						}
					}
					break;
				
				case 'deleteEvent':
					if (isset($_POST['submit']))
					{
						$this->delete = '';
						
						$date = date('Y-m-d');
						$reponse=mysqli_query($this->databaseManager->mysqli, 'SELECT * FROM '.$this->database.' WHERE date >= "'.$date.'"');
						while ($data=mysqli_fetch_array($reponse))
						{
							$id = intval($data['id']);
							if ( isset($_POST['delete_'.$id]) )
							{
								mysqli_query($this->databaseManager->mysqli, 'DELETE FROM '.$this->database.' WHERE id = '.$id);
								$this->delete = 'Suppression(s) effectu�e(s).';
							}
						}
					}
					break;
			}
		}
		
		
		
		function displayPage($page, $action) {
			if ( $action == 'ajax' && isset($_POST['date']) )
			{
				$date = mysqli_real_escape_string($_POST['date']);
				$reponse = mysqli_query($this->databaseManager->mysqli, 'SELECT * FROM '.$this->database.' WHERE date="'.$date.'"');
				$donnees = mysqli_fetch_array($reponse);
				
				$evenement=$donnees['nom'];
				$descriptionEvenement = nl2br(htmlentities($donnees['description']));
				$lieuEvenement = htmlentities($donnees['lieu']);
				$adresseEvenement  =nl2br(htmlentities($donnees['adresse']));
				$lienEvenement = htmlentities($donnees['lien']);
				
				$date = explode("-", $_POST['date']);
				$annee = htmlentities($date[0]);
				$mois = htmlentities($date[1]);
				$jour = htmlentities($date[2]);
				
				$passageEvenement = explode(":", $donnees['passage']);
				$heure = htmlentities($passageEvenement[0]);
				$minute = htmlentities($passageEvenement[1]);
				$seconde = htmlentities($passageEvenement[2]);
				
				echo '<h3>'.$evenement.'</h3>';
				
				if ($descriptionEvenement != '')
				{
					echo '<strong>Description : </strong>';
					echo '<p>'.$descriptionEvenement.'</p>';
				}
					
				if ($lieuEvenement != '')
				{
					echo '<strong>Lieu : </strong>';
					echo '<p>'.$lieuEvenement.'</p>';
				}
					
				if ($adresseEvenement != '')
				{
					echo '<strong>Adresse : </strong>';
					echo '<p>'.$adresseEvenement.'</p>';
				}
					
				if ($lienEvenement != '')
				{
					echo '<strong>Lien : </strong>';
					echo '<p><a href="'.$lienEvenement.'">'.$lienEvenement.'</a></p>';
				}
				
				if ($seconde==00)
				{
					echo '<strong>Horaire de passage : </strong>';
					echo $heure.'H'.$minute;
				}
			}
			else
			{
				echo '<table>';
				
				/* initialisation des variables annees, mois correpondant */
				$annee=date('Y');
				$premierMois = date('m');
				if (isset($_GET['mois']))
				{
					$moisActuel=$_GET['mois'];
					if ($moisActuel<$premierMois)
						$annee++;
				}
				else
				{
					$moisActuel=$premierMois;
				}
				$timestamp=mktime(0,0,0,$moisActuel,1,$annee);
				$premierJour=date('w',$timestamp);
				if ($premierJour==0)
					$premierJour=7;
				$bissextile=date('L');
				
				/* Calcul du Lundi de P�ques, Jeudi de l'Ascension et Lundi de Pentec�te */
				$n=(int)$annee-1900;
				$a=$this->divisionEuclidienne($n, 19, 1);
				$x=$a*7+1;
				$b=$this->divisionEuclidienne($x, 19, 0);
				$y=(11*$a)-$b+4;
				$c=$this->divisionEuclidienne($y, 29, 1);
				$d=$this->divisionEuclidienne($n, 4, 0);
				$z=$n-$c+$d+31;
				$e=$this->divisionEuclidienne($z, 7, 1);
				$paques=25-$c-$e+1;
				$ascension=$paques+38;
				$pentecote=$paques+49;
				if ($paques<=0)
				{
					$jourPaques=31+$paques;
					$moisPaques=3;
				}
				else
				{
					$jourPaques=$paques;
					$moisPaques=4;
				}
				if ($ascension<=30)
				{
					$jourAscension=$ascension;
					$moisAscension=4;
				}
				else
				{
					$jourAscension=$ascension-30;
					$moisAscension=5;
				}
				if ($pentecote<=61)
				{
					$jourPentecote=$pentecote-30;
					$moisPentecote=5;
				}
				else
				{
					$jourPentecote=$pentecote-61;
					$moisPentecote=6;
				}
				
				/* on r�cup�re les �v�nements du mois dans la base  */
				$date1=$annee.'-'.$moisActuel.'-01';
				$date2=$annee.'-'.$moisActuel.'-31';
				$reponse=mysqli_query($this->databaseManager->mysqli, 'SELECT * FROM '.$this->database.' WHERE date >= "'.$date1.'" AND date <="'.$date2.'"');
				$nbEvenement=0;
				$jourEvenement=array('');
				$evenement=array('');
				while ($donnees=mysqli_fetch_array($reponse))
				{
					$dateEvenement=explode('-',$donnees['date']);
					$jourEvenement[$nbEvenement]=$dateEvenement[2];
					$evenement[$nbEvenement]=$donnees['nom'];
					$nbEvenement++;
				}
				
				/* on affiche le tableau */
				if ($moisActuel==4 || $moisActuel==6 || $moisActuel==9 || $moisActuel==11)
				{
					$jourMax=30;
				}
				elseif ($moisActuel==2)
				{
					if ($bissextile)
						$jourMax=29;
					else
						$jourMax=28;
				}
				else
				{
					$jourMax=31;
				}
				if (($moisActuel)==1)
					$moisPrecedant=12;
				else
					$moisPrecedant=($moisActuel-1);
				if (($moisActuel)==12)
					$moisSuivant=1;
				else
					$moisSuivant=($moisActuel+1);
				echo '<caption>';
				if ($moisActuel!=$premierMois)
					echo '<a href="?page='.$page.'&mois='.$moisPrecedant.'" class="left" title="mois pr�c�dent" ><<</a>';
				else
					echo '<span class="left" >&nbsp;&nbsp;</span>';
				if ($moisActuel!=($premierMois-1))
					echo '<a href="?page='.$page.'&mois='.$moisSuivant.'" class="right" title="mois suivant" >>></a>';
				else
					echo '<span class="right" >&nbsp;&nbsp;</span>';
				echo '<div onClick="calendar()" >';
				echo '<span>'.$this->tableauMois[$moisActuel-1].' '.$annee.'</span>';
				echo '<ul id="months" >';
				for ($i=intval($premierMois); $i<($premierMois+12); $i++)
				{
					$moisList=$i;
					$anneeList = $annee;
					if ($i>12)
					{
						$moisList=$i-12;
						$anneeList = $annee+1;
					}
					echo '<li><a href="?page='.$page.'&mois='.$moisList.'" title="'.$this->tableauMois[$moisList-1].' '.$anneeList.'" >';
					echo $this->tableauMois[$moisList-1].' '.$anneeList;
					echo '</a></li>';
				}
				echo '</ul>';
				echo '</div>';
				echo '</caption>';
				echo '<thead>';
				echo '<tr>';
				for ($i=0;$i<7;$i++)
				{
					if ($i==5 || $i==6)
						echo '<td class="ferie">'.$this->tableauJour[$i].'</td>';
					else
						echo '<td>'.$this->tableauJour[$i].'</td>';
				}
				echo '</tr>';
				echo '</thead>';
				echo '<tbody>';
				$jour=1;
				$j=1;
				while ($jour<=$jourMax)
				{
					echo '<tr>';
					for ($i=1;$i<=7;$i++)
					{
						$positif=$i-$premierJour+10*$jour-10;
						if ($positif<0 || $jour>$jourMax)
							echo '<td class="none">';
						if (($i>=$premierJour || $j>=2) && $jour<=$jourMax)
						{
							$event=false;
							for ($n=0; $n<$nbEvenement; $n++)
							{
								if ($jour==$jourEvenement[$n])
								{
									$event=true;
									break;
								}
							}
							if ($event)
							{
								echo '<td onClick="ajax(this)" name="'.$annee.'-'.$moisActuel.'-'.$jour.'|'.$page.'" title="'.$evenement[$n].'" class="event" >'.$jour.'</td>';
							}
							else
							{
								
								if ($moisActuel==1 && $jour==1)									//1er janvier -> nouvel an
								{
									echo '<td class="ferie">Nouvel an</td>';
								}
								elseif ($moisActuel==5 && $jour==1)								//1er mai -> f�te du travail
								{
									echo '<td class="ferie">F�te du travail</td>';
								}
								elseif ($moisActuel==5 && $jour==8)								//8 mai -> armistice WWII
								{
									echo '<td class="ferie">Armistice WWII</td>';
								}
								elseif ($moisActuel==7 && $jour==14)							//14 juillet -> f�te nationale
								{
									echo '<td class="ferie">F�te nationale</td>';
								}
								elseif ($moisActuel==11 && $jour==11)							//11 novembre -> armistice WWI
								{
									echo '<td class="ferie">Armistice WWI</td>';
								}
								elseif ($moisActuel==12 && $jour==25)							//25 d�cembre -> no�l
								{
									echo '<td class="ferie">No�l</td>';
								}
								elseif ($moisActuel==8 && $jour==15)							//15 ao�t -> assomption
								{
									echo '<td class="ferie">Assomption</td>';
								}
								elseif ($moisActuel==11 && $jour==1)							//1er novembre -> toussaint
								{
									echo '<td class="ferie">Toussaint</td>';
								}
								elseif ($moisActuel==$moisPaques && $jour==$jourPaques)			//Lundi de P�ques
								{
									echo '<td class="ferie">Lundi de P�ques</td>';
								}
								elseif ($moisActuel==$moisAscension && $jour==$jourAscension)	//Jeudi de l'Ascension
								{
									echo '<td class="ferie">Jeudi de l\'Ascension</td>';
								}
								elseif ($moisActuel==$moisPentecote && $jour==$jourPentecote)	//Lundi de Pentec�te
								{
									echo '<td class="ferie">Lundi de Pentec�te</td>';
								}
								elseif ($i==6 || $i==7)
								{
									echo '<td class="ferie">'.$jour.'</td>';
								}
								else
								{
									echo '<td>';
									echo $jour;
								}
							}
							$jour++;
						}
					}
					echo '</tr>';
					$j++;
				}
				echo '</tbody>';

?>
				</table>
				<div id="event">
				</div>
<?php

			}
		}
		
		
		
		function displayAdmin($page, $action) {

?>
			<form method="post" action="?page=<?php echo $page; ?>&action=addEvent">
				<fieldset>
					<legend>Ajouter un �v�nement</legend>
					<p>
						<label for="event-nom">
							<strong>
								nom (*) :
								<span class="erreur"><?php echo $this->nameError; ?></span>
							</strong>
						</label><br/>
						<input type="text" name="nom" id="event-nom" class="inputText" value="<?php echo (isset($_POST['nom']) && empty($this->insert))? $_POST['nom']: ''; ?>"/>
					</p>
					<p>
						<label for="event-description"><strong>description : </strong></label><br/>
						<textarea name="description" id="event-description" ><?php echo (isset($_POST['description']) && empty($this->insert))? $_POST['description']: ''; ?></textarea>
					</p>
					<p>
						<strong>
							date (*) :
							<span class="erreur"><?php echo $this->dateError; ?></span>
						</strong><br/>
<?php

			$jour=1;
			$mois=1;
			$an=date('Y');
			$annee=$an;
			echo '<label for="jour">jour : </label>';
			echo '<select name="jour">';
			for ($i=1;$i<=31;$i++)
			{
				if ($jour==$i && !empty($this->insert))
					echo '<option selected="selected" value="'.$i.'">'.$i.'</option>';
				else
					echo '<option value="'.$i.'">'.$i.'</option>';
			}
			echo '</select>';
			
			echo '<label for="mois"> mois : </label>';
			echo '<select name="mois">';
			for ($i=1;$i<=12;$i++)
			{
				if ($mois==$i && !empty($this->insert))
					echo '<option selected="selected" value="'.$i.'">'.$this->tableauMois[$i-1].'</option>';
				else
					echo 't<option value="'.$i.'">'.$this->tableauMois[$i-1].'</option>';
			}
			echo '</select>';
			
			echo '<label for="annee"> annee : </label>';
			echo '<select name="annee">';
			for ($i=0;$i<=2;$i++)
			{
				if ($annee==($an+$i) && !empty($this->insert))
					echo '<option selected="selected" value="'.($an+$i).'">'.($an+$i).'</option>';
				else
					echo '<option value="'.($an+$i).'">'.($an+$i).'</option>';
			}
			echo '</select>'

?>
					</p>
					<p>
						<strong>horaire de passage (**) : </strong><br/>
<?php

			$heure=0;
			$minute=0;
			echo '<label for="heure">heure : </label>';
			echo '<select name="heure">';
			for ($i=0;$i<=23;$i++)
			{
				if ($heure==$i && !empty($this->insert))
					echo '<option selected="selected" value="'.$i.'">'.$i.'</option>';
				else
					echo '<option value="'.$i.'">'.$i.'</option>';
			}
			echo '</select>';
			
			echo '<label for="minute"> minute : </label>';
			echo '<select name="minute">';
			for ($i=0;$i<=59;$i++)
			{
				if ($minute==$i && !empty($this->insert))
					echo '<option selected="selected" value="'.$i.'">'.$i.'</option>';
				else
					echo '<option value="'.$i.'">'.$i.'</option>';
			}
			echo '</select>';

?>
					</p>
					<p>
						<input type="checkbox" name="horaire" id="event-horaire" <?php echo (isset($_POST['horaire']) && empty($this->insert))? 'checked="checked"': ''; ?> />
						<label for="event-horaire">Ne pas renseigner l'horaire de passage</label>
					</p>
					<p>
						<label for="event-lieu"><strong>lieu : </strong></label><br/>
						<input type="text" name="lieu" id="event-lieu" class="inputText" value="<?php echo (isset($_POST['lieu']) && empty($this->insert))? $_POST['lieu']: ''; ?>" />
					</p>
					<p>
						<label for="event-adresse"><strong>adresse : </strong></label><br/>
						<textarea name="adresse" id="event-adresse" ><?php echo (isset($_POST['adresse']) && empty($this->insert))? $_POST['adresse']: ''; ?></textarea>
					</p>
					<p>
						<label for="event-lien"><strong>lien : </strong></label><br/>
						<input type="text" name="lien" id="event-lien" class="inputText" value="<?php echo (isset($_POST['lien']) && empty($this->insert))? $_POST['lien']: ''; ?>"/>
					</p>
					<p>
						<input type="submit" name="submit" />
						<span class="erreur"><?php echo $this->insert; ?></span>
					</p>
					<p class="petit">
						Les champs suivis de (*) doivent obligatoirement �tre renseign�s.<br/>
						/!\ En cas d'oubli, la date est automatiquement fix� par d�fault au 1er janvier de l'ann�e en cours.<br/>
						Le champ suivi de (**) peut ne pas �tre renseign� en cochant la case "Ne pas renseigner l'horaire de passage".<br/>
						/!\ En cas d'oubli, l'heure de passage est automatiquement fix� par d�fault � 00H00.
					</p>
				</fieldset>
			</form>

			<form method="post" action="?page=<?php echo $page; ?>&action=deleteEvent">
				<fieldset>
					<legend>Supprimer un �v�nement</legend>
					<ul>
<?php

			$date = date('Y-m-d');
			$reponse=mysqli_query($this->databaseManager->mysqli, 'SELECT * FROM '.$this->database.' WHERE date >= "'.$date.'"');
			while ($donnees=mysqli_fetch_array($reponse))
			{
				$date = explode('-', $donnees['date']);
				echo '<li><input type="checkbox" name="delete_'.$donnees['id'].'" id="event-'.$donnees['id'].'" /><label for="event-'.$donnees['id'].'" > '.$donnees['nom'].' ('.$date[2].'/'.$date[1].'/'.$date[0].')</label></li>';
			}

?>
					</ul>
					<p>
						<input type="submit" name="submit" />
						<span class="erreur"><?php echo $this->delete; ?></span>
					</p>
				</fieldset>
			</form>
<?php

		}
		
		
		
		function divisionEuclidienne($reste, $diviseur, $i)
		{
			$quotient=0;
			while ($reste>=$diviseur)
			{
				$reste=$reste-$diviseur;
				$quotient++;
			}
			if ($i==0)
				return $quotient;
			else
				return $reste;
		}
		
	}