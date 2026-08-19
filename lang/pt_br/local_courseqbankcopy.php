<?php
// This file is part of Moodle - https://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Brazilian Portuguese language strings for the plugin.
 *
 * @package    local_courseqbankcopy
 * @copyright  2026 jPerpetuo <joao.ariel@crearenet.com.br>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['allowreuseselection'] = 'Permitir que usuários autorizados escolham o modo de reutilização';
$string['allowreuseselection_desc'] = 'Usuários com a capability local/courseqbankcopy:choosereusemode poderão selecionar a reutilização durante a importação.';
$string['backupmanifestinvalid'] = 'O manifesto temporário da importação não contém um XML válido.';
$string['backupmanifestmissing'] = 'O manifesto temporário da importação não foi encontrado.';
$string['cannottransformquestions'] = 'Não foi possível preparar a cópia independente do banco de questões.';
$string['categorymappingmissing'] = 'Não foi encontrado o destino da categoria de questões de origem {$a}.';
$string['categoryoutsidecourse'] = 'A categoria de questões copiada {$a} não pertence ao curso de destino.';
$string['copyinterceptionfailed'] = 'A importação foi interrompida porque o plugin não conseguiu preparar a cópia independente do banco de questões.';
$string['copyquestions'] = 'Incluir cópias independentes dos bancos de questões';
$string['copyquestions_desc'] = 'Cria cópias independentes dos bancos de questões do curso de origem durante a importação.';
$string['copyquestions_help'] = 'Marcada: cria bancos independentes e aponta os questionários importados para as novas questões. Desmarcada: mantém o reaproveitamento padrão do Moodle.';
$string['copyquestions_locked'] = 'Esta opção é obrigatória para o seu perfil. Os bancos de questões importados serão independentes do curso de origem.';
$string['copyreconciliationfailed'] = 'A importação terminou, mas a validação das referências do novo banco de questões falhou. Solicite a análise de um administrador antes de usar o questionário importado.';
$string['courseqbankcopy:choosereusemode'] = 'Escolher o modo de reutilização de bancos de questões durante a importação de curso';
$string['defaultcopymode'] = 'Incluir cópias independentes dos bancos de questões';
$string['defaultcopymode_desc'] = 'Quando habilitado, os bancos de questões são copiados para o curso de destino sem manter referências ao curso de origem. Quando desabilitado, o Moodle reutiliza os bancos existentes.';
$string['diagnosticsbankorcategory'] = 'Entrada ou categoria do banco';
$string['diagnosticsbanks'] = 'Bancos de questões';
$string['diagnosticscategories'] = 'Categorias de questões';
$string['diagnosticscategoryids'] = 'IDs das categorias';
$string['diagnosticscourse'] = 'Curso';
$string['diagnosticscourseid'] = 'ID do curso de destino';
$string['diagnosticsexternalfixedreferences'] = 'Referências fixas externas';
$string['diagnosticsexternalfound'] = 'Foram encontradas {$a} referência(s) externa(s) de questões aleatórias.';
$string['diagnosticsexternalrandomreferences'] = 'Referências aleatórias externas';
$string['diagnosticsexternalreferences'] = 'Referências aleatórias externas';
$string['diagnosticsfixedreferences'] = 'Referências de questões fixas';
$string['diagnosticsgenerate'] = 'Gerar relatório';
$string['diagnosticsindependent'] = 'Nenhuma referência externa ou inválida de questão fixa ou aleatória foi encontrada.';
$string['diagnosticsindependentfixedreferences'] = 'Referências fixas independentes';
$string['diagnosticsindependentrandomreferences'] = 'Referências aleatórias independentes';
$string['diagnosticsinternalquestionentries'] = 'Total de entradas internas (principais + subquestões)';
$string['diagnosticsinternalsubquestions'] = 'Subquestões internas (Cloze)';
$string['diagnosticsintro'] = 'Este relatório somente de leitura identifica os bancos de questões e as referências de questões fixas e aleatórias de um curso de destino. Ele não altera os dados do Moodle.';
$string['diagnosticsinvalidfixedreferences'] = 'Referências fixas inválidas';
$string['diagnosticsinvalidrandomreferences'] = 'Referências aleatórias inválidas';
$string['diagnosticsitem'] = 'Item';
$string['diagnosticsjson'] = 'Relatório técnico completo (JSON)';
$string['diagnosticsmainquestions'] = 'Questões principais';
$string['diagnosticsmigrationtasks'] = 'Tarefas de migração pendentes';
$string['diagnosticsnoreferences'] = 'Nenhuma referência de questão fixa ou aleatória foi encontrada neste curso.';
$string['diagnosticsoperations'] = 'Operações de cópia';
$string['diagnosticsownercourse'] = 'Curso proprietário';
$string['diagnosticspluginrelease'] = 'Versão do plugin';
$string['diagnosticspluginversions'] = 'Versão do plugin no disco / banco de dados';
$string['diagnosticsproblemsfound'] = 'Foram encontradas {$a->external} referência(s) externa(s) e {$a->invalid} referência(s) inválida(s) entre questões fixas e aleatórias.';
$string['diagnosticsquestionbankentry'] = 'Entrada #{$a}';
$string['diagnosticsquestionscontext'] = 'Contexto das questões';
$string['diagnosticsquiz'] = 'Questionário';
$string['diagnosticsrandomreferences'] = 'Referências de questões aleatórias';
$string['diagnosticsreferencestable'] = 'Referências de questões fixas e aleatórias';
$string['diagnosticsslot'] = 'Posição';
$string['diagnosticsstatus'] = 'Situação';
$string['diagnosticsstatusexternal'] = 'Externa';
$string['diagnosticsstatusindependent'] = 'Independente';
$string['diagnosticsstatusinvalid'] = 'Inválida';
$string['diagnosticstitle'] = 'Diagnóstico da cópia de bancos de questões';
$string['diagnosticstype'] = 'Tipo';
$string['diagnosticstypefixed'] = 'Fixa';
$string['diagnosticstyperandom'] = 'Aleatória';
$string['diagnosticsvalue'] = 'Valor';
$string['incompletequestionbanks'] = 'Para garantir uma cópia independente, todos os bancos de questões do curso de origem precisam ser importados. Os seguintes módulos de banco não foram incluídos: {$a}.';
$string['independencevalidationfailed'] = 'Ainda existem {$a} referências a entradas do banco de questões de origem.';
$string['modecopy'] = 'Incluir cópias independentes dos bancos de questões';
$string['modereuse'] = 'Reutilizar bancos de questões existentes';
$string['pluginname'] = 'Cópia independente de bancos de questões';
$string['privacy:metadata'] = 'O plugin Cópia independente de bancos de questões não armazena dados pessoais.';
$string['questionbankmappingmissing'] = 'O curso de origem {$a} contém bancos de questões, mas a importação não registrou nenhum mapeamento para o destino.';
$string['randomreferencevalidationfailed'] = 'Ainda existe uma referência de pergunta aleatória para uma categoria ou contexto do curso de origem.';
$string['targetcourseidentificationfailed'] = 'local_courseqbankcopy: não foi possível identificar com segurança o curso de destino.';
$string['taskcleanupoperations'] = 'Limpar registros antigos de cópia de bancos de questões';
