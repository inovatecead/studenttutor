# Instalação — Nexo Tutoria Acadêmica (`local_studenttutor`)

## 1. Requisitos

| Item | Valor |
|---|---|
| Moodle | 4.0 ou superior (`$plugin->requires = 2022041900`). Desenvolvido e testado em **4.3.5** (`2023100905.04`); faixa declarada `supported = [400, 405]` |
| PHP | 8.0 ou superior (exigência do Moodle 4.x); testado em PHP 8.2.31 |
| Banco de dados | MariaDB/MySQL, PostgreSQL ou SQL Server — o plugin usa exclusivamente a API XMLDB |
| Permissões de arquivo | a pasta do plugin precisa ser legível pelo usuário do servidor web (em instalações padrão, `www-data`) |
| Papel de tutor | um papel de curso que identifique o tutor (por padrão `tutortematico`) |

Não há dependências externas, bibliotecas de terceiros, serviços web de terceiros nem extensões PHP
adicionais. Ver [`DEPENDENCIES.md`](DEPENDENCIES.md).

## 2. Instalação

### 2.1 Copiar os arquivos

```bash
# a partir da raiz da instalação Moodle
cp -r studenttutor/ local/
# resultado esperado: local/studenttutor/version.php
```

### 2.2 Executar a instalação pelo Moodle

1. Acesse **Administração do site → Notificações**.
2. O Moodle detecta o plugin, executa `db/install.xml` (criação das tabelas) e `db/upgrade.php`
   (tipos de atividade padrão) e registra as 11 capabilities e as 4 funções de web service.
3. Alternativa por linha de comando:

```bash
php admin/cli/upgrade.php --non-interactive
```

### 2.3 **Limpar os caches (obrigatório)**

**Administração do site → Desenvolvimento → Limpar caches** ou:

```bash
php admin/cli/purge_caches.php
```

> **Por que é obrigatório:** as classes do plugin (`classes/`) são carregadas por *classmap*
> cacheado. Sem a limpeza, páginas que usam as classes de formulário e os managers podem falhar com
> `Class not found` logo após a atualização dos arquivos.

### 2.4 Configurações

Em **Administração do site → Plugins → Plugins locais → Nexo Tutoria Acadêmica**:

| Configuração | Padrão | Descrição |
|---|---|---|
| Plugin habilitado | Sim | Liga/desliga os recursos do plugin |
| Máximo de atribuições por tutor | 10 | Limite de estudantes por tutor |
| Papel de tutor | `tutortematico` | Atalho (*shortname*) do papel que identifica o tutor no curso |
| Papéis adicionais de tutor | (vazio) | Lista separada por vírgula de outros papéis aceitos como tutor |

O papel indicado precisa **existir** na instalação. Se o ambiente usa outro atalho (por exemplo
`tutor`), ajuste a configuração — ela é lida em tempo de execução.

Também fica disponível, no mesmo menu, a página **Tipos de atividade**, para administrar nome,
atalho, ícone, cor, ordem e disponibilidade dos tipos de atividade de tutoria.

### 2.5 Permissões

As capabilities são concedidas por papel do Moodle (Administração do site → Usuários → Permissões →
Definir papéis). Os arquétipos padrão são aplicados automaticamente na instalação; ajuste conforme a
política da instituição. Consulte [`PERMISSIONS.md`](PERMISSIONS.md).

## 3. Verificação pós-instalação

Execute a lista abaixo em um ambiente de homologação antes de liberar em produção:

| # | Verificação | Como conferir |
|---|---|---|
| 1 | As 3 tabelas existem | Administração do site → Desenvolvimento → XMLDB: `local_studenttutor_assign`, `local_studenttutor_history`, `local_studenttutor_activity_types` |
| 2 | 11 tipos de atividade cadastrados | Página **Tipos de atividade** do plugin |
| 3 | 11 capabilities registradas | Administração do site → Usuários → Permissões → Definir papéis → filtro `local/studenttutor` |
| 4 | 4 funções de web service e o serviço `studenttutor_api` | Administração do site → Servidor → Serviços externos → Serviços |
| 5 | Menu "Meus Alunos" aparece para o tutor | Entrar como tutor em um curso com estudante atribuído |
| 6 | Registro de atividade funciona | Área do tutor → adicionar atividade de tutoria |
| 7 | Relatórios abrem com dados | Página de relatórios, com filtro por tutor/estudante |
| 8 | Exportação e exclusão de dados do titular | Administração do site → Usuários → Privacidade e políticas → Exportar dados de um usuário |
| 9 | Sem mensagens de erro/aviso no log | `debugging` habilitado apenas em homologação; nenhum aviso novo esperado |

## 4. Problemas comuns

| Sintoma | Causa provável | Solução |
|---|---|---|
| `Class not found` em `local_studenttutor\form\...` após copiar arquivos | *classmap* desatualizado | Executar limpeza de caches (passo 2.3) |
| Menu "Meus Alunos" não aparece | usuário não tem o papel de tutor no curso, ou o atalho do papel difere do configurado | Conferir a configuração **Papel de tutor** e a atribuição de papel no curso |
| Nenhum estudante listado ao registrar atividade | não existe atribuição ativa do tutor naquele curso | Criar a atribuição na página de atribuições |
| Página de atribuições vazia para o administrador | filtros ativos ou ausência de atribuições | Limpar filtros; conferir se há atribuições cadastradas |
| Ajuda do campo "Curso" vazia | pacote de idioma incompleto (ambiente com idioma não suportado) | O plugin traz `en` e `pt_br`; usar um deles ou complementar o pacote institucional |

## 5. Desinstalação

A desinstalação remove capabilities e configurações. **As tabelas do plugin não são apagadas
automaticamente** (os dados de tutoria são registro acadêmico). Para remover os dados, faça-o
conscientemente, com backup e conforme a política de retenção da instituição:

```sql
-- executar apenas com autorização e backup
DROP TABLE mdl_local_studenttutor_history;
DROP TABLE mdl_local_studenttutor_assign;
DROP TABLE mdl_local_studenttutor_activity_types;
```

Antes de remover, atenda eventuais solicitações de titular pela ferramenta de privacidade do Moodle
(ver [`../PRIVACY.md`](../PRIVACY.md)).
