# Nexo Tutoria Acadêmica

Plugin de tutoria acadêmica para Moodle: atribui estudantes a tutores e registra o histórico das
atividades de tutoria, com relatórios e API de integração.

| | |
|---|---|
| **Nome do produto** | Nexo Tutoria Acadêmica |
| **Componente técnico** | `local_studenttutor` (não alterado) |
| **Versão** | 1.1.0 |
| **Tipo** | Plugin local (`local`) para Moodle |
| **Autoria** | Rodrigo Severo Ribeiro |
| **Titular** | Universidade Federal de Mato Grosso (UFMT) — INOVATEC/UFMT |
| **Licença** | GPL v3 ou superior (GNU GPL v3+) |
| **Criação funcional** | Agosto de 2025 |
| **Em produção desde** | Novembro de 2025 |

> Este repositório contém o código-fonte do plugin. O histórico anterior à versão 1.1.0 está
> preservado em [`docs/historico/`](docs/historico/).

## O que o plugin faz

- **Atribuições tutor–estudante**: vínculos por curso específico ou globais (todos os cursos do
  estudante), com validação de duplicidade e bloqueio de auto-atribuição.
- **Histórico de tutoria**: registro datado das interações, com tipo de atividade configurável,
  descrição, autor e vínculo com o par estudante–tutor.
- **Área do tutor**: menu "Meus Alunos" na navegação do curso, lista de estudantes atribuídos e
  registro de novas atividades.
- **Relatórios**: filtros por tutor, estudante, curso, tipo de atividade e período, com estatísticas
  de primeiro/último contato e distribuição por tipo.
- **Tipos de atividade administráveis**: nomes, atalhos, ícones, cores e ordem definidos pelo
  administrador (11 tipos já cadastrados no ambiente da UFMT).
- **API de integração**: quatro funções de web service, disponíveis também no serviço oficial do
  aplicativo Moodle Mobile.

## Requisitos

| Item | Valor |
|---|---|
| Moodle | 4.0 ou superior (`$plugin->requires = 2022041900`); **desenvolvido e testado em 4.3.5** (`2023100905.04`) |
| Faixa declarada | `$plugin->supported = [400, 405]` |
| PHP | 8.0 ou superior (exigência do Moodle 4.x); testado em PHP 8.2.31 |
| Banco de dados | MySQL/MariaDB (testado em MariaDB 11.8.6), PostgreSQL ou SQL Server — usa apenas a API XMLDB |
| Papéis | um papel de tutor no curso (por padrão `tutortematico`, configurável) |

## Instalação resumida

1. Copie a pasta `studenttutor` para `local/` da instalação Moodle:
   `local/studenttutor/`
2. Acesse **Administração do site → Notificações** e conclua a instalação (executa `db/install.xml`
   e `db/upgrade.php`).
3. **Execute a limpeza de caches** (Administração do site → Desenvolvimento → Limpar caches).
   As classes novas só passam a ser carregadas depois disso.
4. Revise as configurações em **Administração do site → Plugins → Plugins locais → Nexo Tutoria
   Acadêmica** (papel de tutor, papéis adicionais, limite de atribuições por tutor).
5. Conceda as capabilities às roles desejadas (ver [`docs/PERMISSIONS.md`](docs/PERMISSIONS.md)).

O passo a passo completo, incluindo verificação pós-instalação, está em
[`docs/INSTALL.md`](docs/INSTALL.md).

## Estrutura do plugin

```
local/studenttutor/
├── index.php, assign.php                 # lista/filtros de atribuições e formulário de atribuição
├── course_view.php, student_history.php  # área do tutor e histórico por estudante
├── add_history.php, edit_history.php     # registro e edição de atividades de tutoria
├── reports.php                           # relatórios e estatísticas
├── manage_activity_types.php, edit_activity_type.php
├── ajax_get_students.php                 # endpoint AJAX da seleção de estudantes
├── lib.php, settings.php, version.php
├── classes/
│   ├── assignment_manager.php            # regras de atribuição (validação, eventos)
│   ├── history_manager.php               # regras do histórico
│   ├── activity_type_manager.php         # tipos de atividade
│   ├── event/                            # eventos assignment_created / assignment_updated
│   ├── external/                         # 4 funções de web service
│   ├── form/                             # formulários (moodleform)
│   └── privacy/provider.php              # API de privacidade (LGPD)
├── db/                                   # install.xml, upgrade.php, access.php, services.php
├── lang/en, lang/pt_br                   # 222 strings por idioma, em paridade
├── scripts/, styles/                     # JS/CSS próprios
├── tests/                                # 73 testes automatizados (PHPUnit)
└── docs/                                 # documentação técnica e funcional
```

## Permissões (resumo)

O plugin declara **11 capabilities** em `db/access.php`, das quais **4 são efetivamente verificadas
pelo código** (as demais são legadas e foram mantidas por compatibilidade com configurações de papel
existentes). As quatro usadas são de contexto de sistema (`CONTEXT_SYSTEM`):

| Capability | Para que serve |
|---|---|
| `local/studenttutor:viewassignments` | Ver a lista de atribuições |
| `local/studenttutor:manageassignments` | Criar, editar e excluir atribuições; administrar tipos de atividade |
| `local/studenttutor:viewhistory` | Ver relatórios e histórico |
| `local/studenttutor:managehistory` | Registrar e editar atividades de tutoria |

A tabela completa, com arquétipos padrão e a capability exigida por cada página, está em
[`docs/PERMISSIONS.md`](docs/PERMISSIONS.md).

## API de integração

| Função | Tipo | Capability |
|---|---|---|
| `local_studenttutor_get_assignments` | leitura | `local/studenttutor:viewassignments` |
| `local_studenttutor_create_assignment` | escrita | `local/studenttutor:manageassignments` |
| `local_studenttutor_get_history` | leitura | `local/studenttutor:viewhistory` |
| `local_studenttutor_add_history` | escrita | `local/studenttutor:managehistory` |

As quatro funções compõem o serviço **Nexo Tutoria Acadêmica API** (`studenttutor_api`) e também
estão expostas no serviço oficial do aplicativo Moodle Mobile. Parâmetros, retornos e exemplos estão
em [`docs/API.md`](docs/API.md).

## Privacidade e proteção de dados (LGPD)

O plugin armazena apenas os dados necessários à tutoria (identificadores de usuário, curso, tipo de
atividade, descrição e datas) — **não coleta e-mail, IP, notas, gênero, raça ou qualquer dado
sensível**. Implementa a API de privacidade do Moodle, com exportação e exclusão por titular.

- [`PRIVACY.md`](PRIVACY.md) — quais dados são tratados, finalidade, retenção e direitos do titular.
- [`SECURITY.md`](SECURITY.md) — postura de segurança e canal para relato de vulnerabilidades.

## Testes

```
73 testes, 808 assertivas — OK
```

Os testes automatizados cobrem os managers, as funções de web service, os formulários, a
integridade de esquema/capabilities/strings e a conformidade com a API de privacidade. Como executar
(pré-requisitos de ambiente, locale e dataroot isolado) está em [`docs/TESTING.md`](docs/TESTING.md).

## Padrões de código

O código segue o padrão oficial do Moodle (`moodlehq/moodle-cs`), com cabeçalhos GPL, docblocks em
todas as classes/funções públicas e `@covers` nos testes. O comando de verificação, o resultado
atual e os desvios aceitos (com justificativa e comparação com o próprio núcleo do Moodle) estão em
[`docs/PADROES_DE_CODIGO.md`](docs/PADROES_DE_CODIGO.md).

## Documentação

| Documento | Conteúdo |
|---|---|
| [`docs/INSTALL.md`](docs/INSTALL.md) | instalação e verificação pós-instalação |
| [`docs/UPGRADE.md`](docs/UPGRADE.md) | atualização de versões anteriores e migração de dados |
| [`docs/PERMISSIONS.md`](docs/PERMISSIONS.md) | capabilities, arquétipos e permisssões por página |
| [`docs/DATABASE.md`](docs/DATABASE.md) | tabelas, campos, índices e regras de integridade |
| [`docs/API.md`](docs/API.md) | funções de web service e endpoints AJAX |
| [`docs/ARQUITETURA.md`](docs/ARQUITETURA.md) | camadas, fluxo de requisição e decisões de projeto |
| [`docs/MANUAL_FUNCIONAL.md`](docs/MANUAL_FUNCIONAL.md) | manual do administrador e do tutor |
| [`docs/TESTING.md`](docs/TESTING.md) | como executar e escrever testes |
| [`docs/DEPENDENCIES.md`](docs/DEPENDENCIES.md) | dependências e código de terceiros |
| [`docs/PADROES_DE_CODIGO.md`](docs/PADROES_DE_CODIGO.md) | verificação de estilo e desvios documentados |
| [`CHANGELOG.md`](CHANGELOG.md) | histórico de versões |

## Licença

GNU General Public License, versão 3 ou superior (GPLv3+), a mesma do Moodle. Consulte o cabeçalho
de cada arquivo e o texto integral em <https://www.gnu.org/licenses/gpl-3.0.html>.
