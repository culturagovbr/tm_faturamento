<?php
require_once('Database.php');
require_once('PRelatoriosFaturamento.php');
require_once('NValidacoes.php');

$arMes = array (
    '01' => '01 - Janeiro',
    '02' => '02 - Fevereiro',
    '03' => '03 - Março',
    '04' => '04 - Abril',
    '05' => '05 - Maio',
    '06' => '06 - Junho',
    '07' => '07 - Julho',
    '08' => '08 - Agosto',
    '09' => '09 - Setembro',
    '10' => '10 - Outubro',
    '11' => '11 - Novembro',
    '12' => '12 - Dezembro'
);

?>
<html>
<head>
    <link rel="stylesheet" href="assets/css/jquery-ui.css" />
    <!-- Bootstrap CSS -->
<!--    <link rel="stylesheet" href="assets/css/bootstrap.min.css">-->

    <script type="text/javascript" src="assets/js/infra.js"></script>
    <script type="text/javascript" src="assets/js/jquery.js"></script>
    <script type="text/javascript" src="assets/js/jquery-ui.js"></script>
    <style>
        /*body {*/
            /*padding-top: 20px;*/
            /*padding-left: 10px;*/
            /* Required padding for .navbar-fixed-top. Remove if using .navbar-static-top. Change if height of navigation changes. */
        /*}*/

        a.export, a.export:visited {
            text-decoration: none;
            color:#000;
            background-color:#ddd;
            border: 1px solid #ccc;
            padding:8px;
        }
        .titulo{
            background-color:#ddd;
            color:#000;
            font-weight: bold;
            text-align: center;
        }
    </style>
    <script type="text/javascript" src="assets/js/export/jquery.btechco.excelexport.js"></script>
    <script type="text/javascript" src="assets/js/export/jquery.base64.js"></script>

    <!-- Include all compiled plugins (below), or include individual files as needed -->
<!--    <script src="assets/js/bootstrap.min.js"></script>-->
</head>
<body>
<form action="" method="POST">
    <h2>Preencha os Filtros Abaixo:</h2>
    <label for="dt_inicio"> Data Início</label>
    <input type="text" id="dt_inicio" name="dt_inicio" /><br/><br/>
    <label for="dt_fim"> Data Fim</label>
    <input type="text" id="dt_fim" name="dt_fim" /><br/><br/>

    <input type="submit" name="enviar" value="enviar">
    <input type="button" name="limpar" id="limpar" value="limpar">
    <a href="#" class="export" id="btnExportCSV">Exportar Dados para CSV</a>
    <a href="#" class="export" id="btnExportXLS">Exportar Dados para XLS</a>

</form>
<a href="index.php">Voltar</a>
<hr/>
<h2>Relatório de Resumos</h2>
<div id="dvData">
    <table bordercolor="" width="100%" id="tblExport" style="border:1px solid black; ">
        <!--<thead>-->

        <tr class="titulo">
            <td>Percentual</td>
            <td>Descrição</td>
        </tr>
        <!--        </thead>-->
        <!--        <tbody>-->
        <?php
        if (isset($_POST['enviar']) && $_POST['enviar']) {
            /*Grava os Dados do Formulário na Sessão*/
            $_SESSION['dt_inicio'] = isset($_POST['dt_inicio']) ? $_POST['dt_inicio'] : "";
            $_SESSION['dt_fim'] = isset($_POST['dt_fim']) ? $_POST['dt_fim'] : "";

            $arHorasAtividades = array();
            $relatorio_acompanhamento = new PRelatoriosFaturamento();

            $qnt_chamados_atendidos = $relatorio_acompanhamento->quantidadeDeChamadosAtendidosNoPeriodo($_POST['dt_inicio'], $_POST['dt_fim']);
            $qnt_chamados_recebidos = $relatorio_acompanhamento->quantidadeDeChamadosRecebidosNoPeriodo($_POST['dt_inicio'], $_POST['dt_fim']);

            $qnt_chamados_bom_e_otimo = $relatorio_acompanhamento->quantidadeDeChamadosComRespostaBomEOtimoNoPeriodo($_POST['dt_inicio'], $_POST['dt_fim']);
            $qnt_chamados_com_resposta_satisfacao = $relatorio_acompanhamento->quantidadeDeChamadosComRespostaSatisfacaoNoPeriodo($_POST['dt_inicio'], $_POST['dt_fim']);

            $qnt_chamados_tratados_fechados = $relatorio_acompanhamento->quantidadeDeChamadosFechadosNoPeriodo($_POST['dt_inicio'], $_POST['dt_fim']);

            $qnt_requisicoes_recebidas = $relatorio_acompanhamento->quantidadeDeRequisicoesRecebidasNoPeriodo($_POST['dt_inicio'], $_POST['dt_fim']);
            $qnt_requisicoes_tratadas = $relatorio_acompanhamento->quantidadeDeRequisicoesTratadasNoPeriodo($_POST['dt_inicio'], $_POST['dt_fim']);

            $qnt_incidentes_recebidas = $relatorio_acompanhamento->quantidadeDeIncidentesRecebidasNoPeriodo($_POST['dt_inicio'], $_POST['dt_fim']);
            $qnt_incidentes_tratadas = $relatorio_acompanhamento->quantidadeDeIncidentesTratadasNoPeriodo($_POST['dt_inicio'], $_POST['dt_fim']);

            $qnt_chamados_mesmo_tipo_natureza_reabertos = $relatorio_acompanhamento->quantidadeDeChamadosDoMesmoTipoENaturezaReabertosNoPeriodo($_POST['dt_inicio'], $_POST['dt_fim']);

            $qnt_chamados_nao_atendidos_mesmo_dia = $relatorio_acompanhamento->quantidadeDeChamadosNaoAtendidosNoDiaNoPeriodo($_POST['dt_inicio'], $_POST['dt_fim']);

            $qnt_chamados_por_prioridade = $relatorio_acompanhamento->quantidadeDeChamadosRecebidosPorPrioridadeNoPeriodo($_POST['dt_inicio'], $_POST['dt_fim']);

            $qnt_chamados_atendidos_dentro_prazo = $relatorio_acompanhamento->quantidadeDeChamadosAtendidosDentroDoPrazoNoPeriodo($_POST['dt_inicio'], $_POST['dt_fim']);
            $qnt_chamados_recebidos_com_sla = $relatorio_acompanhamento->quantidadeDeChamadosRecebidosComSLANoPeriodo($_POST['dt_inicio'], $_POST['dt_fim']);

                $percentual_tratado_sucesso = ($qnt_chamados_atendidos/$qnt_chamados_recebidos) * 100;
                $percentual_satisfacao_usuario = ($qnt_chamados_bom_e_otimo/$qnt_chamados_com_resposta_satisfacao) * 100;
                $percentual_chamados_tratados = ($qnt_chamados_tratados_fechados/$qnt_chamados_recebidos) * 100;
                $percentual_requisicoes_tratadas = ($qnt_requisicoes_tratadas/$qnt_requisicoes_recebidas) * 100;
                $percentual_incidentes_tratadas = ($qnt_incidentes_tratadas/$qnt_incidentes_recebidas) * 100;
                $percentual_fechados_validacao_usuario = ($qnt_chamados_com_resposta_satisfacao/$qnt_chamados_recebidos) * 100;
                $percentual_chamados_mesmo_tipo_natureza_reabertos = ($qnt_chamados_mesmo_tipo_natureza_reabertos/$qnt_chamados_recebidos) * 100;
                $percentual_chamados_nao_atendidos_mesmo_dia = ($qnt_chamados_nao_atendidos_mesmo_dia/$qnt_chamados_recebidos) * 100;
                $percentual_chamados_por_prioridade = ($qnt_chamados_por_prioridade/$qnt_chamados_recebidos) * 100;
                $percentual_chamados_atendidos_dentro_prazo = ($qnt_chamados_atendidos_dentro_prazo/$qnt_chamados_recebidos_com_sla) * 100;

                echo "<tr>";
                echo "<td align='left'><h2>" . Infra::converterFormatoMoedaBrazil($percentual_tratado_sucesso) . "%</h2></td>";
                echo "<td align='left'><h3>Chamados tratados com sucesso [(chamados atendidos no mes / chamados recebidos no periodo)*100]</h3></td>";
                echo "</tr>";

                echo "<tr>";
                echo "<td align='left'><h2>" . Infra::converterFormatoMoedaBrazil($percentual_satisfacao_usuario) . "%</h2></td>";
                echo "<td align='left'><h3>Satisfação do Usuário [(respostas bom e otimo / total de chamados com resposta de satisfação atendidos no periodo)*100]</h3></td>";
                echo "</tr>";

                echo "<tr>";
                echo "<td align='left'><h2>" . Infra::converterFormatoMoedaBrazil($percentual_chamados_tratados) . "%</h2></td>";
                echo "<td align='left'><h3>Chamados Tratados no Período [(chamados fechados / chamados recebidos no periodo)*100]</h3></td>";
                echo "</tr>";

                echo "<tr>";
                echo "<td align='left'><h2>" . Infra::converterFormatoMoedaBrazil($percentual_requisicoes_tratadas) . "%</h2></td>";
                echo "<td align='left'><h3>Requisições de Serviço Tratados no Período [(RS Tratadas / RS recebidos no periodo)*100]</h3></td>";
                echo "</tr>";

                echo "<tr>";
                echo "<td align='left'><h2>" . Infra::converterFormatoMoedaBrazil($percentual_incidentes_tratadas) . "%</h2></td>";
                echo "<td align='left'><h3>Incidentes Tratados no Período [(Incidentes Tratados / Incidentes recebidos no periodo)*100]</h3></td>";
                echo "</tr>";

                echo "<tr>";
                echo "<td align='left'><h2>" . Infra::converterFormatoMoedaBrazil($percentual_fechados_validacao_usuario) . "%</h2></td>";
                echo "<td align='left'><h3>Quantidade de Chamados fechados com validação do usuario [(Chamados fechados com validação do usuario / chamados recebidos no periodo)*100]</h3></td>";
                echo "</tr>";

                echo "<tr>";
                echo "<td align='left'><h2>" . Infra::converterFormatoMoedaBrazil($percentual_chamados_mesmo_tipo_natureza_reabertos) . "%</h2></td>";
                echo "<td align='left'><h3>Quantidade de Chamados dos mesmo tipo e natureza reabertos [(Chamados dos mesmo tipo e natureza reabertos / chamados recebidos no periodo)*100]</h3></td>";
                echo "</tr>";

                echo "<tr>";
                echo "<td align='left'><h2>" . Infra::converterFormatoMoedaBrazil($percentual_chamados_nao_atendidos_mesmo_dia) . "%</h2></td>";
                echo "<td align='left'><h3>Percentual de chamados não atendidos no mesmo dia [(Chamados não atendidos no dia / chamados recebidos no periodo)*100]</h3></td>";
                echo "</tr>";

                echo "<tr>";
                echo "<td align='left'><h2>" . Infra::converterFormatoMoedaBrazil($percentual_chamados_por_prioridade) . "%</h2></td>";
                echo "<td align='left'><h3>Chamados com prioridade de 1 a 5 [(todos chamados resolvidos com a prioridade / chamados recebidos no periodo)*100]</h3></td>";
                echo "</tr>";

                echo "<tr>";
                echo "<td align='left'><h2>" . Infra::converterFormatoMoedaBrazil($percentual_chamados_atendidos_dentro_prazo) . "%</h2></td>";
                echo "<td align='left'><h3>Chamados Atendidos dentro do Prazo[(todos chamados atendidos no prazo / chamados com sla recebidos no periodo)*100]</h3></td>";
                echo "</tr>";

        } else {
            echo "<tr>";
            echo "<td align='center' colspan='9'>Nenhuma informação para exibição</td>";
            echo "</tr>";
        }
        ?>
        <!--        </tbody>-->
    </table>
</div>
<script type="text/javascript" language="javascript" class="init">
    $(document).ready(function () {

        $('#limpar').click(function () {
            $('#nome_do_tecnico').val('');
            $('#dt_inicio').val('');
            $('#dt_fim').val('');
        });


        $("#dt_inicio").datepicker({
            //format: 'dd/mm/yyyy',
            language: 'pt-BR',
            separator: ' ',
            //minDate: new Date(),
            dayNames: ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'],
            dayNamesMin: ['D', 'S', 'T', 'Q', 'Q', 'S', 'S', 'D'],
            dayNamesShort: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'],
            monthNames: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
            monthNamesShort: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
            nextText: 'Próximo',
            prevText: 'Anterior',
            dateFormat: 'dd/mm/yy'
        });

        $("#dt_fim").datepicker({
            //format: 'dd/mm/yyyy',
            language: 'pt-BR',
            separator: ' ',
            //minDate: new Date(),
            dayNames: ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'],
            dayNamesMin: ['D', 'S', 'T', 'Q', 'Q', 'S', 'S', 'D'],
            dayNamesShort: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom'],
            monthNames: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
            monthNamesShort: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
            nextText: 'Próximo',
            prevText: 'Anterior',
            dateFormat: 'dd/mm/yy'
        });

        /*Persiste os dados da Busca no Formulário apos o Submit.*/
        <?php if (isset($_SESSION['dt_inicio'])) {?>
        $('#dt_inicio').val('<?php echo $_SESSION['dt_inicio']; ?>');
        <?php }?>
        <?php if (isset($_SESSION['dt_fim'])) {?>
        $('#dt_fim').val('<?php echo $_SESSION['dt_fim']; ?>');
        <?php }?>
        <?php if (isset($_SESSION['qt_registros'])) {?>
        $('#qt_registros').append('<?php echo $_SESSION['qt_registros']; ?>');
        <?php }?>

        $("#btnExportCSV").click(function (e) {
            exportTableToCSV.apply(this, [$('#dvData>table'), 'relatorio_resumos_chamados_' + getDataAtualParaNomearArquivo() + '.csv', 'csv']);
        });

        $("#btnExportXLS").click(function () {
            $("#tblExport").btechco_excelexport({
                containerid: "tblExport"
                , datatype: $datatype.Table
                , filename: 'relatorio_resumos_chamados_' + getDataAtualParaNomearArquivo()
            });
        });

    });



</script>

<br/>
<a href="index.php">Voltar</a>
</body>
</html>

		
		
