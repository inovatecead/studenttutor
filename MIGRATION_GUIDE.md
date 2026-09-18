# StudentTutor - Guia de Migração para Produção

> ⚠️ **AVISO (2026-09-18) — scripts de migração removidos.**
> Os scripts `fix_database.php`, `fix_database_cli.php` e `db/fix_activity_date.php`
> foram **removidos** por falhas de segurança (execução sem autenticação e/ou sem
> sesskey, DDL bruto fora da API XMLDB). A migração oficial passa a ser o caminho
> padrão do Moodle: **Administração do site → Notificações**, que executa
> `db/upgrade.php`.
> As seções "Scripts de Migração" e "Verificação" abaixo estão **desatualizadas** e
> serão reescritas na etapa de documentação (Eixo 8).

## Visão Geral
Este guia contém as instruções para migrar o plugin StudentTutor para produção, incluindo a adição do campo `activity_date` e outras melhorias implementadas.

## Alterações Implementadas

### 1. Campo activity_date
- **Novo campo**: `activity_date` (BIGINT) na tabela `local_studenttutor_history`
- **Finalidade**: Armazenar a data específica da atividade (diferente de `timecreated`)
- **Compatibilidade**: Plugin funciona com ou sem o campo (fallback automático)

### 2. Tipos de Atividades Dinâmicos
- **Nova tabela**: `local_studenttutor_activity_types`
- **Finalidade**: Gerenciar tipos de atividades dinamicamente
- **Tipos padrão**: Orientação, Atendimento, Monitoria, Reforço, Apoio Técnico, Mentoria, Workshop

### 3. Remoção do Campo title
- **Campo removido**: `title` da tabela `local_studenttutor_history`
- **Motivo**: Simplificação do formulário conforme solicitado

### 4. Traduções
- **Idioma**: Todas as strings traduzidas para português
- **Arquivo**: `lang/en/local_studenttutor.php`

## Scripts de Migração

### Opção 1: Script Web (Recomendado)
```
URL: http://seudominio.com/local/studenttutor/fix_database.php
```
- Acesse como administrador
- Interface amigável com feedback visual
- Execução segura com verificações

### Opção 2: Script CLI (Alternativo)
```bash
cd /caminho/para/moodle/local/studenttutor
php fix_database_cli.php
```
- Execução via linha de comando
- Útil se houver problemas com acesso web
- Feedback detalhado no terminal

## Passos para Migração

### 1. Backup
```bash
# Backup do banco de dados
mysqldump -u usuario -p nome_do_banco > backup_antes_migracao.sql

# Backup dos arquivos
tar -czf backup_studenttutor.tar.gz /caminho/para/moodle/local/studenttutor/
```

### 2. Upload dos Arquivos
- Substitua todos os arquivos do plugin StudentTutor
- Mantenha as permissões adequadas (755 para diretórios, 644 para arquivos)

### 3. Executar Migração
**Opção A - Via Web:**
1. Acesse como administrador
2. Vá para: `http://seudominio.com/local/studenttutor/fix_database.php`
3. Execute o script
4. Verifique as mensagens de sucesso

**Opção B - Via CLI:**
```bash
cd /var/www/html/moodle/local/studenttutor
php fix_database_cli.php
```

### 4. Verificação
1. Acesse o StudentTutor
2. Teste a adição de novos registros
3. Teste a edição de registros existentes
4. Verifique se o campo "Data da Atividade" aparece nos formulários

## Resolução de Problemas

### Erro: "No direct script access allowed"
- Use o script CLI em vez do web
- Verifique se está logado como administrador

### Erro de Permissão de Banco
```sql
-- Execute como admin do banco:
GRANT ALTER ON database_name.* TO 'moodle_user'@'localhost';
FLUSH PRIVILEGES;
```

### Campo não aparece nos formulários
- Verifique se o script foi executado com sucesso
- Execute: `php fix_database_cli.php` para verificar status
- Limpe o cache do Moodle: Site administration > Development > Purge caches

### Registros antigos sem data
- Os registros antigos receberão automaticamente a data de criação (`timecreated`)
- Isso é feito automaticamente pelo script de migração

## Verificações Pós-Migração

### 1. Estrutura do Banco
```sql
-- Verificar se as tabelas existem:
SHOW TABLES LIKE '%studenttutor%';

-- Verificar campos da tabela de histórico:
DESCRIBE mdl_local_studenttutor_history;

-- Verificar tipos de atividades:
SELECT * FROM mdl_local_studenttutor_activity_types;
```

### 2. Funcionalidades
- [ ] Adicionar novo histórico
- [ ] Editar histórico existente
- [ ] Visualizar lista de histórico
- [ ] Campo "Data da Atividade" aparece
- [ ] Tipos de atividades funcionam
- [ ] Traduções em português

### 3. Performance
- [ ] Queries executam rapidamente
- [ ] Interface responsiva
- [ ] Sem erros no log do Moodle

## Rollback (Se Necessário)

### 1. Restaurar Backup
```bash
# Restaurar banco
mysql -u usuario -p nome_do_banco < backup_antes_migracao.sql

# Restaurar arquivos
rm -rf /caminho/para/moodle/local/studenttutor/
tar -xzf backup_studenttutor.tar.gz
```

### 2. Limpar Cache
- Site administration > Development > Purge caches

## Suporte

### Logs Úteis
- **Moodle**: `moodledata/temp/backup.log`
- **Apache**: `/var/log/apache2/error.log`
- **MySQL**: `/var/log/mysql/error.log`

### Comandos de Diagnóstico
```bash
# Verificar permissões
ls -la /var/www/html/moodle/local/studenttutor/

# Verificar logs do PHP
tail -f /var/log/apache2/error.log

# Verificar conexão com banco
php -r "require_once('/var/www/html/moodle/config.php'); echo 'DB OK: ' . $DB->get_dbfamily();"
```

## Notas Importantes

1. **Compatibilidade**: O plugin funciona mesmo sem o campo `activity_date`
2. **Segurança**: Scripts verificam permissões de administrador
3. **Performance**: Queries otimizadas com fallback automático
4. **Manutenibilidade**: Código documentado e estruturado

## Versão
- **Plugin Version**: 2024011902
- **Requires Moodle**: 4.0+
- **Data da Migração**: [DATA_DA_EXECUÇÃO]
