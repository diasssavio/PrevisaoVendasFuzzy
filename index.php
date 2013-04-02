<!--
To change this template, choose Tools | Templates
and open the template in the editor.
-->
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <script src="jquery-1.7.2.min.js"></script>
        <link rel="stylesheet" type="text/css" href="view.css" media="all">
        <script type="text/javascript" src="view.js"></script>
        <title>Previsão de vendas / Lógica Fuzzy</title>
    </head>
    <body id="main_body" >
        <img id="top" src="top.png" alt="">
        <div id="form_container">

            <h1><a>Untitled Form</a></h1>
            <form id="form_604486" class="appnitro"  method="post" action="">
                <div class="form_description">
                    <h2>Previsão de Vendas utilizando Lógica Fuzzy</h2>
                </div>
                <ul >

                    <li id="li_2" >
                        <label class="description" for="element_2">Margem (%)</label>
                        <div>
                            <input id="element_2" name="element_2" class="element text medium" type="text" maxlength="2" value="10"/>
                        </div>
                    </li>		<li id="li_6" >
                        <label class="description" for="element_6">Número de Conjuntos
                        </label>
                        <div>
                            <select id="comboConjuntos" class="element select medium" id="element_6" name="element_6">
                                <option value="3">3</option>
                                <option value="5" selected='true'>5</option>
                                <option value="7">7</option>
                                <option value="9">9</option>
                                <option value="11">11</option>
                                <option value="13">13</option>
                                <option value="15">15</option>
                                <option value="17">17</option>
                                <option value="19">19</option>

                            </select>
                        </div>
                    </li>		<li id="li_7" >
                        <label class="description" for="element_7">Inferência </label>
                        <div>
                            <select  id="comboMes" class="element select medium" id="element_7" name="element_7">
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
                        </div>
                    </li>
                    <li id="li_1" >
                        <label class="description" for="element_1">Base de Regras </label>
                        <div>
                            <textarea id="element_1" name="element_1" style='margin-left: -30px;width: 635px;height: 200px'  readonly="true" class="element textarea medium"></textarea>
                        </div>
                    </li>
                    <li id="li_3" >
                        <label class="description" for="element_3">Saída Prevista </label>
                        <div>
                            <input id="element_3" name="element_3" readonly="true" id='saidaPrevista' class="element text medium" type="text" maxlength="255" value=""/>
                        </div>
                    </li>
                    <li id="li_4" >
                        <label class="description" for="element_4">Saída Esperada </label>
                        <div>
                            <input id="element_4" name="element_4" readonly="true" id='saidaReal' class="element text medium" type="text" maxlength="255" value=""/>
                        </div>
                    </li>
                    <li id="li_5" >
                        <label class="description" for="element_5">Erro </label>
                        <div>
                            <input id="element_5" name="element_5" readonly="true" id='erro'  class="element text medium" type="text" maxlength="255" value=""/>
                        </div>
                    </li>

                    <li class="buttons">
                        <input type="hidden" name="form_id" value="604486" />

                        <input id="saveForm" class="button_text"  type='button' value='Gerar Regras' onclick="
                            var nConjuntos = $('#comboConjuntos option:selected').val();
                            var mesSelecionado = $('#comboMes option:selected').val();
                            var margem = $('input[name=element_2]').val();
                            if(margem == null || margem == '')
                            {
                                alert('Preencha todos os campos');
                            }else {
                                $.ajax({
                                    type:'POST',
                                    url: 'PrevisaoVendasFuzzy.php',
                                    data:{operacao:'buscarRegras',nConjuntos:nConjuntos,posMes:mesSelecionado,margem:margem},
                                    success:function(resultado){
                                        $('textarea[id=element_1]').val(resultado);
                                        $('input[id=saveForm2]').removeAttr('disabled');
                                    }
                                });
                            }
                               " />
                        <input id="saveForm2" class="button_text"  type='button' id='enviarDados' value='Enviar dados' disabled="true" onclick="
                            var mesSelecionado = $('#comboMes option:selected').val();
                            var margem = $('input[name=element_2]').val();
                            var nConjuntos = $('#comboConjuntos option:selected').val();
                            if(margem == null || margem == '')
                            {
                                alert('Preencha todos os campos');
                            }else {
                                $.ajax({
                                    type:'POST',
                                    url: 'PrevisaoVendasFuzzy.php',
                                    data:{operacao:'buscarResultado',nConjuntos:nConjuntos,posMes:mesSelecionado,margem:margem},
                                    success:function(resultado){
                                        var dados = JSON.parse(resultado);
                                        if(dados.success){
                                            alert('Saída Prevista: '+dados.saidaPrevista+
                                                '\nSaída Real: '+dados.saidaReal+
                                                '\nErro: '+(dados.saidaReal - dados.saidaPrevista));
                                            $('input[id=element_3]').val(dados.saidaPrevista);
                                            $('input[id=element_4]').val(dados.saidaReal);
                                            $('input[id=element_5]').val(dados.saidaReal - dados.saidaPrevista);

                                        }else
                                        {
                                            alert('Ocorreu um erro.');
                                        }
                                    }
                                });
                            }
                               " />
                    </li>
                </ul>
            </form>
        </div>
        <img id="bottom" src="bottom.png" alt="">
    </body>
</html>
