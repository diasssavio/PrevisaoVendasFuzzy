<?php
 
function getIndexMes($index,$janela){
	while($index > $janela ){
		$index = $index - $janela;
	}
	return $index + 1;
}


function getMesExtenso($mes){
	switch ($mes) {
		case 1:
			return 'Janeiro';
			break;
		case 2:
			return 'Fevereiro';
			break;
		case 3:
			return 'Março';
			break;
		case 4:
			return 'Abril';
			break;
		case 5:
			return 'Maio';
			break;
		case 6:
			return 'Junho';
			break;
		case 7:
			return 'Julho';
			break;
		case 8:
			return 'Agosto';
			break;
		case 9:
			return 'Setembro';
			break;
		case 10:
			return 'Outubro';
			break;
		case 11:
			return 'Novembro';
			break;
		case 12:
			return 'Dezembro';
			break;
		case 13:
			return 'Consequente';
			break;
		default:
			return 'Invalido';
			break;
	}
}

function TRI($x,$e,$f,$g){
	if($x <= $e){
		return 0;
	}
	if($x <= $f) {
		return (1 - ($f-$x)/($f-$e));
	}
	if($x <= $g) {
		return (($g - $x) / ($g - $f));
	}
	return 0;
}

function defineIntervalosTRI($lInferior,$lSuperior,$nConjuntos){
	$aux = array();
	$nTempConjuntos = 0;
	$lIntervalo = $lSuperior - $lInferior;
	while(floor($nConjuntos/2) != $nTempConjuntos){
		$lIntervalo = $lIntervalo /  2;
		$nTempConjuntos++;
	}
	for($i = 0;$i< $nConjuntos;$i++){
		$aux[] =(float)( $lInferior + $lIntervalo * $i);
	}
	return $aux;
}