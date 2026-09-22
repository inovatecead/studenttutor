# Manual funcional — Nexo Tutoria Acadêmica

Manual de uso do plugin `local_studenttutor`, voltado à coordenação de tutoria, aos tutores e à
administração do site.

## 1. Conceitos

| Conceito | Significado |
|---|---|
| **Atribuição** | vínculo entre um estudante e um tutor. Pode ser **por curso** ou **global** (vale para todos os cursos do estudante) |
| **Tipo de atividade** | categoria da interação de tutoria (ex.: "Retorno de dúvidas", "Aplicação de prova/atividade"), com nome, atalho, ícone e cor configuráveis |
| **Atividade de tutoria** | registro datado de uma interação entre tutor e estudante, com tipo e descrição |
| **Tutor** | usuário com o papel definido nas configurações do plugin (por padrão `tutortematico`) atribuído no curso |
| **Atribuição ativa** | vínculo em situação `active`; atribuições `inactive` não são consideradas na área do tutor nem nas listagens detalhadas de histórico |

## 2. Perfis de uso

| Perfil | Precisa de | Onde trabalha |
|---|---|---|
| Coordenação de tutoria / administração | `local/studenttutor:viewassignments` e `manageassignments` | página de atribuições, tipos de atividade e relatórios |
| Tutor | papel de tutor no curso (`managehistory` para editar registros) | menu "Meus Alunos" dentro do curso |
| Estudante | — | o plugin não expõe interface ao estudante |

## 3. Administração do site

### 3.1 Configurações

**Administração do site → Plugins → Plugins locais → Nexo Tutoria Acadêmica**

| Configuração | Para que serve |
|---|---|
| Plugin habilitado | ligar/desligar os recursos do plugin |
| Máximo de atribuições por tutor | limite orientativo de estudantes por tutor |
| Papel de tutor | atalho do papel que identifica o tutor no curso (padrão `tutortematico`) |
| Papéis adicionais de tutor | outros papéis aceitos como tutor, separados por vírgula |

### 3.2 Tipos de atividade

**Administração do site → Plugins → Plugins locais → Nexo Tutoria Acadêmica → Tipos de atividade**

- **Criar**: informe nome, atalho (`shortname`), descrição, ícone (FontAwesome), cor, ordem e
  disponibilidade.
- **Editar**: altere nome, ícone, cor, ordem e situação.
- **Ativar/desativar**: tipos inativos deixam de aparecer para seleção, mas os registros históricos
  permanecem.
- **Reordenar**: a ordem define a sequência nos seletores e relatórios.

> **Atenção**: o atalho é o elo com o histórico já registrado. Alterar o atalho de um tipo em uso
> desvincula os registros antigos daquele tipo — por isso não é editável depois de utilizado.

Tipos cadastrados na instalação da UFMT: Convocatória para reunião; Retorno de dúvidas; Reunião
virtual (webconferência); Reunião presencial no polo; Reunião com grupo do Seminário Integrador
(online e presencial); Apresentação de Seminário Temático; Aplicação de Prova/Atividade; Atividade de
nivelamento em áreas temáticas com dificuldade; Diagnóstico de dificuldade do estudante; Outros.

### 3.3 Capabilities

As permissões são concedidas por papel do Moodle. A lista completa, com os arquétipos padrão e a
capability exigida por cada tela, está em [`PERMISSIONS.md`](PERMISSIONS.md).

### 3.4 Privacidade e LGPD

- **Exportar dados de um titular**: Administração do site → Usuários → Privacidade e políticas →
  Exportar dados de um usuário. O plugin exporta "Tutoria → Atribuições" e "Tutoria → Histórico".
- **Excluir dados de um titular**: mesma área, opção de exclusão. O plugin remove todas as linhas em
  que o titular apareça como estudante, tutor ou autor.
- Detalhes do tratamento de dados: [`../PRIVACY.md`](../PRIVACY.md).

## 4. Página de atribuições (`index.php`)

Acesso: menu do plugin ou URL `/local/studenttutor/index.php`.

- **Filtros**: tutor, estudante e curso (com autocomplete). O botão *Limpar* remove os filtros.
- **Tabela**: tutor, estudante, curso, data de atribuição, situação e ações.
- **Criar atribuição**: botão *Adicionar Atribuição* → página de atribuição.
- **Editar**: botão por linha (altera curso, situação ou o estudante da atribuição).
- **Excluir**: botão por linha, com confirmação e `sesskey`.
- **Paginação**: quando há mais registros do que o limite por página.
- **Escopo do tutor**: um tutor autenticado vê apenas as atribuições em que ele é o tutor.

## 5. Criar ou editar atribuição (`assign.php`)

1. Escolha o **curso** (ou "Todos os cursos", para atribuição global).
2. Escolha o **tutor** (lista de usuários com os papéis de tutor configurados).
3. Selecione um ou mais **estudantes** (lista carregada conforme o curso escolhido; mostra grupos e
   quantidade de tutores de cada estudante).
4. Salve. O sistema valida duplicidade (mesmo estudante + tutor + curso) e rejeita auto-atribuição.
5. Em modo de edição é possível alterar curso, situação (`Ativo`/`Inativo`) e o estudante do vínculo.

Mensagens de retorno informam quantas atribuições foram criadas, quantas já existiam e quantas
falharam.

## 6. Área do tutor (menu "Meus Alunos")

Disponível na navegação do curso para quem tem o papel de tutor.

### 6.1 Lista de estudantes (`course_view.php`)

Mostra os estudantes atribuídos ao tutor no curso (incluindo atribuições globais), com data de
atribuição, último contato registrado e atalhos para o histórico de cada estudante.

### 6.2 Registrar atividade (`add_history.php`)

1. Escolha o **estudante** (apenas os atribuídos ao tutor no curso).
2. Escolha o **tipo de atividade**.
3. Informe a **data da atividade** (padrão: hoje).
4. Descreva a atividade.
5. Salve. A tela exibe um aviso de minimização de dados: registre apenas o necessário sobre a
   interação pedagógica.

### 6.3 Histórico por estudante (`student_history.php`)

Lista cronológica das atividades registradas para o estudante, com tipo, data, descrição e autor.
Permite editar (`edit_history.php`) e excluir registros, conforme as capabilities do usuário.

## 7. Relatórios (`reports.php`)

- **Filtros**: tutor, estudante, curso, tipo de atividade e período (data inicial e final).
- **Resumo**: total de registros, primeiro contato, último contato e distribuição por tipo de
  atividade do filtro aplicado.
- **Tabela**: data, tutor, estudante, curso, tipo e descrição.
- **Exportação**: use os recursos de exportação do próprio navegador/Moodle a partir da tabela;
  o plugin não gera arquivo de exportação próprio.

## 8. Perguntas frequentes

| Pergunta | Resposta |
|---|---|
| Um estudante pode ter mais de um tutor? | Sim, inclusive em cursos diferentes. O sistema impede apenas a duplicidade idêntica (mesmo estudante, tutor e curso) |
| O que é uma atribuição global? | Vínculo com `courseid = 0`: o tutor acompanha o estudante em todos os cursos |
| Um tutor pode acompanhar um estudante de outro curso? | Sim, por meio de atribuição global, criada pela coordenação |
| Por que um registro antigo não aparece no relatório detalhado? | O relatório detalhado exige que o par estudante–tutor tenha atribuição **ativa** |
| Excluí a atribuição, o histórico foi apagado? | Não. O histórico é preservado; a exclusão da atribuição apenas interrompe o acompanhamento |
| Posso mudar o atalho de um tipo de atividade? | Não para tipos já utilizados: o atalho é a ligação com o histórico existente |
| O estudante vê as anotações de tutoria? | Não. Não há interface do plugin para o estudante |
