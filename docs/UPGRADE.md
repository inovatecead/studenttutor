# Atualização — Nexo Tutoria Acadêmica (`local_studenttutor`)

## 1. Antes de atualizar

1. **Backup completo**: banco de dados **e** `dataroot` (o `dataroot` é necessário para rollback
   seguro, embora o plugin não grave arquivos próprios).
2. **Janela de manutenção**: a migração executa alterações de esquema (`db/upgrade.php`).
3. **Homologação primeiro**: aplique a nova versão em cópia do ambiente e execute a lista de
   verificação de [`INSTALL.md`](INSTALL.md#3-verificação-pós-instalação).
4. **Registre a versão de origem**: `$plugin->version` atual do plugin, para saber quais etapas de
   migração serão executadas.

## 2. Procedimento

```bash
# 1. substituir os arquivos (mantendo o diretório)
# 2. executar a migração
php admin/cli/upgrade.php --non-interactive

# 3. limpar caches (obrigatório)
php admin/cli/purge_caches.php
```

Pela interface: **Administração do site → Notificações** (executa a migração) e depois
**Administração do site → Desenvolvimento → Limpar caches**.

> **A limpeza de caches é obrigatória.** As classes são carregadas por *classmap*; sem a limpeza,
> páginas que usam as classes de formulário e os managers podem falhar com `Class not found`.

## 3. O que a migração para 1.1.0 altera

A atualização preserva os dados existentes. **Não há remoção de tabela, coluna, capability, evento ou
endpoint.** As etapas registradas em `db/upgrade.php` são:

| Versão de origem | O que é feito | Observação |
|---|---|---|
| `< 2025062902` | adiciona o campo legado `title` no histórico e preenche valores | etapa histórica, mantida apenas para instalações muito antigas |
| `< 2025062903` | ajusta nomes de campos da tabela de atribuições | etapa histórica |
| `< 2025063001` | garante campos de autoria/data e valores padrão | etapa histórica; usa `timeassigned` como referência |
| `< 2025063013` | garante o campo `title` e valores padrão | etapa histórica |
| `< 2025070102` | cria `local_studenttutor_activity_types` e cadastra os tipos padrão | tabela nova, sem impacto nos dados de tutoria |
| `< 2025070103` | cadastra os tipos de atividade específicos da UFMT (seminário, prova, nivelamento, diagnóstico) | apenas inserção |
| `< 2025070104` | adiciona `activity_date` ao histórico e preenche com `timecreated` | usado a partir desta versão nas telas |

Etapas que citam campos legados (`title`, `timecreated`, `createdby` na tabela de atribuições) são
históricas: elas apenas garantem compatibilidade com instalações criadas por versões anteriores e não
criam essas colunas no esquema atual. O esquema efetivamente mantido está em
[`DATABASE.md`](DATABASE.md).

### Mudança no `$plugin->version`

A versão da versão 1.1.0 é superior a `2025070107`, o que faz o Moodle exigir a execução da migração
(garantindo que instalações antigas recebam as etapas pendentes). Requisitos de plataforma:
`requires = 2022041900` (Moodle 4.0+) e `supported = [400, 405]`.

## 4. Compatibilidade garantida na 1.1.0

| Contrato | Situação |
|---|---|
| Tabelas e colunas de dados de tutoria | inalteradas |
| Capabilities `local/studenttutor:*` | nomes e contextos inalterados (11 declaradas, nenhuma removida) |
| 4 funções de web service e o serviço `studenttutor_api` | nomes, tipos e capabilities inalterados |
| Eventos `assignment_created` e `assignment_updated` | nomes e payload inalterados |
| Campos públicos das respostas de web service | inalterados; campos que referenciavam colunas inexistentes foram corrigidos |
| Formulários e nomes de campos de formulário | inalterados (apenas movidos para `classes/form/`) |

Campos aceitos por compatibilidade e **ignorados** (documentados na própria função): `assignedby` em
`create_assignment` e `title` em `add_history`. O autor do registro é sempre o usuário autenticado que
executa a ação.

## 5. Rollback

1. Restaure os arquivos da versão anterior.
2. Restaure o banco a partir do backup (a migração não é reversível pelo Moodle).
3. Limpe os caches.

Não há caminho de *downgrade* automático: o Moodle recusa executar com `$plugin->version` inferior à
registrada no banco sem restauração do backup.

## 6. Scripts de migração antigos (removidos)

As versões anteriores distribuíam `fix_database.php`, `fix_database_cli.php` e `db/fix_activity_date.php`
para correções pontuais. **Esses scripts foram removidos** por falhas de segurança (execução sem
autenticação e/ou sem `sesskey`, com DDL bruto fora da API XMLDB). A migração oficial passa a ser
exclusivamente o caminho padrão do Moodle: **Administração do site → Notificações**, que executa
`db/upgrade.php`.

O guia antigo, com o histórico dessas etapas, está preservado em
[`historico/MIGRATION_GUIDE-pre-1.1.0.md`](historico/MIGRATION_GUIDE-pre-1.1.0.md).
