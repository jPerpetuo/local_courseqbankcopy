# Cópia independente de bancos de questões

Componente Moodle: `local_courseqbankcopy`.

O plugin foi iniciado para o Moodle 5.1.1 e terá como objetivo tornar a importação de curso independente do banco de questões do curso de origem.

## Regra de negócio

No modo padrão `copy`, a importação deve copiar integralmente os bancos de questões pertencentes ao curso de origem. Depois da operação, a exclusão do curso antigo não poderá afetar os quizzes, questões, categorias, versões ou arquivos do curso novo.

O modo `reuse` permanece disponível apenas para usuários que tenham a capability `local/courseqbankcopy:choosereusemode` e quando a opção administrativa correspondente estiver habilitada.

## Estado atual

Esta primeira entrega cria a base instalável do plugin: identificação, permissões, configurações, textos e o contrato de independência. A cópia ainda não é executada porque a tela nativa de Importar não expõe um hook específico para injetar o modo e alterar o restore de questões.

O próximo passo é validar o código-fonte exato do Moodle 5.1.1 da universidade e implementar o ponto de extensão mínimo no pipeline de Importar/restore. A compatibilidade com a produção em 5.2.1 será validada antes da implantação.
