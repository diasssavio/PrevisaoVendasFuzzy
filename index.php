<!--
To change this template, choose Tools | Templates
and open the template in the editor.
-->
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <script src="jquery-1.7.2.min.js"></script>
        <title>Previsão de vendas / Lógica Fuzzy</title>
    </head>
    <body>
        <?php
        session_start();
        session_destroy();
        ?>
        Margem:
        <input type='text' id='margem' size="1" maxlength="2" />%
        <br/>
        Nº Conjuntos:
        <select id="nConjuntos">
            <option value="3">3</option>
            <option value="5">5</option>
            <option value="7">7</option>
            <option value="9">9</option>
            <option value="11">11</option>
            <option value="13">13</option>
            <option value="15">15</option>
            <option value="17">17</option>
            <option value="19">19</option>
        </select>
        <br/>
        Verificar mês:
        <select id="comboMes">
            <option value="0">Jan - 2011</option>
            <option value="1">Fev - 2011</option>
            <option value="2">Mar - 2011</option>
            <option value="3">Abr - 2011</option>
            <option value="4">Mai - 2011</option>
            <option value="5">Jun - 2011</option>
            <option value="6">Jul - 2011</option>
            <option value="7">Ago - 2011</option>
            <option value="8">Set - 2011</option>
            <option value="9">Out - 2011</option>
            <option value="10">Nov - 2011</option>
            <option value="11">Dez - 2011</option>
            <option value="12">Jan - 2012</option>
            <option value="13">Fev - 2012</option>
            <option value="14">Mar - 2012</option>
            <option value="15">Abr - 2012</option>
            <option value="16">Mai - 2012</option>
            <option value="17">Jun - 2012</option>
            <option value="18">Jul - 2012</option>
            <option value="19">Ago - 2012</option>
            <option value="20">Set - 2012</option>
            <option value="21">Out - 2012</option>
            <option value="22">Nov - 2012</option>
            <option value="23">Dez - 2012</option>
        </select>
        <br/><br/><br/>
        <input type='button' value='Gerar Regras' onclick="
            var mesSelecionado = $('#comboMes option:selected').val();
            var margem = $('input[id=margem]').val();
            if(mesSelecionado == null || mesSelecionado == '' || margem == null || margem == '')
            {
                alert('Preencha todos os campos');
            }else {
                $.ajax({
                    type:'POST',
                    url: 'PrevisaoVendasFuzzy.php',
                    data:{operacao:'buscarRegras',posMes:mesSelecionado,margem:margem},
                    success:function(resultado){

                        alert('Carregado com sucesso.');
                        $('textarea[id=regras]').val(resultado);
                        $('input[id=enviarDados]').removeAttr('disabled');
                    }
                });
            }
               " />
        <input type='button' id='enviarDados' value='Enviar dados' disabled="true" onclick="
            var mesSelecionado = $('#comboMes option:selected').val();
            var margem = $('input[id=margem]').val();
            if(mesSelecionado == null || mesSelecionado == '' || margem == null || margem == '')
            {
                alert('Preencha todos os campos');
            }else {
                $.ajax({
                    type:'POST',
                    url: 'PrevisaoVendasFuzzy.php',
                    data:{operacao:'buscarResultado',posMes:mesSelecionado,margem:margem},
                    success:function(resultado){
                        var dados = JSON.parse(resultado);
                        if(dados.success){
                            alert('Carregado com sucesso.');
                            $('input[id=saidaEsperada]').val(dados.saidaReal);
                            $('input[id=saidaReal]').val(dados.saidaEsperada);
                            $('input[id=erro]').val(dados.saidaEsperada - dados.saidaReal);

                        }else
                        {
                            alert('Ocorreu um erro.');
                        }
                    }
                });
            }
               " />
        <br/><br/>Regras Geradas:<br/>

        <textarea rows="20" cols="120" id="regras"  readonly="true"></textarea>
        <br/>
        Saída Esperada:
        <input type='text' readonly="true" id='saidaEsperada' />
        <br/>
        Saída Real:
        <input type='text' readonly="true" id='saidaReal' />
        <br/>
        Erro:
        <input type='text' readonly="true" id='erro' />
    </body>
</html>
