<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of PrevisaoVendasFuzzy
 *
 * @author igs
 */
class PrevisaoVendasFuzzy {

    public function __construct($tamanhoJanela, $limInferior, $limSuperior, $rotulos)
    {
        $this->tamanhoJanela = $tamanhoJanela;
        $this->limInferior = $limInferior;
        $this->limSuperior = $limSuperior;
        $this->rotulos = $rotulos;
        $this->nRotulos = count($rotulos);
        $this->defineIntervalosTRI();
    }

    public function fuzzificar($entradas, $posEntrada = 0)
    {
        $posJanelaMeses = 0;
        $novaRegra = array();
        $tamJanela = count($entradas);
        //CRIA REGRA
        for ($i = $posEntrada; $i < $posEntrada + $tamJanela; $i++) {
            $x = $entradas[$posJanelaMeses];
            $posJanelaMeses++;
            $temp = array();
            //GERA O VALOR PARA CADA RÓTULO DE PERTINÊNCIA
            for ($j = 0; $j < $this->nRotulos; $j++) {
                $f = $this->intervalosDefinidos[$j];
                $e = ($j - 1) < 0 ? $this->limInferior - 1 : $this->intervalosDefinidos[$j - 1];
                $g = ($j + 1) > $this->nRotulos ? $this->limSuperior + 1 : $this->intervalosDefinidos[$j + 1];
                $temp[$this->rotulos[$j]] = $this->TRI($x, $e, $f, $g);
            }
            $nI = $this->getIndexMes($i, $tamJanela);
            $novaRegra[$this->getMesExtenso($nI) . $nI] = $temp;
        }

        //É ADICIONADA O GRAU DE PERTINENCIA DA REGRA
        //ATRAVÉS DO PRODUTO DOS MAIORES VALORES OBTIDOS EM CADA CONJUNTO DE CADA MES
        $novaRegra['grau'] = $this->calculaGrauRegra($novaRegra);
        return $novaRegra;
    }

    public function ativarRegras($regras)
    {
        $regrasAtivadas = array();
        foreach ($regras as $regra) {
            $regraRotulos = '';
            foreach ($regra as $value) {
                $regraRotulos .= $value;
            }

            foreach ($this->baseRegras as $baseRegra) {
                $baseRegraRotulos = '';
                foreach ($baseRegra as $key => $value) {
                    if ($key != 12) {//Não precisa do consequente
                        $baseRegraRotulos .= $value['chave'];
                    }
                }
                if($regraRotulos == $baseRegraRotulos){
                    $regrasAtivadas[] = $baseRegra;
                }
            }
        }
        return $regrasAtivadas;
    }

    public function eliminarValoresZerados($entrada)
    {
        $saida = array();
        $contador = 0;
        foreach ($entrada as $key => $value) {

            foreach ($value as $key2 => $value2) {
                if (!empty($value2)) {
//                    $saida[$key][$key2] = $value2;
                    $saida[$contador][] = $key2;
                }
            }
            $contador++;
        }
        return $saida;
    }

    public function gerarBaseRegras($entradasTreino = array())
    {
        error_reporting(E_ALL);
        if (empty($entradasTreino)) {
            return FALSE;
        }
        $nEntradas = count($entradasTreino);
        for ($posEntrada = 0; ($posEntrada + $this->tamanhoJanela - 1) < $nEntradas; $posEntrada++) {
            $janelaMeses = array_slice($entradasTreino, $posEntrada, $this->tamanhoJanela);
            $novaRegra = $this->fuzzificar($janelaMeses, $posEntrada);
            $this->regrasPasso3[] = $novaRegra;
            $this->baseRegras[] = $this->getChaveValorRegra($novaRegra);
            //ATÉ O MOMENTO, JÁ FOI OBTIDO OS VALORES DE CADA INTERVALO DE CADA MES(12 MESES + CONSEQUENTE)
            //INCLUSIVE O GRAU DE PERTINENCIA DA REGRA(APENAS COM OS MAIORES VALORES DE CADA CONJUNTO)
        }
        $regrasOrdenadas = $this->ordenarRegras($this->baseRegras);
        unset($this->baseRegras);
        $this->baseRegras = $this->eliminarRedundancia($regrasOrdenadas);
        return TRUE;
    }

    public function gerarProdutoCartesiano($entradaFuzzificada)
    {
        $saida = array();
        for ($a = 0; $a < 2; $a++) {
            for ($b = 0; $b < 2; $b++) {
                for ($c = 0; $c < 2; $c++) {
                    for ($d = 0; $d < 2; $d++) {
                        for ($e = 0; $e < 2; $e++) {
                            for ($f = 0; $f < 2; $f++) {
                                for ($g = 0; $g < 2; $g++) {
                                    for ($h = 0; $h < 2; $h++) {
                                        for ($i = 0; $i < 2; $i++) {
                                            for ($j = 0; $j < 2; $j++) {
                                                for ($k = 0; $k < 2; $k++) {
                                                    for ($l = 0; $l < 2; $l++) {
                                                        $temp = array();
                                                        $temp[0] = $entradaFuzzificada[0][$a];
                                                        $temp[1] = $entradaFuzzificada[1][$b];
                                                        $temp[2] = $entradaFuzzificada[2][$c];
                                                        $temp[3] = $entradaFuzzificada[3][$d];
                                                        $temp[4] = $entradaFuzzificada[4][$e];
                                                        $temp[5] = $entradaFuzzificada[5][$f];
                                                        $temp[6] = $entradaFuzzificada[6][$g];
                                                        $temp[7] = $entradaFuzzificada[7][$h];
                                                        $temp[8] = $entradaFuzzificada[8][$i];
                                                        $temp[9] = $entradaFuzzificada[9][$j];
                                                        $temp[10] = $entradaFuzzificada[10][$k];
                                                        $temp[11] = $entradaFuzzificada[11][$l];
                                                        $saida[] = $temp;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $saida;
    }

    public function eliminarRedundancia($baseRegras)
    {
        $nBaseRegras = count($baseRegras);
        for ($i = 0; $i < $nBaseRegras; $i++) {
            $atual = '';
            $proximo = '';
            for ($k = 0; $k < 12; $k++) {
                $proximo .= $baseRegras[$i + 1][$k]['chave'][1];
                $atual .= $baseRegras[$i][$k]['chave'][1];
            }
            if ($proximo == $atual) {
                $grauAtual = 1.0;
                foreach ($baseRegras[$i] as $value) {
                    $grauAtual *= $value['valor'];
                }
                $grauProximo = 1.0;
                foreach ($baseRegras[$i + 1] as $value) {
                    $grauProximo *= $value['valor'];
                }

                if ($grauAtual > $grauProximo) {
                    $aux = null;
                    $aux = $baseRegras[$i];
                    $baseRegras[$i] = null;

                    $baseRegras[$i] = $baseRegras[$i + 1];
                    $baseRegras[$i + 1] = null;
                    $baseRegras[$i + 1] = $aux;
                    unset($baseRegras[$i]);
                } else {
                    unset($baseRegras[$i]);
                }
            }
        }
        return $baseRegras;
    }

    public function ordenarRegras($baseRegras)
    {
        $nBaseRegras = count($baseRegras);
        #bubble sort para Ordenar
        for ($i = 0; $i < $nBaseRegras; $i++) {
            for ($j = 0; $j < $nBaseRegras - 1; $j++) {
                $atual = '';
                $proximo = '';
                for ($k = 0; $k < 12; $k++) {
                    $proximo .= $baseRegras[$j + 1][$k]['chave'][1];
                    $atual .= $baseRegras[$j][$k]['chave'][1];
                }
                if ($proximo < $atual) {
                    $aux = NULL;
                    $aux = $baseRegras[$j + 1];
                    $baseRegras[$j + 1] = $baseRegras[$j];
                    $baseRegras[$j] = NULL;
                    $baseRegras[$j] = $aux;
                }
            }
        }
        return $baseRegras;
    }

    public function getChaveValorRegra($regra)
    {
        $maiorValorMes = array();
        $Consequente13 = array();
        foreach ($regra as $key => $mes) {
            if ($key != 'grau') {
                $valorMax = max($mes);
                $chaveMax = '';
                foreach ($mes as $key2 => $value) {
                    if ($valorMax == $value) {
                        $chaveMax = $key2;
                        break;
                    }
                }
                if ($key === 'Consequente13') {
                    $Consequente13 = array('chave' => $chaveMax, 'valor' => $valorMax);
                } else {
                    $maiorValorMes[] = array('chave' => $chaveMax, 'valor' => $valorMax);
                }
            }
        }
        if (!empty($Consequente13)) {
            $maiorValorMes[] = $Consequente13;
        }
        return $maiorValorMes;
    }

    public function calculaGrauRegra($regra)
    {
        $grauRegra = 1.0;//Número neutro para multiplicação
        foreach ($regra as $mes) {
            $maiorGrauPertinenciaValor = 0.0;
            foreach ($mes as $valorRotulo) {
                if ($valorRotulo > $maiorGrauPertinenciaValor) {
                    $maiorGrauPertinenciaValor = $valorRotulo;
                }
            }
            $grauRegra = $grauRegra * $maiorGrauPertinenciaValor;
        }
        return $grauRegra;
    }

    //DEFINE OS INTERVALOS DE CADA RÓTULO
    //PASSO 1
    public function defineIntervalosTRI()
    {
        $this->intervalosDefinidos = array();
        $contadorDeConjuntos = 0;
        $difLimites = $this->limSuperior - $this->limInferior;
        while (floor($this->nRotulos / 2) != $contadorDeConjuntos) {
            $difLimites = $difLimites / 2;
            $contadorDeConjuntos++;
        }
        for ($i = 0; $i < $this->nRotulos; $i++) {
            $this->intervalosDefinidos[] = (float)( $this->limInferior + $difLimites * $i);
        }
    }

    public function TRI($x, $e, $f, $g)
    {
        if ($x <= $e) {
            return 0;
        }
        if ($x <= $f) {
            return (1 - ($f - $x) / ($f - $e));
        }
        if ($x <= $g) {
            return (($g - $x) / ($g - $f));
        }
        return 0;
    }

    public function getIndexMes($index, $janela)
    {

        while ($index > $janela - 1) {
            $index = $index - $janela;
        }
        return $index + 1;
    }

    public function getMesExtenso($mes)
    {
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

}