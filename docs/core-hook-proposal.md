# Proposta: polÃ­tica extensÃ­vel para bancos de questÃµes em importaÃ§Ãµes

## DecisÃ£o proposta

Adicionar ao subsistema de backup/restore do Moodle uma polÃ­tica extensÃ­vel para definir como os bancos de questÃµes sÃ£o tratados em `Curso > Reutilizar > Importar`.

O comportamento padrÃ£o do Moodle permanece `reuse` para preservar compatibilidade. Um plugin local, como `local_courseqbankcopy`, poderÃ¡ solicitar `copy` para uma importaÃ§Ã£o especÃ­fica. O core continuarÃ¡ sendo o Ãºnico responsÃ¡vel por criar categorias, entradas de banco, versÃµes, questÃµes, dados de tipos, arquivos e referÃªncias.

O objetivo Ã© eliminar qualquer alteraÃ§Ã£o direta em arquivos do core na instalaÃ§Ã£o da universidade.

## Problema resolvido

Na importaÃ§Ã£o no mesmo site, o Moodle pode mapear uma categoria ou questÃ£o da origem para um objeto jÃ¡ existente. AlÃ©m disso, para bancos em contexto de mÃ³dulo, hÃ¡ um passo final que pode reconectar as referÃªncias de questionÃ¡rios ao banco original acessÃ­vel.

Esse comportamento Ã© adequado para o compartilhamento introduzido no Moodle 5, mas nÃ£o atende ao cenÃ¡rio de arquivamento de cursos. Quando o curso antigo Ã© removido, questionÃ¡rios do curso novo nÃ£o podem depender de suas entradas de banco de questÃµes.

No modo `copy`, o resultado obrigatÃ³rio Ã©:

- todas as categorias e subcategorias do banco pertencente ao curso de origem sÃ£o criadas no banco do curso de destino;
- cada entrada do banco, versÃ£o, questÃ£o e arquivo passa a existir de forma independente no destino;
- referÃªncias diretas de questionÃ¡rios usam as novas `question_bank_entries`;
- questÃµes aleatÃ³rias usam a categoria e o contexto do banco copiado;
- excluir o curso de origem nÃ£o altera o banco nem os questionÃ¡rios do curso importado.

## Por que um observador pÃ³s-importaÃ§Ã£o nÃ£o Ã© suficiente

O evento nativo `\core\event\course_restored` informa origem, destino e modo da operaÃ§Ã£o. Ele permite que um plugin saiba que X foi importado em Y.

Entretanto, o evento sÃ³ Ã© disparado depois que o restore terminou e a tabela temporÃ¡ria `backup_ids_temp` foi removida. Essa tabela contÃ©m o mapa exato de IDs antigos para novos. Um plugin que agisse somente depois do evento teria de reconstruir o mapa por aproximaÃ§Ã£o, usando campos como `stamp`, categoria, versÃ£o e idnumber.

Isso Ã© inseguro para bancos grandes, versÃµes de questÃµes, questÃµes aleatÃ³rias, arquivos, datasets de calculadas e tipos de questÃ£o de terceiros. TambÃ©m pode alterar, por engano, um questionÃ¡rio que jÃ¡ existia no curso de destino.

Por essa razÃ£o, a soluÃ§Ã£o deve atuar dentro do restore, antes de as referÃªncias dos questionÃ¡rios serem finalizadas.

## Pontos de extensÃ£o propostos

### 1. Hook para acrescentar a configuraÃ§Ã£o Ã  importaÃ§Ã£o

Novo hook no fim de `backup_root_task::define_settings()`:

```php
\core_backup\hook\after_backup_root_define_settings
```

Ele recebe a instÃ¢ncia de `backup_root_task`. Um plugin local pode criar uma configuraÃ§Ã£o booleana prÃ³pria, por exemplo `local_courseqbankcopy_copy`, somente quando o modo for `backup::MODE_IMPORT`.

Para usuÃ¡rios comuns, o plugin define o valor como marcado e deixa a interface nÃ£o editÃ¡vel. Para administradores com capability prÃ³pria, o campo pode ficar editÃ¡vel e permitir `reuse`.

NÃ£o Ã© necessÃ¡rio alterar `backup/import.php`: uma configuraÃ§Ã£o nÃ£o editÃ¡vel, mas nÃ£o bloqueada, continua visÃ­vel na tela nativa de configuraÃ§Ãµes iniciais.

O valor Ã© gravado no arquivo temporÃ¡rio de backup como configuraÃ§Ã£o raiz. O hook jÃ¡ existente `\core_backup\hook\after_restore_root_define_settings` permite que o plugin registre a configuraÃ§Ã£o correspondente no restore e recupere o valor.

### 2. Hook de polÃ­tica de restauraÃ§Ã£o do banco de questÃµes

Novo hook, disparado uma vez por restore antes do prÃ©-processamento das categorias e questÃµes:

```php
\core_question\hook\question_bank_restore_policy
```

Local sugerido: inÃ­cio de `restore_dbops::precheck_precheck_qbanks_by_level()`, depois que o controlador de restore estÃ¡ disponÃ­vel e antes de qualquer comparaÃ§Ã£o por `stamp` ou hash.

Contrato mÃ­nimo do objeto do hook:

```php
final class question_bank_restore_policy {
    public const REUSE = 'reuse';
    public const COPY = 'copy';

    public function __construct(
        public readonly \restore_controller $controller,
        public readonly int $contextlevel,
    ) {}

    public function set_strategy(string $strategy): void;
    public function get_strategy(): string;
}
```

Regras do contrato:

- o valor inicial Ã© `reuse`;
- somente `copy` e `reuse` sÃ£o aceitos;
- o hook sÃ³ pode alterar a estratÃ©gia para restores de curso no mesmo site;
- o core valida o resultado e registra a estratÃ©gia no plano de restore para que todos os passos usem a mesma decisÃ£o;
- sem plugin interessado, o comportamento atual Ã© mantido sem mudanÃ§a.

## AlteraÃ§Ãµes internas esperadas no core

O hook nÃ£o deve transferir responsabilidade de cÃ³pia ao plugin. Ele apenas informa a polÃ­tica. O core aplica essa polÃ­tica em trÃªs pontos jÃ¡ existentes.

| Ponto do core | Com `reuse` | Com `copy` |
| --- | --- | --- |
| `restore_dbops::precheck_precheck_qbanks_by_level()` | Pode mapear categoria por `stamp` e questÃ£o por hash. | NÃ£o mapeia objetos pertencentes ao banco de origem; registra criaÃ§Ã£o de novas categorias e questÃµes. |
| `restore_create_categories_and_questions` | Reutiliza objetos mapeados ou cria os ausentes. | Recebe `newitemid = 0` e executa o fluxo nativo de criaÃ§Ã£o de categoria, entrada, versÃ£o, questÃ£o, dados de qtype e arquivos. |
| `restore_move_module_questions_categories` e referÃªncias aleatÃ³rias | Pode voltar a apontar para o banco original acessÃ­vel. | MantÃ©m as referÃªncias para as entradas copiadas e transfere filtros aleatÃ³rios para a categoria/contexto de destino. |

Com isso, `process_question_reference()` jÃ¡ encontra o mapeamento nativo `question_bank_entry` e liga os slots dos questionÃ¡rios Ã s cÃ³pias. NÃ£o hÃ¡ SQL de atualizaÃ§Ã£o de referÃªncias implementado pelo plugin.

## Responsabilidades do plugin `local_courseqbankcopy`

O plugin fica restrito a regras institucionais:

1. Acrescentar a opÃ§Ã£o â€œCopiar bancos de questÃµes para este cursoâ€ Ã  tela nativa de importaÃ§Ã£o.
2. Definir `copy` como padrÃ£o para professores.
3. Permitir `reuse` somente para uma capability administrativa explÃ­cita.
4. Responder ao hook de polÃ­tica e selecionar `copy` quando a configuraÃ§Ã£o estiver marcada.
5. Oferecer tela administrativa, logs e testes do comportamento escolhido.

O plugin nÃ£o cria registros nas tabelas `question`, `question_bank_entries`, `question_versions`, `question_references` ou `question_set_references` diretamente.

## Fluxo resultante

```text
Professor: Curso Y > Reutilizar > Importar > seleciona Curso X
        |
        v
Plugin adiciona a opÃ§Ã£o "Copiar bancos de questÃµes" (marcada)
        |
        v
Backup temporÃ¡rio registra a configuraÃ§Ã£o do plugin
        |
        v
Restore chama question_bank_restore_policy
        |
        +-- reuse: comportamento padrÃ£o do Moodle
        |
        +-- copy: core cria banco independente e mapeia questionÃ¡rios para ele
        |
        v
Curso Y funciona sem referÃªncias ao banco de X
```

## CritÃ©rios de aceite

1. Em uma importaÃ§Ã£o X â†’ Y com `copy`, editar uma questÃ£o em X nÃ£o modifica Y.
2. Excluir X nÃ£o impede a abertura nem a ediÃ§Ã£o dos questionÃ¡rios de Y.
3. Categorias, subcategorias, questÃµes nÃ£o usadas em questionÃ¡rios, arquivos e questÃµes aleatÃ³rias estÃ£o disponÃ­veis em Y.
4. Em `reuse`, a importaÃ§Ã£o mantÃ©m o comportamento padrÃ£o do Moodle.
5. Importar X duas vezes para Y cria cÃ³pias independentes em cada execuÃ§Ã£o, sem alterar questionÃ¡rios que jÃ¡ estavam em Y.
6. O plugin Ã© instalado, atualizado e removido sem editar arquivos do core.
7. Os testes sÃ£o executados nas versÃµes 5.1.1 e 5.2.1; a homologaÃ§Ã£o deve incluir ao menos uma atualizaÃ§Ã£o de versÃ£o menor do Moodle.

## Compatibilidade e manutenÃ§Ã£o

Esta proposta adiciona uma API de extensÃ£o pequena e genÃ©rica ao core. Ela Ã© mais sustentÃ¡vel do que um patch que altera regras internas do restore, pois a polÃ­tica institucional permanece no plugin e a cÃ³pia continua usando as rotinas oficiais do Moodle.

Enquanto o hook nÃ£o estiver presente em uma versÃ£o oficial do Moodle, nÃ£o existe uma implementaÃ§Ã£o puramente local que entregue a mesma garantia de cÃ³pia integral. Um pÃ³s-processamento por evento pode ser usado apenas como experimento controlado, nÃ£o como substituto confiÃ¡vel deste desenho.

