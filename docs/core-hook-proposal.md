# Proposta de ponto de extensão no núcleo

## Objetivo

Permitir que um plugin local altere, de forma suportada, a estratégia de bancos de questões durante `Curso > Reutilizar > Importar` no Moodle 5.1.1.

## Dados necessários

- curso de origem;
- curso de destino;
- controlador de importação/restore;
- modo solicitado: `copy` ou `reuse`.

## Contrato proposto

O núcleo deve emitir um hook imediatamente antes de construir/executar o plano de restore da importação. O hook precisa permitir que o plugin:

1. leia o modo selecionado;
2. force o modo `copy` para professores;
3. solicite um banco de destino independente;
4. forneça o mapa de referências para os quizzes restaurados.

## Regra de segurança

O modo `copy` nunca pode usar a correspondência por hash para reutilizar a entrada de uma questão do curso de origem. A correspondência pode continuar sendo usada para evitar duplicações dentro da mesma importação, desde que o objeto resultante pertença ao banco novo.
