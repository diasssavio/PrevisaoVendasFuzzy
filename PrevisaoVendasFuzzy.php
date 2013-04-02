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
error_reporting(E_ALL ^ E_NOTICE);

if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $posMes = $_POST['posMes'];
    $margem = $_POST['margem'];
    $nConjuntos = isset($_POST['nConjuntos']) ? $_POST['nConjuntos'] : 5;
    $todasEntradas = getArrayEntradas();
    $entradasTreino = array_slice($todasEntradas, 0, 84 + $posMes);

    //DESCOBRIR ESTES VALORES
    $minimo = -34.16;//ALGUM CÁLCULO
    $maximo = 42.72;//ALGUM CALCULO

    for ($i = 0; $i < $nConjuntos; $i++) {
        $rotulos[] = (string)'a' . $i;
    }
    list($limInferior, $limSuperior, $tamanhoJanela) = array($minimo, $maximo, 13);
    $fuzzy = new PrevisaoVendasFuzzy($tamanhoJanela, $limInferior, $limSuperior, $rotulos);
    $fuzzy->gerarBaseRegras($entradasTreino);

    switch ($_POST['operacao']) {
        case 'buscarRegras':
            $regras = $fuzzy->getBaseRegrasFormatada();
            foreach ($regras as $regra) {
                echo $regra;
                ?>                              <?php

            }
            break;
        case 'buscarResultado':
            $entrada = array_slice($todasEntradas, 84 + $posMes - 12, 12);
            $saidaEsperada = $todasEntradas[84 + $posMes];
            $todasRegrasPossiveis = $fuzzy->gerarProdutoCartesiano($fuzzy->eliminarValoresZerados($fuzzy->fuzzificar($entrada), TRUE));
            $regrasAtivadas = $fuzzy->ativarRegras($todasRegrasPossiveis);
            $saidaReal = $fuzzy->aplicarOperadorFuzzy($regrasAtivadas);
            echo json_encode(array('success' => TRUE, 'saidaReal' => $saidaReal, 'saidaEsperada' => $saidaEsperada));
            break;
    }
}

class PrevisaoVendasFuzzy {

    public $tamanhoJanela;
    public $limInferior;
    public $limSuperior;
    public $rotulos;
    public $nRotulos;
    public $intervalosDefinidos;
    public $baseRegras;
    public $regrasPasso3;

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

    public function getBaseRegrasFormatada()
    {
        $saida = array();
        $countRegras = 1;
        foreach ($this->baseRegras as $regra) {
            $rotuloExtenso = array();
            for ($i = 0; $i < 12; $i++) {
                foreach ($regra[$i] as $rotulo => $grau) {
                    $rotuloExtenso[] = $rotulo;
                }
            }
            $saida[] = (strlen($countRegras) == 1 ? ('0' . $countRegras) : ($countRegras)) . ') SE ' . implode(' E ', $rotuloExtenso) . ' ENTAO ' . $regra[13] . ' = ' . $regra['grau'];
            $countRegras++;
        }
        return $saida;
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
            $novaRegra[$this->getMesExtenso($nI)] = $temp;
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
            $x = $this->limSuperior - $this->limInferior;
            $minimo['valor'] = ($x * $minimo['valor']) - $this->limSuperior;
            $valores[] = $minimo;
        }
        return $valores;
    }

    public function ativarRegras($RegrasProdutoCartesiano)
    {
        $nRegrasProdutoCartesiano = count($RegrasProdutoCartesiano);
        $regrasAtivadas = array();
        foreach ($this->baseRegras as $regra) {
            $regraRotulo = '';
            for ($i = 0; $i < 12; $i++) {
                foreach ($regra[$i] as $rotulo => $value) {
                    $regraRotulo .=$rotulo;
                }
            }
            for ($i = 0; $i < $nRegrasProdutoCartesiano; $i++) {
                if ($RegrasProdutoCartesiano[$i] == $regraRotulo) {
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

    public function gerarBaseRegras($entradasTreino)
    {
        $nEntradas = count($entradasTreino);
        $baseRegrasTotal = array();
        for ($posEntrada = 0; ($posEntrada + $this->tamanhoJanela - 1) < $nEntradas; $posEntrada++) {
            $janelaMeses = array_slice($entradasTreino, $posEntrada, $this->tamanhoJanela);
            $novaRegra = $this->fuzzificar($janelaMeses, $posEntrada);
            $baseRegrasTotal[] = $this->getChaveValorRegra($novaRegra);
            //ATÉ O MOMENTO, JÁ FOI OBTIDO OS VALORES DE CADA INTERVALO DE CADA MES(12 MESES + CONSEQUENTE)
            //INCLUSIVE O GRAU DE PERTINENCIA DA REGRA(APENAS COM OS MAIORES VALORES DE CADA CONJUNTO)
        }
        $baseRegrasTotalOrdenadas = $this->ordenarRegras($baseRegrasTotal);
        $this->baseRegras = $this->eliminarRedundancia($baseRegrasTotalOrdenadas);
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
                                                                $temp[0] = $key;
                                                                break;
                                                            }
                                                            $countTemp = 0;
                                                        }
                                                        $countTemp = $b;
                                                        foreach ($entradaFuzzificada[1] as $key => $value) {
                                                            if ($countTemp == 0) {
                                                                $temp[1] = $key;
                                                                break;
                                                            }
                                                            $countTemp = 0;
                                                        }
                                                        $countTemp = $c;
                                                        foreach ($entradaFuzzificada[2] as $key => $value) {
                                                            if ($countTemp == 0) {
                                                                $temp[2] = $key;
                                                                break;
                                                            }
                                                            $countTemp = 0;
                                                        }
                                                        $countTemp = $d;
                                                        foreach ($entradaFuzzificada[3] as $key => $value) {
                                                            if ($countTemp == 0) {
                                                                $temp[3] = $key;
                                                                break;
                                                            }
                                                            $countTemp = 0;
                                                        }
                                                        $countTemp = $e;
                                                        foreach ($entradaFuzzificada[4] as $key => $value) {
                                                            if ($countTemp == 0) {
                                                                $temp[4] = $key;
                                                                break;
                                                            }
                                                            $countTemp = 0;
                                                        }
                                                        $countTemp = $f;
                                                        foreach ($entradaFuzzificada[5] as $key => $value) {
                                                            if ($countTemp == 0) {
                                                                $temp[5] = $key;
                                                                break;
                                                            }
                                                            $countTemp = 0;
                                                        }
                                                        $countTemp = $g;
                                                        foreach ($entradaFuzzificada[6] as $key => $value) {
                                                            if ($countTemp == 0) {
                                                                $temp[6] = $key;
                                                                break;
                                                            }
                                                            $countTemp = 0;
                                                        }
                                                        $countTemp = $h;
                                                        foreach ($entradaFuzzificada[7] as $key => $value) {
                                                            if ($countTemp == 0) {
                                                                $temp[7] = $key;
                                                                break;
                                                            }
                                                            $countTemp = 0;
                                                        }
                                                        $countTemp = $i;
                                                        foreach ($entradaFuzzificada[8] as $key => $value) {
                                                            if ($countTemp == 0) {
                                                                $temp[8] = $key;
                                                                break;
                                                            }
                                                            $countTemp = 0;
                                                        }
                                                        $countTemp = $j;
                                                        foreach ($entradaFuzzificada[9] as $key => $value) {
                                                            if ($countTemp == 0) {
                                                                $temp[9] = $key;
                                                                break;
                                                            }
                                                            $countTemp = 0;
                                                        }
                                                        $countTemp = $k;
                                                        foreach ($entradaFuzzificada[10] as $key => $value) {
                                                            if ($countTemp == 0) {
                                                                $temp[10] = $key;
                                                                break;
                                                            }
                                                            $countTemp = 0;
                                                        }
                                                        $countTemp = $l;
                                                        foreach ($entradaFuzzificada[11] as $key => $value) {
                                                            if ($countTemp == 0) {
                                                                $temp[11] = $key;
                                                                break;
                                                            }
                                                            $countTemp = 0;
                                                        }
                                                        $saida[] = implode('', $temp);
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

    public function eliminarRedundancia($baseRegrasTotal)
    {
        $nRegras = count($baseRegrasTotal);
        for ($i = 0; $i < $nRegras; $i++) {

            $rotuloRegraI = '';
            $regraI = $baseRegrasTotal[$i];
            for ($k = 0; $k < 12; $k++) {
                foreach ($regraI[$k] as $rotulo => $chave) {
                    $rotuloRegraI .=$rotulo;
                }
            }
            $regraJ = $baseRegrasTotal[$i + 1];
            $rotuloRegraJ = '';
            for ($k = 0; $k < 12; $k++) {
                foreach ($regraJ[$k] as $rotulo => $chave) {
                    $rotuloRegraJ .=$rotulo;
                }
            }
            if ($rotuloRegraI == $rotuloRegraJ) {
                if ($baseRegrasTotal[$i]['grau'] > $baseRegrasTotal[$i + 1]['grau']) {
                    unset($baseRegrasTotal[$i + 1]);
                } else {
                    unset($baseRegrasTotal[$i]);
                }
            }
        }
        return $baseRegrasTotal;
    }

    public function ordenarRegras($baseRegrasTotal)
    {
        $nRegras = count($baseRegrasTotal);
        for ($i = 0; $i < $nRegras; $i++) {
            for ($j = 0; $j < $nRegras - 1; $j++) {
                $rotuloRegraI = '';
                $regraI = $baseRegrasTotal[$i];
                for ($k = 0; $k < 12; $k++) {
                    foreach ($regraI[$k] as $rotulo => $chave) {
                        $rotuloRegraI .=$rotulo;
                    }
                }
                $regraJ = $baseRegrasTotal[$j];
                $rotuloRegraJ = '';
                for ($k = 0; $k < 12; $k++) {
                    foreach ($regraJ[$k] as $rotulo => $chave) {
                        $rotuloRegraJ .=$rotulo;
                    }
                }
                if ($rotuloRegraI < $rotuloRegraJ) {
                    $aux = $baseRegrasTotal[$i];
                    $baseRegrasTotal[$i] = NULL;
                    $baseRegrasTotal[$i] = $baseRegrasTotal[$j];
                    $baseRegrasTotal[$j] = NULL;
                    $baseRegrasTotal[$j] = $aux;
                }
            }
        }
        return $baseRegrasTotal;
    }

    public function getChaveValorRegra($regra)
    {
        $saida = array();
        $grauRegra = $regra['grau'];
        unset($regra['grau']);
        foreach ($regra as $mes => $rotulos) {
            $rotuloMaximo = '';
            $grauMaximo = 0;
            foreach ($rotulos as $rotulo => $grau) {
                if ($grau > $grauMaximo) {
                    $grauMaximo = $grau;
                    $rotuloMaximo = $rotulo;
                }
            }
            $temp = array();
            $temp[$rotuloMaximo] = $grauMaximo;
            $saida[] = $temp;
        }
        $saida['grau'] = $grauRegra;
        return $saida;
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
                return 'Jan';
                break;
            case 2:
                return 'Fev';
                break;
            case 3:
                return 'Mar';
                break;
            case 4:
                return 'Abr';
                break;
            case 5:
                return 'Mai';
                break;
            case 6:
                return 'Jun';
                break;
            case 7:
                return 'Jul';
                break;
            case 8:
                return 'Ago';
                break;
            case 9:
                return 'Set';
                break;
            case 10:
                return 'Out';
                break;
            case 11:
                return 'Nov';
                break;
            case 12:
                return 'Dez';
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

function getArrayEntradas()
{
    return array(
        /* 2004 */-27.75, 1.47, 4.29, 6.51, -4.22, -2.79, 7.76, -1.24, -1.45, 7.99, -3.24, 36.31,
        /* 2005 */-25.97, -4.22, 16.09, -8.23, -0.35, -3.85, 6.63, -3.3, -2.1, 7.13, -3.01, 35.6,
        /* 2006 */-25.38, -3.99, 6.14, 5.23, -8.54, -2.07, 4.78, -1.12, 1.12, 2.32, 1.18, 31.79,
        /* 2007 */-23.29, -2.41, 10.79, -0.77, -4.51, -0.9, 1.56, 2.37, 0.47, 1.63, 1.88, 33.56,
        /* 2008 */-22.84, -2.35, 16.76, -12.13, 8.66, -5.65, 4.4, 3.57, -5.45, 7.22, 1.19, 27.86,
        /* 2009 */-22.59, -4.12, 6.68, 7.2, -3.67, -5.25, 5.92, 0.95, -3.77, 8.33, -2.24, 31.68,
        /* 2010 */-20.96, -5.54, 10.74, -3.28, 0.46, -4.59, 4.21, -1.37, -0.11, 7.82, -4.43, 34.71,
        //posicao = 84
        /* 2011 */-20.49, -6.35, 10.14, 8.00, -10.92, -2.49, 6.41, -1.84, -0.22, 5.16, -1.84, 30.66,
        /* 2012 */-18.91, 0.27, 7.54, -1.37, -2.37, -5.39, -0.08, 2.12, 0.79, 2.80, 2.13, 29.73);
}