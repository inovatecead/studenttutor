# Permissões — Nexo Tutoria Acadêmica (`local_studenttutor`)

Todas as capabilities são declaradas em `db/access.php` e concedidas por papel do Moodle em
**Administração do site → Usuários → Permissões → Definir papéis**.

## 1. Capabilities declaradas

| Capability | Tipo | Contexto | Arquétipos com permissão (padrão) | Usada no código |
|---|---|---|---|---|
| `local/studenttutor:viewassignments` | leitura | sistema | teacher, editingteacher, manager | **sim** |
| `local/studenttutor:manageassignments` | escrita | sistema | editingteacher, manager | **sim** |
| `local/studenttutor:viewhistory` | leitura | sistema | teacher, editingteacher, manager | **sim** |
| `local/studenttutor:managehistory` | escrita | sistema | teacher, editingteacher, manager | **sim** |
| `local/studenttutor:manage` | escrita | sistema | teacher, editingteacher, manager | não (legado) |
| `local/studenttutor:assign_students` | escrita | curso | editingteacher, manager | não (legado) |
| `local/studenttutor:view_assignments` | leitura | curso | teacher, editingteacher, manager | não (legado) |
| `local/studenttutor:manage_history` | escrita | curso | teacher, editingteacher, manager | não (legado) |
| `local/studenttutor:view_own_students` | leitura | curso | teacher, editingteacher | não (legado) |
| `local/studenttutor:view_all_history` | leitura | curso | editingteacher, manager | não (legado) |
| `local/studenttutor:viewreports` | leitura | sistema | editingteacher, manager | não (legado) |

> **Sobre as capabilities legadas:** sete capabilities permanecem declaradas e traduzidas, mas não
> são consultadas pelo código na versão 1.1.0 — o controle de acesso das telas do tutor é feito pelo
> papel de tutor no curso. Elas foram **mantidas** de propósito: remover uma capability em uso por
> configurações de papel existentes é uma mudança de contrato. Qualquer remoção futura exige análise
> de impacto, comunicação às instituições e atualização desta documentação.

O papel que identifica o tutor no curso **não** é uma capability: é resolvido por
`local_studenttutor_is_tutor()`, a partir das configurações **Papel de tutor** (`tutortematico` por
padrão) e **Papéis adicionais de tutor**.

## 2. O que cada página exige

| Página | Exigência de acesso |
|---|---|
| `index.php` | `local/studenttutor:viewassignments` para administração; tutores acessam com o papel de tutor (veem apenas os próprios estudantes). Exclusão exige `manageassignments` |
| `assign.php` | `local/studenttutor:manageassignments` |
| `reports.php` | `local/studenttutor:viewhistory` |
| `course_view.php` | papel de tutor no curso |
| `student_history.php` | papel de tutor no curso + `local/studenttutor:managehistory` |
| `add_history.php` | papel de tutor no curso |
| `edit_history.php` | papel de tutor no curso + `local/studenttutor:managehistory` |
| `manage_activity_types.php` | `local/studenttutor:manageassignments` |
| `edit_activity_type.php` | `local/studenttutor:manageassignments` |
| `ajax_get_students.php` | `local/studenttutor:manageassignments` + `require_login()` + `require_sesskey()` |
| `settings.php` | `$hassiteconfig` (apenas administradores do site) |

Todas as páginas chamam `require_login()` — as de curso com `require_login($course)` — antes de
qualquer verificação de permissão.

## 3. Capabilities das funções de web service

| Função | Capability exigida |
|---|---|
| `local_studenttutor_get_assignments` | `local/studenttutor:viewassignments` |
| `local_studenttutor_create_assignment` | `local/studenttutor:manageassignments` |
| `local_studenttutor_get_history` | `local/studenttutor:viewhistory` |
| `local_studenttutor_add_history` | `local/studenttutor:managehistory` |

As capabilities são declaradas no metadado da função (`db/services.php`) **e** verificadas dentro de
`execute()`. Detalhes em [`API.md`](API.md).

## 4. Configuração recomendada por perfil

| Perfil | Papel sugerido | Capabilities necessárias |
|---|---|---|
| Tutor | papel de tutor no curso (`tutortematico` ou configurado) | papel de tutor; `managehistory` para editar registros na área do tutor |
| Coordenação de tutoria | papel de curso com permissão de edição | `viewassignments`, `manageassignments` |
| Administração do site | manager/admin | todas |
| Estudante | — | nenhuma (o plugin não expõe interface ao estudante) |

## 5. Verificação de permissões

1. **Conferir se as capabilities aparecem**: Administração do site → Usuários → Permissões → Definir
   papéis → filtrar por `local/studenttutor`.
2. **Verificar o provedor de privacidade**: Administração do site → Usuários → Privacidade e políticas
   → Verificação do provedor de privacidade.
3. **Testar o acesso negado**: com um usuário sem papel de tutor, abrir `course_view.php` — deve
   receber "sem permissão"; abrir `index.php` — deve ser redirecionado.
4. **Testar o escopo do tutor**: com dois tutores, cada um deve ver apenas os próprios estudantes na
   lista de atribuições.
