# Política de segurança — Nexo Tutoria Acadêmica

Plugin `local_studenttutor` — versão 1.1.0.

## Versões suportadas

| Versão | Suporte |
|---|---|
| 1.1.0 | Sim (versão atual) |
| 1.0.x | Não — recomenda-se atualizar para 1.1.0 |

## Como relatar uma vulnerabilidade

Envie um relato para a equipe responsável pelo plugin (INOVATEC/UFMT) pelos canais internos da
instituição, com:

- descrição do problema e impacto potencial;
- passos para reproduzir (URL, papel do usuário, parâmetros);
- versão do plugin e do Moodle;
- eventual prova de conceito, sem dados pessoais reais.

**Não abra issue pública enquanto a correção não estiver disponível.** Evite incluir dados pessoais,
credenciais, tokens ou *dumps* de banco no relato.

## Modelo de segurança do plugin

O plugin delega autenticação e autorização ao Moodle e segue as seguintes premissas:

### Autorização

- Toda página executa `require_login()` (com curso, quando aplicável) e verifica capability ou
  papel de tutor no contexto correto antes de qualquer leitura/escrita.
- As ações administrativas exigem `local/studenttutor:manageassignments`, em contexto de sistema.
- As páginas da área do tutor exigem papel de tutor **no curso**, resolvido por
  `local_studenttutor_is_tutor()`, respeitando os papéis configurados.
- As funções de web service declaram a capability exigida em `db/services.php` e repetem a
  verificação em `execute()`; a API do Moodle (`external_api::validate_context`,
  `require_capability`) executa o controle no contexto de sistema.

### Proteção contra CSRF

- O endpoint AJAX `ajax_get_students.php` exige `require_login()`, `require_sesskey()` e
  `local/studenttutor:manageassignments`.
- Ações destrutivas em `index.php` usam `sesskey()` no URL de confirmação.
- As funções de web service chamadas por AJAX dependem do `sesskey` validado pelo núcleo do Moodle.

### Tratamento de dados e saída

- Todas as entradas passam por `required_param`/`optional_param` com tipo explícito (`PARAM_INT`,
  `PARAM_ALPHA`, `PARAM_ALPHANUMEXT`, `PARAM_TEXT`).
- Toda consulta usa a API de banco do Moodle com *placeholders* nomeados; não há SQL concatenado com
  dados de entrada.
- A saída HTML passa por `html_writer`, `s()` ou `format_text` conforme o caso.
- Nenhum dado pessoal (e-mail, IP, token) é devolvido pela API além do necessário à identificação do
  estudante/tutor no contexto de tutoria.

### Exposição de informação

- Não há mensagens de exceção (`getMessage()`), `print_r()`/`var_dump()` ou `debugging()` voltados ao
  usuário final.
- Mensagens de depuração restantes usam `DEBUG_DEVELOPER` e aparecem apenas com depuração
  habilitada no site.
- Nenhuma credencial, chave ou segredo é armazenada no código do plugin.

## Correções de segurança da versão 1.1.0

| Problema | Impacto | Correção |
|---|---|---|
| 11 scripts de apoio com execução sem autenticação e/ou sem `sesskey`, alguns com DDL bruto fora da API XMLDB | Alteração/consulta indevida de dados e informações de diagnóstico expostas | Scripts removidos; migração passa a ser feita por `db/upgrade.php` via Notificações |
| `print_r()` e dumps de consulta em telas do plugin | Vazamento de dados pessoais na interface | Removidos |
| Mensagens de exceção exibidas ao usuário | Exposição de estrutura interna e de SQL | Substituídas por mensagens traduzidas |
| `debugging()` de produção com identificadores de usuário | Registro desnecessário de dados pessoais em log | Removido |
| Consulta de atribuições que devolvia objeto vazio em silêncio | Perda de informação operacional | Ordenação e mapeamento corrigidos |
| Exposição de e-mail em consultas internas sem uso | Dado pessoal trafegado sem finalidade | Colunas removidas |

## Escopo e limitações

- O plugin não implementa autenticação própria; depende das sessões e da configuração de segurança do
  Moodle.
- Vulnerabilidades do próprio Moodle, de bibliotecas do Moodle ou da infraestrutura de hospedagem não
  fazem parte deste escopo — devem ser tratadas na atualização do Moodle e do ambiente.
- O plugin não faz chamadas a serviços externos: não há envio de dados para terceiros.
