# Testes — Nexo Tutoria Acadêmica (`local_studenttutor`)

O plugin usa o **PHPUnit do Moodle** (`advanced_testcase`) e roda dentro da estrutura oficial de
testes de plugins. Estado atual:

```
OK (73 tests, 808 assertions)
```

## 1. Cobertura

| Arquivo | Testes | O que verifica |
|---|---|---|
| `tests/assignment_manager_test.php` | 12 | criação/atualização/exclusão de atribuições, duplicidade, auto-atribuição, autoria padrão, escopo, ordenação, campos de nome, estatísticas |
| `tests/history_manager_test.php` | 13 | mapeamento de argumentos, valores padrão, validações, atualização, exclusão, filtros, histórico por par, exigência de atribuição ativa, campos de nome, estatísticas |
| `tests/activity_type_manager_test.php` | 12 | criação, atualização, exclusão, ordenação, filtro de ativos, unicidade de atalho |
| `tests/external_test.php` | 8 | contrato das 4 funções de web service: mapeamento de argumentos, autoria, capabilities, filtros (`_` nos atalhos), ordenação padrão |
| `tests/privacy/provider_test.php` | 12 | conformidade com a API de privacidade: metadados, exportação e exclusão de dados |
| `tests/lib_test.php` | 7 | resolução de papel de tutor e funções de `lib.php` |
| `tests/plugin_integrity_test.php` | 7 | esquema real × `install.xml`, colunas legadas, capabilities usadas × declaradas, strings usadas × declaradas (inclusive de outros componentes e de `addHelpButton`), paridade dos pacotes de idioma |
| `tests/form_test.php` | 2 | autolocalização e construção dos 4 formulários; estabilidade dos nomes de campos |

Testes de integridade merecem destaque por terem encontrado defeitos reais durante a versão 1.1.0
(string de validação inexistente, help sem conteúdo, formulário não autolocalizável).

## 2. Pré-requisitos do ambiente

| Item | Observação |
|---|---|
| Dependências de desenvolvimento | `composer install` na raiz do Moodle (traz PHPUnit 9.5 conforme `composer.lock`) |
| Prefixo e `dataroot` de teste isolados | `$CFG->phpunit_prefix` e `$CFG->phpunit_dataroot` em `config.php` (**somente em ambiente de desenvolvimento**) |
| Locale `en_AU.UTF-8` | exigido pelo inicializador de testes do Moodle; instale com `localedef` no contêiner/servidor |
| Pacote de idioma `pt_br` | necessário para os testes de paridade de tradução |
| Caches limpos | após criar/renomear classes execute `php admin/cli/purge_caches.php` |

> **Atenção:** `$CFG->phpunit_prefix` e `$CFG->phpunit_dataroot` apontam para um banco e um diretório
> separados e **não** devem existir em produção. Este repositório não os distribui.

## 3. Como executar

```bash
# 1. dependências de desenvolvimento (uma vez)
composer install

# 2. inicializar o ambiente de teste (uma vez por instalação)
php admin/tool/phpunit/cli/init.php

# 3. gerar o phpunit.xml com a suíte do plugin (uma vez por instalação)
php admin/tool/phpunit/cli/util.php --buildconfig

# 4. executar a suíte do plugin
vendor/bin/phpunit --testsuite local_studenttutor_testsuite

# 4b. ou apenas um arquivo / um teste
vendor/bin/phpunit local/studenttutor/tests/assignment_manager_test.php
vendor/bin/phpunit --filter test_detail_queries_return_all_name_fields local/studenttutor/tests

# 5. descartar o ambiente de teste quando não for mais necessário
php admin/tool/phpunit/cli/util.php --purge
```

Em `docker` (ambiente usado no desenvolvimento):

```bash
docker exec <container-php> sh -c 'cd /var/www/html && vendor/bin/phpunit --no-coverage \
  --testsuite local_studenttutor_testsuite'
```

## 4. Convenções para novos testes

1. Cabeçalho GPL e bloco de identidade (`@package`, `@author`, `@copyright`, `@license`).
2. `namespace local_studenttutor;` (ou `local_studenttutor\privacy` para testes de privacidade).
3. `defined('MOODLE_INTERNAL') || die();` e, quando necessário, `global $CFG;` antes de `require_once`.
4. Classe estendendo `\advanced_testcase` (ou `\core_privacy\tests\provider_testcase`) com
   **`@covers`** apontando para a classe sob teste (`@coversNothing` quando o teste verifica dados ou
   arquivos).
5. `$this->resetAfterTest()` e criação de dados pelo gerador do plugin
   (`$this->getDataGenerator()->get_plugin_generator('local_studenttutor')`).
6. Nunca usar dados reais: os testes geram usuários, cursos e registros sintéticos.
7. A suíte **falha** quando o código emite `debugging()` inesperado — é assim que as regressões de
   campo de nome e de string ausente foram detectadas. Ao esperar um aviso de depuração, use
   `assertDebuggingCalled()`.

## 5. Gerador de dados de teste

`tests/generator/lib.php` oferece:

| Método | Uso |
|---|---|
| `create_assignment($studentid, $tutorid, $courseid, $status)` | cria atribuição (cria usuários/curso aleatórios se não informados) |
| `create_history_entry($studentid, $tutorid, $activitytype, $description, $courseid, $createdby, $activitydate)` | cria registro de histórico (garante o tipo de atividade) |
| `create_activity_type($name, $shortname)` | cria tipo de atividade |

## 6. Testes que dependem de outros plugins

O `tests/plugin_integrity_test.php` valida strings de **outros componentes** usadas pelo plugin e
requer que o site de teste tenha os componentes referenciados instalados (todos do núcleo do Moodle).

## 7. Integração contínua (sugestão)

```bash
# estilo
vendor/bin/phpcs --standard=moodle --extensions=php local/studenttutor

# testes
vendor/bin/phpunit --testsuite local_studenttutor_testsuite
```

Ver [`PADROES_DE_CODIGO.md`](PADROES_DE_CODIGO.md) para o comando de instalação do verificador de
estilo e para os desvios aceitos.
