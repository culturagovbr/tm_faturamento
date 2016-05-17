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
<h2>Relatório de Chamados Tratados com Sucesso</h2> - <div id="qt_registros"> </div>
<div id="dvData">
    <table bordercolor="" width="100%" id="tblExport" style="border:1px solid black; ">
        <!--<thead>-->
        <tr class="titulo">
            <td>Nº Ticket</td>
            <td>Usuário</td>
            <td>Setor</td>
            <td>Prioridade</td>
            <td>Assunto</td>
            <td>Atendente</td>
            <td>Hora Abertura</td>
            <td>Prazo Atendimento</td>
            <td>Hora Fechamento</td>
            <td>Status Chamado</td>
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
            $rs = $relatorio_acompanhamento->buscarChamadosRecebidosPorPrioridadePorPeriodo($_POST['dt_inicio'], $_POST['dt_fim']);
            $qnt_resgistros = count($rs);
            $_SESSION['qt_registros'] = $qnt_resgistros > 0 ? $qnt_resgistros : 0;
            if($rs){
                $cont = 0;
                foreach ($rs as $resultado) {
                    echo $cont % 2 == 0 ? "<tr bgcolor='#FFFFFF'>":"<tr bgcolor='#a9a9a9'>";
                    $cont ++;

                    #echo NValidacoes::validarColunaNomeDoTecnico($resultado->nome_tecnico);
                    #echo "<td align='left'>$resultado->tempo_execucao</td>";
                    #echo NValidacoes::exibirTextoNaColunaFormatoMoeda($resultado->use_trabalhada, 4);

                    echo "<td align='left'>&nbsp;$resultado->ticket</td>";
                    echo "<td align='left'>$resultado->usuario</td>";
                    echo "<td align='left'>$resultado->setor</td>";
                    echo "<td align='left'>$resultado->prioridade</td>";
                    //echo "<td align='left'>". str_replace('–','-', str_replace('—','-',str_replace('"', "", $resultado->assunto))) . "</td>";
                    echo NValidacoes::tratarTextoColunaAssunto($resultado->assunto);
                    echo "<td align='left'>$resultado->atendente</td>";
                    echo "<td align='left'>" . Infra::converterDataHoraBanco2Brazil($resultado->hora_abertura) . "</td>";
                    echo "<td align='left'>" . Infra::converterDataHoraBanco2Brazil($resultado->prazo_atendimento) . "</td>";
                    echo "<td align='left'>" . Infra::converterDataHoraBanco2Brazil($resultado->hora_fechamento) . "</td>";
                    echo "<td align='left'>$resultado->status_chamado</td>";

                    echo "</tr>";
                }
            }else{
                echo "<tr>";
                echo "<td align='center' colspan='9'>Não Existem dados cadastrados para este Período</td>";
                echo "</tr>";
            }

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
            exportTableToCSV.apply(this, [$('#dvData>table'), 'relatorio_chamados_com_prioridades_' + getDataAtualParaNomearArquivo() + '.csv', 'csv']);
        });

        $("#btnExportXLS").click(function () {
            $("#tblExport").btechco_excelexport({
                containerid: "tblExport"
                , datatype: $datatype.Table
                , filename: 'relatorio_chamados_com_prioridades_' + getDataAtualParaNomearArquivo()
            });
        });

    });



</script>

<br/>
<a href="index.php">Voltar</a>
</body>
</html>

		
		
