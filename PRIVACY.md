# Privacidade e proteção de dados — Nexo Tutoria Acadêmica

Documento de transparência sobre o tratamento de dados pessoais realizado pelo plugin
`local_studenttutor` (Nexo Tutoria Acadêmica), em conformidade com a Lei nº 13.709/2018 (LGPD) e com
as diretrizes de privacidade do Moodle.

## Papéis no tratamento

| Papel | Responsável |
|---|---|
| Controlador | Universidade Federal de Mato Grosso (UFMT) |
| Operador / mantenedor técnico | INOVATEC/UFMT |
| Titular dos dados | Estudante e tutor (servidores e estudantes da UFMT) |
| Encarregado (DPO) | Designado institucionalmente pela UFMT |

O plugin é uma ferramenta de apoio à tutoria acadêmica; não define finalidade própria nem realiza
tratamento para terceiros.

## Dados tratados

O plugin trata exclusivamente dados vinculados à atividade de tutoria:

| Tabela | Dados | Finalidade |
|---|---|---|
| `local_studenttutor_assign` | identificador do estudante, identificador do tutor, curso, quem atribuiu, data da atribuição/modificação e situação do vínculo | registrar e controlar quem acompanha cada estudante |
| `local_studenttutor_history` | identificador do estudante, do tutor e de quem registrou, curso, tipo de atividade, descrição da interação, data da atividade e datas de criação/modificação | manter o histórico pedagógico das interações de tutoria |
| `local_studenttutor_activity_types` | nome, atalho, descrição, ícone, cor e ordem | configurar os tipos de atividade (não contém dados pessoais) |

**Não são coletados nem armazenados pelo plugin**: e-mail, telefone, endereço, IP, dados de
navegação, notas, frequência, biometria, gênero, raça, religião, opinião política, dados de saúde ou
qualquer dado pessoal sensível. Também não há envio de dados a serviços externos, telemetria ou
rastreamento.

## Base legal e finalidade

- Execução de política pública de apoio à permanência e ao acompanhamento acadêmico (art. 7º, III) e,
  quando aplicável, execução de contrato/convênio educacional (art. 7º, V).
- Os dados são utilizados somente para organizar a tutoria, registrar o acompanhamento pedagógico,
  produzir relatórios institucionais agregados e prestar contas de indicadores de apoio estudantil.

## Minimização e qualidade

- Só são gravados os campos necessários à finalidade descrita.
- Campos de dado pessoal sem uso foram removidos das consultas internas (por exemplo, e-mail), de
  modo que o dado pessoal não transite sem finalidade.
- Os nomes exibidos usam a formatação de nome configurada no próprio Moodle (nome fonético, nome do
  meio, nome alternativo), respeitando a preferência registrada pelo usuário.
- Descrições de atividade são registradas em campo de texto simples e formatadas como texto puro na
  exibição, evitando execução de conteúdo.

## Compartilhamento

- Não há compartilhamento com terceiros, nem transferência internacional de dados.
- Os dados ficam no banco de dados da instalação Moodle da UFMT, sob as políticas de acesso,
  backup e retenção da instituição.
- O acesso é limitado por capability: ver [`docs/PERMISSIONS.md`](docs/PERMISSIONS.md).

## Retenção e eliminação

- Os dados permanecem enquanto durar o vínculo de tutoria e enquanto forem necessários ao registro
  acadêmico/auditoria institucional.
- A eliminação de dados de um titular pode ser solicitada a qualquer momento pelo próprio titular ou
  pela autoridade competente e é atendida pela ferramenta de privacidade do Moodle (ver abaixo).
- A exclusão de um usuário no Moodle não remove automaticamente os registros de tutoria — eles são
  tratados pela API de privacidade e pelas políticas de retenção da instituição.

## Direitos do titular — como são atendidos

O plugin implementa a **API de privacidade do Moodle** (`classes/privacy/provider.php`), verificada
por testes automatizados de conformidade:

1. **Declaração de metadados**: as tabelas e os campos de dado pessoal estão declarados ao Moodle
   (Administração do site → Usuários → Privacidade e políticas → Verificação do provedor).
2. **Exportação**: Administração do site → Usuários → Privacidade e políticas → Exportar dados de um
   usuário. O plugin exporta, em "Tutoria → Atribuições" e "Tutoria → Histórico", os vínculos em
   que o titular aparece como estudante, tutor ou autor do registro.
3. **Exclusão**: Administração do site → Usuários → Privacidade e políticas → Excluir dados de um
   usuário. O plugin exclui todas as linhas em que o titular aparece em qualquer dessas posições
   (inclusive em lote, via `core_userlist_provider`).

## Segurança do tratamento

Controle de acesso por capability, proteção contra CSRF (`sesskey`) nas ações de escrita, uso
obrigatório da API de banco com *placeholders*, ausência de exposição de mensagens internas ao
usuário e ausência de log de dado pessoal em produção. Detalhes em [`SECURITY.md`](SECURITY.md).

## Observação sobre os dados de produção

Este repositório contém apenas código-fonte. Nenhum dado pessoal de estudante ou tutor — real ou de
produção — é armazenado, versionado ou distribuído com o plugin. Os testes automatizados usam dados
sintéticos gerados pelo próprio Moodle.

## Contato

Solicitações de titular, dúvidas sobre tratamento de dados ou incidentes devem ser encaminhados ao
Encarregado de Proteção de Dados (DPO) da UFMT e, em aspectos técnicos, à equipe do plugin na
INOVATEC/UFMT.
