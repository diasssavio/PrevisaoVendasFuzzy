<!--
To change this template, choose Tools | Templates
and open the template in the editor.
-->
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        try {
            require 'PrevisaoVendasFuzzy.php';
            $entradasTreino = array(
                /* 2004 */-27.75, 1.47, 4.29, 6.51, -4.22, -2.79, 7.76, -1.24, -1.45, 7.99, -3.24, 36.31,
                /* 2005 */-25.97, -4.22, 16.09, -8.23, -0.35, -3.85, 6.63, -3.3, -2.1, 7.13, -3.01, 35.6,
                /* 2006 */-25.38, -3.99, 6.14, 5.23, -8.54, -2.07, 4.78, -1.12, 1.12, 2.32, 1.18, 31.79,
                /* 2007 */-23.29, -2.41, 10.79, -0.77, -4.51, -0.9, 1.56, 2.37, 0.47, 1.63, 1.88, 33.56,
                /* 2008 */-22.84, -2.35, 16.76, -12.13, 8.66, -5.65, 4.4, 3.57, -5.45, 7.22, 1.19, 27.86,
                /* 2009 */-22.59, -4.12, 6.68, 7.2, -3.67, -5.25, 5.92, 0.95, -3.77, 8.33, -2.24, 31.68,
                /* 2010 */-20.96, -5.54, 10.74, -3.28, 0.46, -4.59, 4.21, -1.37, -0.11, 7.82, -4.43, 34.71
            );
            $entradasTeste = array(
                /* 2011 */-20.49, -6.35, 10.14, 8, -10.92, -2.49, 6.41, -1.84, -0.22, 5.16, -1.84, 30.66,
                /* 2012 */-18.91, 0.27, 7.54, -1.37, -2.37, -5.39, -0.08, 2.12, 0.79, 2.80, 2.13, 29.73
            );
            $rotulos = array('a0', 'a1', 'a2', 'a3', 'a4');
            list($limInferior, $limSuperior, $tamanhoJanela) = array(-34.16, 42.72, 13);
            $fuzzy = new PrevisaoVendasFuzzy($tamanhoJanela, $limInferior, $limSuperior, $rotulos);

            if ($fuzzy->gerarBaseRegras($entradasTreino)) {
                $entrada = array(-20.96, -5.54, 10.74, -3.28, 0.46, -4.59, 4.21, -1.37, -0.11, 7.82, -4.43, 34.71);
                $todasRegrasPossiveis = $fuzzy->gerarProdutoCartesiano($fuzzy->eliminarValoresZerados($fuzzy->fuzzificar($entrada)));
                $regrasAtivadas = $fuzzy->ativarRegras($todasRegrasPossiveis);
                echo "regras ativadas";
                echo "<br><br>";
                var_dump(count($regrasAtivadas));
                echo "<br><br>";
                var_dump($regrasAtivadas);
            } else {
                echo "Erro";
            }
        } catch (Exception $e) {
            echo "erro:";
            var_dump($e);
        }
        ?>
<!--        <select>
            <option value="">one</option>
            <option value="">two</option>
            <option value="">three</option>
        </select>
        <input type="button" value="Enviar" />-->
    </body>
</html>
