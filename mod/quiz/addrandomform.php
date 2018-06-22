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
 * Defines the Moodle forum used to add random questions to the quiz.
 *
 * @package   mod_quiz
 * @copyright 2008 Olli Savolainen
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');


/**
 * The add random questions form.
 *
 * @copyright  1999 onwards Martin Dougiamas and others {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_add_random_form extends moodleform {

    protected function definition() {
        global $OUTPUT, $PAGE;

        $mform =& $this->_form;
        $mform->setDisableShortforms();

        $contexts = $this->_customdata['contexts'];
        $usablecontexts = $contexts->having_cap('moodle/question:useall');

        //  Demanda ( Questoes aleatorias ){
            // Gerar aleatoriamente as questões, com a opção de escolher a quantidade de questões por nivel
            $mform->addElement('header', 'categoryheader', 'Adicionar questões randômicas por classificação.');

            // Tratamento para resgatar a categoria das questões
            $categoryQuestion = $this->_customdata['cat'];
            $categoryQuestionParent = $categoryQuestion;

            // Verifica se a categoria foi passada pela url e adiciona na variavel $categoryQuestion
            if(!empty(optional_param('idCategory', 0, PARAM_INT))){
                $categoryQuestion = optional_param('idCategory', 0, PARAM_INT);
            }

            $mform->addElement('questioncategory', 'activitySelector', get_string('category'), array('contexts' => $usablecontexts, 'top' => false ));
            $mform->setDefault('activitySelector', $categoryQuestion);

            // texto informativo
            $string = $this->qtd_questoes(NULL, $categoryQuestion);
            $qtd = end( $string );
            if( $qtd > 0 ){ 
                $str = 'Quantidade de questões disponíveis'; 
            }else{
                $str = 'Nenhuma questão disponíveis';
            }
            $mform->addElement('html', html_writer::tag('h5', $str, array('id' => 'qtd_questoes_disponiveis')));

            // Select para exibir todas as categorias
            $categoriasDisponiveis = $this->pegar_categoria_geral($categoryQuestionParent);

            // Necessario para criar a url de retorno 
            $mform->addElement('hidden', 'idcategoryparent', $categoryQuestionParent);
            $mform->setType('idcategoryparent', PARAM_TEXT);

            $cmid = optional_param('cmid', 0, PARAM_INT);
            $mform->addElement('hidden', 'cmid', $cmid, array('id' => 'cmid'));
            $mform->setType('cmid', PARAM_INT);

            $mform->addElement('select','numberofeasy','Quantidade de questões fáceis',$this->qtd_questoes(1,$categoryQuestion));
            $mform->addElement('select','numberofmedium','Quantidade de questões medianas',$this->qtd_questoes(2,$categoryQuestion));
            $mform->addElement('select','numberofhard','Quantidade de questões difíceis',$this->qtd_questoes(3,$categoryQuestion));

            $mform->addElement('submit','randomquestionybylevel', 'Adicionar questões aleatórias por nível');
        //  }

        // Random from existing category section.
        $mform->addElement('header', 'existingcategoryheader',
                get_string('randomfromexistingcategory', 'quiz'));

        $mform->addElement('questioncategory', 'category', get_string('category'),
                array('contexts' => $usablecontexts, 'top' => true));
        $mform->setDefault('category', $this->_customdata['cat']);

        $mform->addElement('checkbox', 'includesubcategories', '', get_string('recurse', 'quiz'));

        $tops = question_get_top_categories_for_contexts(array_column($contexts->all(), 'id'));
        $mform->hideIf('includesubcategories', 'category', 'in', $tops);

        $tags = core_tag_tag::get_tags_by_area_in_contexts('core_question', 'question', $usablecontexts);
        $tagstrings = array();
        foreach ($tags as $tag) {
            $tagstrings["{$tag->id},{$tag->name}"] = $tag->name;
        }
        $options = array(
            'multiple' => true,
            'noselectionstring' => get_string('anytags', 'quiz'),
        );
        $mform->addElement('autocomplete', 'fromtags', get_string('randomquestiontags', 'mod_quiz'), $tagstrings, $options);
        $mform->addHelpButton('fromtags', 'randomquestiontags', 'mod_quiz');

        $mform->addElement('select', 'numbertoadd', get_string('randomnumber', 'quiz'),
                $this->get_number_of_questions_to_add_choices());

        $previewhtml = $OUTPUT->render_from_template('mod_quiz/random_question_form_preview', []);
        $mform->addElement('html', $previewhtml);

        $mform->addElement('submit', 'existingcategory', get_string('addrandomquestion', 'quiz'));

        // Random from a new category section.
        $mform->addElement('header', 'newcategoryheader',
                get_string('randomquestionusinganewcategory', 'quiz'));

        $mform->addElement('text', 'name', get_string('name'), 'maxlength="254" size="50"');
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('questioncategory', 'parent', get_string('parentcategory', 'question'),
                array('contexts' => $usablecontexts, 'top' => true));
        $mform->addHelpButton('parent', 'parentcategory', 'question');

        $mform->addElement('submit', 'newcategory',
                get_string('createcategoryandaddrandomquestion', 'quiz'));

        // Cancel button.
        $mform->addElement('cancel');
        $mform->closeHeaderBefore('cancel');

        $mform->addElement('hidden', 'addonpage', 0, 'id="rform_qpage"');
        $mform->setType('addonpage', PARAM_SEQUENCE);
        $mform->addElement('hidden', 'cmid', 0);
        $mform->setType('cmid', PARAM_INT);
        $mform->addElement('hidden', 'returnurl', 0);
        $mform->setType('returnurl', PARAM_LOCALURL);

        // Add the javascript required to enhance this mform.
        $PAGE->requires->js_call_amd('mod_quiz/add_random_form', 'init', [
            $mform->getAttribute('id'),
            $contexts->lowest()->id,
            $tops
        ]);
    }

    public function validation($fromform, $files) {
        $errors = parent::validation($fromform, $files);

        if (!empty($fromform['newcategory']) && trim($fromform['name']) == '') {
            $errors['name'] = get_string('categorynamecantbeblank', 'question');
        }

        return $errors;
    }

    /**
     * Return an arbitrary array for the dropdown menu
     * @return array of integers array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 20, 30, 40, 50, 60, 70, 80, 90, 100)
     */
    private function get_number_of_questions_to_add_choices() {
        $maxrand = 100;
        $randomcount = array();
        for ($i = 1; $i <= min(10, $maxrand); $i++) {
            $randomcount[$i] = $i;
        }
        for ($i = 20; $i <= min(100, $maxrand); $i += 10) {
            $randomcount[$i] = $i;
        }
        return $randomcount;
    }


    //  Demanda ( Questoes aleatorias ){
        /**
         * Recebe o id da categoria e o nivel
         * @return retorna o numero de questoes cadastradas, incluindo as sub-categorias 
         *
         */
        private function qtd_questoes($nivel, $categoryQuestion){

            global $DB;

            $categoryQuestion = explode(',',$categoryQuestion);
            $categoryQuestion = $categoryQuestion[0];

            $result = $this->pegar_categoria_especifica($categoryQuestion);

            $keys = array_keys($result);
            $key = implode(",",$keys);

            if($nivel == null){
                $nivel = 'q.nivel IS NOT NULL';
            }else{
                $nivel = 'q.nivel = ' . $nivel;
            }

            $count = $DB->count_records_sql(
                "SELECT count(*) FROM mdl_question AS q 
                INNER JOIN mdl_question_categories AS qc on q.category = qc.id
                WHERE q.category IN ($key) AND $nivel AND q.validada = 1 AND q.qtype != 'random'" 
            );

            $arrayNivel = array();
            for($c = 0; $c <= $count; $c++){
                array_push($arrayNivel, $c);
            }

            return $arrayNivel;

        }

        /**
         * Recebe o id da categoria
         * @return retorna todas as categorias associadas a categoria indicada
         */
        private function pegar_categoria_geral($categoryQuestion){
        
            global $DB;

            $categoryQuestion = explode(',',$categoryQuestion);
            $categoryQuestion = $categoryQuestion[0];

            $return = $DB->get_records_sql(
                "SELECT id, contextid, name FROM mdl_question_categories WHERE parent = $categoryQuestion OR id = $categoryQuestion ORDER BY id ASC"
            );

            $categoria = array();
            foreach ($return as $value) {
                $categoria[$value->id . "," .$value->contextid] = $value->name;
            }

            return $categoria; 
        }

        /**
         * Recebe o id da categoria
         * @return retorna todas as categorias associadas a categoria indicada
         */
        private function pegar_categoria_especifica($categoryQuestion, $newArray = true){
        
            global $DB;
            global $cat_esp;

            $categoryQuestion = explode(',',$categoryQuestion);
            $categoryQuestion = $categoryQuestion[0];

            $return = $DB->get_records_sql(
                "SELECT id, name, parent FROM mdl_question_categories 
                WHERE parent IN ( SELECT id
                FROM mdl_question_categories 
                WHERE parent = $categoryQuestion OR id = $categoryQuestion ) OR id = $categoryQuestion"
            );

            foreach ($return as $value) {
                $cat_esp[$value->id] = $value->name;
            }

            return $cat_esp; 

        }

        /**
         * Recebe a url do localhost e a url da pagina
         * Ao alterar o campo select o usuário é redirecionado para outra pagina para configuracao do questionario aleatorio
         */
        // static function call_js($url,$urlpage){
        //     echo "<script type='text/javascript'> 
        //             //<![CDATA[ 
        //                 var activities = document.getElementById('id_activitySelector');
        //                 activities.addEventListener('change', function() { 
        //                     var cmid = document.getElementById('cmid').value;
        //                     var idCategoria = this.value; 
        //                     window.location.href = 'addrandom.php?returnurl={$url}/mod/quiz/edit.php?cmid='+cmid+'&amp;data-addonpage=0&cmid='+cmid+'&appendqnumstring=addarandomquestion&idCategory='+idCategoria;
        //                 });

        //                 var urlPagina = document.URL;
        //                 var idPagina = urlPagina.lastIndexOf('='); 
        //                 document.getElementById('id_category').value = urlPagina.substring(idPagina+1);

        //             //]] 
        //         </script> 
        //     ";
        // }
    //  }
}