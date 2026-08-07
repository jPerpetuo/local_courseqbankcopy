# Plano de testes

## Preparação

- Restaurar os arquivos oficiais do núcleo antes de instalar esta versão.
- Atualizar o plugin e purgar todos os caches.
- Executar o cron ao menos uma vez para confirmar o registro da tarefa agendada.
- Ativar mensagens de depuração em ambiente de desenvolvimento.

## Cenário básico

1. Criar o curso X com um banco, categorias, subcategorias e questões.
2. Criar questionários com questões fixas e aleatórias.
3. Importar todo o conteúdo de X para Y no modo de cópia.
4. Confirmar que os bancos aparecem em Y.
5. Editar questões em X e verificar que Y não muda.
6. Editar questões em Y e verificar que X não muda.
7. Excluir X e confirmar que Y permanece funcional.

## Casos obrigatórios

- Banco com questões não utilizadas por nenhum questionário.
- Várias versões da mesma entrada de banco.
- Questões com imagens e arquivos incorporados.
- Categorias vazias e categorias profundamente aninhadas.
- Perguntas aleatórias com filtros de categoria e tags.
- Mais de um banco `qbank` no mesmo curso.
- Importação para curso vazio e para curso que já possui conteúdo.
- Duas importações simultâneas executadas por usuários diferentes.
- Tentativa de desmarcar um banco `qbank` no modo de cópia.
- Modo de reutilização com e sem a capability correspondente.

## Tipos de questão

Testar todos os tipos instalados na universidade. No mínimo:

- múltipla escolha;
- verdadeiro/falso;
- resposta curta;
- numérica;
- associação;
- dissertação;
- calculada e calculada simples;
- arrastar e soltar;
- respostas embutidas;
- tipos adicionais de terceiros.

## Verificação técnica

Depois da importação, consultar `question_references` e `question_set_references` para confirmar que os contextos usados pelos módulos importados não apontam para entradas ou categorias do curso de origem.

Confirmar também que a operação correspondente em `local_cqbc_operation` terminou com o status `complete`.
