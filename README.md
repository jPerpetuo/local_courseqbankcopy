# Cópia independente de bancos de questões

Componente Moodle: `local_courseqbankcopy`.

Plugin local para tornar independentes os bancos de questões copiados por
`Curso > Reutilizar > Importar`, sem editar arquivos do núcleo do Moodle.

## Comportamento

- O modo padrão é **Copiar bancos de questões para este curso**.
- Professores comuns não podem desativar a cópia.
- Administradores do site podem desativar a cópia diretamente nas configurações iniciais da importação.
- Usuários autorizados podem escolher o reaproveitamento quando a configuração administrativa estiver habilitada.
- No modo de cópia, todos os módulos `qbank` do curso de origem precisam fazer parte da importação.
- Questões fixas e perguntas aleatórias dos questionários importados são redirecionadas para o banco copiado.
- A operação termina com uma validação que procura referências remanescentes ao banco de origem.

## Arquitetura

O plugin usa pontos de extensão do próprio Moodle:

1. um hook de saída registra antecipadamente a opção na página nativa de importação;
2. o evento síncrono `course_backup_created` transforma o `questions.xml` temporário antes do restore;
3. a integração local com a Backup/Restore API conserva os mapeamentos de módulos, categorias e entradas;
4. o evento `course_restored` reconcilia e valida as referências;
5. uma tarefa agendada remove metadados de diagnóstico após 30 dias.

Nenhum arquivo de `public/backup/` deve ser alterado.

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
