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

namespace core_question\bank;

/**
 *
 * Demanda ( Questoes aleatorias )
 * Exibe icones que informao o estado da questao ( Nao validada, Aprovada, Reprovada )
 *
 */
class validator_action_column extends action_column_base {

    public function init() {
        parent::init();
        $this->strinvalid = 'Não aprovada';
        $this->strvalid   = 'Aprovada';
        $this->stredit    = 'Não validada, por favor validar a questão';
    }

    public function get_name() {
        return 'validatoraction';
    }

    protected function display_content($question, $rowclasses) {

        global $DB;

        $courseid   = optional_param('courseid', null, PARAM_INT);
        $cmid       = optional_param('cmid', null, PARAM_INT);
        $category   = optional_param('category', null, PARAM_RAW);

        if($category == null){
            $category  = optional_param('cat', null, PARAM_RAW);
        }

        $qpage     = optional_param('qpage', null, PARAM_INT);
        $ordenacao = optional_param('qbs1', null, PARAM_RAW);

        if (question_has_capability_on($question, 'edit')) {
            if( $question->validada == '2' ){
                $this->print_icon('t/block', $this->strinvalid, $this->qbank->edit_question_edit_url($question->id, $courseid, $cmid, $category, $qpage, $ordenacao));
            }else if( $question->validada == '1' ){
                $this->print_icon('t/check', $this->strvalid, $this->qbank->edit_question_edit_url($question->id, $courseid, $cmid, $category, $qpage, $ordenacao));
            }else{
                $this->print_icon('e/help', $this->stredit, $this->qbank->edit_question_edit_url($question->id, $courseid, $cmid, $category, $qpage, $ordenacao));
            }
        }
        
    }

    // recebe campo validada e envia para o display_content
    public function get_required_fields() {
        return array('q.id','q.validada');
    }

}