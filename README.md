# Student-Tutor Assignment Plugin

Um plugin robusto para Moodle que permite gerenciar atribuições entre tutores e estudantes, incluindo registro detalhado de atividades de tutoria e geração de relatórios abrangentes.

## 📋 Análise do Plugin

### Funcionalidades Principais

- **Gestão de Atribuições**: 
  - Atribuir tutores a estudantes por curso específico ou atribuições globais
  - Sistema de múltiplas atribuições com suporte a autocomplete
  - Validação de duplicatas e prevenção de auto-atribuição
  
- **Histórico de Atividades**: 
  - Registro de diferentes tipos de atividades (reuniões, emails, feedback, avaliações, orientações)
  - Sistema de comentários e descrições detalhadas
  - Timestamps automáticos e rastreamento de criador
  
- **Relatórios e Analytics**: 
  - Visualização de histórico com filtros avançados por data, tipo de atividade e usuários
  - Estatísticas rápidas (total de interações, primeiro/último contato, tipos de interação)
  - Exportação de dados para análise externa
  
- **API REST Completa**: 
  - Endpoints para criação, leitura e gestão de atribuições
  - Integração com sistemas externos via web services
  - Autenticação e autorização baseada em capabilities do Moodle
  
- **Integração Nativa com Moodle**: 
  - Menu "Meus Alunos" integrado na navegação de cursos para tutores
  - Sistema de permissões baseado em roles (teacher, editingteacher, manager)
  - Suporte a contextos de curso e sistema
  
- **Interface Multilíngue**: 
  - Suporte completo a português (pt_br) e inglês (en)
  - Strings localizáveis para fácil tradução

### Arquitetura Técnica

- **Padrão MVC**: Separação clara entre lógica de negócio (managers), apresentação (páginas) e dados
- **Event System**: Eventos para criação e atualização de atribuições
- **Database Design**: Duas tabelas principais com relacionamentos bem definidos
- **Security First**: Validações de input, verificações de permissão e sanitização de dados

## 🗂️ Estrutura de Arquivos

```
local/studenttutor/
├── 📄 Core Pages
│   ├── index.php               # Dashboard principal - listagem e filtros de atribuições
│   ├── assign.php              # Formulário de criação de atribuições (múltiplas)
│   ├── course_view.php         # Visualização específica por curso para tutores
│   ├── student_history.php     # Histórico detalhado de um estudante específico
│   ├── add_history.php         # Formulário para adicionar atividades de tutoria
│   ├── edit_history.php        # Edição de registros de histórico
│   └── reports.php             # Relatórios e análises com filtros avançados
│
├── ⚙️ Configuration
│   ├── lib.php                 # Hooks de navegação e integração com Moodle
│   ├── settings.php            # Configurações administrativas
│   └── version.php             # Metadados da versão (v1.1.0)
│
├── 🏗️ Classes (Business Logic)
│   ├── assignment_form.php     # Formulário Moodle para atribuições
│   ├── assignment_manager.php  # Lógica de negócio para atribuições
│   ├── history_manager.php     # Gestão de histórico de atividades
│   ├── event/
│   │   ├── assignment_created.php  # Evento de criação
│   │   └── assignment_updated.php  # Evento de atualização
│   └── external/               # Web Services API
│       ├── create_assignment.php   # API para criar atribuições
│       ├── get_assignments.php     # API para listar atribuições
│       ├── get_history.php         # API para histórico
│       └── add_history.php         # API para adicionar atividades
│
├── 🗄️ Database
│   ├── install.xml             # Schema das tabelas (assign + history)
│   ├── upgrade.php             # Scripts de migração
│   ├── access.php              # Definição de capabilities
│   └── services.php            # Configuração de web services
│
├── 🌐 Internationalization
│   ├── en/local_studenttutor.php   # Strings em inglês (245+ strings)
│   └── pt_br/local_studenttutor.php # Strings em português
│
└── 🎨 Assets
    └── styles/multiselect.css      # Estilos para componentes
```

### Database Schema

**local_studenttutor_assign**: Armazena atribuições tutor-estudante
- Suporte a atribuições globais (courseid=0) e específicas por curso
- Status: active, inactive, completed
- Timestamps: timeassigned, timecreated, timemodified
- Foreign keys: studentid, tutorid, courseid, assignedby

**local_studenttutor_history**: Log de atividades de tutoria
- Tipos: meeting, email, feedback, assessment, guidance, other
- Campos: description, activitytype, timecreated, timemodified
- Relacionamento: studentid + tutorid + courseid

## 🚀 Instalação

1. **Deploy do Plugin**
   ```bash
   # Copie o plugin para o diretório correto
   cp -r studenttutor/ /path/to/moodle/local/
   ```

2. **Instalação via Interface Web**
   - Acesse: Administração > Notificações
   - Execute o processo de instalação automática
   - Verifique se as tabelas foram criadas corretamente

3. **Configuração de Permissões**
   - Acesse: Administração > Usuários > Permissões > Definir roles
   - Configure as capabilities necessárias por role

## 🔐 Sistema de Permissões

| Capability | Descrição | Roles Padrão |
|------------|-----------|--------------|
| `local/studenttutor:viewassignments` | Visualizar atribuições | Teacher, Manager |
| `local/studenttutor:manageassignments` | Criar/editar/excluir atribuições | EditingTeacher, Manager |
| `local/studenttutor:viewhistory` | Visualizar histórico de atividades | Teacher, Manager |
| `local/studenttutor:managehistory` | Gerenciar histórico de atividades | Teacher, Manager |
| `local/studenttutor:view_own_students` | Ver apenas estudantes próprios | Teacher |
| `local/studenttutor:assign_students` | Atribuir estudantes | EditingTeacher, Manager |

## 🔧 Configurações do Sistema

O plugin oferece configurações administrativas para:
- Número máximo de atribuições por tutor
- Habilitação/desabilitação de funcionalidades
- Configurações de notificações e eventos

## 📊 Fluxo de Uso

### Para Administradores:
1. **index.php**: Dashboard com todas as atribuições e filtros avançados
2. **assign.php**: Criar múltiplas atribuições simultaneamente
3. **reports.php**: Relatórios estatísticos e exportação de dados

### Para Tutores:
1. **course_view.php**: Acessível via menu "Meus Alunos" no curso
2. **student_history.php**: Histórico detalhado de cada estudante
3. **add_history.php**: Registrar novas atividades de tutoria

### Integração com Navegação:
- Menu automático "Meus Alunos" aparece na navegação do curso para tutores
- Links contextuais para perfis de estudantes
- Breadcrumbs e navegação intuitiva

## 🌐 API REST

O plugin fornece uma API REST completa para integração com sistemas externos:

### Endpoints Disponíveis:

| Endpoint | Método | Descrição | Capabilities Necessárias |
|----------|---------|-----------|--------------------------|
| `local_studenttutor_create_assignment` | POST | Criar nova atribuição | manageassignments |
| `local_studenttutor_get_assignments` | GET | Listar atribuições com filtros | viewassignments |
| `local_studenttutor_get_history` | GET | Obter histórico de atividades | viewhistory |
| `local_studenttutor_add_history` | POST | Adicionar atividade ao histórico | managehistory |

### Exemplo de Uso:
```php
// Criar atribuição via web service
$assignment = external_api::call_external_function(
    'local_studenttutor_create_assignment',
    [
        'tutorid' => 123,
        'studentid' => 456,
        'courseid' => 789
    ]
);
```

## 📈 Funcionalidades Avançadas

### Sistema de Filtros:
- **Por Usuário**: Tutores, estudantes específicos
- **Por Curso**: Atribuições globais ou específicas por curso
- **Por Data**: Períodos personalizados para histórico
- **Por Tipo de Atividade**: Meeting, email, feedback, assessment, etc.

### Estatísticas e Analytics:
- Total de estudantes por tutor
- Frequência de interações
- Primeiro e último contato
- Distribuição por tipo de atividade
- Estudantes contatados recentemente (últimos 7 dias)

### Recursos de UX:
- Autocomplete para seleção de usuários
- Confirmações antes de exclusões
- Mensagens de feedback contextuais
- Links rápidos para perfis e mensagens
- Interface responsiva

## 🔄 Estados e Workflow

### Status de Atribuições:
- **Active**: Atribuição ativa e funcional
- **Inactive**: Temporariamente desabilitada
- **Completed**: Atribuição finalizada

### Tipos de Atividades:
- **Meeting**: Reuniões presenciais ou virtuais
- **Email**: Comunicações por email
- **Feedback**: Feedback sobre trabalhos/avaliações
- **Assessment**: Revisão de avaliações
- **Guidance**: Orientação acadêmica geral
- **Other**: Outras atividades personalizadas

## 🛠️ Aspectos Técnicos

### Padrões Implementados:
- **PSR-4**: Autoloading de classes
- **Moodle Coding Standards**: Seguindo padrões oficiais
- **Event Driven**: Sistema de eventos para auditoria
- **Security by Design**: Validações e sanitização rigorosas

### Performance:
- Queries otimizadas com JOINs eficientes
- Paginação em listagens grandes
- Índices estratégicos nas tabelas
- Cache de consultas frequentes

### Compatibilidade:
- **Moodle**: 3.9+ (requer 2020061500)
- **PHP**: 7.4+
- **MySQL/PostgreSQL**: Suporte completo
- **Browsers**: Modernos com JavaScript habilitado

## 🐛 Desenvolvimento e Debug

### Logs e Debugging:
O plugin implementa debugging detalhado em pontos críticos:
```php
debugging("get_tutor_students_including_global: Found " . count($results) . " assignments", DEBUG_DEVELOPER);
```

### Tratamento de Erros:
- Validações rigorosas de entrada
- Try-catch em operações críticas
- Mensagens de erro localizadas
- Fallbacks para situações de erro

### Extensibilidade:
- Managers bem estruturados para fácil extensão
- Event system para hooks customizados
- Constantes bem definidas para status e tipos
- Interfaces claras entre componentes

## 📚 Suporte e Documentação

### Recursos Disponíveis:
- **Código Autodocumentado**: Comentários PHPDoc em todas as classes
- **Language Strings**: Mais de 245 strings traduzíveis
- **Database Schema**: Documentação inline no install.xml
- **Examples**: Código de exemplo em comentários

### Para Desenvolvedores:
- Arquitetura modular facilita manutenção
- Separation of concerns bem implementada
- Testes de validação em formulários
- APIs consistentes entre managers

### Troubleshooting Comum:
1. **Permissions**: Verificar capabilities nos roles
2. **Database**: Confirmar instalação das tabelas
3. **Cache**: Limpar cache após modificações
4. **Logs**: Verificar logs do Moodle para erros

## 📄 Licença e Créditos

**Licença**: GNU GPL v3 or later  
**Versão**: 1.1.0 (2025063010)  
**Compatibilidade**: Moodle 3.9+  
**Arquitetura**: Plugin Local para Moodle  

### Características da Versão Atual:
- ✅ Sistema completo de atribuições
- ✅ Histórico de atividades robusto  
- ✅ API REST funcional
- ✅ Interface multilíngue
- ✅ Integração nativa com Moodle
- ⚠️ Edição de atribuições temporariamente desabilitada
- 🔄 Sistema de eventos implementado

---

**Desenvolvido para facilitar o acompanhamento pedagógico e fortalecer a relação tutor-estudante no ambiente Moodle.**
