<?php

// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

	/**
	 * Demanda ( questoes aleatorias ) +++
	 * Pagina de exibicao das questoes editadas
	 *
	 * @package    moodlecore
	 * @subpackage question
	 * @copyright  Equipe criacao IMD
	 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
	 */

	require_once(dirname(__FILE__) . '/../config.php');

	require_login();

	$courseid   = optional_param('courseid', 0, PARAM_INT);
	$categoryid = optional_param('categoryid', 0, PARAM_INT);

	if($courseid){
		$course = $DB->get_record('course', array('id' => $courseid));
	}

	if($categoryid){
		$category = $DB->get_record('course_categories', array('id' => $categoryid));
	}

	$PAGE->set_context(context_system::instance());
	$PAGE->set_title( 'Resumo das questões editadas por disciplina - '.format_string($category->name));
	$PAGE->set_url('/question/review_questions.php', array('courseid' => 'value'));
	$PAGE->set_heading( 'Resumo das questões editadas por disciplina - '.format_string($category->name));
	$PAGE->set_pagelayout('admin');

	$PAGE->navbar->add('Cursos', new moodle_url('/course/index.php'));
	$PAGE->navbar->add( $category->name, new moodle_url('/course/index.php?categoryid=' . $category->id));
	$PAGE->navbar->add( $course->shortname, new moodle_url('/course/view.php?id=' . $courseid));
	$PAGE->navbar->add('Banco de questões', new moodle_url('/question/edit.php?courseid=' . $courseid));

	require_capability('moodle/question:viewall', context_system::instance());

	echo $OUTPUT->header();
	echo html_writer::tag('p', 'Obs: Apenas disciplinas que possuem questões serão exibidas nessa lista.');

	$curso = $DB->get_records_sql('SELECT * FROM {course} WHERE category = ? ORDER BY fullname ASC', array( $category->id ));

	foreach ($curso as $c) {
		show_review_questions($c->id, $c->fullname);
	}

	echo $OUTPUT->footer();

	function show_review_questions( $curso_id, $curso_nome ){

		global $DB;

		$sql_sintaxe = "SELECT q.id, q.nivel, q.validada, q.observacao FROM {course} AS c INNER JOIN {context} AS ct ON ct.instanceid = c.id 
				INNER JOIN {question_categories} AS qc ON qc.contextid = ct.id
				INNER JOIN {question} AS q ON q.category = qc.id 
				WHERE c.id = {$curso_id} AND q.qtype != 'random' AND q.hidden = 0";

        $result = $DB->get_records_sql($sql_sintaxe);

        $total = 0;
        $aprovadas = 0;
        $reprovadas = 0;
        foreach ($result as $key => $value) {
            if( $value->validada == 1  ){
                $aprovadas++;
            }
            if ( $value->validada == 2 ){
                $reprovadas++;
            }
            $total++;
        }

		// Status geral
		if($total > 0){
			$disciplina  = html_writer::tag('a', $curso_nome, array('href' => '../question/edit.php?courseid=' . $curso_id));
			$imprime  = html_writer::tag('strong', $disciplina );
			$imprime .= html_writer::empty_tag('br');
			$imprime .= html_writer::tag('span', 'Total de questões: ' . $total);
			$imprime .= html_writer::empty_tag('br');
		    if( $aprovadas + $reprovadas == 0 ){
		          	$imprime .= html_writer::tag('span', 'Nenhum questão foi editada.');
		    }else{
		       	$imprime .= html_writer::tag('span', 'Quantidade de questões validadas: ' . ( $aprovadas + $reprovadas ) );
		      	$imprime .= html_writer::empty_tag('br');
		       	$imprime .= html_writer::tag('span', 'Quantidade de questões aprovadas / não aprovadas: ' . $aprovadas . " / " . $reprovadas);
		    }	        
	        echo html_writer::tag('div', $imprime, array('class' => 'infoadministrator'));
		}
	
    }
    //  +++

?>