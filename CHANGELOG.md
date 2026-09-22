# Histórico de versões — Nexo Tutoria Acadêmica (`local_studenttutor`)

Todas as alterações relevantes do plugin são registradas neste arquivo.
Formato inspirado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/).

## [1.1.0] — 2026

Versão de estabilização, segurança, documentação e testabilidade, mantendo compatibilidade com os
dados e as funcionalidades existentes (nenhuma tabela, capability, evento ou endpoint foi
removido ou teve o significado alterado).

### Adicionado

- **API de privacidade (LGPD)**: `classes/privacy/provider.php`, com declaração de metadados,
  exportação e exclusão de dados por titular, incluindo `core_userlist_provider` para exclusão em
  lote.
- **Suíte de testes automatizados (73 testes / 808 assertivas)**: managers, funções de web service,
  formulários, integridade de esquema/capabilities/strings e conformidade da API de privacidade.
- **Documentação completa** em `docs/` (instalação, atualização, permissões, banco, API,
  arquitetura, manual funcional, testes, dependências e padrões de código) e `thirdpartylibs.xml`.
- **Aviso de minimização de dados** nas telas de registro de tutoria.
- **Formulários como classes próprias** em `classes/form/` (`assignment_form`,
  `course_history_form`, `edit_history_form`, `activity_type_form`), agora autolocalizáveis.
- Versões de idioma **inglês e português em paridade total** (222 chaves cada), sem chaves órfãs.

### Corrigido

- **Segurança** — remoção de 11 scripts de apoio indevidos (`diagnostico.php`, `debug_navigation.php`,
  `test_ajax.php`, `teste_dashboard.php`, `verificacao_final.php`, `verificar_chaves.php`,
  `fix_database.php`, `fix_database_cli.php`, `db/fix_activity_date.php`, entre outros) que
  executavam DDL, diagnóstico ou depuração sem autenticação e/ou sem `sesskey`.
- **Segurança** — eliminação de `print_r()`, de `debugging()` em produção e de mensagens de exceção
  (`getMessage()`) exibidas ao usuário final.
- **Campos de nome de usuário** — as consultas montavam objetos de usuário apenas com `firstname` e
  `lastname`, o que fazia `fullname()` emitir avisos e **ignorava a configuração de exibição de nome
  do site** (nome fonético, nome do meio, nome alternativo). Agora os campos vêm de
  `\core_user\fields::for_name()` e são mapeados com `username_load_fields_from_object()`.
- **Ordenação padrão** de `get_assignments()` e `get_all_assignments_with_details()` referenciava
  coluna inexistente, retornando lista vazia em silêncio.
- **Filtro de tipo de atividade** no web service usava `PARAM_ALPHA`, que descarta `_` e portanto
  nunca correspondia aos atalhos reais (`retorno_duvidas`, etc.).
- **Mapeamento de argumentos** nas funções de web service `create_assignment` e `add_history`
  (autor deixou de ser informado pelo cliente e passou a ser o usuário que executa a ação).
- **Cache estático** em `local_studenttutor_is_tutor()`, que não era invalidado quando as atribuições
  de papel mudavam.
- **Params duplicados/desconhecidos** nas funções de web service (`title` deixou de ser aceito como
  parâmetro efetivo; `assignedby` é aceito por compatibilidade e ignorado).
- **`VALUE_OPTIONAL` em parâmetro de topo** nas funções de web service, construção proibida pelo
  Moodle e que impedia a chamada da função.
- **String de validação inexistente** (`get_string('alphanumeric', 'core')`), que exibia
  `[[[alphanumeric]]]` no formulário de tipo de atividade.
- **Ícone de ajuda sem conteúdo** no campo "Curso" (chave `course_help_help` ausente).
- **`install.xml` divergente do banco real**: o arquivo descrevia colunas que não existiam
  (`timecreated`, `title`) e não declarava índices presentes. Agora corresponde exatamente ao banco.
- **`local/processoseletivo`** (plugin irmão): o `db/upgrade.php` abortava instalações novas ao
  atribuir capability inexistente na versão (`moodle/user:viewownprofile`). Corrigido com verificação
  prévia, o que destrava a instalação limpa do Moodle.

### Alterado

- `lib.php` reduzido ao essencial (ganchos de navegação e resolução de papel de tutor): de 793 para
  ~142 linhas. Funções de dashboard sem uso foram removidas.
- Páginas reorganizadas: `index.php` deixou de ser dashboard e passou a ser a lista de atribuições
  com filtros; `reports.php` responde pelos relatórios.
- Ações administrativas (`assign.php`, `manage_activity_types.php`, `edit_activity_type.php`)
  exigem `local/studenttutor:manageassignments`.
- Regras de integridade de esquema/capabilities/strings passaram a ser verificadas por testes.
- Interface totalmente traduzida: nenhum texto fixo em português ou inglês permanece no código.

### Removido

- Dashboards e seus ativos (`dashboard*.php`, `amd/`, `styles/multiselect.css`,
  `scripts/index_filters.js`, `classes/external/get_dashboard_data.php`), substituídos pela lista de
  atribuições e pelos relatórios.
- `history_manager::bulk_import_history()` — método sem nenhuma chamada, que expunha mensagem de
  exceção ao usuário e gravava argumentos em posições erradas.
- Colunas mortas de e-mail nas consultas (`tu.email`, `st.email`): eram lidas do banco e nunca
  utilizadas, o que contrariava a minimização de dados.
- 5 sondagens de esquema em tempo de execução, substituídas pela definição real em `db/install.xml`.
- 50 chaves de idioma órfãs e duplicadas herdadas da versão anterior.

### Notas de compatibilidade

- **Não há alteração de esquema destrutiva**: as tabelas, campos e índices existentes foram
  preservados; `db/install.xml` foi ajustado para descrevê-los corretamente.
- As 11 capabilities declaradas mantêm nomes e contextos originais (nenhuma foi removida ou
transformada em oposta; 7 delas são legadas e continuam sem uso, conforme documentado em
  `docs/PERMISSIONS.md`).
- Os 4 endpoints de web service mantêm nome, tipo e capability originais.
- Os eventos `assignment_created` e `assignment_updated` mantêm nome e payload.
- Ao atualizar, **execute a limpeza de caches** após a migração (as classes novas dependem do
  *classmap* atualizado).

## [1.0.x] — 2025

Versão inicial em produção (novembro de 2025), com atribuições tutor–estudante, histórico de
atividades, relatórios e API de integração. O registro detalhado do desenvolvimento dessa fase está
preservado em [`docs/historico/CHANGELOG_GROUPS.md`](docs/historico/CHANGELOG_GROUPS.md) e
[`docs/historico/README-pre-1.1.0.md`](docs/historico/README-pre-1.1.0.md).
