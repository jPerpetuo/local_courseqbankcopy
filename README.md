# Cópia independente de bancos de questões

Componente Moodle: `local_courseqbankcopy`.

Plugin local para tornar independentes os bancos de questões copiados por
`Curso > Reutilizar > Importar`, sem editar arquivos do núcleo do Moodle.

## Comportamento

- O modo padrão é **Incluir cópias independentes dos bancos de questões**.
- O padrão e seu bloqueio podem ser administrados em
  `Administração do site > Cursos > Backups > Configurações gerais de importação`.
- Professores comuns seguem o padrão administrativo e não podem alterá-lo.
- Administradores do site podem alterar o modo diretamente nas configurações iniciais da importação quando o padrão não estiver bloqueado.
- Usuários autorizados podem escolher o reaproveitamento quando a configuração administrativa estiver habilitada.
- No modo de cópia, todos os módulos `qbank` do curso de origem precisam fazer parte da importação.
- Questões fixas e perguntas aleatórias dos questionários importados são redirecionadas para o banco copiado.
- A operação termina com uma validação que procura referências remanescentes ao banco de origem.
- Uma operação com bancos de origem e nenhum mapeamento persistente falha explicitamente, em vez de registrar um falso sucesso.

## Arquitetura

O plugin usa pontos de extensão do próprio Moodle:

1. um hook de saída registra antecipadamente a opção na página nativa de importação;
2. o evento síncrono `course_backup_created` transforma o `questions.xml` temporário antes do restore;
3. a integração local com a Backup/Restore API conserva os mapeamentos de módulos, categorias e entradas;
4. o evento `course_restored` reconcilia e valida as referências;
5. uma tarefa agendada remove metadados de diagnóstico após 30 dias.

Nenhum arquivo de `public/backup/` deve ser alterado.

A inicialização antecipada da opção na tela de importação é uma exceção
deliberada ao carregamento JavaScript modular padrão do Moodle. A justificativa
e os critérios para reavaliá-la estão em
`docs/decisao-frontend-carregamento-antecipado.md`.

## Compatibilidade declarada

- Moodle 5.1.x
- Moodle 5.2.x
- PHP suportado por essas versões do Moodle

O plugin está em estágio `alpha`. Antes de produção, execute a matriz descrita em
`docs/plano-de-testes.md`, principalmente com tipos de questão adicionais instalados pela universidade.

## Instalação e atualização

Copie a pasta como:

`public/local/courseqbankcopy`

Depois acesse as notificações administrativas ou execute:

```text
php admin/cli/upgrade.php
php admin/cli/purge_caches.php
```

Se o patch experimental anterior estiver aplicado, restaure os arquivos oficiais do núcleo antes dos testes desta versão.

## Diagnóstico administrativo

Administradores podem abrir
`Administração do site > Plugins > Plugins locais > Diagnóstico da cópia de bancos de questões`
e informar o ID de um curso de destino. O relatório é somente de leitura e mostra:

- operações de cópia e mapeamentos registrados pelo plugin;
- bancos e categorias de questões encontrados no curso;
- referências de questões aleatórias dos questionários;
- o curso proprietário do contexto de cada referência;
- tarefas pendentes de migração do banco de questões.

Uma referência marcada como **Externa** ainda aponta para um contexto ou uma categoria fora do curso analisado.
O relatório técnico em JSON pode ser copiado para análise, mas deve ser compartilhado apenas com a equipe responsável.

## Licença

Este plugin é distribuído sob a GNU General Public License v3 ou posterior.
Consulte o arquivo `LICENSE` para obter o texto completo da licença.
