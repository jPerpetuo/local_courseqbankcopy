# Proposta de ponto de extensão no núcleo

## Objetivo

Permitir que um plugin local altere, de forma suportada, a estratégia de bancos de questões durante `Curso > Reutilizar > Importar` no Moodle 5.1.1.

## Diagnóstico confirmado no Moodle 5.1.1

O reaproveitamento é decidido antes de as questões serem gravadas, em
`backup/util/dbops/restore_dbops.class.php`, no método
`restore_dbops::prechek_precheck_qbanks_by_level()`.

- Categorias são comparadas pelo campo `stamp` no contexto de destino.
- Questões são comparadas por um hash de identidade gerado a partir de conteúdo e opções.

Quando há equivalência, o core grava em `backup_ids_temp` um mapeamento para a categoria ou a questão existente. Mais tarde,
`restore_create_categories_and_questions::process_question()` encontra esse mapeamento e deixa de criar uma nova entrada de banco, versão e questão. As referências de quiz passam a usar a entrada existente.

## Pontos mínimos no core

1. `backup/import.php`
   - incluir a escolha de estratégia na configuração da importação;
   - persistir o modo selecionado no controlador antes do backup e do restore.

2. `backup/util/dbops/restore_dbops.class.php`
   - no modo `copy`, evitar o ramo que mapeia categorias por `stamp` e questões por hash;
   - registrar `newitemid = 0` para todas as categorias e questões do banco pertencente ao curso de origem;
   - preservar o contexto de destino e as verificações de capability do core.

3. `backup/moodle2/restore_stepslib.php`
   - a lógica existente cria `question_bank_entries`, `question_versions`, `question`, dados dos tipos e arquivos quando `newitemid = 0`;
   - com um novo mapeamento de `question_bank_entry`, `process_question_reference()` aponta os quizzes restaurados para a cópia.

## Dados necessários

- curso de origem;
- curso de destino;
- controlador de importação/restore;
- modo solicitado: `copy` ou `reuse`.

## Contrato proposto

O núcleo deve emitir um hook imediatamente antes de o pré-processamento de categorias e questões decidir entre criar ou mapear objetos. O hook precisa permitir que o plugin:

1. leia o modo selecionado;
2. force o modo `copy` para professores;
3. solicite um banco de destino independente;
4. faça com que os mapas de referências dos quizzes usem as novas entradas.

## Regra de segurança

O modo `copy` nunca pode usar a correspondência por `stamp` ou hash para reutilizar categoria, entrada ou questão do curso de origem. A correspondência pode continuar sendo usada para evitar duplicações dentro da mesma importação, desde que o objeto resultante pertença ao banco novo.
