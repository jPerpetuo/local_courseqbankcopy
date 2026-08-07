<?php
// This file is part of Moodle - https://moodle.org/.

$string['pluginname'] = 'Cópia independente de bancos de questões';
$string['copyquestions'] = 'Copiar bancos de questões para este curso';
$string['copyquestions_desc'] = 'Cria cópias independentes dos bancos de questões do curso de origem durante a importação.';
$string['copyquestions_help'] = 'Marcada: cria bancos independentes e aponta os questionários importados para as novas questões. Desmarcada: mantém o reaproveitamento padrão do Moodle.';
$string['copyquestions_locked'] = 'Esta opção é obrigatória para o seu perfil. Os bancos de questões importados serão independentes do curso de origem.';
$string['modecopy'] = 'Copiar bancos de questões para este curso';
$string['modereuse'] = 'Reutilizar bancos de questões existentes';
$string['allowreuseselection'] = 'Permitir que usuários autorizados escolham o modo de reutilização';
$string['allowreuseselection_desc'] = 'Usuários com a capability local/courseqbankcopy:choosereusemode poderão selecionar a reutilização durante a importação.';
$string['courseqbankcopy:choosereusemode'] = 'Escolher o modo de reutilização de bancos de questões durante a importação de curso';
$string['privacy:metadata'] = 'O plugin Cópia independente de bancos de questões não armazena dados pessoais.';
$string['cannottransformquestions'] = 'Não foi possível preparar a cópia independente do banco de questões.';
$string['copyinterceptionfailed'] = 'A importação foi interrompida porque o plugin não conseguiu preparar a cópia independente do banco de questões.';
$string['copyreconciliationfailed'] = 'A importação terminou, mas a validação das referências do novo banco de questões falhou. Solicite a análise de um administrador antes de usar o questionário importado.';
$string['independencevalidationfailed'] = 'Ainda existem {$a} referências a entradas do banco de questões de origem.';
$string['categorymappingmissing'] = 'Não foi encontrado o destino da categoria de questões de origem {$a}.';
$string['categoryoutsidecourse'] = 'A categoria de questões copiada {$a} não pertence ao curso de destino.';
$string['randomreferencevalidationfailed'] = 'Ainda existe uma referência de pergunta aleatória para uma categoria ou contexto do curso de origem.';
$string['backupmanifestmissing'] = 'O manifesto temporário da importação não foi encontrado.';
$string['backupmanifestinvalid'] = 'O manifesto temporário da importação não contém um XML válido.';
$string['incompletequestionbanks'] = 'Para garantir uma cópia independente, todos os bancos de questões do curso de origem precisam ser importados. Os seguintes módulos de banco não foram incluídos: {$a}.';
$string['taskcleanupoperations'] = 'Limpar registros antigos de cópia de bancos de questões';
