<?php
require_once('Infra.php');
require_once('Database.php');

class PRelatoriosFaturamento {

    function buscarTodosChamadosPorPeriodo($dtInicioPeriodo, $dtFimPeriodo) {
        $dtInicioPeriodo = Infra::converterDataBrazil2Banco($dtInicioPeriodo);
        $dtFimPeriodo = Infra::converterDataBrazil2Banco($dtFimPeriodo);

        $dtInicioPeriodo .= ' 00:00:00';
        $dtFimPeriodo .= ' 23:59:59';

        $sql = "SELECT t.id,
                       t.tn as ticket,
                       t.customer_id as usuario,
                       dfv.value_text as setor,
                       t.title as assunto,
                       t.queue_id,
                       q.name AS Fila_Atendimento,
                       t.ticket_lock_id,
                       t.type_id,
                       tt.name AS Tipo_Chamado,
                       t.service_id,
                       sc.name AS Catalogo_Servico,
                       t.sla_id,
                       s.name AS SLA,
                       t.user_id,
                       u.login AS login_atendente,
                       u.first_name || ' ' || u.last_name AS atendente,
                       t.responsible_user_id,
                       ru.login AS Login_responsavel,
                       ru.first_name || ' ' || ru.last_name AS Responsavel,
                       t.ticket_priority_id,
                       tp.name AS Prioridade,
                       t.ticket_state_id,
                       ts.name AS Estado,
                       t.customer_user_id AS CPF,
                       CASE
			                WHEN t.escalation_solution_time != 0 THEN to_timestamp(t.escalation_solution_time)
			           END AS prazo_atendimento,
                       t.archive_flag,
                       t.create_time as hora_abertura,
                       t.create_by,
                       tcru.first_name || ' ' || tcru.last_name AS Criado_por,
                       t.change_time,
                       t.change_by,
                       tchu.first_name || ' ' || tchu.last_name AS Trocado_por,
                       CASE EXTRACT(DOW
                                    FROM t.create_time)
                           WHEN 0 THEN 'Domingo'
                           WHEN 1 THEN 'Segunda'
                           WHEN 2 THEN 'Terça'
                           WHEN 3 THEN 'Quarta'
                           WHEN 4 THEN 'Quinta'
                           WHEN 5 THEN 'Sexta'
                           WHEN 6 THEN 'Sábado'
                       END AS DiaSemanaCriado,
                       CASE EXTRACT(MONTH
                                    FROM t.create_time)
                           WHEN 1 THEN 'Janeiro'
                           WHEN 2 THEN 'Fevereiro'
                           WHEN 3 THEN 'Março'
                           WHEN 4 THEN 'Abril'
                           WHEN 5 THEN 'Maio'
                           WHEN 6 THEN 'Junho'
                           WHEN 7 THEN 'Julho'
                           WHEN 8 THEN 'Agosto'
                           WHEN 9 THEN 'Setembro'
                           WHEN 10 THEN 'Outubro'
                           WHEN 11 THEN 'Novembro'
                           WHEN 12 THEN 'Dezembro'
                       END || ' ' || EXTRACT (YEAR
                                              FROM t.create_time) AS Mes_Ano,
                                             EXTRACT (YEAR
                                                      FROM t.create_time) AS AnoCriado,
                                                     th.hora_fechamento,
                                                     est.hora_solucao,
                                                     CASE
                                                         WHEN est.hora_solucao IS NULL THEN 'SEM SLA'
                                                         WHEN est.hora_solucao > th.hora_fechamento THEN 'DENTRO PRAZO'
                                                         WHEN est.hora_solucao < th.hora_fechamento THEN 'FORA DO PRAZO'
                                                         WHEN th.hora_fechamento IS NULL THEN CASE
                                                                                                  WHEN est.hora_solucao < now() THEN 'FORA DO PRAZO'
                                                                                                  ELSE 'DENTRO PRAZO'
                                                                                              END
                                                     END AS status_chamado
                FROM ticket t
                INNER JOIN queue q ON q.id=t.queue_id
                INNER JOIN ticket_type tt ON tt.id = t.type_id
                INNER JOIN service sc ON sc.id = t.service_id
                LEFT JOIN sla s ON s.id = t.sla_id
                INNER JOIN users u ON u.id = t.user_id
                INNER JOIN users ru ON ru.id = t.responsible_user_id
                INNER JOIN ticket_priority tp ON tp.id = t.ticket_priority_id
                INNER JOIN ticket_state ts ON ts.id = t.ticket_state_id
                INNER JOIN users tcru ON tcru.id = t.create_by
                INNER JOIN users tchu ON tchu.id = t.change_by
                LEFT JOIN
                  (SELECT ticket_id,
                          max(create_time) AS hora_fechamento
                   FROM ticket_history
                   WHERE state_id =
                       (SELECT ts.id
                        FROM ticket_state ts
                        WHERE ts.name = 'Aguardando validação')
                   GROUP BY ticket_id) th ON th.ticket_id = t.id
                LEFT JOIN
                  (SELECT id_ticket,
                          max(date_insert),
                          to_timestamp(max(escalation_solution_time)) AS hora_solucao
                   FROM ticket_est
                   GROUP BY id_ticket) est ON est.id_ticket = t.id
                INNER JOIN dynamic_field_value AS dfv ON dfv.object_id = t.id AND dfv.field_id = " . CONST_DYNAMIC_FIELD_DEPARTAMENTO . "
                WHERE
                    t.create_time BETWEEN '$dtInicioPeriodo' AND '$dtFimPeriodo'
                    AND t.queue_id != ".CONST_FILA_DE_SPAM ."

        ";

        $sql .= " ORDER BY t.create_time";

        #die("<pre>".$sql."</pre>");

        $stmt = Database::prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    function buscarTodosChamadosComSLAPorPeriodo($dtInicioPeriodo, $dtFimPeriodo) {
        $dtInicioPeriodo = Infra::converterDataBrazil2Banco($dtInicioPeriodo);
        $dtFimPeriodo = Infra::converterDataBrazil2Banco($dtFimPeriodo);

        $dtInicioPeriodo .= ' 00:00:00';
        $dtFimPeriodo .= ' 23:59:59';

        $sql = "SELECT t.id,
                       t.tn as ticket,
                       t.customer_id as usuario,
                       dfv.value_text as setor,
                       t.title as assunto,
                       t.queue_id,
                       q.name AS Fila_Atendimento,
                       t.ticket_lock_id,
                       t.type_id,
                       tt.name AS Tipo_Chamado,
                       t.service_id,
                       sc.name AS Catalogo_Servico,
                       t.sla_id,
                       s.name AS SLA,
                       t.user_id,
                       u.login AS login_atendente,
                       u.first_name || ' ' || u.last_name AS atendente,
                       t.responsible_user_id,
                       ru.login AS Login_responsavel,
                       ru.first_name || ' ' || ru.last_name AS Responsavel,
                       t.ticket_priority_id,
                       tp.name AS Prioridade,
                       t.ticket_state_id,
                       ts.name AS Estado,
                       t.customer_user_id AS CPF,
                       CASE
			                WHEN t.escalation_solution_time != 0 THEN to_timestamp(t.escalation_solution_time)
			           END AS prazo_atendimento,
                       t.archive_flag,
                       t.create_time as hora_abertura,
                       t.create_by,
                       tcru.first_name || ' ' || tcru.last_name AS Criado_por,
                       t.change_time,
                       t.change_by,
                       tchu.first_name || ' ' || tchu.last_name AS Trocado_por,
                       CASE EXTRACT(DOW
                                    FROM t.create_time)
                           WHEN 0 THEN 'Domingo'
                           WHEN 1 THEN 'Segunda'
                           WHEN 2 THEN 'Terça'
                           WHEN 3 THEN 'Quarta'
                           WHEN 4 THEN 'Quinta'
                           WHEN 5 THEN 'Sexta'
                           WHEN 6 THEN 'Sábado'
                       END AS DiaSemanaCriado,
                       CASE EXTRACT(MONTH
                                    FROM t.create_time)
                           WHEN 1 THEN 'Janeiro'
                           WHEN 2 THEN 'Fevereiro'
                           WHEN 3 THEN 'Março'
                           WHEN 4 THEN 'Abril'
                           WHEN 5 THEN 'Maio'
                           WHEN 6 THEN 'Junho'
                           WHEN 7 THEN 'Julho'
                           WHEN 8 THEN 'Agosto'
                           WHEN 9 THEN 'Setembro'
                           WHEN 10 THEN 'Outubro'
                           WHEN 11 THEN 'Novembro'
                           WHEN 12 THEN 'Dezembro'
                       END || ' ' || EXTRACT (YEAR
                                              FROM t.create_time) AS Mes_Ano,
                                             EXTRACT (YEAR
                                                      FROM t.create_time) AS AnoCriado,
                                                     th.hora_fechamento,
                                                     est.hora_solucao,
                                                     CASE
                                                         WHEN est.hora_solucao IS NULL THEN 'SEM SLA'
                                                         WHEN est.hora_solucao > th.hora_fechamento THEN 'DENTRO PRAZO'
                                                         WHEN est.hora_solucao < th.hora_fechamento THEN 'FORA DO PRAZO'
                                                         WHEN th.hora_fechamento IS NULL THEN CASE
                                                                                                  WHEN est.hora_solucao < now() THEN 'FORA DO PRAZO'
                                                                                                  ELSE 'DENTRO PRAZO'
                                                                                              END
                                                     END AS status_chamado
                FROM ticket t
                INNER JOIN queue q ON q.id=t.queue_id
                INNER JOIN ticket_type tt ON tt.id = t.type_id
                INNER JOIN service sc ON sc.id = t.service_id
                LEFT JOIN sla s ON s.id = t.sla_id
                INNER JOIN users u ON u.id = t.user_id
                INNER JOIN users ru ON ru.id = t.responsible_user_id
                INNER JOIN ticket_priority tp ON tp.id = t.ticket_priority_id
                INNER JOIN ticket_state ts ON ts.id = t.ticket_state_id
                INNER JOIN users tcru ON tcru.id = t.create_by
                INNER JOIN users tchu ON tchu.id = t.change_by
                LEFT JOIN
                  (SELECT ticket_id,
                          max(create_time) AS hora_fechamento
                   FROM ticket_history
                   WHERE state_id =
                       (SELECT ts.id
                        FROM ticket_state ts
                        WHERE ts.name = 'Aguardando validação')
                   GROUP BY ticket_id) th ON th.ticket_id = t.id
                LEFT JOIN
                  (SELECT id_ticket,
                          max(date_insert),
                          to_timestamp(max(escalation_solution_time)) AS hora_solucao
                   FROM ticket_est
                   GROUP BY id_ticket) est ON est.id_ticket = t.id
                INNER JOIN dynamic_field_value AS dfv ON dfv.object_id = t.id AND dfv.field_id = " . CONST_DYNAMIC_FIELD_DEPARTAMENTO . "
                WHERE
                    t.create_time BETWEEN '$dtInicioPeriodo' AND '$dtFimPeriodo'
                    AND t.queue_id != ".CONST_FILA_DE_SPAM ."
                    AND ticket_state_id = 2
                    AND est.hora_solucao IS NOT NULL

        ";

        $sql .= " ORDER BY t.create_time";

        #die("<pre>".$sql."</pre>");

        $stmt = Database::prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /***
     * Chamados atendidos por Periodo
     *
     * @param null $mes_selecionado
     * @param null $ano_selecionado
     * @return array
     */
    function buscarChamadosAtendidosPorPeriodo($dtInicioPeriodo, $dtFimPeriodo) {
        $dtInicioPeriodo = Infra::converterDataBrazil2Banco($dtInicioPeriodo);
        $dtFimPeriodo = Infra::converterDataBrazil2Banco($dtFimPeriodo);

        $dtInicioPeriodo .= ' 00:00:00';

        $dtFimPeriodo .= ' 23:59:59';


        $sql = "SELECT t.id,
                       t.tn as ticket,
                       t.customer_id as usuario,
                       dfv.value_text as setor,
                       t.title as assunto,
                       t.queue_id,
                       q.name AS Fila_Atendimento,
                       t.ticket_lock_id,
                       t.type_id,
                       tt.name AS Tipo_Chamado,
                       t.service_id,
                       sc.name AS Catalogo_Servico,
                       t.sla_id,
                       s.name AS SLA,
                       t.user_id,
                       u.login AS login_atendente,
                       u.first_name || ' ' || u.last_name AS atendente,
                       t.responsible_user_id,
                       ru.login AS Login_responsavel,
                       ru.first_name || ' ' || ru.last_name AS Responsavel,
                       t.ticket_priority_id,
                       tp.name AS Prioridade,
                       t.ticket_state_id,
                       ts.name AS Estado,
                       t.customer_user_id AS CPF,
                       CASE
			                WHEN t.escalation_solution_time != 0 THEN to_timestamp(t.escalation_solution_time)
			           END AS prazo_atendimento,
                       t.archive_flag,
                       t.create_time as hora_abertura,
                       t.create_by,
                       tcru.first_name || ' ' || tcru.last_name AS Criado_por,
                       t.change_time,
                       t.change_by,
                       tchu.first_name || ' ' || tchu.last_name AS Trocado_por,
                       CASE EXTRACT(DOW
                                    FROM t.create_time)
                           WHEN 0 THEN 'Domingo'
                           WHEN 1 THEN 'Segunda'
                           WHEN 2 THEN 'Terça'
                           WHEN 3 THEN 'Quarta'
                           WHEN 4 THEN 'Quinta'
                           WHEN 5 THEN 'Sexta'
                           WHEN 6 THEN 'Sábado'
                       END AS DiaSemanaCriado,
                       CASE EXTRACT(MONTH
                                    FROM t.create_time)
                           WHEN 1 THEN 'Janeiro'
                           WHEN 2 THEN 'Fevereiro'
                           WHEN 3 THEN 'Março'
                           WHEN 4 THEN 'Abril'
                           WHEN 5 THEN 'Maio'
                           WHEN 6 THEN 'Junho'
                           WHEN 7 THEN 'Julho'
                           WHEN 8 THEN 'Agosto'
                           WHEN 9 THEN 'Setembro'
                           WHEN 10 THEN 'Outubro'
                           WHEN 11 THEN 'Novembro'
                           WHEN 12 THEN 'Dezembro'
                       END || ' ' || EXTRACT (YEAR
                                              FROM t.create_time) AS Mes_Ano,
                                             EXTRACT (YEAR
                                                      FROM t.create_time) AS AnoCriado,
                                                     th.hora_fechamento,
                                                     est.hora_solucao,
                                                     CASE
                                                         WHEN est.hora_solucao IS NULL THEN 'SEM SLA'
                                                         WHEN est.hora_solucao > th.hora_fechamento THEN 'DENTRO PRAZO'
                                                         WHEN est.hora_solucao < th.hora_fechamento THEN 'FORA DO PRAZO'
                                                         WHEN th.hora_fechamento IS NULL THEN CASE
                                                                                                  WHEN est.hora_solucao < now() THEN 'FORA DO PRAZO'
                                                                                                  ELSE 'DENTRO PRAZO'
                                                                                              END
                                                     END AS status_chamado
                FROM ticket t
                INNER JOIN queue q ON q.id=t.queue_id
                INNER JOIN ticket_type tt ON tt.id = t.type_id
                INNER JOIN service sc ON sc.id = t.service_id
                LEFT JOIN sla s ON s.id = t.sla_id
                INNER JOIN users u ON u.id = t.user_id
                INNER JOIN users ru ON ru.id = t.responsible_user_id
                INNER JOIN ticket_priority tp ON tp.id = t.ticket_priority_id
                INNER JOIN ticket_state ts ON ts.id = t.ticket_state_id
                INNER JOIN users tcru ON tcru.id = t.create_by
                INNER JOIN users tchu ON tchu.id = t.change_by
                LEFT JOIN
                  (SELECT ticket_id,
                          max(create_time) AS hora_fechamento
                   FROM ticket_history
                   WHERE state_id =
                       (SELECT ts.id
                        FROM ticket_state ts
                        WHERE ts.name = 'Aguardando validação')
                   GROUP BY ticket_id) th ON th.ticket_id = t.id
                LEFT JOIN
                  (SELECT id_ticket,
                          max(date_insert),
                          to_timestamp(max(escalation_solution_time)) AS hora_solucao
                   FROM ticket_est
                   GROUP BY id_ticket) est ON est.id_ticket = t.id
                INNER JOIN dynamic_field_value AS dfv ON dfv.object_id = t.id AND dfv.field_id = " . CONST_DYNAMIC_FIELD_DEPARTAMENTO . "
                WHERE
                    t.create_time BETWEEN '$dtInicioPeriodo' AND '$dtFimPeriodo'
                    AND t.queue_id != ".CONST_FILA_DE_SPAM ."
                    AND (ticket_state_id = " . CONST_TICKET_ESTADO_ENCERRADO . " OR ticket_state_id = " . CONST_TICKET_ESTADO_AGUARDANDO_VALIDACAO . ")
        ";

        $sql .= " ORDER BY t.create_time";

        #die("<pre>".$sql."</pre>");

        $stmt = Database::prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /***
     * @param $dtInicioPeriodo
     * @param $dtFimPeriodo
     * @return array*
     */
    function buscarChamadosSatisfacaoBonsEOtimosPorPeriodo($dtInicioPeriodo, $dtFimPeriodo) {
        $dtInicioPeriodo = Infra::converterDataBrazil2Banco($dtInicioPeriodo);
        $dtFimPeriodo = Infra::converterDataBrazil2Banco($dtFimPeriodo);

        $dtInicioPeriodo .= ' 00:00:00';
        $dtFimPeriodo .= ' 23:59:59';

        $sql = "SELECT t.id,
                       dfv2.value_text as resposta_pesquisa,
                       t.tn as ticket,
                       t.customer_id as usuario,
                       dfv.value_text as setor,
                       t.title as assunto,
                       t.queue_id,
                       q.name AS Fila_Atendimento,
                       t.ticket_lock_id,
                       t.type_id,
                       tt.name AS Tipo_Chamado,
                       t.service_id,
                       sc.name AS Catalogo_Servico,
                       t.sla_id,
                       s.name AS SLA,
                       t.user_id,
                       u.login AS login_atendente,
                       u.first_name || ' ' || u.last_name AS atendente,
                       t.responsible_user_id,
                       ru.login AS Login_responsavel,
                       ru.first_name || ' ' || ru.last_name AS Responsavel,
                       t.ticket_priority_id,
                       tp.name AS Prioridade,
                       t.ticket_state_id,
                       ts.name AS Estado,
                       t.customer_user_id AS CPF,
                       CASE
			                WHEN t.escalation_solution_time != 0 THEN to_timestamp(t.escalation_solution_time)
			           END AS prazo_atendimento,
                       t.archive_flag,
                       t.create_time as hora_abertura,
                       t.create_by,
                       tcru.first_name || ' ' || tcru.last_name AS Criado_por,
                       t.change_time,
                       t.change_by,
                       tchu.first_name || ' ' || tchu.last_name AS Trocado_por,
                       CASE EXTRACT(DOW
                                    FROM t.create_time)
                           WHEN 0 THEN 'Domingo'
                           WHEN 1 THEN 'Segunda'
                           WHEN 2 THEN 'Terça'
                           WHEN 3 THEN 'Quarta'
                           WHEN 4 THEN 'Quinta'
                           WHEN 5 THEN 'Sexta'
                           WHEN 6 THEN 'Sábado'
                       END AS DiaSemanaCriado,
                       CASE EXTRACT(MONTH
                                    FROM t.create_time)
                           WHEN 1 THEN 'Janeiro'
                           WHEN 2 THEN 'Fevereiro'
                           WHEN 3 THEN 'Março'
                           WHEN 4 THEN 'Abril'
                           WHEN 5 THEN 'Maio'
                           WHEN 6 THEN 'Junho'
                           WHEN 7 THEN 'Julho'
                           WHEN 8 THEN 'Agosto'
                           WHEN 9 THEN 'Setembro'
                           WHEN 10 THEN 'Outubro'
                           WHEN 11 THEN 'Novembro'
                           WHEN 12 THEN 'Dezembro'
                       END || ' ' || EXTRACT (YEAR
                                              FROM t.create_time) AS Mes_Ano,
                                             EXTRACT (YEAR
                                                      FROM t.create_time) AS AnoCriado,
                                                     th.hora_fechamento,
                                                     est.hora_solucao,
                                                     CASE
                                                        WHEN th.hora_fechamento IS NULL THEN CASE
                                                                                                  WHEN est.hora_solucao < now() THEN 'FORA DO PRAZO'
                                                                                                  ELSE 'DENTRO PRAZO'
                                                                                              END
                                                         --WHEN est.hora_solucao IS NULL THEN 'SEM SLA'
                                                         WHEN est.hora_solucao IS NULL THEN 'SEM SLA'
                                                         WHEN est.hora_solucao > th.hora_fechamento THEN 'DENTRO PRAZO'
                                                         --WHEN est.hora_solucao < th.hora_fechamento THEN 'FORA DO PRAZO'
                                                         WHEN est.hora_solucao < th.hora_fechamento THEN 'FORA DO PRAZO'
                                                         WHEN th.hora_fechamento IS NULL THEN CASE
                                                                                                  --WHEN est.hora_solucao < now() THEN 'FORA DO PRAZO'
                                                                                                  WHEN est.hora_solucao < now() THEN 'FORA DO PRAZO'
                                                                                                  ELSE 'DENTRO PRAZO'
                                                                                              END
                                                     END AS status_chamado
                FROM ticket t
                INNER JOIN queue q ON q.id=t.queue_id
                INNER JOIN ticket_type tt ON tt.id = t.type_id
                INNER JOIN service sc ON sc.id = t.service_id
                LEFT JOIN sla s ON s.id = t.sla_id
                INNER JOIN users u ON u.id = t.user_id
                INNER JOIN users ru ON ru.id = t.responsible_user_id
                INNER JOIN ticket_priority tp ON tp.id = t.ticket_priority_id
                INNER JOIN ticket_state ts ON ts.id = t.ticket_state_id
                INNER JOIN users tcru ON tcru.id = t.create_by
                INNER JOIN users tchu ON tchu.id = t.change_by
                LEFT JOIN
                  (SELECT ticket_id,
                          max(create_time) AS hora_fechamento
                   FROM ticket_history
                   WHERE state_id =
                       (SELECT ts.id
                        FROM ticket_state ts
                        WHERE ts.name = 'Aguardando validação')
                   GROUP BY ticket_id) th ON th.ticket_id = t.id
                LEFT JOIN
                  (SELECT id_ticket,
                          max(date_insert),
                          to_timestamp(max(escalation_solution_time)) AS hora_solucao
                   FROM ticket_est
                   GROUP BY id_ticket) est ON est.id_ticket = t.id
                INNER JOIN dynamic_field_value AS dfv ON dfv.object_id = t.id AND dfv.field_id = " . CONST_DYNAMIC_FIELD_DEPARTAMENTO . "
                INNER JOIN dynamic_field_value AS dfv2 ON dfv2.object_id = t.id AND dfv2.field_id = " . CONST_DYNAMIC_FIELD_RESPOSTA_SATISFACAO . "
                WHERE
                    t.create_time BETWEEN '$dtInicioPeriodo' AND '$dtFimPeriodo'
                    AND t.queue_id != ".CONST_FILA_DE_SPAM ."
                    AND ticket_state_id = " . CONST_TICKET_ESTADO_ENCERRADO . "
                    AND dfv2.value_text like '%5%' OR dfv2.value_text like '%4%' --5 = Otimo e 4 = Bom
        ";

        $sql .= " ORDER BY resposta_pesquisa desc, t.create_time";

        #die("<pre>".$sql."</pre>");

        $stmt = Database::prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    function buscarChamadosComRespostaSatisfacaoPorPeriodo($dtInicioPeriodo, $dtFimPeriodo) {
        $dtInicioPeriodo = Infra::converterDataBrazil2Banco($dtInicioPeriodo);
        $dtFimPeriodo = Infra::converterDataBrazil2Banco($dtFimPeriodo);

        $dtInicioPeriodo .= ' 00:00:00';
        $dtFimPeriodo .= ' 23:59:59';

        $sql = "SELECT t.id,
                       dfv2.value_text as resposta_pesquisa,
                       t.tn as ticket,
                       t.customer_id as usuario,
                       dfv.value_text as setor,
                       t.title as assunto,
                       t.queue_id,
                       q.name AS Fila_Atendimento,
                       t.ticket_lock_id,
                       t.type_id,
                       tt.name AS Tipo_Chamado,
                       t.service_id,
                       sc.name AS Catalogo_Servico,
                       t.sla_id,
                       s.name AS SLA,
                       t.user_id,
                       u.login AS login_atendente,
                       u.first_name || ' ' || u.last_name AS atendente,
                       t.responsible_user_id,
                       ru.login AS Login_responsavel,
                       ru.first_name || ' ' || ru.last_name AS Responsavel,
                       t.ticket_priority_id,
                       tp.name AS Prioridade,
                       t.ticket_state_id,
                       ts.name AS Estado,
                       t.customer_user_id AS CPF,
                       CASE
			                WHEN t.escalation_solution_time != 0 THEN to_timestamp(t.escalation_solution_time)
			           END AS prazo_atendimento,
                       t.archive_flag,
                       t.create_time as hora_abertura,
                       t.create_by,
                       tcru.first_name || ' ' || tcru.last_name AS Criado_por,
                       t.change_time,
                       t.change_by,
                       tchu.first_name || ' ' || tchu.last_name AS Trocado_por,
                       CASE EXTRACT(DOW
                                    FROM t.create_time)
                           WHEN 0 THEN 'Domingo'
                           WHEN 1 THEN 'Segunda'
                           WHEN 2 THEN 'Terça'
                           WHEN 3 THEN 'Quarta'
                           WHEN 4 THEN 'Quinta'
                           WHEN 5 THEN 'Sexta'
                           WHEN 6 THEN 'Sábado'
                       END AS DiaSemanaCriado,
                       CASE EXTRACT(MONTH
                                    FROM t.create_time)
                           WHEN 1 THEN 'Janeiro'
                           WHEN 2 THEN 'Fevereiro'
                           WHEN 3 THEN 'Março'
                           WHEN 4 THEN 'Abril'
                           WHEN 5 THEN 'Maio'
                           WHEN 6 THEN 'Junho'
                           WHEN 7 THEN 'Julho'
                           WHEN 8 THEN 'Agosto'
                           WHEN 9 THEN 'Setembro'
                           WHEN 10 THEN 'Outubro'
                           WHEN 11 THEN 'Novembro'
                           WHEN 12 THEN 'Dezembro'
                       END || ' ' || EXTRACT (YEAR
                                              FROM t.create_time) AS Mes_Ano,
                                             EXTRACT (YEAR
                                                      FROM t.create_time) AS AnoCriado,
                                                     th.hora_fechamento,
                                                     est.hora_solucao,
                                                     CASE
                                                        WHEN th.hora_fechamento IS NULL THEN CASE
                                                                                                  WHEN est.hora_solucao < now() THEN 'FORA DO PRAZO'
                                                                                                  ELSE 'DENTRO PRAZO'
                                                                                              END
                                                         --WHEN est.hora_solucao IS NULL THEN 'SEM SLA'
                                                         WHEN est.hora_solucao IS NULL THEN 'SEM SLA'
                                                         WHEN est.hora_solucao > th.hora_fechamento THEN 'DENTRO PRAZO'
                                                         --WHEN est.hora_solucao < th.hora_fechamento THEN 'FORA DO PRAZO'
                                                         WHEN est.hora_solucao < th.hora_fechamento THEN 'FORA DO PRAZO'
                                                         WHEN th.hora_fechamento IS NULL THEN CASE
                                                                                                  --WHEN est.hora_solucao < now() THEN 'FORA DO PRAZO'
                                                                                                  WHEN est.hora_solucao < now() THEN 'FORA DO PRAZO'
                                                                                                  ELSE 'DENTRO PRAZO'
                                                                                              END
                                                     END AS status_chamado
                FROM ticket t
                INNER JOIN queue q ON q.id=t.queue_id
                INNER JOIN ticket_type tt ON tt.id = t.type_id
                INNER JOIN service sc ON sc.id = t.service_id
                LEFT JOIN sla s ON s.id = t.sla_id
                INNER JOIN users u ON u.id = t.user_id
                INNER JOIN users ru ON ru.id = t.responsible_user_id
                INNER JOIN ticket_priority tp ON tp.id = t.ticket_priority_id
                INNER JOIN ticket_state ts ON ts.id = t.ticket_state_id
                INNER JOIN users tcru ON tcru.id = t.create_by
                INNER JOIN users tchu ON tchu.id = t.change_by
                LEFT JOIN
                  (SELECT ticket_id,
                          max(create_time) AS hora_fechamento
                   FROM ticket_history
                   WHERE state_id =
                       (SELECT ts.id
                        FROM ticket_state ts
                        WHERE ts.name = 'Aguardando validação')
                   GROUP BY ticket_id) th ON th.ticket_id = t.id
                LEFT JOIN
                  (SELECT id_ticket,
                          max(date_insert),
                          to_timestamp(max(escalation_solution_time)) AS hora_solucao
                   FROM ticket_est
                   GROUP BY id_ticket) est ON est.id_ticket = t.id
                INNER JOIN dynamic_field_value AS dfv ON dfv.object_id = t.id AND dfv.field_id = " . CONST_DYNAMIC_FIELD_DEPARTAMENTO . "
                INNER JOIN dynamic_field_value AS dfv2 ON dfv2.object_id = t.id AND dfv2.field_id = " . CONST_DYNAMIC_FIELD_RESPOSTA_SATISFACAO . "
                WHERE
                    t.create_time BETWEEN '$dtInicioPeriodo' AND '$dtFimPeriodo'
                    AND t.queue_id != ".CONST_FILA_DE_SPAM ."
                    AND ticket_state_id = " . CONST_TICKET_ESTADO_ENCERRADO . "
        ";

        $sql .= " ORDER BY resposta_pesquisa desc, t.create_time";

        #die("<pre>".$sql."</pre>");

        $stmt = Database::prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    function buscarChamadosTratadosPorPeriodo($dtInicioPeriodo, $dtFimPeriodo) {
        $dtInicioPeriodo = Infra::converterDataBrazil2Banco($dtInicioPeriodo);
        $dtFimPeriodo = Infra::converterDataBrazil2Banco($dtFimPeriodo);

        $dtInicioPeriodo .= ' 00:00:00';
        $dtFimPeriodo .= ' 23:59:59';

        $sql = "SELECT t.id,
                       t.tn as ticket,
                       t.customer_id as usuario,
                       dfv.value_text as setor,
                       t.title as assunto,
                       t.queue_id,
                       q.name AS Fila_Atendimento,
                       t.ticket_lock_id,
                       t.type_id,
                       tt.name AS Tipo_Chamado,
                       t.service_id,
                       sc.name AS Catalogo_Servico,
                       t.sla_id,
                       s.name AS SLA,
                       t.user_id,
                       u.login AS login_atendente,
                       u.first_name || ' ' || u.last_name AS atendente,
                       t.responsible_user_id,
                       ru.login AS Login_responsavel,
                       ru.first_name || ' ' || ru.last_name AS Responsavel,
                       t.ticket_priority_id,
                       tp.name AS Prioridade,
                       t.ticket_state_id,
                       ts.name AS Estado,
                       t.customer_user_id AS CPF,
                       CASE
			                WHEN t.escalation_solution_time != 0 THEN to_timestamp(t.escalation_solution_time)
			           END AS prazo_atendimento,
                       t.archive_flag,
                       t.create_time as hora_abertura,
                       t.create_by,
                       tcru.first_name || ' ' || tcru.last_name AS Criado_por,
                       t.change_time,
                       t.change_by,
                       tchu.first_name || ' ' || tchu.last_name AS Trocado_por,
                       CASE EXTRACT(DOW
                                    FROM t.create_time)
                           WHEN 0 THEN 'Domingo'
                           WHEN 1 THEN 'Segunda'
                           WHEN 2 THEN 'Terça'
                           WHEN 3 THEN 'Quarta'
                           WHEN 4 THEN 'Quinta'
                           WHEN 5 THEN 'Sexta'
                           WHEN 6 THEN 'Sábado'
                       END AS DiaSemanaCriado,
                       CASE EXTRACT(MONTH
                                    FROM t.create_time)
                           WHEN 1 THEN 'Janeiro'
                           WHEN 2 THEN 'Fevereiro'
                           WHEN 3 THEN 'Março'
                           WHEN 4 THEN 'Abril'
                           WHEN 5 THEN 'Maio'
                           WHEN 6 THEN 'Junho'
                           WHEN 7 THEN 'Julho'
                           WHEN 8 THEN 'Agosto'
                           WHEN 9 THEN 'Setembro'
                           WHEN 10 THEN 'Outubro'
                           WHEN 11 THEN 'Novembro'
                           WHEN 12 THEN 'Dezembro'
                       END || ' ' || EXTRACT (YEAR
                                              FROM t.create_time) AS Mes_Ano,
                                             EXTRACT (YEAR
                                                      FROM t.create_time) AS AnoCriado,
                                                     th.hora_fechamento,
                                                     est.hora_solucao,
                                                     CASE
                                                         WHEN est.hora_solucao IS NULL THEN 'SEM SLA'
                                                         WHEN est.hora_solucao > th.hora_fechamento THEN 'DENTRO PRAZO'
                                                         WHEN est.hora_solucao < th.hora_fechamento THEN 'FORA DO PRAZO'
                                                         WHEN th.hora_fechamento IS NULL THEN CASE
                                                                                                  WHEN est.hora_solucao < now() THEN 'FORA DO PRAZO'
                                                                                                  ELSE 'DENTRO PRAZO'
                                                                                              END
                                                     END AS status_chamado
                FROM ticket t
                INNER JOIN queue q ON q.id=t.queue_id
                INNER JOIN ticket_type tt ON tt.id = t.type_id
                INNER JOIN service sc ON sc.id = t.service_id
                LEFT JOIN sla s ON s.id = t.sla_id
                INNER JOIN users u ON u.id = t.user_id
                INNER JOIN users ru ON ru.id = t.responsible_user_id
                INNER JOIN ticket_priority tp ON tp.id = t.ticket_priority_id
                INNER JOIN ticket_state ts ON ts.id = t.ticket_state_id
                INNER JOIN users tcru ON tcru.id = t.create_by
                INNER JOIN users tchu ON tchu.id = t.change_by
                LEFT JOIN
                  (SELECT ticket_id,
                          max(create_time) AS hora_fechamento
                   FROM ticket_history
                   WHERE state_id =
                       (SELECT ts.id
                        FROM ticket_state ts
                        WHERE ts.name = 'Aguardando validação')
                   GROUP BY ticket_id) th ON th.ticket_id = t.id
                LEFT JOIN
                  (SELECT id_ticket,
                          max(date_insert),
                          to_timestamp(max(escalation_solution_time)) AS hora_solucao
                   FROM ticket_est
                   GROUP BY id_ticket) est ON est.id_ticket = t.id
                INNER JOIN dynamic_field_value AS dfv ON dfv.object_id = t.id AND dfv.field_id = " . CONST_DYNAMIC_FIELD_DEPARTAMENTO . "
                WHERE
                    t.create_time BETWEEN '$dtInicioPeriodo' AND '$dtFimPeriodo'
                    AND t.queue_id != ".CONST_FILA_DE_SPAM ."
                    AND ticket_state_id = " . CONST_TICKET_ESTADO_ENCERRADO . "
        ";

        $sql .= " ORDER BY t.create_time";

        #die("<pre>".$sql."</pre>");

        $stmt = Database::prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    function buscarRequisicoesRecebidasPorPeriodo($dtInicioPeriodo, $dtFimPeriodo) {
        $dtInicioPeriodo = Infra::converterDataBrazil2Banco($dtInicioPeriodo);
        $dtFimPeriodo = Infra::converterDataBrazil2Banco($dtFimPeriodo);

        $dtInicioPeriodo .= ' 00:00:00';
        $dtFimPeriodo .= ' 23:59:59';

        $sql = "SELECT t.id,
                       t.tn as ticket,
                       t.customer_id as usuario,
                       dfv.value_text as setor,
                       t.title as assunto,
                       t.queue_id,
                       q.name AS Fila_Atendimento,
                       t.ticket_lock_id,
                       t.type_id,
                       tt.name AS Tipo_Chamado,
                       t.service_id,
                       sc.name AS Catalogo_Servico,
                       t.sla_id,
                       s.name AS SLA,
                       t.user_id,
                       u.login AS login_atendente,
                       u.first_name || ' ' || u.last_name AS atendente,
                       t.responsible_user_id,
                       ru.login AS Login_responsavel,
                       ru.first_name || ' ' || ru.last_name AS Responsavel,
                       t.ticket_priority_id,
                       tp.name AS Prioridade,
                       t.ticket_state_id,
                       ts.name AS Estado,
                       t.customer_user_id AS CPF,
                       CASE
			                WHEN t.escalation_solution_time != 0 THEN to_timestamp(t.escalation_solution_time)
			           END AS prazo_atendimento,
                       t.archive_flag,
                       t.create_time as hora_abertura,
                       t.create_by,
                       tcru.first_name || ' ' || tcru.last_name AS Criado_por,
                       t.change_time,
                       t.change_by,
                       tchu.first_name || ' ' || tchu.last_name AS Trocado_por,
                       CASE EXTRACT(DOW
                                    FROM t.create_time)
                           WHEN 0 THEN 'Domingo'
                           WHEN 1 THEN 'Segunda'
                           WHEN 2 THEN 'Terça'
                           WHEN 3 THEN 'Quarta'
                           WHEN 4 THEN 'Quinta'
                           WHEN 5 THEN 'Sexta'
                           WHEN 6 THEN 'Sábado'
                       END AS DiaSemanaCriado,
                       CASE EXTRACT(MONTH
                                    FROM t.create_time)
                           WHEN 1 THEN 'Janeiro'
                           WHEN 2 THEN 'Fevereiro'
                           WHEN 3 THEN 'Março'
                           WHEN 4 THEN 'Abril'
                           WHEN 5 THEN 'Maio'
                           WHEN 6 THEN 'Junho'
                           WHEN 7 THEN 'Julho'
                           WHEN 8 THEN 'Agosto'
                           WHEN 9 THEN 'Setembro'
                           WHEN 10 THEN 'Outubro'
                           WHEN 11 THEN 'Novembro'
                           WHEN 12 THEN 'Dezembro'
                       END || ' ' || EXTRACT (YEAR
                                              FROM t.create_time) AS Mes_Ano,
                                             EXTRACT (YEAR
                                                      FROM t.create_time) AS AnoCriado,
                                                     th.hora_fechamento,
                                                     est.hora_solucao,
                                                     CASE
                                                         WHEN est.hora_solucao IS NULL THEN 'SEM SLA'
                                                         WHEN est.hora_solucao > th.hora_fechamento THEN 'DENTRO PRAZO'
                                                         WHEN est.hora_solucao < th.hora_fechamento THEN 'FORA DO PRAZO'
                                                         WHEN th.hora_fechamento IS NULL THEN CASE
                                                                                                  WHEN est.hora_solucao < now() THEN 'FORA DO PRAZO'
                                                                                                  ELSE 'DENTRO PRAZO'
                                                                                              END
                                                     END AS status_chamado
                FROM ticket t
                INNER JOIN queue q ON q.id=t.queue_id
                INNER JOIN ticket_type tt ON tt.id = t.type_id
                INNER JOIN service sc ON sc.id = t.service_id
                LEFT JOIN sla s ON s.id = t.sla_id
                INNER JOIN users u ON u.id = t.user_id
                INNER JOIN users ru ON ru.id = t.responsible_user_id
                INNER JOIN ticket_priority tp ON tp.id = t.ticket_priority_id
                INNER JOIN ticket_state ts ON ts.id = t.ticket_state_id
                INNER JOIN users tcru ON tcru.id = t.create_by
                INNER JOIN users tchu ON tchu.id = t.change_by
                LEFT JOIN
                  (SELECT ticket_id,
                          max(create_time) AS hora_fechamento
                   FROM ticket_history
                   WHERE state_id =
                       (SELECT ts.id
                        FROM ticket_state ts
                        WHERE ts.name = 'Aguardando validação')
                   GROUP BY ticket_id) th ON th.ticket_id = t.id
                LEFT JOIN
                  (SELECT id_ticket,
                          max(date_insert),
                          to_timestamp(max(escalation_solution_time)) AS hora_solucao
                   FROM ticket_est
                   GROUP BY id_ticket) est ON est.id_ticket = t.id
                INNER JOIN dynamic_field_value AS dfv ON dfv.object_id = t.id AND dfv.field_id = " . CONST_DYNAMIC_FIELD_DEPARTAMENTO . "
                WHERE
                    t.create_time BETWEEN '$dtInicioPeriodo' AND '$dtFimPeriodo'
                    AND t.queue_id != ".CONST_FILA_DE_SPAM ."
                    AND t.type_id = " . CONST_TICKET_TYPE_REQUISICAO . "
        ";

        $sql .= " ORDER BY t.create_time";

        #die("<pre>".$sql."</pre>");

        $stmt = Database::prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    function buscarRequisicoesTratadosPorPeriodo($dtInicioPeriodo, $dtFimPeriodo) {
        $dtInicioPeriodo = Infra::converterDataBrazil2Banco($dtInicioPeriodo);
        $dtFimPeriodo = Infra::converterDataBrazil2Banco($dtFimPeriodo);

        $dtInicioPeriodo .= ' 00:00:00';
        $dtFimPeriodo .= ' 23:59:59';

        $sql = "SELECT t.id,
                       t.tn as ticket,
                       t.customer_id as usuario,
                       dfv.value_text as setor,
                       t.title as assunto,
                       t.queue_id,
                       q.name AS Fila_Atendimento,
                       t.ticket_lock_id,
                       t.type_id,
                       tt.name AS Tipo_Chamado,
                       t.service_id,
                       sc.name AS Catalogo_Servico,
                       t.sla_id,
                       s.name AS SLA,
                       t.user_id,
                       u.login AS login_atendente,
                       u.first_name || ' ' || u.last_name AS atendente,
                       t.responsible_user_id,
                       ru.login AS Login_responsavel,
                       ru.first_name || ' ' || ru.last_name AS Responsavel,
                       t.ticket_priority_id,
                       tp.name AS Prioridade,
                       t.ticket_state_id,
                       ts.name AS Estado,
                       t.customer_user_id AS CPF,
                       CASE
			                WHEN t.escalation_solution_time != 0 THEN to_timestamp(t.escalation_solution_time)
			           END AS prazo_atendimento,
                       t.archive_flag,
                       t.create_time as hora_abertura,
                       t.create_by,
                       tcru.first_name || ' ' || tcru.last_name AS Criado_por,
                       t.change_time,
                       t.change_by,
                       tchu.first_name || ' ' || tchu.last_name AS Trocado_por,
                       CASE EXTRACT(DOW
                                    FROM t.create_time)
                           WHEN 0 THEN 'Domingo'
                           WHEN 1 THEN 'Segunda'
                           WHEN 2 THEN 'Terça'
                           WHEN 3 THEN 'Quarta'
                           WHEN 4 THEN 'Quinta'
                           WHEN 5 THEN 'Sexta'
                           WHEN 6 THEN 'Sábado'
                       END AS DiaSemanaCriado,
                       CASE EXTRACT(MONTH
                                    FROM t.create_time)
                           WHEN 1 THEN 'Janeiro'
                           WHEN 2 THEN 'Fevereiro'
                           WHEN 3 THEN 'Março'
                           WHEN 4 THEN 'Abril'
                           WHEN 5 THEN 'Maio'
                           WHEN 6 THEN 'Junho'
                           WHEN 7 THEN 'Julho'
                           WHEN 8 THEN 'Agosto'
                           WHEN 9 THEN 'Setembro'
                           WHEN 10 THEN 'Outubro'
                           WHEN 11 THEN 'Novembro'
                           WHEN 12 THEN 'Dezembro'
                       END || ' ' || EXTRACT (YEAR
                                              FROM t.create_time) AS Mes_Ano,
                                             EXTRACT (YEAR
                                                      FROM t.create_time) AS AnoCriado,
                                                     th.hora_fechamento,
                                                     est.hora_solucao,
                                                     CASE
                                                         WHEN est.hora_solucao IS NULL THEN 'SEM SLA'
                                                         WHEN est.hora_solucao > th.hora_fechamento THEN 'DENTRO PRAZO'
                                                         WHEN est.hora_solucao < th.hora_fechamento THEN 'FORA DO PRAZO'
                                                         WHEN th.hora_fechamento IS NULL THEN CASE
                                                                                                  WHEN est.hora_solucao < now() THEN 'FORA DO PRAZO'
                                                                                                  ELSE 'DENTRO PRAZO'
                                                                                              END
                                                     END AS status_chamado
                FROM ticket t
                INNER JOIN queue q ON q.id=t.queue_id
                INNER JOIN ticket_type tt ON tt.id = t.type_id
                INNER JOIN service sc ON sc.id = t.service_id
                LEFT JOIN sla s ON s.id = t.sla_id
                INNER JOIN users u ON u.id = t.user_id
                INNER JOIN users ru ON ru.id = t.responsible_user_id
                INNER JOIN ticket_priority tp ON tp.id = t.ticket_priority_id
                INNER JOIN ticket_state ts ON ts.id = t.ticket_state_id
                INNER JOIN users tcru ON tcru.id = t.create_by
                INNER JOIN users tchu ON tchu.id = t.change_by
                LEFT JOIN
                  (SELECT ticket_id,
                          max(create_time) AS hora_fechamento
                   FROM ticket_history
                   WHERE state_id =
                       (SELECT ts.id
                        FROM ticket_state ts
                        WHERE ts.name = 'Aguardando validação')
                   GROUP BY ticket_id) th ON th.ticket_id = t.id
                LEFT JOIN
                  (SELECT id_ticket,
                          max(date_insert),
                          to_timestamp(max(escalation_solution_time)) AS hora_solucao
                   FROM ticket_est
                   GROUP BY id_ticket) est ON est.id_ticket = t.id
                INNER JOIN dynamic_field_value AS dfv ON dfv.object_id = t.id AND dfv.field_id = " . CONST_DYNAMIC_FIELD_DEPARTAMENTO . "
                WHERE
                    t.create_time BETWEEN '$dtInicioPeriodo' AND '$dtFimPeriodo'
                    AND t.queue_id != ".CONST_FILA_DE_SPAM ."
                    AND ticket_state_id = " . CONST_TICKET_ESTADO_ENCERRADO . "
                    AND t.type_id = " . CONST_TICKET_TYPE_REQUISICAO . "
        ";

        $sql .= " ORDER BY t.create_time";

        #die("<pre>".$sql."</pre>");

        $stmt = Database::prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }


    function buscarIncidentesTratadosPorPeriodo($dtInicioPeriodo, $dtFimPeriodo) {
        $dtInicioPeriodo = Infra::converterDataBrazil2Banco($dtInicioPeriodo);
        $dtFimPeriodo = Infra::converterDataBrazil2Banco($dtFimPeriodo);

        $dtInicioPeriodo .= ' 00:00:00';
        $dtFimPeriodo .= ' 23:59:59';

        $sql = "SELECT t.id,
                       t.tn as ticket,
                       t.customer_id as usuario,
                       dfv.value_text as setor,
                       t.title as assunto,
                       t.queue_id,
                       q.name AS Fila_Atendimento,
                       t.ticket_lock_id,
                       t.type_id,
                       tt.name AS Tipo_Chamado,
                       t.service_id,
                       sc.name AS Catalogo_Servico,
                       t.sla_id,
                       s.name AS SLA,
                       t.user_id,
                       u.login AS login_atendente,
                       u.first_name || ' ' || u.last_name AS atendente,
                       t.responsible_user_id,
                       ru.login AS Login_responsavel,
                       ru.first_name || ' ' || ru.last_name AS Responsavel,
                       t.ticket_priority_id,
                       tp.name AS Prioridade,
                       t.ticket_state_id,
                       ts.name AS Estado,
                       t.customer_user_id AS CPF,
                       CASE
			                WHEN t.escalation_solution_time != 0 THEN to_timestamp(t.escalation_solution_time)
			           END AS prazo_atendimento,
                       t.archive_flag,
                       t.create_time as hora_abertura,
                       t.create_by,
                       tcru.first_name || ' ' || tcru.last_name AS Criado_por,
                       t.change_time,
                       t.change_by,
                       tchu.first_name || ' ' || tchu.last_name AS Trocado_por,
                       CASE EXTRACT(DOW
                                    FROM t.create_time)
                           WHEN 0 THEN 'Domingo'
                           WHEN 1 THEN 'Segunda'
                           WHEN 2 THEN 'Terça'
                           WHEN 3 THEN 'Quarta'
                           WHEN 4 THEN 'Quinta'
                           WHEN 5 THEN 'Sexta'
                           WHEN 6 THEN 'Sábado'
                       END AS DiaSemanaCriado,
                       CASE EXTRACT(MONTH
                                    FROM t.create_time)
                           WHEN 1 THEN 'Janeiro'
                           WHEN 2 THEN 'Fevereiro'
                           WHEN 3 THEN 'Março'
                           WHEN 4 THEN 'Abril'
                           WHEN 5 THEN 'Maio'
                           WHEN 6 THEN 'Junho'
                           WHEN 7 THEN 'Julho'
                           WHEN 8 THEN 'Agosto'
                           WHEN 9 THEN 'Setembro'
                           WHEN 10 THEN 'Outubro'
                           WHEN 11 THEN 'Novembro'
                           WHEN 12 THEN 'Dezembro'
                       END || ' ' || EXTRACT (YEAR
                                              FROM t.create_time) AS Mes_Ano,
                                             EXTRACT (YEAR
                                                      FROM t.create_time) AS AnoCriado,
                                                     th.hora_fechamento,
                                                     est.hora_solucao,
                                                     CASE
                                                         WHEN est.hora_solucao IS NULL THEN 'SEM SLA'
                                                         WHEN est.hora_solucao > th.hora_fechamento THEN 'DENTRO PRAZO'
                                                         WHEN est.hora_solucao < th.hora_fechamento THEN 'FORA DO PRAZO'
                                                         WHEN th.hora_fechamento IS NULL THEN CASE
                                                                                                  WHEN est.hora_solucao < now() THEN 'FORA DO PRAZO'
                                                                                                  ELSE 'DENTRO PRAZO'
                                                                                              END
                                                     END AS status_chamado
                FROM ticket t
                INNER JOIN queue q ON q.id=t.queue_id
                INNER JOIN ticket_type tt ON tt.id = t.type_id
                INNER JOIN service sc ON sc.id = t.service_id
                LEFT JOIN sla s ON s.id = t.sla_id
                INNER JOIN users u ON u.id = t.user_id
                INNER JOIN users ru ON ru.id = t.responsible_user_id
                INNER JOIN ticket_priority tp ON tp.id = t.ticket_priority_id
                INNER JOIN ticket_state ts ON ts.id = t.ticket_state_id
                INNER JOIN users tcru ON tcru.id = t.create_by
                INNER JOIN users tchu ON tchu.id = t.change_by
                LEFT JOIN
                  (SELECT ticket_id,
                          max(create_time) AS hora_fechamento
                   FROM ticket_history
                   WHERE state_id =
                       (SELECT ts.id
                        FROM ticket_state ts
                        WHERE ts.name = 'Aguardando validação')
                   GROUP BY ticket_id) th ON th.ticket_id = t.id
                LEFT JOIN
                  (SELECT id_ticket,
                          max(date_insert),
                          to_timestamp(max(escalation_solution_time)) AS hora_solucao
                   FROM ticket_est
                   GROUP BY id_ticket) est ON est.id_ticket = t.id
                INNER JOIN dynamic_field_value AS dfv ON dfv.object_id = t.id AND dfv.field_id = " . CONST_DYNAMIC_FIELD_DEPARTAMENTO . "
                WHERE
                    t.create_time BETWEEN '$dtInicioPeriodo' AND '$dtFimPeriodo'
                    AND t.queue_id != ".CONST_FILA_DE_SPAM ."
                    AND ticket_state_id = " . CONST_TICKET_ESTADO_ENCERRADO . "
                    AND t.type_id = " . CONST_TICKET_TYPE_INCIDENTE . "
        ";

        $sql .= " ORDER BY t.create_time";

        #die("<pre>".$sql."</pre>");

        $stmt = Database::prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    function buscarIncidentesRecebidasPorPeriodo($dtInicioPeriodo, $dtFimPeriodo) {
        $dtInicioPeriodo = Infra::converterDataBrazil2Banco($dtInicioPeriodo);
        $dtFimPeriodo = Infra::converterDataBrazil2Banco($dtFimPeriodo);

        $dtInicioPeriodo .= ' 00:00:00';
        $dtFimPeriodo .= ' 23:59:59';

        $sql = "SELECT t.id,
                       t.tn as ticket,
                       t.customer_id as usuario,
                       dfv.value_text as setor,
                       t.title as assunto,
                       t.queue_id,
                       q.name AS Fila_Atendimento,
                       t.ticket_lock_id,
                       t.type_id,
                       tt.name AS Tipo_Chamado,
                       t.service_id,
                       sc.name AS Catalogo_Servico,
                       t.sla_id,
                       s.name AS SLA,
                       t.user_id,
                       u.login AS login_atendente,
                       u.first_name || ' ' || u.last_name AS atendente,
                       t.responsible_user_id,
                       ru.login AS Login_responsavel,
                       ru.first_name || ' ' || ru.last_name AS Responsavel,
                       t.ticket_priority_id,
                       tp.name AS Prioridade,
                       t.ticket_state_id,
                       ts.name AS Estado,
                       t.customer_user_id AS CPF,
                       CASE
			                WHEN t.escalation_solution_time != 0 THEN to_timestamp(t.escalation_solution_time)
			           END AS prazo_atendimento,
                       t.archive_flag,
                       t.create_time as hora_abertura,
                       t.create_by,
                       tcru.first_name || ' ' || tcru.last_name AS Criado_por,
                       t.change_time,
                       t.change_by,
                       tchu.first_name || ' ' || tchu.last_name AS Trocado_por,
                       CASE EXTRACT(DOW
                                    FROM t.create_time)
                           WHEN 0 THEN 'Domingo'
                           WHEN 1 THEN 'Segunda'
                           WHEN 2 THEN 'Terça'
                           WHEN 3 THEN 'Quarta'
                           WHEN 4 THEN 'Quinta'
                           WHEN 5 THEN 'Sexta'
                           WHEN 6 THEN 'Sábado'
                       END AS DiaSemanaCriado,
                       CASE EXTRACT(MONTH
                                    FROM t.create_time)
                           WHEN 1 THEN 'Janeiro'
                           WHEN 2 THEN 'Fevereiro'
                           WHEN 3 THEN 'Março'
                           WHEN 4 THEN 'Abril'
                           WHEN 5 THEN 'Maio'
                           WHEN 6 THEN 'Junho'
                           WHEN 7 THEN 'Julho'
                           WHEN 8 THEN 'Agosto'
                           WHEN 9 THEN 'Setembro'
                           WHEN 10 THEN 'Outubro'
                           WHEN 11 THEN 'Novembro'
                           WHEN 12 THEN 'Dezembro'
                       END || ' ' || EXTRACT (YEAR
                                              FROM t.create_time) AS Mes_Ano,
                                             EXTRACT (YEAR
                                                      FROM t.create_time) AS AnoCriado,
                                                     th.hora_fechamento,
                                                     est.hora_solucao,
                                                     CASE
                                                         WHEN est.hora_solucao IS NULL THEN 'SEM SLA'
                                                         WHEN est.hora_solucao > th.hora_fechamento THEN 'DENTRO PRAZO'
                                                         WHEN est.hora_solucao < th.hora_fechamento THEN 'FORA DO PRAZO'
                                                         WHEN th.hora_fechamento IS NULL THEN CASE
                                                                                                  WHEN est.hora_solucao < now() THEN 'FORA DO PRAZO'
                                                                                                  ELSE 'DENTRO PRAZO'
                                                                                              END
                                                     END AS status_chamado
                FROM ticket t
                INNER JOIN queue q ON q.id=t.queue_id
                INNER JOIN ticket_type tt ON tt.id = t.type_id
                INNER JOIN service sc ON sc.id = t.service_id
                LEFT JOIN sla s ON s.id = t.sla_id
                INNER JOIN users u ON u.id = t.user_id
                INNER JOIN users ru ON ru.id = t.responsible_user_id
                INNER JOIN ticket_priority tp ON tp.id = t.ticket_priority_id
                INNER JOIN ticket_state ts ON ts.id = t.ticket_state_id
                INNER JOIN users tcru ON tcru.id = t.create_by
                INNER JOIN users tchu ON tchu.id = t.change_by
                LEFT JOIN
                  (SELECT ticket_id,
                          max(create_time) AS hora_fechamento
                   FROM ticket_history
                   WHERE state_id =
                       (SELECT ts.id
                        FROM ticket_state ts
                        WHERE ts.name = 'Aguardando validação')
                   GROUP BY ticket_id) th ON th.ticket_id = t.id
                LEFT JOIN
                  (SELECT id_ticket,
                          max(date_insert),
                          to_timestamp(max(escalation_solution_time)) AS hora_solucao
                   FROM ticket_est
                   GROUP BY id_ticket) est ON est.id_ticket = t.id
                INNER JOIN dynamic_field_value AS dfv ON dfv.object_id = t.id AND dfv.field_id = " . CONST_DYNAMIC_FIELD_DEPARTAMENTO . "
                WHERE
                    t.create_time BETWEEN '$dtInicioPeriodo' AND '$dtFimPeriodo'
                    AND t.queue_id != ".CONST_FILA_DE_SPAM ."
                    AND t.type_id = " . CONST_TICKET_TYPE_INCIDENTE . "
        ";

        $sql .= " ORDER BY t.create_time";

        #die("<pre>".$sql."</pre>");

        $stmt = Database::prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    function buscarChamadosAtendidosDentroDoPrazoPorPeriodo($dtInicioPeriodo, $dtFimPeriodo) {
        $dtInicioPeriodo = Infra::converterDataBrazil2Banco($dtInicioPeriodo);
        $dtFimPeriodo = Infra::converterDataBrazil2Banco($dtFimPeriodo);

        $dtInicioPeriodo .= ' 00:00:00';
        $dtFimPeriodo .= ' 23:59:59';

        $sql = "SELECT t.id,
                       --dfv2.value_text as resposta_pesquisa,
                       t.tn as ticket,
                       t.customer_id as usuario,
                       dfv.value_text as setor,
                       t.title as assunto,
                       t.queue_id,
                       q.name AS Fila_Atendimento,
                       t.ticket_lock_id,
                       t.type_id,
                       tt.name AS Tipo_Chamado,
                       t.service_id,
                       sc.name AS Catalogo_Servico,
                       t.sla_id,
                       s.name AS SLA,
                       t.user_id,
                       u.login AS login_atendente,
                       u.first_name || ' ' || u.last_name AS atendente,
                       t.responsible_user_id,
                       ru.login AS Login_responsavel,
                       ru.first_name || ' ' || ru.last_name AS Responsavel,
                       t.ticket_priority_id,
                       tp.name AS Prioridade,
                       t.ticket_state_id,
                       ts.name AS Estado,
                       t.customer_user_id AS CPF,
                       CASE
			                WHEN t.escalation_solution_time != 0 THEN to_timestamp(t.escalation_solution_time)
			           END AS prazo_atendimento,
                       t.archive_flag,
                       t.create_time as hora_abertura,
                       t.create_by,
                       tcru.first_name || ' ' || tcru.last_name AS Criado_por,
                       t.change_time,
                       t.change_by,
                       tchu.first_name || ' ' || tchu.last_name AS Trocado_por,
                       CASE EXTRACT(DOW
                                    FROM t.create_time)
                           WHEN 0 THEN 'Domingo'
                           WHEN 1 THEN 'Segunda'
                           WHEN 2 THEN 'Terça'
                           WHEN 3 THEN 'Quarta'
                           WHEN 4 THEN 'Quinta'
                           WHEN 5 THEN 'Sexta'
                           WHEN 6 THEN 'Sábado'
                       END AS DiaSemanaCriado,
                       CASE EXTRACT(MONTH
                                    FROM t.create_time)
                           WHEN 1 THEN 'Janeiro'
                           WHEN 2 THEN 'Fevereiro'
                           WHEN 3 THEN 'Março'
                           WHEN 4 THEN 'Abril'
                           WHEN 5 THEN 'Maio'
                           WHEN 6 THEN 'Junho'
                           WHEN 7 THEN 'Julho'
                           WHEN 8 THEN 'Agosto'
                           WHEN 9 THEN 'Setembro'
                           WHEN 10 THEN 'Outubro'
                           WHEN 11 THEN 'Novembro'
                           WHEN 12 THEN 'Dezembro'
                       END || ' ' || EXTRACT (YEAR
                                              FROM t.create_time) AS Mes_Ano,
                                             EXTRACT (YEAR
                                                      FROM t.create_time) AS AnoCriado,
                                                     th.hora_fechamento,
                                                     est.hora_solucao,
                                                     CASE
                                                        WHEN th.hora_fechamento IS NULL THEN CASE
                                                                                                  WHEN est.hora_solucao < now() THEN 'FORA DO PRAZO'
                                                                                                  ELSE 'DENTRO PRAZO'
                                                                                              END
                                                         --WHEN est.hora_solucao IS NULL THEN 'SEM SLA'
                                                         WHEN est.hora_solucao IS NULL THEN 'SEM SLA'
                                                         WHEN est.hora_solucao > th.hora_fechamento THEN 'DENTRO PRAZO'
                                                         --WHEN est.hora_solucao < th.hora_fechamento THEN 'FORA DO PRAZO'
                                                         WHEN est.hora_solucao < th.hora_fechamento THEN 'FORA DO PRAZO'
                                                         WHEN th.hora_fechamento IS NULL THEN CASE
                                                                                                  --WHEN est.hora_solucao < now() THEN 'FORA DO PRAZO'
                                                                                                  WHEN est.hora_solucao < now() THEN 'FORA DO PRAZO'
                                                                                                  ELSE 'DENTRO PRAZO'
                                                                                              END
                                                     END AS status_chamado
                FROM ticket t
                INNER JOIN queue q ON q.id=t.queue_id
                INNER JOIN ticket_type tt ON tt.id = t.type_id
                INNER JOIN service sc ON sc.id = t.service_id
                LEFT JOIN sla s ON s.id = t.sla_id
                INNER JOIN users u ON u.id = t.user_id
                INNER JOIN users ru ON ru.id = t.responsible_user_id
                INNER JOIN ticket_priority tp ON tp.id = t.ticket_priority_id
                INNER JOIN ticket_state ts ON ts.id = t.ticket_state_id
                INNER JOIN users tcru ON tcru.id = t.create_by
                INNER JOIN users tchu ON tchu.id = t.change_by
                LEFT JOIN
                  (SELECT ticket_id,
                          max(create_time) AS hora_fechamento
                   FROM ticket_history
                   WHERE state_id =
                       (SELECT ts.id
                        FROM ticket_state ts
                        WHERE ts.name = 'Aguardando validação')
                   GROUP BY ticket_id) th ON th.ticket_id = t.id
                LEFT JOIN
                  (SELECT id_ticket,
                          max(date_insert),
                          to_timestamp(max(escalation_solution_time)) AS hora_solucao
                   FROM ticket_est
                   GROUP BY id_ticket) est ON est.id_ticket = t.id
                INNER JOIN dynamic_field_value AS dfv ON dfv.object_id = t.id AND dfv.field_id = " . CONST_DYNAMIC_FIELD_DEPARTAMENTO . "
                --INNER JOIN dynamic_field_value AS dfv2 ON dfv2.object_id = t.id AND dfv2.field_id = " . CONST_DYNAMIC_FIELD_RESPOSTA_SATISFACAO . "
                WHERE
                    t.create_time BETWEEN '$dtInicioPeriodo' AND '$dtFimPeriodo'
                    AND t.queue_id != ".CONST_FILA_DE_SPAM ."
                    AND ticket_state_id = " . CONST_TICKET_ESTADO_ENCERRADO . "
                    AND (est.hora_solucao > th.hora_fechamento AND est.hora_solucao IS NOT NULL OR (th.hora_fechamento IS NULL AND est.hora_solucao >= now()))
        ";

        $sql .= " ORDER BY t.create_time";

        #die("<pre>".$sql."</pre>");

        $stmt = Database::prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }


    function buscarChamadosRecebidosPorPrioridadePorPeriodo($dtInicioPeriodo, $dtFimPeriodo) {
        $dtInicioPeriodo = Infra::converterDataBrazil2Banco($dtInicioPeriodo);
        $dtFimPeriodo = Infra::converterDataBrazil2Banco($dtFimPeriodo);

        $dtInicioPeriodo .= ' 00:00:00';
        $dtFimPeriodo .= ' 23:59:59';

        $sql = "SELECT t.id,
                       t.tn as ticket,
                       t.customer_id as usuario,
                       dfv.value_text as setor,
                       t.title as assunto,
                       t.queue_id,
                       q.name AS Fila_Atendimento,
                       t.ticket_lock_id,
                       t.type_id,
                       tt.name AS Tipo_Chamado,
                       t.service_id,
                       sc.name AS Catalogo_Servico,
                       t.sla_id,
                       s.name AS SLA,
                       t.user_id,
                       u.login AS login_atendente,
                       u.first_name || ' ' || u.last_name AS atendente,
                       t.responsible_user_id,
                       ru.login AS Login_responsavel,
                       ru.first_name || ' ' || ru.last_name AS Responsavel,
                       t.ticket_priority_id,
                       tp.name AS Prioridade,
                       t.ticket_state_id,
                       ts.name AS Estado,
                       t.customer_user_id AS CPF,
                       CASE
			                WHEN t.escalation_solution_time != 0 THEN to_timestamp(t.escalation_solution_time)
			           END AS prazo_atendimento,
                       t.archive_flag,
                       t.create_time as hora_abertura,
                       t.create_by,
                       tcru.first_name || ' ' || tcru.last_name AS Criado_por,
                       t.change_time,
                       t.change_by,
                       tchu.first_name || ' ' || tchu.last_name AS Trocado_por,
                       CASE EXTRACT(DOW
                                    FROM t.create_time)
                           WHEN 0 THEN 'Domingo'
                           WHEN 1 THEN 'Segunda'
                           WHEN 2 THEN 'Terça'
                           WHEN 3 THEN 'Quarta'
                           WHEN 4 THEN 'Quinta'
                           WHEN 5 THEN 'Sexta'
                           WHEN 6 THEN 'Sábado'
                       END AS DiaSemanaCriado,
                       CASE EXTRACT(MONTH
                                    FROM t.create_time)
                           WHEN 1 THEN 'Janeiro'
                           WHEN 2 THEN 'Fevereiro'
                           WHEN 3 THEN 'Março'
                           WHEN 4 THEN 'Abril'
                           WHEN 5 THEN 'Maio'
                           WHEN 6 THEN 'Junho'
                           WHEN 7 THEN 'Julho'
                           WHEN 8 THEN 'Agosto'
                           WHEN 9 THEN 'Setembro'
                           WHEN 10 THEN 'Outubro'
                           WHEN 11 THEN 'Novembro'
                           WHEN 12 THEN 'Dezembro'
                       END || ' ' || EXTRACT (YEAR
                                              FROM t.create_time) AS Mes_Ano,
                                             EXTRACT (YEAR
                                                      FROM t.create_time) AS AnoCriado,
                                                     th.hora_fechamento,
                                                     est.hora_solucao,
                                                     CASE
                                                         WHEN est.hora_solucao IS NULL THEN 'SEM SLA'
                                                         WHEN est.hora_solucao > th.hora_fechamento THEN 'DENTRO PRAZO'
                                                         WHEN est.hora_solucao < th.hora_fechamento THEN 'FORA DO PRAZO'
                                                         WHEN th.hora_fechamento IS NULL THEN CASE
                                                                                                  WHEN est.hora_solucao < now() THEN 'FORA DO PRAZO'
                                                                                                  ELSE 'DENTRO PRAZO'
                                                                                              END
                                                     END AS status_chamado
                FROM ticket t
                INNER JOIN queue q ON q.id=t.queue_id
                INNER JOIN ticket_type tt ON tt.id = t.type_id
                INNER JOIN service sc ON sc.id = t.service_id
                LEFT JOIN sla s ON s.id = t.sla_id
                INNER JOIN users u ON u.id = t.user_id
                INNER JOIN users ru ON ru.id = t.responsible_user_id
                INNER JOIN ticket_priority tp ON tp.id = t.ticket_priority_id
                INNER JOIN ticket_state ts ON ts.id = t.ticket_state_id
                INNER JOIN users tcru ON tcru.id = t.create_by
                INNER JOIN users tchu ON tchu.id = t.change_by
                LEFT JOIN
                  (SELECT ticket_id,
                          max(create_time) AS hora_fechamento
                   FROM ticket_history
                   WHERE state_id =
                       (SELECT ts.id
                        FROM ticket_state ts
                        WHERE ts.name = 'Aguardando validação')
                   GROUP BY ticket_id) th ON th.ticket_id = t.id
                LEFT JOIN
                  (SELECT id_ticket,
                          max(date_insert),
                          to_timestamp(max(escalation_solution_time)) AS hora_solucao
                   FROM ticket_est
                   GROUP BY id_ticket) est ON est.id_ticket = t.id
                INNER JOIN dynamic_field_value AS dfv ON dfv.object_id = t.id AND dfv.field_id = " . CONST_DYNAMIC_FIELD_DEPARTAMENTO . "
                WHERE
                    t.create_time BETWEEN '$dtInicioPeriodo' AND '$dtFimPeriodo'
                    AND t.queue_id != ".CONST_FILA_DE_SPAM ."
        ";

        $sql .= " ORDER BY prioridade, t.create_time";

        #die("<pre>".$sql."</pre>");

        $stmt = Database::prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }


    function buscarChamadosNaoAtendidosNoDiaPorPeriodo($dtInicioPeriodo, $dtFimPeriodo) {
        $dtInicioPeriodo = Infra::converterDataBrazil2Banco($dtInicioPeriodo);
        $dtFimPeriodo = Infra::converterDataBrazil2Banco($dtFimPeriodo);

        $dtInicioPeriodo .= ' 00:00:00';
        $dtFimPeriodo .= ' 23:59:59';

        $sql = "SELECT t.id,
                       t.tn as ticket,
                       t.customer_id as usuario,
                       dfv.value_text as setor,
                       t.title as assunto,
                       t.queue_id,
                       q.name AS Fila_Atendimento,
                       t.ticket_lock_id,
                       t.type_id,
                       tt.name AS Tipo_Chamado,
                       t.service_id,
                       sc.name AS Catalogo_Servico,
                       t.sla_id,
                       s.name AS SLA,
                       t.user_id,
                       u.login AS login_atendente,
                       u.first_name || ' ' || u.last_name AS atendente,
                       t.responsible_user_id,
                       ru.login AS Login_responsavel,
                       ru.first_name || ' ' || ru.last_name AS Responsavel,
                       t.ticket_priority_id,
                       tp.name AS Prioridade,
                       t.ticket_state_id,
                       ts.name AS Estado,
                       t.customer_user_id AS CPF,
                       CASE
			                WHEN t.escalation_solution_time != 0 THEN to_timestamp(t.escalation_solution_time)
			           END AS prazo_atendimento,
                       t.archive_flag,
                       t.create_time as hora_abertura,
                       t.create_by,
                       tcru.first_name || ' ' || tcru.last_name AS Criado_por,
                       t.change_time,
                       t.change_by,
                       tchu.first_name || ' ' || tchu.last_name AS Trocado_por,
                       CASE EXTRACT(DOW
                                    FROM t.create_time)
                           WHEN 0 THEN 'Domingo'
                           WHEN 1 THEN 'Segunda'
                           WHEN 2 THEN 'Terça'
                           WHEN 3 THEN 'Quarta'
                           WHEN 4 THEN 'Quinta'
                           WHEN 5 THEN 'Sexta'
                           WHEN 6 THEN 'Sábado'
                       END AS DiaSemanaCriado,
                       CASE EXTRACT(MONTH
                                    FROM t.create_time)
                           WHEN 1 THEN 'Janeiro'
                           WHEN 2 THEN 'Fevereiro'
                           WHEN 3 THEN 'Março'
                           WHEN 4 THEN 'Abril'
                           WHEN 5 THEN 'Maio'
                           WHEN 6 THEN 'Junho'
                           WHEN 7 THEN 'Julho'
                           WHEN 8 THEN 'Agosto'
                           WHEN 9 THEN 'Setembro'
                           WHEN 10 THEN 'Outubro'
                           WHEN 11 THEN 'Novembro'
                           WHEN 12 THEN 'Dezembro'
                       END || ' ' || EXTRACT (YEAR
                                              FROM t.create_time) AS Mes_Ano,
                                             EXTRACT (YEAR
                                                      FROM t.create_time) AS AnoCriado,
                                                     th.hora_fechamento,
                                                     est.hora_solucao,
                                                     CASE
                                                         WHEN est.hora_solucao IS NULL THEN 'SEM SLA'
                                                         WHEN est.hora_solucao > th.hora_fechamento THEN 'DENTRO PRAZO'
                                                         WHEN est.hora_solucao < th.hora_fechamento THEN 'FORA DO PRAZO'
                                                         WHEN th.hora_fechamento IS NULL THEN CASE
                                                                                                  WHEN est.hora_solucao < now() THEN 'FORA DO PRAZO'
                                                                                                  ELSE 'DENTRO PRAZO'
                                                                                              END
                                                     END AS status_chamado
                FROM ticket t
                INNER JOIN queue q ON q.id=t.queue_id
                INNER JOIN ticket_type tt ON tt.id = t.type_id
                INNER JOIN service sc ON sc.id = t.service_id
                LEFT JOIN sla s ON s.id = t.sla_id
                INNER JOIN users u ON u.id = t.user_id
                INNER JOIN users ru ON ru.id = t.responsible_user_id
                INNER JOIN ticket_priority tp ON tp.id = t.ticket_priority_id
                INNER JOIN ticket_state ts ON ts.id = t.ticket_state_id
                INNER JOIN users tcru ON tcru.id = t.create_by
                INNER JOIN users tchu ON tchu.id = t.change_by
                LEFT JOIN
                  (SELECT ticket_id,
                          max(create_time) AS hora_fechamento
                   FROM ticket_history
                   WHERE state_id =
                       (SELECT ts.id
                        FROM ticket_state ts
                        WHERE ts.name = 'Aguardando validação')
                   GROUP BY ticket_id) th ON th.ticket_id = t.id
                LEFT JOIN
                  (SELECT id_ticket,
                          max(date_insert),
                          to_timestamp(max(escalation_solution_time)) AS hora_solucao
                   FROM ticket_est
                   GROUP BY id_ticket) est ON est.id_ticket = t.id
                INNER JOIN dynamic_field_value AS dfv ON dfv.object_id = t.id AND dfv.field_id = " . CONST_DYNAMIC_FIELD_DEPARTAMENTO . "
                WHERE
                    t.create_time BETWEEN '$dtInicioPeriodo' AND '$dtFimPeriodo'
                    AND t.queue_id != ".CONST_FILA_DE_SPAM ."
                    AND TO_CHAR(t.create_time, 'YYYY-MM-DD') != TO_CHAR(th.hora_fechamento,'YYYY-MM-DD')
        ";

        $sql .= " ORDER BY t.create_time";

        #die("<pre>".$sql."</pre>");

        $stmt = Database::prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    function buscarChamadosDoMesmoTipoENaturezaReabertosPorPeriodo($dtInicioPeriodo, $dtFimPeriodo) {
        $dtInicioPeriodo = Infra::converterDataBrazil2Banco($dtInicioPeriodo);
        $dtFimPeriodo = Infra::converterDataBrazil2Banco($dtFimPeriodo);

        $dtInicioPeriodo .= ' 00:00:00';
        $dtFimPeriodo .= ' 23:59:59';

        $sql = "SELECT t.id,
                       t.tn as ticket,
                       t.customer_id as usuario,
                       dfv.value_text as setor,
                       t.title as assunto,
                       t.queue_id,
                       q.name AS Fila_Atendimento,
                       t.ticket_lock_id,
                       t.type_id,
                       tt.name AS Tipo_Chamado,
                       t.service_id,
                       sc.name AS Catalogo_Servico,
                       t.sla_id,
                       s.name AS SLA,
                       t.user_id,
                       u.login AS login_atendente,
                       u.first_name || ' ' || u.last_name AS atendente,
                       t.responsible_user_id,
                       ru.login AS Login_responsavel,
                       ru.first_name || ' ' || ru.last_name AS Responsavel,
                       t.ticket_priority_id,
                       tp.name AS Prioridade,
                       t.ticket_state_id,
                       ts.name AS Estado,
                       t.customer_user_id AS CPF,
                       CASE
			                WHEN t.escalation_solution_time != 0 THEN to_timestamp(t.escalation_solution_time)
			           END AS prazo_atendimento,
                       t.archive_flag,
                       t.create_time as hora_abertura,
                       t.create_by,
                       tcru.first_name || ' ' || tcru.last_name AS Criado_por,
                       t.change_time,
                       t.change_by,
                       tchu.first_name || ' ' || tchu.last_name AS Trocado_por,
                       CASE EXTRACT(DOW
                                    FROM t.create_time)
                           WHEN 0 THEN 'Domingo'
                           WHEN 1 THEN 'Segunda'
                           WHEN 2 THEN 'Terça'
                           WHEN 3 THEN 'Quarta'
                           WHEN 4 THEN 'Quinta'
                           WHEN 5 THEN 'Sexta'
                           WHEN 6 THEN 'Sábado'
                       END AS DiaSemanaCriado,
                       CASE EXTRACT(MONTH
                                    FROM t.create_time)
                           WHEN 1 THEN 'Janeiro'
                           WHEN 2 THEN 'Fevereiro'
                           WHEN 3 THEN 'Março'
                           WHEN 4 THEN 'Abril'
                           WHEN 5 THEN 'Maio'
                           WHEN 6 THEN 'Junho'
                           WHEN 7 THEN 'Julho'
                           WHEN 8 THEN 'Agosto'
                           WHEN 9 THEN 'Setembro'
                           WHEN 10 THEN 'Outubro'
                           WHEN 11 THEN 'Novembro'
                           WHEN 12 THEN 'Dezembro'
                       END || ' ' || EXTRACT (YEAR
                                              FROM t.create_time) AS Mes_Ano,
                                             EXTRACT (YEAR
                                                      FROM t.create_time) AS AnoCriado,
                                                     th.hora_fechamento,
                                                     est.hora_solucao,
                                                     CASE
                                                         WHEN est.hora_solucao IS NULL THEN 'SEM SLA'
                                                         WHEN est.hora_solucao > th.hora_fechamento THEN 'DENTRO PRAZO'
                                                         WHEN est.hora_solucao < th.hora_fechamento THEN 'FORA DO PRAZO'
                                                         WHEN th.hora_fechamento IS NULL THEN CASE
                                                                                                  WHEN est.hora_solucao < now() THEN 'FORA DO PRAZO'
                                                                                                  ELSE 'DENTRO PRAZO'
                                                                                              END
                                                     END AS status_chamado
                FROM ticket t
                INNER JOIN queue q ON q.id=t.queue_id
                INNER JOIN ticket_type tt ON tt.id = t.type_id
                INNER JOIN service sc ON sc.id = t.service_id
                LEFT JOIN sla s ON s.id = t.sla_id
                INNER JOIN users u ON u.id = t.user_id
                INNER JOIN users ru ON ru.id = t.responsible_user_id
                INNER JOIN ticket_priority tp ON tp.id = t.ticket_priority_id
                INNER JOIN ticket_state ts ON ts.id = t.ticket_state_id
                INNER JOIN users tcru ON tcru.id = t.create_by
                INNER JOIN users tchu ON tchu.id = t.change_by
                LEFT JOIN
                  (SELECT ticket_id,
                          max(create_time) AS hora_fechamento
                   FROM ticket_history
                   WHERE state_id =
                       (SELECT ts.id
                        FROM ticket_state ts
                        WHERE ts.name = 'Aguardando validação')
                   GROUP BY ticket_id) th ON th.ticket_id = t.id
                LEFT JOIN
                  (SELECT id_ticket,
                          max(date_insert),
                          to_timestamp(max(escalation_solution_time)) AS hora_solucao
                   FROM ticket_est
                   GROUP BY id_ticket) est ON est.id_ticket = t.id
                INNER JOIN dynamic_field_value AS dfv ON dfv.object_id = t.id AND dfv.field_id = " . CONST_DYNAMIC_FIELD_DEPARTAMENTO . "
                INNER JOIN ticket_history as thx
				    ON thx.ticket_id = t.id    AND (thx.name like '%%reaberto__')
                WHERE
                    t.create_time BETWEEN '$dtInicioPeriodo' AND '$dtFimPeriodo'
                    AND t.queue_id != ".CONST_FILA_DE_SPAM ."

        ";

        $sql .= " ORDER BY t.create_time";

        #die("<pre>".$sql."</pre>");

        $stmt = Database::prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }


    function buscarChamadosAtendidosPorStatusSLAPorPeriodo($dtInicioPeriodo, $dtFimPeriodo) {
        $dtInicioPeriodo = Infra::converterDataBrazil2Banco($dtInicioPeriodo);
        $dtFimPeriodo = Infra::converterDataBrazil2Banco($dtFimPeriodo);

        $dtInicioPeriodo .= ' 00:00:00';
        $dtFimPeriodo .= ' 23:59:59';

        $sql = "SELECT t.id,
                       t.tn as ticket,
                       t.customer_id as usuario,
                       dfv.value_text as setor,
                       t.title as assunto,
                       t.queue_id,
                       q.name AS Fila_Atendimento,
                       t.ticket_lock_id,
                       t.type_id,
                       tt.name AS Tipo_Chamado,
                       t.service_id,
                       sc.name AS Catalogo_Servico,
                       t.sla_id,
                       s.name AS SLA,
                       t.user_id,
                       u.login AS login_atendente,
                       u.first_name || ' ' || u.last_name AS atendente,
                       t.responsible_user_id,
                       ru.login AS Login_responsavel,
                       ru.first_name || ' ' || ru.last_name AS Responsavel,
                       t.ticket_priority_id,
                       tp.name AS Prioridade,
                       t.ticket_state_id,
                       ts.name AS Estado,
                       t.customer_user_id AS CPF,
                       CASE
			                WHEN t.escalation_solution_time != 0 THEN to_timestamp(t.escalation_solution_time)
			           END AS prazo_atendimento,
                       t.archive_flag,
                       t.create_time as hora_abertura,
                       t.create_by,
                       tcru.first_name || ' ' || tcru.last_name AS Criado_por,
                       t.change_time,
                       t.change_by,
                       tchu.first_name || ' ' || tchu.last_name AS Trocado_por,
                       CASE EXTRACT(DOW
                                    FROM t.create_time)
                           WHEN 0 THEN 'Domingo'
                           WHEN 1 THEN 'Segunda'
                           WHEN 2 THEN 'Terça'
                           WHEN 3 THEN 'Quarta'
                           WHEN 4 THEN 'Quinta'
                           WHEN 5 THEN 'Sexta'
                           WHEN 6 THEN 'Sábado'
                       END AS DiaSemanaCriado,
                       CASE EXTRACT(MONTH
                                    FROM t.create_time)
                           WHEN 1 THEN 'Janeiro'
                           WHEN 2 THEN 'Fevereiro'
                           WHEN 3 THEN 'Março'
                           WHEN 4 THEN 'Abril'
                           WHEN 5 THEN 'Maio'
                           WHEN 6 THEN 'Junho'
                           WHEN 7 THEN 'Julho'
                           WHEN 8 THEN 'Agosto'
                           WHEN 9 THEN 'Setembro'
                           WHEN 10 THEN 'Outubro'
                           WHEN 11 THEN 'Novembro'
                           WHEN 12 THEN 'Dezembro'
                       END || ' ' || EXTRACT (YEAR
                                              FROM t.create_time) AS Mes_Ano,
                                             EXTRACT (YEAR
                                                      FROM t.create_time) AS AnoCriado,
                                                     th.hora_fechamento,
                                                     est.hora_solucao,
                                                     CASE
                                                         WHEN est.hora_solucao IS NULL THEN 'SEM SLA'
                                                         WHEN est.hora_solucao > th.hora_fechamento THEN 'DENTRO PRAZO'
                                                         WHEN est.hora_solucao < th.hora_fechamento THEN 'FORA DO PRAZO'
                                                         WHEN th.hora_fechamento IS NULL THEN CASE
                                                                                                  WHEN est.hora_solucao < now() THEN 'FORA DO PRAZO'
                                                                                                  ELSE 'DENTRO PRAZO'
                                                                                              END
                                                     END AS status_chamado
                FROM ticket t
                INNER JOIN queue q ON q.id=t.queue_id
                INNER JOIN ticket_type tt ON tt.id = t.type_id
                INNER JOIN service sc ON sc.id = t.service_id
                LEFT JOIN sla s ON s.id = t.sla_id
                INNER JOIN users u ON u.id = t.user_id
                INNER JOIN users ru ON ru.id = t.responsible_user_id
                INNER JOIN ticket_priority tp ON tp.id = t.ticket_priority_id
                INNER JOIN ticket_state ts ON ts.id = t.ticket_state_id
                INNER JOIN users tcru ON tcru.id = t.create_by
                INNER JOIN users tchu ON tchu.id = t.change_by
                LEFT JOIN
                  (SELECT ticket_id,
                          max(create_time) AS hora_fechamento
                   FROM ticket_history
                   WHERE state_id =
                       (SELECT ts.id
                        FROM ticket_state ts
                        WHERE ts.name = 'Aguardando validação')
                   GROUP BY ticket_id) th ON th.ticket_id = t.id
                LEFT JOIN
                  (SELECT id_ticket,
                          max(date_insert),
                          to_timestamp(max(escalation_solution_time)) AS hora_solucao
                   FROM ticket_est
                   GROUP BY id_ticket) est ON est.id_ticket = t.id
                INNER JOIN dynamic_field_value AS dfv ON dfv.object_id = t.id AND dfv.field_id = " . CONST_DYNAMIC_FIELD_DEPARTAMENTO . "
                WHERE
                    t.create_time BETWEEN '$dtInicioPeriodo' AND '$dtFimPeriodo'
                    AND t.queue_id != ".CONST_FILA_DE_SPAM ."
                    AND (ticket_state_id = " . CONST_TICKET_ESTADO_ENCERRADO . " OR ticket_state_id = " . CONST_TICKET_ESTADO_AGUARDANDO_VALIDACAO . ")
        ";

        $sql .= " ORDER BY status_chamado, t.create_time DESC";

        #die("<pre>".$sql."</pre>");

        $stmt = Database::prepare($sql);
        $stmt->execute();

        $resultado_consulta = $stmt->fetchAll();
        $arrCustomizado = array();
        foreach($resultado_consulta as $indice=>$chamado){
            $arrCustomizado[$chamado->status_chamado][] = $chamado;
        }

        return $arrCustomizado; //return $stmt->fetchAll();
    }

    function quantidadeDeChamadosRecebidosNoPeriodo($dtInicioPeriodo, $dtFimPeriodo) {

        $rs = $this->buscarTodosChamadosPorPeriodo($dtInicioPeriodo, $dtFimPeriodo);
        $qnt_resgistros = count($rs);

        return $qnt_resgistros;
    }

    function quantidadeDeChamadosRecebidosComSLANoPeriodo($dtInicioPeriodo, $dtFimPeriodo) {

        $rs = $this->buscarTodosChamadosComSLAPorPeriodo($dtInicioPeriodo, $dtFimPeriodo);
        $qnt_resgistros = count($rs);

        return $qnt_resgistros;
    }

    function quantidadeDeChamadosAtendidosNoPeriodo($dtInicioPeriodo, $dtFimPeriodo) {

        $rs = $this->buscarChamadosAtendidosPorPeriodo($dtInicioPeriodo, $dtFimPeriodo);
        $qnt_resgistros = count($rs);

        return $qnt_resgistros;
    }

    function quantidadeDeChamadosComRespostaSatisfacaoNoPeriodo($dtInicioPeriodo, $dtFimPeriodo) {

        $rs = $this->buscarChamadosComRespostaSatisfacaoPorPeriodo($dtInicioPeriodo, $dtFimPeriodo);
        $qnt_resgistros = count($rs);

        return $qnt_resgistros;
    }

    function quantidadeDeChamadosComRespostaBomEOtimoNoPeriodo($dtInicioPeriodo, $dtFimPeriodo) {

        $rs = $this->buscarChamadosSatisfacaoBonsEOtimosPorPeriodo($dtInicioPeriodo, $dtFimPeriodo);
        $qnt_resgistros = count($rs);

        return $qnt_resgistros;
    }

    function quantidadeDeChamadosFechadosNoPeriodo($dtInicioPeriodo, $dtFimPeriodo) {

        $rs = $this->buscarChamadosTratadosPorPeriodo($dtInicioPeriodo, $dtFimPeriodo);
        $qnt_resgistros = count($rs);

        return $qnt_resgistros;
    }

    function quantidadeDeRequisicoesRecebidasNoPeriodo($dtInicioPeriodo, $dtFimPeriodo) {

        $rs = $this->buscarRequisicoesRecebidasPorPeriodo($dtInicioPeriodo, $dtFimPeriodo);
        $qnt_resgistros = count($rs);

        return $qnt_resgistros;
    }

    function quantidadeDeRequisicoesTratadasNoPeriodo($dtInicioPeriodo, $dtFimPeriodo) {

        $rs = $this->buscarRequisicoesTratadosPorPeriodo($dtInicioPeriodo, $dtFimPeriodo);
        $qnt_resgistros = count($rs);

        return $qnt_resgistros;
    }

    function quantidadeDeIncidentesRecebidasNoPeriodo($dtInicioPeriodo, $dtFimPeriodo) {

        $rs = $this->buscarIncidentesRecebidasPorPeriodo($dtInicioPeriodo, $dtFimPeriodo);
        $qnt_resgistros = count($rs);

        return $qnt_resgistros;
    }

    function quantidadeDeIncidentesTratadasNoPeriodo($dtInicioPeriodo, $dtFimPeriodo) {

        $rs = $this->buscarIncidentesTratadosPorPeriodo($dtInicioPeriodo, $dtFimPeriodo);
        $qnt_resgistros = count($rs);

        return $qnt_resgistros;
    }

    function quantidadeDeChamadosDoMesmoTipoENaturezaReabertosNoPeriodo($dtInicioPeriodo, $dtFimPeriodo) {

        $rs = $this->buscarChamadosDoMesmoTipoENaturezaReabertosPorPeriodo($dtInicioPeriodo, $dtFimPeriodo);
        $qnt_resgistros = count($rs);

        return $qnt_resgistros;
    }

    function quantidadeDeChamadosNaoAtendidosNoDiaNoPeriodo($dtInicioPeriodo, $dtFimPeriodo) {

        $rs = $this->buscarChamadosNaoAtendidosNoDiaPorPeriodo($dtInicioPeriodo, $dtFimPeriodo);
        $qnt_resgistros = count($rs);

        return $qnt_resgistros;
    }

    function quantidadeDeChamadosRecebidosPorPrioridadeNoPeriodo($dtInicioPeriodo, $dtFimPeriodo) {

        $rs = $this->buscarChamadosRecebidosPorPrioridadePorPeriodo($dtInicioPeriodo, $dtFimPeriodo);
        $qnt_resgistros = count($rs);

        return $qnt_resgistros;
    }


    function quantidadeDeChamadosAtendidosDentroDoPrazoNoPeriodo($dtInicioPeriodo, $dtFimPeriodo) {

        $rs = $this->buscarChamadosAtendidosDentroDoPrazoPorPeriodo($dtInicioPeriodo, $dtFimPeriodo);
        $qnt_resgistros = count($rs);

        return $qnt_resgistros;
    }

}