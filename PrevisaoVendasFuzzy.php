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
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    session_start();
    switch ($_POST['operacao']) {
        case 'buscarResultado':
            if (!isset($_SESSION['fuzzy'])) {
                $rotulos = array('a0', 'a1', 'a2', 'a3', 'a4');
                list($limInferior, $limSuperior, $tamanhoJanela) = array(-34.16, 42.72, 13);
                $fuzzy = new PrevisaoVendasFuzzy($tamanhoJanela, $limInferior, $limSuperior, $rotulos);
                $_SESSION['fuzzy'] = $fuzzy;
                $entradasTreino = array(
                    /* 2004 */-27.75, 1.47, 4.29, 6.51, -4.22, -2.79, 7.76, -1.24, -1.45, 7.99, -3.24, 36.31,
                    /* 2005 */-25.97, -4.22, 16.09, -8.23, -0.35, -3.85, 6.63, -3.3, -2.1, 7.13, -3.01, 35.6,
                    /* 2006 */-25.38, -3.99, 6.14, 5.23, -8.54, -2.07, 4.78, -1.12, 1.12, 2.32, 1.18, 31.79,
                    /* 2007 */-23.29, -2.41, 10.79, -0.77, -4.51, -0.9, 1.56, 2.37, 0.47, 1.63, 1.88, 33.56,
                    /* 2008 */-22.84, -2.35, 16.76, -12.13, 8.66, -5.65, 4.4, 3.57, -5.45, 7.22, 1.19, 27.86,
                    /* 2009 */-22.59, -4.12, 6.68, 7.2, -3.67, -5.25, 5.92, 0.95, -3.77, 8.33, -2.24, 31.68,
                    /* 2010 */-20.96, -5.54, 10.74, -3.28, 0.46, -4.59, 4.21, -1.37, -0.11, 7.82, -4.43, 34.71
                );
                $fuzzy->gerarBaseRegras($entradasTreino);
            }
            $fuzzy = $_SESSION['fuzzy'];
            $entrada = array(-20.96, -5.54, 10.74, -3.28, 0.46, -4.59, 4.21, -1.37, -0.11, 7.82, -4.43, 34.71);

            $todasRegrasPossiveis = $fuzzy->gerarProdutoCartesiano($fuzzy->eliminarValoresZerados($fuzzy->fuzzificar($entrada), TRUE));
            $regrasAtivadas = $fuzzy->ativarRegras($todasRegrasPossiveis);
            $minimo = $fuzzy->aplicarOperadorFuzzy($regrasAtivadas);
            echo json_encode(array('success' => TRUE, 'resultado' => $minimo));
            break;
//            if ($fuzzy->gerarBaseRegras($entradasTreino)) {
//                $entrada = array(-20.96, -5.54, 10.74, -3.28, 0.46, -4.59, 4.21, -1.37, -0.11, 7.82, -4.43, 34.71);
//                $todasRegrasPossiveis = $fuzzy->gerarProdutoCartesiano($fuzzy->eliminarValoresZerados($fuzzy->fuzzificar($entrada)));
//                $regrasAtivadas = $fuzzy->ativarRegras($todasRegrasPossiveis);
//                echo "regras ativadas";
//                echo "<br><br>";
//                var_dump(count($regrasAtivadas));
//                echo "<br><br>";
//                var_dump($regrasAtivadas);
//                echo json_encode(array('success' => TRUE));
//                if (!isset($_SESSION['fuzzy'])) {
//                    $_SESSION['fuzzy'] = $fuzzy;
//                }
//            } else {
//                echo json_encode(array('success' => FALSE));
//            }
////
//            echo json_encode(array('success' => TRUE));
            break;
        default:
            $entradasTeste = array(
                /* 2011 */-20.49, -6.35, 10.14, 8, -10.92, -2.49, 6.41, -1.84, -0.22, 5.16, -1.84, 30.66,
                /* 2012 */-18.91, 0.27, 7.54, -1.37, -2.37, -5.39, -0.08, 2.12, 0.79, 2.80, 2.13, 29.73
            );
            break;
    }
}

class PrevisaoVendasFuzzy {

    public function __construct($tamanhoJanela, $limInferior, $limSuperior, $rotulos)
    {
        $this->tamanhoJanela = $tamanhoJanela;
        $this->limInferior = $limInferior;
        $this->limSuperior = $limSuperior;
        $this->rotulos = $rotulos;
        $this->nRotulos = count($rotulos);
        $this->intervalosDefinidos = 0;
        $this->baseRegras = array();
        $this->regrasPasso3 = array();
        $this->defineIntervalosTRI();
    }

    public function getSinonimoRotulo($rotulo)
    {
        $sinonimos = array(
            'a0' => 'Muito Baixo',
            'a1' => 'Baixo',
            'a2' => 'Médio',
            'a3' => 'Alto',
            'a4' => 'Muito Alto'
        );
        return $sinonimos[$rotulo];
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

    public function aplicarOperadorFuzzy($regrasAtivadas)
    {
        $valores = array();
        foreach ($regrasAtivadas as $regra) {
            $minimo = array();
            $minimo['valor'] = 1;
            $minimo['chave'] = 1;
            foreach ($regra as $value) {
                foreach ($value as $key2 => $value2) {
                    if ($value2 < $minimo['valor']) {
                        $minimo['chave'] = $this->getSinonimoRotulo($key2);
                        $minimo['valor'] = $value2;
                    }
                }
            }
            $valores[] = $minimo;
        }
        return $valores;
    }

    public function ativarRegras($regras)
    {
        $regrasAtivadas = array();
        foreach ($regras as $regra) {
            $regraRotulos = '';
            foreach ($regra as $value) {
                foreach ($value as $key2 => $value2) {
                    $regraRotulos .= $key2;
                }
            }
            foreach ($this->baseRegras as $baseRegra) {
                $baseRegraRotulos = '';
                foreach ($baseRegra as $key => $value) {
                    if ($key != 12) {//Não precisa do consequente
                        $baseRegraRotulos .= $value['chave'];
                    }
                }
                if ($regraRotulos == $baseRegraRotulos) {
                    $regrasAtivadas[] = $regra;
                }
            }
        }
        return $regrasAtivadas;
    }

    public function eliminarValoresZerados($entrada, $preservarChaves = FALSE)
    {
        $saida = array();
        $contador = 0;
        if (!$preservarChaves) {
            foreach ($entrada as $value) {
                foreach ($value as $key2 => $value2) {
                    if (!empty($value2)) {
                        $saida[$contador][] = $key2;
                    }
                }
                $contador++;
            }
        } else {
            foreach ($entrada as $value) {
                foreach ($value as $key2 => $value2) {
                    if (!empty($value2)) {
                        $saida[$contador][$key2] = $value2;
                    }
                }
                $contador++;
            }
        }
        return $saida;
    }

    public function gerarBaseRegras($entradasTreino = array())
    {
        error_reporting(E_ALL);
        if (empty($entradasTreino)) {
            return;
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
        return;
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
                                                        $countTemp = $a;
                                                        foreach ($entradaFuzzificada[0] as $key => $value) {
                                                            if ($countTemp == 0) {
                                                                $temp[0][$key] = $entradaFuzzificada[0][$key];
                                                                break;
                                                            }
                                                            $countTemp = 0;
                                                        }
                                                        $countTemp = $b;
                                                        foreach ($entradaFuzzificada[1] as $key => $value) {
                                                            if ($countTemp == 0) {
                                                                $temp[1][$key] = $entradaFuzzificada[1][$key];
                                                                break;
                                                            }
                                                            $countTemp = 0;
                                                        }
                                                        $countTemp = $c;
                                                        foreach ($entradaFuzzificada[2] as $key => $value) {
                                                            if ($countTemp == 0) {
                                                                $temp[2][$key] = $entradaFuzzificada[2][$key];
                                                                break;
                                                            }
                                                            $countTemp = 0;
                                                        }
                                                        $countTemp = $d;
                                                        foreach ($entradaFuzzificada[3] as $key => $value) {
                                                            if ($countTemp == 0) {
                                                                $temp[3][$key] = $entradaFuzzificada[3][$key];
                                                                break;
                                                            }
                                                            $countTemp = 0;
                                                        }
                                                        $countTemp = $e;
                                                        foreach ($entradaFuzzificada[4] as $key => $value) {
                                                            if ($countTemp == 0) {
                                                                $temp[4][$key] = $entradaFuzzificada[4][$key];
                                                                break;
                                                            }
                                                            $countTemp = 0;
                                                        }
                                                        $countTemp = $f;
                                                        foreach ($entradaFuzzificada[5] as $key => $value) {
                                                            if ($countTemp == 0) {
                                                                $temp[5][$key] = $entradaFuzzificada[5][$key];
                                                                break;
                                                            }
                                                            $countTemp = 0;
                                                        }
                                                        $countTemp = $g;
                                                        foreach ($entradaFuzzificada[6] as $key => $value) {
                                                            if ($countTemp == 0) {
                                                                $temp[6][$key] = $entradaFuzzificada[6][$key];
                                                                break;
                                                            }
                                                            $countTemp = 0;
                                                        }
                                                        $countTemp = $h;
                                                        foreach ($entradaFuzzificada[7] as $key => $value) {
                                                            if ($countTemp == 0) {
                                                                $temp[7][$key] = $entradaFuzzificada[7][$key];
                                                                break;
                                                            }
                                                            $countTemp = 0;
                                                        }
                                                        $countTemp = $i;
                                                        foreach ($entradaFuzzificada[8] as $key => $value) {
                                                            if ($countTemp == 0) {
                                                                $temp[8][$key] = $entradaFuzzificada[8][$key];
                                                                break;
                                                            }
                                                            $countTemp = 0;
                                                        }
                                                        $countTemp = $j;
                                                        foreach ($entradaFuzzificada[9] as $key => $value) {
                                                            if ($countTemp == 0) {
                                                                $temp[9][$key] = $entradaFuzzificada[9][$key];
                                                                break;
                                                            }
                                                            $countTemp = 0;
                                                        }
                                                        $countTemp = $k;
                                                        foreach ($entradaFuzzificada[10] as $key => $value) {
                                                            if ($countTemp == 0) {
                                                                $temp[10][$key] = $entradaFuzzificada[10][$key];
                                                                break;
                                                            }
                                                            $countTemp = 0;
                                                        }
                                                        $countTemp = $l;
                                                        foreach ($entradaFuzzificada[11] as $key => $value) {
                                                            if ($countTemp == 0) {
                                                                $temp[11][$key] = $entradaFuzzificada[11][$key];
                                                                break;
                                                            }
                                                            $countTemp = 0;
                                                        }
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
