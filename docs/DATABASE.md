# Banco de dados — Nexo Tutoria Acadêmica (`local_studenttutor`)

Esquema definido em `db/install.xml` e aplicado pelas etapas de `db/upgrade.php`. O plugin usa
exclusivamente a API XMLDB do Moodle (`$DB->get_records*`, `get_record_select`, `insert_record`,
`update_record`, `execute`), com *placeholders* nomeados e prefixo de tabela do site.

## 1. Visão geral

| Tabela | Conteúdo | Dado pessoal |
|---|---|---|
| `local_studenttutor_assign` | vínculos tutor–estudante | sim (identificadores de usuário) |
| `local_studenttutor_history` | histórico das atividades de tutoria | sim (identificadores + descrição) |
| `local_studenttutor_activity_types` | tipos de atividade configuráveis | não |

Volume no ambiente de produção da UFMT (referência de dimensionamento): 20.716 atribuições,
3.728 registros de histórico e 11 tipos de atividade.

## 2. `local_studenttutor_assign`

Vínculo entre estudante e tutor, por curso ou global (`courseid = 0`).

| Campo | Tipo | Nulo | Descrição |
|---|---|---|---|
| `id` | int(10) | não | chave primária |
| `studentid` | int(10) | não | FK → `user.id` (estudante) |
| `tutorid` | int(10) | não | FK → `user.id` (tutor) |
| `courseid` | int(10) | sim | FK → `course.id`; `0` = atribuição global |
| `assignedby` | int(10) | não | FK → `user.id` (quem criou a atribuição) |
| `timeassigned` | int(10) | não | data de criação do vínculo (*timestamp*) |
| `timemodified` | int(10) | não | data da última alteração |
| `status` | char(20) | não | `active`, `inactive` ou `completed` (`class assignment_manager`) |

| Chave / índice | Tipo | Campos |
|---|---|---|
| `primary` | primária | `id` |
| `studentid`, `tutorid`, `courseid`, `assignedby` | estrangeira | campo → `user.id` / `course.id` |
| `student_tutor_idx` | índice | `studentid, tutorid` |
| `course_idx` | índice | `courseid` |
| `status_idx` | índice | `status` |

**Regras de integridade aplicadas em PHP** (não por restrição de banco):

- auto-atribuição (estudante = tutor) é rejeitada;
- duplicidade do trio estudante + tutor + curso é rejeitada;
- estudante, tutor e curso precisam existir e não estar excluídos/`deleted`.

> **Nota técnica:** não há índice único para o trio (estudante, tutor, curso); a prevenção de
> duplicidade é feita em `assignment_manager::create_assignment()`. As linhas duplicadas criadas por
> versões anteriores foram tratadas na limpeza de dados da versão 1.1.0. Uma restrição única no banco
> seria desejável, mas é uma mudança de esquema que exige análise de impacto e janela de manutenção.

## 3. `local_studenttutor_history`

Registro das interações de tutoria de um par estudante–tutor.

| Campo | Tipo | Nulo | Descrição |
|---|---|---|---|
| `id` | int(10) | não | chave primária |
| `studentid` | int(10) | não | FK → `user.id` |
| `tutorid` | int(10) | não | FK → `user.id` |
| `courseid` | int(10) | sim | FK → `course.id`; `0` = atividade fora de curso específico |
| `activitytype` | char(50) | não | atalho do tipo de atividade (`local_studenttutor_activity_types.shortname`) |
| `description` | text | sim | descrição da atividade (texto simples) |
| `timecreated` | int(10) | não | data do registro no sistema |
| `timemodified` | int(10) | não | data da última edição |
| `createdby` | int(10) | não | FK → `user.id` (autor do registro) |
| `activity_date` | int(10) | não | data em que a atividade de tutoria ocorreu |

| Chave / índice | Tipo | Campos |
|---|---|---|
| `primary` | primária | `id` |
| `studentid`, `tutorid`, `courseid`, `createdby` | estrangeira | campo → `user.id` / `course.id` |
| `student_tutor_hist_idx` | índice | `studentid, tutorid` |
| `course_hist_idx` | índice | `courseid` |
| `activitytype_idx` | índice | `activitytype` |
| `timecreated_idx` | índice | `timecreated` |

**Regras de integridade aplicadas em PHP:**

- `activitytype` precisa existir em `local_studenttutor_activity_types`;
- `description` não pode ser vazia;
- estudante e tutor precisam existir e não estar excluídos;
- a listagem detalhada (`get_history_with_details()`) só devolve registros cujo par
  estudante–tutor tenha atribuição **ativa**, no mesmo curso ou global — comportamento herdado da
  implementação original e coberto por teste.

> **Nota histórica:** o campo `title` foi criado na versão 1.0.x e deixado de ser usado; `activity_date`
> passou a ser o campo de data de exibição. As etapas de `db/upgrade.php` que citam `title` existem
> apenas para compatibilidade com instalações antigas.

## 4. `local_studenttutor_activity_types`

Tipos de atividade de tutoria administráveis (nome, atalho, aparência e ordem).

| Campo | Tipo | Nulo | Descrição |
|---|---|---|---|
| `id` | int(10) | não | chave primária |
| `name` | char(100) | não | nome exibido |
| `shortname` | char(50) | não | atalho único, usado em `history.activitytype` |
| `description` | text | sim | descrição do tipo |
| `icon` | char(50) | sim | classe do ícone FontAwesome (ex.: `fa-users`) |
| `color` | char(7) | sim | cor em hexadecimal (ex.: `#28a745`) |
| `active` | int(1) | não | disponível para seleção |
| `sortorder` | int(10) | não | ordem de exibição |
| `timecreated` | int(10) | não | criação |
| `timemodified` | int(10) | não | última alteração |

| Chave / índice | Tipo | Campos |
|---|---|---|
| `primary` | primária | `id` |
| `shortname` | **único** | `shortname` |
| `active` | índice | `active` |
| `sortorder` | índice | `sortorder` |

Tipos cadastrados na instalação da UFMT: convocação de reunião, retorno de dúvidas, reunião virtual
(webconferência), reunião presencial no polo, grupo do seminário integrador (online e presencial),
apresentação de seminário temático, aplicação de prova/atividade, nivelamento em áreas temáticas,
diagnóstico de dificuldades e "Outros".

O atalho (`shortname`) é **contrato de dados**: alterá-lo depois de haver registros de histórico
desvincula o tipo dos registros antigos. A interface não permite editar o atalho de um tipo já
utilizado.

## 5. Consultas e desempenho

- As listagens usam `get_records_sql` com `JOIN` para usuário e curso, com *placeholder* nomeado e
  paginação (`limitfrom`, `limitnum`).
- Campos de nome do usuário são obtidos por `\core_user\fields::for_name()->get_sql()` e mapeados com
  `username_load_fields_from_object()` — a lista de campos acompanha a configuração do site.
- O histórico detalhado usa `EXISTS` sobre a tabela de atribuições; `student_tutor_hist_idx` e
  `course_hist_idx` sustentam esse acesso.
- Nenhuma consulta executa DDL em tempo de execução.

## 6. Backup, retenção e LGPD

- Tabelas incluídas no backup padrão do Moodle; não têm arquivos próprios em `dataroot`.
- Retenção definida pela instituição; exclusão por titular é feita pela ferramenta de privacidade
  (ver [`../PRIVACY.md`](../PRIVACY.md)).
- Ao restaurar um curso ou fazer cópia do site, avalie a duplicação de registros pessoais de tutoria
  e as políticas institucionais aplicáveis.

## 7. Operações manuais

O plugin não distribui scripts de manipulação de banco. Operações corretivas devem ser feitas com
`moodle_database` (API do Moodle) ou `admin/cli/` do próprio Moodle, sempre com backup e registro —
nunca com scripts avulsos no diretório do plugin (motivo pelo qual os antigos `fix_database*.php`
foram removidos na versão 1.1.0).
