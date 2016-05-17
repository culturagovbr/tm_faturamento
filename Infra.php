<?php
require_once('Constantes.php');

class Infra
{

    public static function converterDataMySQL2Brazil($dataMysql)
    {
        return date('d/m/Y', strtotime($dataMysql));
    }

    public static function converterDataBanco2Brazil($dataBanco)
    {
        return date('d/m/Y', strtotime($dataBanco));
    }

    public static function converterDataHoraBanco2Brazil($dataMysql)
    {
        #return $dataMysql != null ? date('d/m/Y H:i:s', strtotime($dataMysql)) : $dataMysql;
        return date('d/m/Y H:i:s', strtotime($dataMysql));
    }

    public static function converterDataBrazil2Banco($dataBrazil)
    {
        $array = explode("/", $dataBrazil);
        $array = array_reverse($array);
        $str = implode($array, "/");
        return date("Y-m-d", strtotime($str));
    }

    public static function somarHoras(array $arHoras)
    {
        $segundos = 0;
        foreach ($arHoras as $tempo) { //percorre o array $tempo
            if(!empty($tempo)) {
                list($h, $m, $s) = explode(':', $tempo); //explode a variavel tempo e coloca as horas em $h, minutos em $m, e os segundos em $s
                //transforma todas os valores em segundos e add na variavel segundos
                $segundos += $h * 3600;
                $segundos += $m * 60;
                $segundos += $s;
            }
        }
        $horas = floor( $segundos / 3600 ); //converte os segundos em horas e arredonda caso nescessario
        $horas = $horas < 10 ? str_pad($horas, 2, "0", STR_PAD_LEFT) : $horas;
        $segundos %= 3600; // pega o restante dos segundos subtraidos das horas
        $minutos = floor( $segundos / 60 );//converte os segundos em minutos e arredonda caso nescessario
        $minutos = $minutos < 10 ? str_pad($minutos, 2, "0", STR_PAD_LEFT) : $minutos;
        $segundos %= 60;// pega o restante dos segundos subtraidos dos minutos
        $segundos = $segundos < 10 ? str_pad($segundos, 2, "0", STR_PAD_LEFT) : $segundos;

        return "{$horas}:{$minutos}:{$segundos}";
    }

    public static function getDataAtual2Brazil()
    {
        return date("d/m/Y");
    }

    public static function getDataHoraAtual2Brazil()
    {
        return date("d/m/Y H:i:s");
    }

    public static function getDataAtual2Banco()
    {
        return date("Y-m-d");
    }

    public static function getDataHoraAtual2Banco()
    {
        return date("Y-m-d H:i:s");
    }

    public static function tratarTextoUtf8Banco($texto)
    {
        return utf8_decode(trim($texto));
    }

    public static function tratarTextoUtf8Formulario($texto)
    {
        return utf8_encode(trim($texto));
    }

    public static function converterParaMonetario($valor)
    {
        return number_format($valor, 2, '.', '');
    }


    public static function calcularDiferencaEntreDatasBrazilEmMeses($dtInicio, $dtFim)
    {
        $inicio = DateTime::createFromFormat('d/m/Y H:i:s', $dtInicio);
        $fim = DateTime::createFromFormat('d/m/Y H:i:s', $dtFim);
        $diff = $inicio->diff($fim);
        return $diff->format('%m');
    }

    public static function calcularDiferencaEntreDatasBrazilEmDias($dtInicio, $dtFim)
    {
        $inicio = DateTime::createFromFormat('d/m/Y H:i:s', $dtInicio);
        $fim = DateTime::createFromFormat('d/m/Y H:i:s', $dtFim);
        $diff = $inicio->diff($fim);

        return $diff->format('%d');
    }

    public static function calcularDiferencaEntreDatasBrazilEmHoras($dtInicio, $dtFim)
    {
        $inicio = DateTime::createFromFormat('d/m/Y H:i:s', $dtInicio);
        $fim = DateTime::createFromFormat('d/m/Y H:i:s', $dtFim);
        $diff = $inicio->diff($fim);
        return $diff->format('%h');
    }

    public static function calcularDiferencaEntreDatasBrazilEmMinutos($dtInicio, $dtFim)
    {
        $inicio = DateTime::createFromFormat('d/m/Y H:i:s', $dtInicio);
        $fim = DateTime::createFromFormat('d/m/Y H:i:s', $dtFim);
        $diff = $inicio->diff($fim);
        return $diff->format('%i');
    }

    public static function calcularDiferencaEntreDatasBrazilEmSegundos($dtInicio, $dtFim)
    {
        $inicio = DateTime::createFromFormat('d/m/Y H:i:s', $dtInicio);
        $fim = DateTime::createFromFormat('d/m/Y H:i:s', $dtFim);
        $diff = $inicio->diff($fim);
        return $diff->format('%i');
    }

    public static function somarValores(array $arValores)
    {
        $soma_valores = 0;
        foreach ($arValores as $valor) { //percorre o array $tempo
            if(!empty($valor)) {
                $soma_valores += $valor;
            }
        }

        return $soma_valores;
    }

    /**
     * @param $texto
     * @return string
     */
    public static function subtituirVirgulaPorPonto($valor)
    {
        $texto_substituido = str_replace(',','.',$valor);
        return $texto_substituido;
    }

    public static function subtituirPontoPorVirgula($valor)
    {
        $texto_substituido = str_replace('.',',',$valor);
        return $texto_substituido;
    }

    public static function converterFormatoMoedaBrazil($texto, $nrCasasDecimais = 2)
    {
        $valor = number_format($texto, $nrCasasDecimais,',', '.');

        return $valor;
    }

    public static function converterFormatoMilharBrazil($texto)
    {
        $valor = number_format($texto,0, ',', '.');

        return $valor;
    }


}
