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
 * Brazilian Portuguese language strings for the Nexo Tutoria Acadêmica plugin.
 *
 * @package    local_studenttutor
 * @author     Rodrigo Severo Ribeiro
 * @copyright  2025-2026 Universidade Federal de Mato Grosso (UFMT) - INOVATEC/UFMT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Plugin e configurações.
$string['pluginname'] = 'Nexo Tutoria Acadêmica';
$string['settings'] = 'Configurações';
$string['general_settings'] = 'Configurações Gerais';
$string['general_settings_desc'] = 'Configure as configurações gerais do plugin';
$string['enable_plugin'] = 'Habilitar Plugin';
$string['enable_plugin_desc'] = 'Habilitar ou desabilitar o plugin Nexo Tutoria Acadêmica';
$string['max_assignments'] = 'Máximo de Atribuições';
$string['max_assignments_desc'] = 'Número máximo de estudantes que um tutor pode ter atribuído';
$string['tutor_role'] = 'Papel de tutor';
$string['tutor_role_desc'] = 'Shortname do papel usado para identificar tutores (padrão: tutortematico).';
$string['additional_tutor_roles'] = 'Papéis adicionais de tutor';
$string['additional_tutor_roles_desc'] = 'Lista de shortnames de papéis adicionais separados por vírgula, usados para identificar tutores.';
$string['limited_access_redirect'] = 'Acesso limitado. Redirecionando para os relatórios.';

// Navegação e páginas.
$string['manage_assignments'] = 'Gerenciar Atribuições de Tutoria';
$string['view_history'] = 'Ver Histórico de Tutoria';
$string['my_students'] = 'Meus Estudantes';
$string['assignments_title'] = 'Atribuições de Tutoria';
$string['history_title'] = 'Histórico de Tutoria';
$string['add_history_entry'] = 'Adicionar Atividade de Tutoria';
$string['edit_history'] = 'Editar Registro de Tutoria';
$string['adding_history_for'] = 'Adicionando atividade de tutoria para: {$a}';
$string['editing_history_for'] = 'Editando o registro de tutoria de {$a}';
$string['add_interaction'] = 'Adicionar Interação';
$string['history_for_student'] = 'Histórico de Tutoria de {$a}';
$string['my_students_in_course'] = 'Meus Estudantes em {$a}';
$string['no_students_assigned'] = 'Nenhum estudante atribuído a você neste curso';
$string['back_to_course'] = 'Voltar ao Curso';
$string['back_to_students'] = 'Voltar aos Meus Estudantes';
$string['back_to_assignments'] = 'Voltar às Atribuições';
$string['access_denied'] = 'Acesso negado';
$string['student_not_assigned'] = 'Este estudante não está atribuído a você';

// Atribuições.
$string['add_assignment'] = 'Adicionar Atribuição';
$string['date_assigned'] = 'Data de Atribuição';
$string['assignment_created'] = 'Estudante atribuído ao tutor com sucesso';
$string['assignment_deleted'] = 'Atribuição removida com sucesso';
$string['assignment_creation_failed'] = 'Falha ao criar atribuição';
$string['assignment_exists_for'] = 'Atribuição já existe para o estudante: {$a}';
$string['assignmentalreadyexists'] = 'Esta atribuição já existe';
$string['assignmentnotfound'] = 'Atribuição não encontrada';
$string['edit_assignment'] = 'Editar Atribuição';
$string['delete_assignment'] = 'Excluir Atribuição';
$string['confirm_delete_assignment'] = 'Tem certeza que deseja excluir esta atribuição?\n\nEsta ação não pode ser desfeita!';
$string['assignment_updated_success'] = 'Atribuição atualizada com sucesso';
$string['assignment_update_error'] = 'Erro ao atualizar a atribuição';
$string['required_fields_missing'] = 'Dados obrigatórios não preenchidos';
$string['invalid_tutor'] = 'O tutor selecionado não é válido';
$string['assignments_created_count'] = '{$a} atribuição(ões) criada(s) com sucesso';
$string['assignments_duplicated_count'] = '{$a} atribuição(ões) já existia(m)';
$string['assignments_error_count'] = '{$a} erro(s) ao criar atribuições';
$string['no_assignments_found'] = 'Nenhuma atribuição encontrada.';
$string['assign_students_hint'] = 'Para atribuir estudantes, acesse a página de atribuições.';
$string['error_deleting_assignment'] = 'Erro ao excluir atribuição';

// Histórico de tutoria.
$string['history_added'] = 'Atividade adicionada ao histórico com sucesso';
$string['history_added_success'] = 'Atividade de tutoria adicionada com sucesso';
$string['history_add_error'] = 'Erro ao adicionar atividade de tutoria';
$string['history_updated_success'] = 'Registro de tutoria atualizado com sucesso';
$string['history_update_error'] = 'Erro ao atualizar o registro de tutoria';
$string['history_deleted_success'] = 'Registro de tutoria excluído com sucesso';
$string['history_delete_error'] = 'Erro ao excluir o registro de tutoria';
$string['history_not_found'] = 'Registro de tutoria não encontrado';
$string['confirm_delete_history'] = 'Tem certeza que deseja excluir este registro de tutoria?';
$string['no_history'] = 'Nenhuma entrada no histórico encontrada';
$string['no_history_entries'] = 'Nenhuma atividade de tutoria registrada ainda';

// Campos de formulário.
$string['action_type'] = 'Tipo de Atividade';
$string['activity_type'] = 'Tipo de Atividade';
$string['description'] = 'Descrição';
$string['course_help'] = 'Selecione um curso para filtrar estudantes, ou escolha "Todos os cursos" para atribuições globais.';
$string['course_help_help'] = 'Escolha a disciplina à qual a tutoria se refere. Selecione "Todos os cursos" quando a tutoria não estiver vinculada a uma disciplina específica.';
$string['activity_date'] = 'Data da atividade';
$string['activity_date_help'] = 'Selecione a data em que esta atividade de tutoria ocorreu.';
$string['created_on'] = 'Criado em';
$string['name'] = 'Nome';
$string['send_message'] = 'Enviar Mensagem';
$string['status'] = 'Situação';

// Cabeçalhos de tabela.
$string['tutor'] = 'Tutor';
$string['student'] = 'Estudante';
$string['course'] = 'Curso';
$string['date'] = 'Data';
$string['actions'] = 'Ações';
$string['general'] = 'Geral';
$string['total_records'] = 'Total de registros encontrados';

// Filtros.
$string['filters'] = 'Filtros';
$string['filter'] = 'Filtrar';
$string['clear'] = 'Limpar';
$string['all_tutors'] = 'Todos os Tutores';
$string['all_students'] = 'Todos os Estudantes';
$string['all_courses'] = 'Todos os Cursos';
$string['select_course'] = 'Selecionar Curso';
$string['select_tutor'] = 'Selecionar Tutor';
$string['students'] = 'Estudantes';
$string['select_course_to_see_students'] = 'Selecione um curso para ver os estudantes disponíveis.';
$string['search_placeholder'] = 'Digite para buscar...';
$string['search_course_placeholder'] = 'Digite para buscar um curso...';
$string['search_tutor_placeholder'] = 'Digite para buscar um tutor...';
$string['search_student_placeholder'] = 'Digite para buscar um estudante...';
$string['showing_results'] = 'Mostrando {$a->start}-{$a->end} de {$a->total} resultados';
$string['hold_ctrl_multi_select'] = 'Segure Ctrl (ou Cmd) para selecionar múltiplos';
$string['without_tutors'] = 'Sem tutores';
$string['with_one_tutor'] = 'Com 1 tutor';
$string['with_multiple_tutors'] = 'Com múltiplos tutores';
$string['without_group'] = 'Sem grupo';
$string['all_activity_types'] = 'Todos os Tipos de Atividade';
$string['date_from'] = 'Data Inicial';
$string['date_to'] = 'Data Final';
$string['select_tutors'] = 'Selecione os tutores...';
$string['select_students'] = 'Selecione os estudantes';
$string['filters_active'] = 'Filtros ativos';

// Estatísticas.
$string['quick_stats'] = 'Estatísticas Rápidas';
$string['stats_total_students'] = 'Total de estudantes: {$a}';
$string['stats_total_interactions'] = 'Total de interações: {$a}';
$string['stats_recent_contact'] = 'Estudantes contatados nos últimos 7 dias: {$a}';
$string['interaction_summary'] = 'Resumo das Interações';
$string['first_contact'] = 'Primeiro contato';
$string['last_contact'] = 'Último contato';
$string['total_interactions'] = 'Total de Interações';
$string['interaction_types'] = 'Tipos de interações';
$string['never'] = 'Nunca';

// Rótulos de tipos de atividade usados pelo histórico e relatórios.
$string['action_meeting'] = 'Encontro/Reunião';
$string['action_email'] = 'Comunicação por E-mail';
$string['action_feedback'] = 'Feedback/Retorno';
$string['action_assessment'] = 'Revisão de Avaliação';
$string['action_phone'] = 'Ligação telefônica';
$string['action_other'] = 'Outro';
$string['activity_meeting'] = 'Encontro/Reunião';
$string['activity_email'] = 'Comunicação por E-mail';
$string['activity_feedback'] = 'Feedback/Retorno';
$string['activity_assessment'] = 'Revisão de Avaliação';
$string['activity_guidance'] = 'Orientação Acadêmica';
$string['activity_other'] = 'Outro';

// Gerenciamento de tipos de atividade.
$string['manage_activity_types'] = 'Gerenciar Tipos de Atividade';
$string['add_activity_type'] = 'Adicionar Tipo de Atividade';
$string['edit_activity_type'] = 'Editar Tipo de Atividade';
$string['activity_type_created'] = 'Tipo de atividade criado com sucesso';
$string['activity_type_updated'] = 'Tipo de atividade atualizado com sucesso';
$string['activity_type_deleted'] = 'Tipo de atividade excluído com sucesso';
$string['activity_type_enabled'] = 'Tipo de atividade habilitado';
$string['activity_type_disabled'] = 'Tipo de atividade desabilitado';
$string['activity_types_reordered'] = 'Tipos de atividade reordenados com sucesso';
$string['no_activity_types'] = 'Nenhum tipo de atividade encontrado';
$string['confirm_delete_activity_type'] = 'Tem certeza que deseja excluir este tipo de atividade?';
$string['activity_type_create_error'] = 'Erro ao criar tipo de atividade';
$string['activity_type_update_error'] = 'Erro ao atualizar tipo de atividade';
$string['activity_type_delete_error'] = 'Erro ao excluir tipo de atividade';
$string['activity_type_save_error'] = 'Erro ao salvar tipo de atividade';
$string['editing_activity_type'] = 'Editando tipo de atividade: {$a}';
$string['shortnameexists'] = 'Este shortname já existe';
$string['activity_types_statistics'] = 'Estatísticas de Uso';
$string['usage_count'] = 'Quantidade de usos';
$string['uses'] = 'usos';
$string['order'] = 'Ordem';
$string['preview'] = 'Pré-visualização';
$string['shortname'] = 'Shortname';
$string['icon'] = 'Ícone';
$string['color'] = 'Cor';
$string['sortorder'] = 'Ordem de exibição';
$string['export_csv'] = 'Exportar CSV';

// Campos do formulário de tipo de atividade.
$string['activity_type_name'] = 'Nome do Tipo de Atividade';
$string['activity_type_name_help'] = 'O nome exibido para este tipo de atividade';
$string['activity_type_shortname'] = 'Nome curto';
$string['activity_type_shortname_help'] = 'Um identificador único para este tipo de atividade (apenas letras, números e sublinhado)';
$string['activity_type_description'] = 'Descrição';
$string['activity_type_description_help'] = 'Uma breve descrição de quando usar este tipo de atividade';
$string['activity_type_icon'] = 'Ícone';
$string['activity_type_icon_help'] = 'Ícone FontAwesome exibido com este tipo de atividade';
$string['activity_type_color'] = 'Cor';
$string['activity_type_color_help'] = 'Cor usada na exibição deste tipo de atividade';
$string['activity_type_active'] = 'Ativo';
$string['activity_type_active_help'] = 'Se este tipo de atividade está disponível para seleção';
$string['activity_type_sortorder'] = 'Ordem de exibição';
$string['activity_type_sortorder_help'] = 'Ordem em que este tipo aparece nas listas (números menores primeiro)';

// Icons for the activity types.
$string['icon_users'] = 'Pessoas (reuniões)';
$string['icon_envelope'] = 'Envelope (e-mail)';
$string['icon_comment'] = 'Comentário (feedback)';
$string['icon_clipboard'] = 'Prancheta (avaliação)';
$string['icon_compass'] = 'Bússola (orientação)';
$string['icon_phone'] = 'Telefone (ligações)';
$string['icon_video'] = 'Vídeo (videochamadas)';
$string['icon_file'] = 'Arquivo (documentos)';
$string['icon_chart'] = 'Gráfico (análises)';
$string['icon_lightbulb'] = 'Lâmpada (ideias)';
$string['icon_graduation'] = 'Capelo (acadêmico)';
$string['icon_other'] = 'Outro';

// Cores de tipos de atividade.
$string['color_green'] = 'Verde';
$string['color_blue'] = 'Azul';
$string['color_yellow'] = 'Amarelo';
$string['color_red'] = 'Vermelho';
$string['color_purple'] = 'Roxo';
$string['color_orange'] = 'Laranja';
$string['color_teal'] = 'Verde-azulado';
$string['color_gray'] = 'Cinza';

// Eventos.
$string['event_assignment_created'] = 'Atribuição criada';
$string['event_assignment_updated'] = 'Atribuição atualizada';

// Aviso de minimização de dados exibido no formulário de registro.
$string['history_privacy_notice'] = 'Os registros de tutoria são dados acadêmicos do estudante. Registre somente o necessário ao acompanhamento pedagógico e evite dados pessoais sensíveis (saúde, origem racial ou étnica, convicção religiosa, opinião política, dados biométricos ou genéticos, vida sexual).';

// Capabilities (o Moodle exibe estas descrições nas telas de permissão).
$string['studenttutor:manage'] = 'Gerenciar as configurações do plugin de tutoria';
$string['studenttutor:viewassignments'] = 'Ver atribuições estudante-tutor';
$string['studenttutor:manageassignments'] = 'Gerenciar atribuições estudante-tutor';
$string['studenttutor:viewhistory'] = 'Ver histórico de tutoria';
$string['studenttutor:managehistory'] = 'Gerenciar histórico de tutoria';
$string['studenttutor:assign_students'] = 'Atribuir estudantes a tutores em um curso';
$string['studenttutor:view_assignments'] = 'Ver atribuições em um curso';
$string['studenttutor:manage_history'] = 'Gerenciar histórico de tutoria em um curso';
$string['studenttutor:view_own_students'] = 'Ver os próprios estudantes atribuídos';
$string['studenttutor:view_all_history'] = 'Ver o histórico de tutoria de toda a equipe';
$string['studenttutor:viewreports'] = 'Ver relatórios de tutoria';

// Metadados de privacidade (Privacy API - não altere os nomes das chaves).
$string['privacy:metadata'] = 'O plugin Nexo Tutoria Acadêmica armazena qual tutor é responsável por cada estudante e o histórico das interações de tutoria.';
$string['privacy:metadata:local_studenttutor_assign'] = 'Informações sobre qual tutor é responsável por qual estudante.';
$string['privacy:metadata:local_studenttutor_history'] = 'Informações sobre cada interação de tutoria registrada por um tutor.';
$string['privacy:path'] = 'Tutoria';
$string['privacy:assignments'] = 'Atribuições';
$string['privacy:history'] = 'Histórico de tutoria';
$string['privacy:student'] = 'Estudante';
$string['privacy:tutor'] = 'Tutor';
$string['privacy:assignedby'] = 'Atribuído por';
$string['privacy:createdby'] = 'Criado por';
$string['privacy:course'] = 'Curso';
$string['privacy:activitytype'] = 'Tipo de atividade';
$string['privacy:description'] = 'Relato da tutoria';
$string['privacy:activitydate'] = 'Data da atividade';
$string['privacy:timecreated'] = 'Criado em';
$string['privacy:timemodified'] = 'Modificado em';
$string['privacy:timeassigned'] = 'Atribuído em';
$string['privacy:status'] = 'Situação';
