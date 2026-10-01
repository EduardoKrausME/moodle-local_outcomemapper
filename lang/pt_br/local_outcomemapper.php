<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Outcome Mapper local_outcomemapper.php.
 *
 * @package   local_outcomemapper
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;


$string['activities'] = 'Atividades';
$string['activity'] = 'Atividade';
$string['additionalobjectives'] = 'Objetivos de aprendizagem adicionais';
$string['additionalobjectives_help'] = 'Digite um objetivo por linha. Você pode usar "Título | Descrição". Esses objetivos existem somente para esta análise e não alteram competências nem outcomes oficiais do Moodle.';
$string['additionalobjectives_placeholder'] = 'Explicar o fluxo de autenticação | O estudante consegue explicar criação, renovação e invalidação de sessão.
Projetar uma API segura';
$string['aicaveat'] = 'As relações produzidas pela IA são sugestões. O campo de confiança é informado pelo modelo e não deve ser tratado como probabilidade verificada.';
$string['analyse'] = 'Analisar mapeamento do curso';
$string['analysisby'] = 'Analisada por';
$string['analysiscomplete'] = 'Análise de mapeamento concluída.';
$string['analysisconfiguration'] = 'Escopo da análise';
$string['analysisdate'] = 'Criada em';
$string['analysisfailed'] = 'A análise falhou.';
$string['analysisid'] = 'ID da análise';
$string['assessedwithoutpreparation'] = 'Avaliados sem preparação aparente';
$string['assessment'] = 'Avaliação';
$string['assessments'] = 'Avaliações';
$string['batches'] = 'Lotes de IA';
$string['bridgeerror'] = 'O AI Bridge não conseguiu concluir a análise: {$a}';
$string['cmids'] = 'Atividades e recursos a incluir';
$string['competency'] = 'Competência';
$string['confidence'] = 'Confiança';
$string['confirm'] = 'Confirmar';
$string['confirmed'] = 'Confirmado pelo professor';
$string['confirmedmappings'] = 'Mapeamentos confirmados';
$string['content'] = 'Conteúdo';
$string['coursecontent'] = 'Conteúdo do curso';
$string['customobjective'] = 'Objetivo adicional';
$string['error_analysismissing'] = 'A análise solicitada não existe neste curso.';
$string['error_cannotconfirmnone'] = 'Uma relação none não pode ser persistida como mapping confirmado.';
$string['error_duplicateobjective'] = 'A IA retornou o mesmo objetivo mais de uma vez para o objeto {$a}.';
$string['error_invalidjson'] = 'A resposta da IA não é um JSON válido.';
$string['error_invalidrelation'] = 'A IA retornou uma relação não suportada: {$a}';
$string['error_invalidshape'] = 'O JSON retornado pela IA não segue o schema obrigatório.';
$string['error_missingobjective'] = 'A IA não classificou todos os objetivos para o objeto {$a}.';
$string['error_suggestionmissing'] = 'A sugestão solicitada não existe neste curso.';
$string['error_unknownobjective'] = 'A IA retornou um ID de objetivo desconhecido: {$a}';
$string['error_unknowntarget'] = 'A IA retornou um ID de objeto desconhecido: {$a}';
$string['evidence'] = 'Evidência';
$string['explanation'] = 'Explicação';
$string['findings'] = 'Achados de cobertura';
$string['heuristicnote'] = 'Os achados de cobertura são heurísticas construídas a partir de relações sugeridas por IA. Não são fatos e devem ser revisados pelo professor.';
$string['includecompetencies'] = 'Incluir competências do Moodle';
$string['includeoutcomes'] = 'Incluir outcomes do livro de notas';
$string['includequestions'] = 'Incluir questões dos quizzes';
$string['invalidaction'] = 'Ação de revisão inválida.';
$string['invalidairesponse'] = 'O AI Bridge retornou um mapeamento inválido. Nada dessa resposta foi salvo.';
$string['latestanalysis'] = 'Análise mais recente';
$string['lowcompetencyevidence'] = 'Competências com pouca evidência mapeada';
$string['mappingconfirmed'] = 'Mapeamento confirmado.';
$string['mappingrejected'] = 'Mapeamento rejeitado.';
$string['matrix'] = 'Matriz objetivo × evidência do curso';
$string['maxitemchars'] = 'Máximo de caracteres por objeto do curso';
$string['maxitemchars_desc'] = 'Máximo de texto normalizado enviado à IA para cada objetivo ou objeto do curso. Valores abaixo de 500 são elevados a 500 e acima de 12000 são limitados a 12000.';
$string['noconfirmedmappings'] = 'Nenhum mapeamento foi confirmado até agora.';
$string['nomapping'] = 'Sem relação aparente';
$string['none'] = 'Nenhuma';
$string['noneavailable'] = 'Não há itens selecionáveis disponíveis.';
$string['noobjectives'] = 'Não há objetivos de aprendizagem disponíveis para análise.';
$string['nosuggestions'] = 'Não há sugestões disponíveis para esta análise.';
$string['notargets'] = 'Não há conteúdos, atividades, avaliações ou questões disponíveis para análise.';
$string['objective'] = 'Objetivo';
$string['objectivebatchsize'] = 'Objetivos por lote de IA';
$string['objectivebatchsize_desc'] = 'Quantidade de objetivos enviada em cada chamada. O intervalo efetivo é de 5 a 80.';
$string['objectives'] = 'Objetivos';
$string['outcome'] = 'Outcome';
$string['outcomemapper:analyse'] = 'Analisar objetivos de aprendizagem e evidências do curso';
$string['overrepresented'] = 'Objetivos possivelmente super-representados';
$string['pluginname'] = 'Mapeador de objetivos';
$string['privacy:metadata:analysis'] = 'Armazena metadados das análises e catálogos determinísticos dos objetos do curso.';
$string['privacy:metadata:analysis:userid'] = 'Usuário que iniciou a análise.';
$string['privacy:metadata:map'] = 'Armazena mapeamentos explicitamente confirmados por um professor.';
$string['privacy:metadata:map:confirmedby'] = 'Usuário que confirmou o mapeamento.';
$string['privacy:metadata:suggest'] = 'Armazena sugestões de IA e o estado de revisão pelo professor.';
$string['privacy:metadata:suggest:reviewedby'] = 'Usuário que revisou uma sugestão.';
$string['privacy:path:analysis'] = 'Análises do Mapeador de objetivos';
$string['privacy:path:confirmed'] = 'Mapeamentos confirmados do Mapeador de objetivos';
$string['privacy:path:reviews'] = 'Revisões do Mapeador de objetivos';
$string['probable'] = 'Provável';
$string['purposeid'] = 'Purpose do AI Bridge';
$string['purposeid_desc'] = 'Este plugin sempre chama o local_ai_bridge usando o idnumber outcomemapper-map.';
$string['question'] = 'Questão';
$string['questions'] = 'Questões';
$string['reject'] = 'Rejeitar';
$string['rejected'] = 'Rejeitado pelo professor';
$string['reviewmapping'] = 'Revisar mapeamento';
$string['section'] = 'Seção';
$string['sectionids'] = 'Seções a incluir';
$string['source'] = 'Origem';
$string['status'] = 'Status';
$string['strong'] = 'Forte';
$string['suggested'] = 'Sugerido pela IA';
$string['targetbatchsize'] = 'Objetos do curso por lote de IA';
$string['targetbatchsize_desc'] = 'Quantidade de conteúdos/atividades/avaliações/questões enviada em cada chamada. O intervalo efetivo é de 1 a 20.';
$string['targets'] = 'Objetos do curso';
$string['taughtnotassessed'] = 'Ensinados sem avaliação aparente';
$string['unmappedactivities'] = 'Atividades sem objetivo aparente';
$string['unmappedassessments'] = 'Avaliações sem objetivo aparente';
$string['viewactivity'] = 'Abrir atividade';
$string['viewquiz'] = 'Abrir quiz';
$string['warning_competencynopermission'] = 'As competências foram solicitadas, mas o usuário atual não tem permissão para visualizar as competências do curso.';
$string['warning_contentmissing'] = 'Não havia conteúdo textual disponível para "{$a}". O objeto foi incluído apenas com título e metadados.';
$string['warning_modulemissing'] = 'O módulo do curso {$a} não pôde ser carregado.';
$string['warning_quizquestionsnopermission'] = 'As questões do quiz "{$a}" foram ignoradas porque o usuário atual não pode visualizar ou gerenciar esse quiz.';
$string['warnings'] = 'Avisos da coleta';
$string['weak'] = 'Fraca';
