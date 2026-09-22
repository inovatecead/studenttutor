# Dependências — Nexo Tutoria Acadêmica (`local_studenttutor`)

## 1. Dependências de execução

| Dependência | Versão | Origem | Observação |
|---|---|---|---|
| Moodle | 4.0+ (testado em 4.3.5) | plataforma hospedeira | o plugin usa as APIs públicas do núcleo: XMLDB, Form API, `external_api`, Events API, Privacy API, Output/`html_writer` |
| PHP | 8.0+ (testado em 8.2.31) | plataforma | exigência herdada do Moodle 4.x |
| Banco de dados | MariaDB/MySQL, PostgreSQL ou SQL Server | plataforma | acesso exclusivamente por XMLDB/`moodle_database` |
| Papel de tutor | configurável (`tutortematico` por padrão) | instalação Moodle | precisa existir no site; é configuração, não dependência de código |

**Não há** dependências de terceiros, bibliotecas externas, serviços web de terceiros, SDKs,
extensões PHP adicionais, chamadas de rede ou telemetria.

## 2. Ativos próprios incluídos no plugin

| Arquivo | Linhas | Descrição | Licença |
|---|---|---|---|
| `scripts/assign_simple.js` | 273 | JavaScript próprio (vanilla) para carregamento dinâmico da lista de estudantes | GPL v3+ |
| `styles/assign_form.css` | 118 | estilos próprios do formulário de atribuição | GPL v3+ |
| `styles/assign_enhanced.css` | 250 | estilos próprios da área de atribuições | GPL v3+ |

Esses arquivos são de autoria própria e seguem a licença do plugin. Ver
[`../thirdpartylibs.xml`](../thirdpartylibs.xml).

## 3. Bibliotecas do núcleo utilizadas (não redistribuídas)

O plugin **referencia** recursos que já fazem parte do Moodle — nada é copiado para dentro do plugin:

| Recurso | Uso | Onde |
|---|---|---|
| Bootstrap (do tema Moodle) | classes de layout (`btn`, `alert`, `badge`, `table-striped`, `text-muted`) | páginas e formulários |
| FontAwesome (do Moodle) | ícones dos tipos de atividade (`fa-users`, `fa-video`, `fa-clipboard-check`, ...) | campo `icon` dos tipos de atividade |
| jQuery (do Moodle) | usado apenas no módulo AMD embutido da página de atribuições | `index.php` |
| Módulo AMD `core/form-autocomplete` | autocomplete dos filtros | `index.php` |

Como esses recursos vêm da instalação Moodle/tema, suas licenças e versões são as do próprio núcleo,
e não deste plugin (por isso não constam em `thirdpartylibs.xml`).

## 4. Dependências de desenvolvimento (não distribuídas)

| Dependência | Uso | Onde fica |
|---|---|---|
| PHPUnit 9.5 | execução dos 73 testes automatizados | `vendor/` da instalação Moodle, via `composer install` |
| `moodlehq/moodle-cs` (PHP_CodeSniffer + padrão Moodle) | verificação de estilo de código | instalado fora do plugin (ferramenta de desenvolvimento) |

**Importante:** a suíte de testes não faz parte do pacote de distribuição do plugin. Antes de gerar o
pacote, `vendor/`, `composer.lock`, `phpunit.xml` e as diretivas `$CFG->phpunit_*` do `config.php`
(que são do ambiente de desenvolvimento) **não** devem ser incluídos nem permanecer no ambiente de
produção.

## 5. Verificação de código de terceiros

Comando usado para confirmar que não há bibliotecas embarcadas:

```bash
# termos típicos de bibliotecas de terceiros
grep -rniE "chart\.js|jquery|bootstrap|fontawesome|spectrum|datatables|select2|vendor/" \
     --include=*.php --include=*.js --include=*.css .
```

Resultado: nenhuma biblioteca de terceiros é embarcada no plugin. O código de gráficos
(Chart.js) e os *assets* de dashboard da versão 1.0.x foram removidos na versão 1.1.0.
