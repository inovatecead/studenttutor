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
 * Strings de idioma para o plugin de atribuição Estudante-Tutor
 *
 * @package    local_studenttutor
 * @copyright  2025 Sua Organização
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Atribuição Estudante-Tutor';

// Navegação
$string['manage_assignments'] = 'Gerenciar Atribuições Estudante-Tutor';
$string['view_history'] = 'Ver Histórico de Tutoria';
$string['my_students'] = 'Meus Estudantes';

// Títulos de páginas
$string['assignments_title'] = 'Atribuições Estudante-Tutor';
$string['history_title'] = 'Histórico de Tutoria';
$string['add_assignment_title'] = 'Atribuir Estudante a Tutor';
$string['add_history_title'] = 'Adicionar Atividade de Tutoria';

// Rótulos de formulário
$string['select_tutor'] = 'Selecionar Tutor';
$string['select_student'] = 'Selecionar Estudante';
$string['select_course'] = 'Selecionar Curso';
$string['action_type'] = 'Tipo de Atividade';
$string['activity_title'] = 'Título da Atividade';
$string['description'] = 'Descrição';

// Strings de ajuda
$string['course_help'] = 'Selecione um curso para filtrar estudantes, ou escolha "Todos os cursos" para atribuições globais.';

// Dashboard strings
$string['dashboard'] = 'Painel de Tutoria';
$string['pedagogical_dashboard'] = 'Painel Pedagógico';

// Tipos de ação
$string['action_meeting'] = 'Encontro/Reunião';
$string['action_email'] = 'Comunicação por Email';
$string['action_feedback'] = 'Feedback/Retorno';
$string['action_assessment'] = 'Revisão de Avaliação';

// Configurações
$string['settings'] = 'Configurações';
$string['general_settings'] = 'Configurações Gerais';
$string['general_settings_desc'] = 'Configure as configurações gerais do plugin';
$string['enable_plugin'] = 'Habilitar Plugin';
$string['enable_plugin_desc'] = 'Habilitar ou desabilitar o plugin de atribuição Estudante-Tutor';
$string['max_assignments'] = 'Máximo de Atribuições';
$string['max_assignments_desc'] = 'Número máximo de estudantes que um tutor pode ter atribuído';

// Rótulos de formulário adicionais
$string['add_assignment'] = 'Adicionar Atribuição';
$string['edit_assignment'] = 'Editar Atribuição';
$string['assignment_created'] = 'Atribuição criada com sucesso';
$string['assignment_updated'] = 'Atribuição atualizada com sucesso';
$string['assignment_exists'] = 'Esta atribuição já existe';
$string['add_history_entry'] = 'Adicionar Entrada no Histórico';
$string['edit_assignment_info'] = 'Ao editar uma atribuição, apenas o curso pode ser alterado. O tutor e estudante não podem ser modificados.';

// Cabeçalhos de tabela
$string['tutor'] = 'Tutor';
$string['student'] = 'Estudante';
$string['course'] = 'Curso';
$string['date_assigned'] = 'Data de Atribuição';
$string['status'] = 'Status';
$string['actions'] = 'Ações';
$string['date'] = 'Data';
$string['activity_type'] = 'Tipo de Atividade';

// Status
$string['active'] = 'Ativo';
$string['inactive'] = 'Inativo';

// Mensagens
$string['no_assignments'] = 'Nenhuma atribuição encontrada';
$string['no_history'] = 'Nenhuma entrada no histórico encontrada';
$string['all_courses'] = 'Todos os Cursos';
$string['general'] = 'Geral';

// Eventos
$string['event_assignment_created'] = 'Atribuição criada';
$string['event_assignment_updated'] = 'Atribuição atualizada';

// Erros
$string['error_no_permission'] = 'Você não tem permissão para executar esta ação';
$string['error_invalid_assignment'] = 'ID de atribuição inválido';
$string['assignmentnotfound'] = 'Atribuição não encontrada';
$string['assignmentalreadyexists'] = 'Esta atribuição já existe';
$string['assignment_creation_failed'] = 'Falha ao criar atribuição';
$string['assignment_update_failed'] = 'Falha ao atualizar atribuição';
$string['error_deleting_assignment'] = 'Erro ao excluir atribuição';

// Opções de filtro
$string['filters'] = 'Filtros';
$string['filter'] = 'Filtrar';
$string['clear'] = 'Limpar';
$string['all_tutors'] = 'Todos os Tutores';
$string['all_students'] = 'Todos os Estudantes';

// Integração com curso
$string['my_students_in_course'] = 'Meus Alunos em {$a}';
$string['no_students_assigned'] = 'Nenhum aluno atribuído a você neste curso';
$string['back_to_course'] = 'Voltar ao Curso';
$string['add_history_entry'] = 'Adicionar Atividade de Tutoria';
$string['add_interaction'] = 'Adicionar Interação';
$string['send_message'] = 'Enviar Mensagem';
$string['last_contact'] = 'Último Contato';
$string['total_interactions'] = 'Total de Interações';
$string['never'] = 'Nunca';
$string['quick_stats'] = 'Estatísticas Rápidas';
$string['stats_total_students'] = 'Total de alunos: {$a}';
$string['stats_total_interactions'] = 'Total de interações: {$a}';
$string['stats_recent_contact'] = 'Alunos contatados nos últimos 7 dias: {$a}';
$string['history_added_success'] = 'Atividade de tutoria adicionada com sucesso';
$string['history_add_error'] = 'Erro ao adicionar atividade de tutoria';
$string['adding_history_for'] = 'Adicionando atividade de tutoria para: {$a}';
$string['access_denied'] = 'Acesso negado';
$string['studenttutor_settings'] = 'Gestão Aluno-Tutor';
$string['other'] = 'Outro';
$string['student_not_assigned'] = 'Este aluno não está atribuído a você';
$string['history_for_student'] = 'Histórico de Tutoria para {$a}';
$string['back_to_students'] = 'Voltar aos Meus Alunos';
$string['no_history_entries'] = 'Nenhuma atividade de tutoria registrada ainda';
$string['interaction_summary'] = 'Resumo das Interações';
$string['first_contact'] = 'Primeiro contato';
$string['interaction_types'] = 'Tipos de interações';
$string['confirm_delete_assignment'] = 'Tem certeza que deseja excluir esta atribuição?';
$string['assignment_deleted'] = 'Atribuição excluída com sucesso';

// Strings de filtro
$string['all_activity_types'] = 'Todos os Tipos de Atividade';
$string['date_from'] = 'Data Inicial';
$string['date_to'] = 'Data Final';
$string['all_courses'] = 'Todos os Cursos';

// Atividades de tutoria para exibição
$string['activity_meeting'] = 'Encontro/Reunião';
$string['activity_email'] = 'Comunicação por Email';
$string['activity_feedback'] = 'Feedback/Retorno';
$string['activity_assessment'] = 'Revisão de Avaliação';
$string['activity_other'] = 'Outro';

// Interface de relatórios
$string['reports_title'] = 'Relatórios de Tutoria';
$string['filters_active'] = 'Filtros ativos';
$string['total_records'] = 'Total de registros encontrados';
$string['no_filters_active'] = 'Nenhum filtro ativo';
$string['select_tutors'] = 'Selecione os tutores...';
$string['select_students'] = 'Selecione os estudantes...';
$string['back_to_assignments'] = 'Voltar às Atribuições';

// Mensagens de atribuição múltipla
$string['assignment_exists_for'] = 'Atribuição já existe para o estudante: {$a}';
$string['assignments_created_multiple'] = '{$a} atribuições criadas com sucesso';
$string['assignments_partially_created'] = '{$a->success} atribuições criadas com sucesso. Erro para: {$a->errors}';
$string['select_students'] = 'Selecione os estudantes';

// Strings de gerenciamento de atribuições
$string['edit_assignment'] = 'Editar Atribuição';
$string['assignment_updated'] = 'Atribuição atualizada com sucesso';
$string['assignments_created'] = '{$a} atribuições criadas com sucesso';
$string['assignmentnotfound'] = 'Atribuição não encontrada';
$string['edit_temporarily_disabled'] = 'A edição de atribuições está temporariamente desabilitada. Entre em contato com o administrador se precisar modificar uma atribuição.';
$string['dashboard'] = 'Painel de Tutoria';
