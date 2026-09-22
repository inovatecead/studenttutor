# API de integração — Nexo Tutoria Acadêmica (`local_studenttutor`)

O plugin expõe quatro funções de web service (padrão Moodle) e um endpoint AJAX interno.

## 1. Como habilitar

1. **Administração do site → Servidor → Serviços externos**: confirme que os serviços externos estão
   habilitados e que o protocolo desejado (REST) está ativo.
2. O serviço **Nexo Tutoria Acadêmica API** (`studenttutor_api`) já é criado na instalação e contém as
   quatro funções.
3. Gere um *token* em **Administração do site → Servidor → Serviços externos → Gerenciar tokens**
   (recomenda-se usuário de serviço dedicado, com apenas as capabilities necessárias).
4. As funções também estão expostas no **serviço oficial do aplicativo Moodle Mobile**.

> Nunca versione *tokens*. Gere-os no ambiente e guarde-os em cofre de segredos.

## 2. Funções disponíveis

| Função | Tipo | Capability exigida | Descrição |
|---|---|---|---|
| `local_studenttutor_get_assignments` | leitura | `local/studenttutor:viewassignments` | lista atribuições com nomes de tutor, estudante e curso |
| `local_studenttutor_create_assignment` | escrita | `local/studenttutor:manageassignments` | cria uma atribuição tutor–estudante |
| `local_studenttutor_get_history` | leitura | `local/studenttutor:viewhistory` | lista registros de histórico |
| `local_studenttutor_add_history` | escrita | `local/studenttutor:managehistory` | registra uma atividade de tutoria |

### 2.1 `local_studenttutor_get_assignments`

**Parâmetros**

| Parâmetro | Tipo | Obrigatório | Padrão | Descrição |
|---|---|---|---|---|
| `filters[studentid]` | int | não | — | filtra por estudante |
| `filters[tutorid]` | int | não | — | filtra por tutor |
| `filters[courseid]` | int | não | — | filtra por curso |
| `filters[status]` | alfa | não | — | `active`, `inactive` ou `completed` |
| `sort` | texto | não | `a.timeassigned DESC` | ordenação (expressão SQL controlada) |
| `limitfrom` | int | não | 0 | posição inicial (paginação) |
| `limitnum` | int | não | 0 | quantidade de registros (0 = todos) |

**Retorno** — `assignments[]`, cada item com: `id`, `studentid`, `tutorid`, `courseid`, `status`,
`timecreated`, `timemodified`, `createdby`, `tutor_name`, `student_name`, `course_name`.

> **Compatibilidade de nomes:** os campos públicos `timecreated` e `createdby` retornam,
> respectivamente, os valores das colunas `timeassigned` e `assignedby`. Os nomes públicos foram
> mantidos para não quebrar integrações existentes; a tradução está documentada no próprio código.

### 2.2 `local_studenttutor_create_assignment`

**Parâmetros**

| Parâmetro | Tipo | Obrigatório | Padrão | Descrição |
|---|---|---|---|---|
| `studentid` | int | sim | — | estudante |
| `tutorid` | int | sim | — | tutor |
| `courseid` | int | não | 0 | curso; `0` cria atribuição global |
| `assignedby` | int | não | 0 | **obsoleto, ignorado** — o autor é sempre o usuário autenticado |

**Retorno**: `success` (bool), `assignmentid` (int), `message` (texto).

**Erros esperados**: `nopermissions` (sem capability), `invalidparameter`/`dml_missing_record_exception`
(usuário ou curso inexistente), exceção de validação para auto-atribuição e para duplicidade
(mensagem traduzida de `assignmentalreadyexists`).

### 2.3 `local_studenttutor_get_history`

**Parâmetros**

| Parâmetro | Tipo | Obrigatório | Padrão | Descrição |
|---|---|---|---|---|
| `filters[studentid]` | int | não | — | filtra por estudante |
| `filters[tutorid]` | int | não | — | filtra por tutor |
| `filters[courseid]` | int | não | — | filtra por curso |
| `filters[activitytype]` | texto | não | — | atalho do tipo (aceita `_`, ex.: `retorno_duvidas`) |
| `sort` | texto | não | `h.timecreated DESC` | ordenação |
| `limitfrom` / `limitnum` | int | não | 0 / 0 | paginação |

**Retorno** — `history[]`, cada item com: `id`, `studentid`, `tutorid`, `courseid`, `activitytype`,
`description`, `timecreated`, `timemodified`, `createdby`, `tutor_name`, `student_name`, `course_name`.

> A listagem detalhada só devolve registros cujo par estudante–tutor tenha atribuição **ativa** (no
> mesmo curso ou global) — comportamento herdado e coberto por teste automatizado.

### 2.4 `local_studenttutor_add_history`

**Parâmetros**

| Parâmetro | Tipo | Obrigatório | Padrão | Descrição |
|---|---|---|---|---|
| `studentid` | int | sim | — | estudante |
| `tutorid` | int | sim | — | tutor |
| `activitytype` | texto | sim | — | atalho do tipo de atividade (`PARAM_ALPHANUMEXT`) |
| `description` | HTML limpo | sim | — | descrição da atividade (`PARAM_CLEANHTML`) |
| `title` | texto | não | — | **obsoleto, ignorado** — mantido por compatibilidade |
| `courseid` | int | não | 0 | curso |

**Retorno**: `success` (bool), `historyid` (int), `message` (texto).

## 3. Exemplos

### 3.1 Chamada REST com token

```bash
curl -s "https://moodle.exemplo.br/webservice/rest/server.php" \
  -d "wstoken=SEU_TOKEN" \
  -d "wsfunction=local_studenttutor_get_assignments" \
  -d "moodlewsrestformat=json" \
  -d "filters[tutorid]=2059" \
  -d "limitnum=10"
```

```bash
curl -s "https://moodle.exemplo.br/webservice/rest/server.php" \
  -d "wstoken=SEU_TOKEN" \
  -d "wsfunction=local_studenttutor_add_history" \
  -d "moodlewsrestformat=json" \
  -d "studentid=1234" \
  -d "tutorid=2059" \
  -d "activitytype=retorno_duvidas" \
  -d "description=Retorno sobre a lista 3" \
  -d "courseid=42"
```

### 3.2 Chamada em contexto AJAX (JavaScript do próprio Moodle)

Chamadas via `core/ajax` / `external_api::call_external_function()` **exigem `sesskey`**, porque as
funções são declaradas sem `loginrequired = false` e a chamada não passa pelo servidor de web
services:

```php
// Em contexto PHP/AJAX, o núcleo valida o sesskey automaticamente quando a requisição o envia.
define('AJAX_SCRIPT', true);
require_once('config.php');
$result = external_api::call_external_function('local_studenttutor_get_assignments', [
    'filters' => ['tutorid' => $USER->id],
]);
```

## 4. Endpoint AJAX interno — `ajax_get_students.php`

Usado pela tela de atribuições (`scripts/assign_simple.js`) para montar a lista de estudantes do curso
selecionado.

| Item | Valor |
|---|---|
| Método | `POST` |
| Parâmetros | `courseid` (int; `0` = todos os cursos), `sesskey` |
| Exigências | `require_login()`, `require_sesskey()`, `local/studenttutor:manageassignments` |
| Retorno | JSON: `success`, `students[]` (`id`, `name`, `email`, `tutor_count`, `tutor_names`, `group_names`, `group_ids`, `group_display`, `has_tutors`, `has_groups`, `category`, `category_label`, `sort_priority`), `summary` |
| Erro | JSON `{"success": false, "error": "<mensagem traduzida>"}` — sem detalhes internos |

Este endpoint é **interno** (interface do plugin) e não faz parte do contrato de integração; para
integrações use as funções de web service.

## 5. Boas práticas de integração

- Use um usuário de serviço com o mínimo de capabilities necessárias.
- Considere a paginação (`limitfrom`/`limitnum`) em ambiente com muitos vínculos — o ambiente da UFMT
  já possui mais de 20 mil atribuições.
- Trate `success = false` e o campo `message` como estado esperado; não dependa do texto da mensagem
  (é traduzível).
- Não persista dados pessoais recebidos da API além do necessário e observe a política institucional
  de proteção de dados (ver [`../PRIVACY.md`](../PRIVACY.md)).
