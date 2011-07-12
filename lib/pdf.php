<?php
	require_once 'lib/fpdf.php';
	
	class PDF extends FPDF
	{
		//En-tête
		function Header()
		{
			if (isset($this->header))
			{
			    //Logo
			    $this->Image('gui/images/logo_pdf.jpg',5,5,50);
			    //Police Arial gras 15
			    $this->SetFont('Arial','B',20);
			    //Décalage à droite
			    $this->Cell(55);
			    //Titre
			    $this->Write(25, $this->titre);
			     //Barcode ?
			     if (isset($this->barcode))
			     {
			     	$this->SetFont('Arial','B',12);
			     	$this->EAN13(250,10,$this->barcode);
			     }
				//Ligne de séparation
			    $this->Line(5,35,$this->w - 5,35);
			    //Saut de ligne
			    $this->Ln(35);
			}
		}
		
		//Pied de page
		function Footer()
		{
			if (isset($this->footer))
			{
			    //Positionnement à 1,5 cm du bas
			    $this->SetY(-15);
			    //Police Arial italique 8
			    $this->SetFont('Arial','I',8);
			    //Numéro de page
			    $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
			}
		}
		
		function Cell_dec()
		{	
			
		}
		
		function BasicTable($header,$data)
		{
		    //En-tête
		    foreach($header as $col)
		        $this->Cell(40,7,$col,1);
		    $this->Ln();
		    //Données
		    foreach($data as $row)
		    {
		        foreach($row as $col)
		            $this->Cell(40,6,$col,1);
		        $this->Ln();
		    }
		}
		
	
		//Tableau amélioré
		function ImprovedTable($header,$data,$header2)
		{
		    //Largeurs des colonnes
		    $w=array(40,35,45,40,30,40);
		    //print_r($w);
		    //En-tête
		    for($i=0;$i<count($header);$i++)
		    {	
		    	$this->Cell($w[$i],7,$header[$i],1,0,'C');
		    }
		        
		    $this->Ln();
		    //Données
		    foreach($data as $row)
		    {
		        $this->Cell($w[0],6,$row[$header2[0]],'LR');
		        $this->Cell($w[1],6,$row[$header2[1]],'LR');
		        $this->Cell($w[2],6,$row[$header2[2]],'LR');
		        $this->Cell($w[3],6,$row[$header2[3]],'LR');
		        $this->Cell($w[4],6,$row[$header2[4]],'LR');
		        $this->Cell($w[5],6,$row[$header2[5]],'LR');
		        $this->Ln();
		    }
		    //Trait de terminaison
		    $this->Cell(array_sum($w),0,'','T');
		}
		
	//Tableau coloré
		function FancyTable($header,$data,$header2,$w)
		{
		    //Couleurs, épaisseur du trait et police grasse
		    $this->SetFillColor(255,0,0);
		    $this->SetTextColor(255);
		    $this->SetDrawColor(128,0,0);
		    $this->SetLineWidth(.3);
		    $this->SetFont('','B');
		    //En-tête
		    for($i=0;$i<count($header);$i++)
		        $this->Cell($w[$i],7,$header[$i],1,0,'C',1);
		    $this->Ln();
		    //Restauration des couleurs et de la police
		    $this->SetFillColor(224,235,255);
		    $this->SetTextColor(0);
		    $this->SetFont('');
		    //Données
		    $fill=false;
		    foreach($data as $row)
		    {
		    	for($i=0;$i<count($header);$i++)
		    	{
		    		if (isset($this->cellule_haute))
		    			$this->Cell($w[$i],15,$row[$header2[$i]],'LR',0,'L',$fill);
		    		else
		    			$this->Cell($w[$i],6,$row[$header2[$i]],'LR',0,'L',$fill);
		    	}
		        $this->Ln();
		        $fill=!$fill;
		    }
		    $this->Cell(array_sum($w),0,'','T');
		}
	}
?>