<?php
require_once('Infra.php');

class NValidacoes
{
    /**
     * @param $estado
     * @return string
     */
    public static function validarColunaEstadoDaDemanda($estado)
    {
        if ($estado && $estado == CONST_ENCERRADO_PELO_ANALISTA) {
            $resposta = "<td align='left'>$estado</td>";
        } else {
            $resposta = "<td align='center' bgcolor='red'>$estado</td>";
        }

        return $resposta;
    }

    /**
     * @param $nome_tecnico
     * @return string
     */
    public static function validarColunaNomeDoTecnico($nome_tecnico)
    {
        $resposta = "<td align='left'>$nome_tecnico</td>";

        return $resposta;
    }

    /**
     * @param $ticket_pai
     * @return string
     */
    public static function validarColunaTicketPai($ticket_pai)
    {
        if ($ticket_pai) {
            $resposta = "<td align='left'>$ticket_pai</td>";
        } else {
            $resposta = "<td align='center' bgcolor='red'>Campo não Preenchido!</td>";
        }

        return $resposta;
    }

    /**
     * @param $ticket
     * @return string
     */
    public static function validarColunaTicket($ticket)
    {
        $resposta = "<td align='left'>$ticket</td>";

        return $resposta;
    }

    /**
     * @param $torre
     * @return string
     */
    public static function validarColunaTorreDoAnalista($torre)
    {
        $resposta = "<td align='left'>$torre</td>";

        return $resposta;
    }

    /**
     * @param $atividade
     * @return string
     */
    public static function validarColunaAtividadeRealizada($atividade)
    {
        if ($atividade) {
            $resposta = "<td align='left'>$atividade</td>";
        } else {
            $resposta = "<td align='center' bgcolor='red'>Campo não Preenchido!</td>";
        }

        return $resposta;
    }

    /**
     * @param $data_hora_inicio
     * @return string
     */
    public static function validarColunaDataHoraInicioDaAtividade($data_hora_inicio)
    {
        if ($data_hora_inicio) {
            $resposta = "<td align='left'>$data_hora_inicio</td>";
            if($data_hora_inicio == "01/01/1970 01:00:00"){
                $resposta = "<td align='center' bgcolor='#ffd700'>Campo não Preenchido!</td>";
            }
        } else {
            $resposta = "<td align='center' bgcolor='red'>Campo não Preenchido!</td>";
        }


        return $resposta;
    }

    /**
     * @param $data_hora_fim
     * @return string
     */
    public static function validarColunaDataHoraFimDaAtividade($data_hora_fim)
    {
        if ($data_hora_fim) {
            $resposta = "<td align='left'>$data_hora_fim</td>";
            if($data_hora_fim == "01/01/1970 01:00:00"){
                $resposta = "<td align='center' bgcolor='#ffd700'>Campo não Preenchido!</td>";
            }
        } else {
            $resposta = "<td align='center' bgcolor='red'>Campo não Preenchido!</td>";
        }

        return $resposta;
    }

    /**
     * @param $tempo_execucao
     * @param $verificar_duracao_atividade
     * @return string
     */
    public static function validarColunaTempoDeExecucaoDaAtividade($tempo_execucao, $verificar_duracao_atividade)
    {
        #echo $tempo_execucao < "00:00:00" ? "Menor" : "Maior";
        $resposta = "<td align='center' bgcolor='red'>$tempo_execucao</td>";
        if($tempo_execucao && $verificar_duracao_atividade < 1 && $tempo_execucao > "00:00:00") { #Se estiver OK
            $resposta = "<td align='left'>$tempo_execucao</td>";
        } else{
            #Se não tiver Tempo de Execução
            if(!$tempo_execucao){
                $resposta = "<td align='center' bgcolor='red'>Campo não Preenchido!</td>";
            }
            #Se o Tempo de Execução for maior que 1 dia
            if($verificar_duracao_atividade > 1 || $tempo_execucao < "00:00:00"){
                $resposta = "<td align='center' bgcolor='red'>$tempo_execucao</td>";
            }

            #echo $tempo_execucao < 0 ? $tempo_execucao : "Maior";
        }

        return $resposta;
    }

    /**
     * @param $torre
     * @return string
     */
    public static function exibirTextoNaColuna($texto)
    {
        $resposta = "<td align='left'>$texto</td>";

        return $resposta;
    }

    public static function exibirNumeroComoTextoNaColuna($texto)
    {
        $resposta = "<td align='left'>&nbsp;$texto</td>";

        return $resposta;
    }

    public static function exibirTextoNaColunaColorindoHoraExtra($texto)
    {
        $resposta = $texto > 0 ? "<td align='left' bgcolor='#5f9ea0'>$texto</td>" : "<td align='left'>$texto</td>";

        return $resposta;
    }

    /**
     * @param $texto
     * @return string
     */
    public static function exibirTextoNaColunaSubtituiPontoPorVirgula($texto)
    {
        $texto_substituido = str_replace('.',',',$texto);
        $resposta = "<td align='left'>$texto_substituido</td>";

        return $resposta;
    }

    /**
     * @param $texto
     * @return string
     */
    public static function exibirTextoNaColunaSubtituiVirgulaPorPonto($texto)
    {
        $texto_substituido = str_replace(',','.',$texto);
        $resposta = "<td align='left'>$texto_substituido</td>";

        return $resposta;
    }

    public static function exibirTextoNaColunaFormatoMoeda($texto, $nrCasasDecimais = 2)
    {
        $valor = number_format($texto, $nrCasasDecimais,',', '.');
        $resposta = "<td align='left'>$valor</td>";

        return $resposta;
    }

    public static function tratarTextoColunaAssunto($texto)
    {
        $resposta = "<td align='left'>". str_replace('–','-', str_replace('—','-',str_replace('"', "", $texto))) . "</td>";

        return $resposta;
    }


}