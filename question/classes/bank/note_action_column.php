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
 * Demanda ( Questoes aleatorias ) +++
 * Exibe icones que informao o estado da questao ( se possui observacao ou nao )
 *
 */
class note_action_column extends action_column_base {

    public function get_name() {
        return 'noteaction';
    }

    public function init() {
        parent::init();
        $this->strhasnote    = 'Possui observacao';
        $this->strhasnotnote = 'Nao possui observacao';
    }

    protected function display_content($question, $rowclasses) {
        
        global $CFG;
        global $PAGE;

        if (question_has_capability_on($question, 'use')) {

            $link = $CFG->wwwroot;
            $courseid = $PAGE->course->id;
           
            if($question->observacao){
                echo $PAGE->get_renderer('core_question')->question_preview_link_note($question->id, $this->qbank->get_most_specific_context(), false, $link, $courseid);      
            }
        }
    }

    public function get_required_fields() {
        return array('q.id','q.observacao');
    }
    
}