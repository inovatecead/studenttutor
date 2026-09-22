# Padrões de código — Nexo Tutoria Acadêmica (`local_studenttutor`)

O código segue o **padrão oficial de código do Moodle**, verificado com a ferramenta oficial do
projeto (`moodlehq/moodle-cs`, que empacota o PHP_CodeSniffer com o *standard* `moodle`).

## 1. Regras aplicadas

| Regra | Como é atendida no plugin |
|---|---|
| Cabeçalho GPL v3+ em todo arquivo PHP | presente em todas as páginas, classes, `db/`, `lang/` e `tests/` |
| Bloco de identidade (`@package`, `@author`, `@copyright`, `@license`) | presente em todos os arquivos |
| Docblock em classes e funções públicas | presente, inclusive nas classes de evento e em `db/upgrade.php` |
| Sintaxe de array curta (`[]`) e vírgula final | aplicada em todos os arquivos |
| Comentários de fim de linha iniciando com maiúscula | aplicada em todos os arquivos |
| Nenhum espaço em branco no fim da linha (inclusive dentro de strings) | aplicada em todos os arquivos |
| Linhas com no máximo 132 caracteres | aplicada em todos os arquivos |
| `defined('MOODLE_INTERNAL') || die();` | presente em páginas, classes com efeitos colaterais, `db/` e `lang/` |
| `@covers` nos testes | presente nas classes de teste (`@coversNothing` quando o teste verifica dados/arquivos) |
| Sem código comentado | bloco comentado remanescente removido |
| Strings de interface sempre por `get_string()` | nenhum texto fixo PT/EN no código (222 chaves, em paridade) |

## 2. Como verificar

```bash
# instalação da ferramenta (uma vez, em máquina de desenvolvimento com internet)
mkdir -p /tmp/moodle-cs && cd /tmp/moodle-cs
composer config --no-plugins allow-plugins.dealerdirect/phpcodesniffer-composer-installer true
composer require --no-interaction moodlehq/moodle-cs

# verificação de estilo, a partir da raiz do Moodle
/tmp/moodle-cs/vendor/bin/phpcs --standard=moodle --extensions=php local/studenttutor

# correção automática do que é corrigível
/tmp/moodle-cs/vendor/bin/phpcbf --standard=moodle --extensions=php local/studenttutor
```

Resumo por categoria (`--report=source`) e detalhe por linha (`--report=csv`) ajudam a triagem.

## 3. Situação atual

| Etapa da versão 1.1.0 | Resultado |
|---|---|
| Situação inicial | 1229 erros + 358 avisos |
| Corrigido automaticamente (`phpcbf`, 38 arquivos) | 736 |
| Corrigido manualmente | 161 (docblocks, `@covers`, linhas longas, comentários, espaços em strings, `@var`, PSR-12, código comentado) |
| **Restante** | **617** |

As 617 ocorrências restantes estão **integralmente** em três regras que o próprio núcleo do Moodle
não cumpre:

| Regra | Ocorrências no plugin | Ocorrências em arquivo do núcleo (`course/lib.php`) |
|---|---|---|
| Nome de variável com underscore (`ValidVariableName`) | 397 | 27 (a correção é marcada como `phpcbf-only` no *standard*, ou seja, nem o time do núcleo a aplica) |
| Comentário de linha sem pontuação final | 208 | 63 |
| `defined('MOODLE_INTERNAL')` em arquivo de classe sem efeitos colaterais | 12 | 4 em `admin/classes/form/purge_caches.php` |

Comparação feita com o próprio Moodle 4.3.5 desta instalação:

```
$ phpcs --standard=moodle course/lib.php
A TOTAL OF 822 ERRORS AND 110 WARNINGS WERE FOUND
```

**Política adotada:** o plugin mantém **zero ocorrências em todas as categorias que o núcleo mantém
limpas** (docblock, tamanho de linha, estilo de comentário, espaços em branco, PSR-12, `@covers`,
código comentado, vírgula final, sintaxe curta de array). Nas três categorias acima, o plugin adota o
mesmo estilo do código do Moodle — renomear centenas de variáveis locais e pontuar comentários
históricos criaria divergência em relação ao *standard* praticado pelo projeto, sem ganho funcional.
Código novo deve continuar sem nenhuma ocorrência auto-corrigível.

## 4. Convenções internas do projeto

- **Idioma do código**: identificadores e comentários técnicos em inglês; strings de interface em
  `lang/en` e `lang/pt_br`, sempre em paridade (verificado por teste).
- **Nomenclatura de arquivos**: uma classe por arquivo, nome do arquivo igual ao nome da classe,
  `classes/form/` para formulários, `classes/external/` para web services, `classes/event/` para
  eventos, `classes/privacy/` para o provedor de privacidade.
- **Formulários**: declarados em `classes/form/` com namespace `local_studenttutor\form` — nunca
  dentro de páginas.
- **Consultas**: sempre com `placeholders` nomeados; jamais concatenar entrada do usuário no SQL.
- **Saída**: `html_writer`, `s()` ou `format_text()` conforme o contexto; strings traduzidas quando
  inseridas em HTML/JS passam por `s()` ou `json_encode()`.
- **Depuração**: `debugging()` apenas com `DEBUG_DEVELOPER` e sem dado pessoal; nenhuma mensagem
  interna (`getMessage()`) exibida ao usuário.
- **Testes**: qualquer nova funcionalidade deve vir com teste na suíte
  (ver [`TESTING.md`](TESTING.md)).
