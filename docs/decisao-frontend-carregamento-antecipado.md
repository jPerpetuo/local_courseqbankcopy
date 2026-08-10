# Decisão de frontend: carregamento antecipado

## Situação

A opção **Copiar bancos de questões para este curso** precisa aparecer junto das
configurações iniciais da importação, sem surgir depois que o formulário já foi
exibido. O plugin também precisa continuar compatível com Moodle 5.1.1 e 5.2.1.

## Alternativas avaliadas

### Módulo ESM em `amd/src`

Este é o padrão recomendado para novo JavaScript compatível com Moodle 5.1. O
arquivo-fonte usa sintaxe ESM, é compilado pelo Grunt para AMD e normalmente é
iniciado por `$PAGE->requires->js_call_amd()`.

No Moodle 5.1.1, o carregador RequireJS e as chamadas registradas por
`js_call_amd()` são emitidos no rodapé. Quando o módulo começa a executar, o
formulário já foi desenhado. Isso reintroduz o deslocamento visual observado
antes da implementação do carregamento antecipado.

### Módulo padrão com formulário oculto

Seria possível ocultar as configurações até o módulo terminar de carregar. Essa
alternativa evita o deslocamento do checkbox, mas atrasa a exibição de todo o
formulário e piora a experiência do usuário.

### Carregamento manual do módulo no cabeçalho

Antecipar manualmente o RequireJS, carregar diretamente arquivos de origem ou
duplicar a configuração de módulos do núcleo criaria dependência de detalhes
internos do Moodle. Essa alternativa teria risco de incompatibilidade maior do
que o pequeno script atual.

## Decisão

Manter `js/import_options_early.js` como um script pequeno e independente,
carregado no cabeçalho exclusivamente em `/backup/import.php`.

Essa é uma exceção consciente ao padrão modular, justificada pelos seguintes
controles:

- o script só é incluído na página nativa de importação;
- não depende de jQuery, YUI, RequireJS nem bibliotecas de terceiros;
- usa APIs estáveis do navegador e classes visuais nativas do Moodle;
- recebe textos e permissões preparados no backend;
- o backend mantém o modo de cópia como padrão, mesmo sem JavaScript;
- o teste Behat percorre a importação nativa nas versões 5.1.1 e 5.2.1.

## Quando reavaliar

A conversão deve ser reconsiderada quando ocorrer uma destas condições:

1. o plugin deixar de oferecer suporte ao Moodle 5.1;
2. o Moodle disponibilizar uma API pública para iniciar um módulo no cabeçalho;
3. o formulário nativo de importação oferecer um hook próprio para adicionar o
   campo no servidor;
4. o carregamento antecipado deixar de ser necessário por mudança no fluxo
   nativo.

Até lá, converter apenas para satisfazer a estrutura ESM produziria uma
regressão visual sem benefício funcional equivalente.
