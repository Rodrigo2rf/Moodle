<?php

// Demanda ( Questoes aleatorias ) +++
// This file is part of Moodle IMD
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
 * Page for editing questions.
 *
 * @package    moodlecore
 * @subpackage questionbank
 * @copyright  1999 onwards Martin Dougiamas {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__FILE__) . '/../config.php');
require_once(dirname(__FILE__) . '/editlib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/formslib.php');

require_login( );

// Read URL parameters telling us which question to edit.
$id = optional_param('id', 0, PARAM_INT); // question id
$originalreturnurl = optional_param('returnurl', 0, PARAM_LOCALURL); //
$courseid = optional_param('courseid', 0, PARAM_INT); //
$makecopy = optional_param('makecopy', 0, PARAM_BOOL);
$qtype = optional_param('qtype', '', PARAM_FILE);
$categoryid = optional_param('category', 0, PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT);
$wizardnow = optional_param('wizardnow', '', PARAM_ALPHA);
$appendqnumstring = optional_param('appendqnumstring', '', PARAM_ALPHA);
$inpopup = optional_param('inpopup', 0, PARAM_BOOL);
$scrollpos = optional_param('scrollpos', 0, PARAM_INT);
$categoria = optional_param('cat', NULL, PARAM_RAW);
$qpage = optional_param('qpage', NULL, PARAM_RAW);

if( !empty($cmid) ){
	$course_modules = $DB->get_record('course_modules',array('id'=>$cmid));
	$courseid = $course_modules->course;
}

$url = new moodle_url('/question/edit_validate_question.php');
if ($id !== 0) {
    $r = $url->param('id', $id);
}
if ($makecopy) {
    $url->param('makecopy', $makecopy);
}
if ($qtype !== '') {
    $url->param('qtype', $qtype);
}
if ($categoryid !== 0) {
    $url->param('category', $categoryid);
}
if ($cmid !== 0) {
    $url->param('cmid', $cmid);
}
if ($courseid !== 0) {
    $url->param('courseid', $courseid);
}
if ($wizardnow !== '') {
    $url->param('wizardnow', $wizardnow);
}
if ($originalreturnurl !== 0) {
    $url->param('returnurl', $originalreturnurl);
}
if ($appendqnumstring !== '') {
    $url->param('appendqnumstring', $appendqnumstring);
}
if ($inpopup !== 0) {
    $url->param('inpopup', $inpopup);
}
if ($scrollpos) {
    $url->param('scrollpos', $scrollpos);
}
$PAGE->set_url($url);

if ($cmid) {
    $questionbankurl = new moodle_url('/question/edit.php', array('cmid' => $cmid));
} else {
    $questionbankurl = new moodle_url('/question/edit.php', array('courseid' => $courseid));
}
navigation_node::override_active_url($questionbankurl);

if ($originalreturnurl) {
    if (strpos($originalreturnurl, '/') !== 0) {
        throw new coding_exception("returnurl must be a local URL starting with '/'. $originalreturnurl was given.");
    }
    $returnurl = new moodle_url($originalreturnurl);
} else {
    $returnurl = $questionbankurl;
}

if ($scrollpos) {
    $returnurl->param('scrollpos', $scrollpos);
}

if ($cmid){
    list($module, $cm) = get_module_from_cmid($cmid);
    require_login($cm->course, false, $cm);
    $thiscontext = context_module::instance($cmid);
} elseif ($courseid) {
    require_login($courseid, false);
    $thiscontext = context_course::instance($courseid);
    $module = null;
    $cm = null;
} else {
    print_error('missingcourseorcmid', 'question');
}
$contexts = new question_edit_contexts($thiscontext);

$PAGE->set_pagelayout('admin');

if (optional_param('addcancel', false, PARAM_BOOL)) {
    redirect($returnurl);
}

if ($id) {
    if (!$question = $DB->get_record('question', array('id' => $id))) {
        print_error('questiondoesnotexist', 'question', $returnurl);
    }
    get_question_options($question, true);

} else if ($categoryid && $qtype) { // only for creating new questions
    $question = new stdClass();
    $question->category = $categoryid;
    $question->qtype = $qtype;
    $question->createdby = $USER->id;

    // Check that users are allowed to create this question type at the moment.
    if (!question_bank::qtype_enabled($qtype)) {
        print_error('cannotenable', 'question', $returnurl, $qtype);
    }

} else if ($categoryid) {
    // Category, but no qtype. They probably came from the addquestion.php
    // script without choosing a question type. Send them back.
    $addurl = new moodle_url('/question/addquestion.php', $url->params());
    $addurl->param('validationerror', 1);
    redirect($addurl);

} else {
    print_error('notenoughdatatoeditaquestion', 'question', $returnurl);
}

$qtypeobj = question_bank::get_qtype($question->qtype);

// Validate the question category.
if (!$category = $DB->get_record('question_categories', array('id' => $question->category))) {
    print_error('categorydoesnotexist', 'question', $returnurl);
}

// Check permissions
$question->formoptions = new stdClass();

$categorycontext = context::instance_by_id($category->contextid);
$addpermission = has_capability('moodle/question:add', $categorycontext);

if ($id) {
    $question->formoptions->canedit = question_has_capability_on($question, 'edit');
    $question->formoptions->canmove = $addpermission && question_has_capability_on($question, 'move');
    $question->formoptions->cansaveasnew = $addpermission &&
            (question_has_capability_on($question, 'view') || $question->formoptions->canedit);
    $question->formoptions->repeatelements = $question->formoptions->canedit || $question->formoptions->cansaveasnew;
    $formeditable =  $question->formoptions->canedit || $question->formoptions->cansaveasnew || $question->formoptions->canmove;
    if (!$formeditable) {
        question_require_capability_on($question, 'view');
    }
    if ($makecopy) {
        // If we are duplicating a question, add some indication to the question name.
        $question->name = get_string('questionnamecopy', 'question', $question->name);
        $question->beingcopied = true;
    }

} else  { // creating a new question
    $question->formoptions->canedit = question_has_capability_on($question, 'edit');
    $question->formoptions->canmove = (question_has_capability_on($question, 'move') && $addpermission);
    $question->formoptions->cansaveasnew = false;
    $question->formoptions->repeatelements = true;
    $formeditable = true;
    require_capability('moodle/question:add', $categorycontext);
}
$question->formoptions->mustbeusable = (bool) $appendqnumstring;

// Validate the question type.
$PAGE->set_pagetype('question-type-' . $question->qtype);

// Create the question editing form.
if ($wizardnow !== '') {
    $mform = $qtypeobj->next_wizard_form('question.php', $question, $wizardnow, $formeditable);
} else {
    $mform = $qtypeobj->create_editing_form('question.php', $question, $category, $contexts, $formeditable);
}
$toform = fullclone($question); // send the question object and a few more parameters to the form
$toform->category = "{$category->id},{$category->contextid}";
$toform->scrollpos = $scrollpos;
if ($formeditable && $id){
    $toform->categorymoveto = $toform->category;
}

$toform->appendqnumstring = $appendqnumstring;
$toform->returnurl = $originalreturnurl;
$toform->makecopy = $makecopy;
if ($cm !== null){
    $toform->cmid = $cm->id;
    $toform->courseid = $cm->course;
} else {
    $toform->courseid = $COURSE->id;
}

$toform->inpopup = $inpopup;

$mform->set_data($toform);

$streditingquestion = $qtypeobj->get_heading();
$PAGE->set_title($streditingquestion);
$PAGE->set_heading($COURSE->fullname);
$PAGE->navbar->add($streditingquestion);

// Display a heading, question editing form and possibly some extra content needed for
// for this question type.
echo $OUTPUT->header();

// $buttonvalidar = optional_param('btvalidarquestao',0,PARAM_ALPHA);
$buttonvalidar = optional_param('submitbutton',0,PARAM_ALPHA);
$buttoneditar = optional_param('bteditarquestao',0,PARAM_ALPHA);

$link = $CFG->wwwroot.'/question/question.php?returnurl=%2Fquestion%2Fedit.php%3Fcourseid%3D'.$courseid.'&courseid='.$courseid.'&id='.$id;

if($buttonvalidar){
		
	global $DB;

	$nivel      = optional_param('nivel',NULL,PARAM_INT);
	$aprovada   = optional_param('aprovada',NULL,PARAM_INT);
    $observacao = optional_param('observacao',NULL,PARAM_RAW);
	
	$sql = "UPDATE {question} SET nivel = ?, validada = ?, observacao = ? WHERE id = ?";
    $params = array($nivel, $aprovada, $observacao, $id);
    $DB->execute($sql, $params);

	redirect($CFG->wwwroot.'/question/edit.php?courseid='.$courseid.''.completar_link("cat",$categoria).''.completar_link("qpage",$qpage), 'Dados editados com sucesso !!!', 1);
}

?>

<h2>Dados da questão</h2>

<div class="container-exibir-info" style="padding:15px; background-color:#f0f6f6; margin-bottom:20px;">
<h3 style="border-bottom:2px solid #ffffff; margin-top: 0px;">Geral</h3>
<table>
	<?php if($question->name != ""){ ?>
		<tr>
			<td style='text-align:right;'><strong>Nome</strong></td>
			<td style='padding-left:10px;'><?php echo $question->name; ?></td>
		</tr>
	<?php 
		}	
		if($question->questiontext != ""){
	?>
		<tr>
			<td style='text-align:right;'><strong>Texto da questão</strong></td>
			<td style='padding-left:10px;'><?php echo strip_tags($question->questiontext); ?></td>
		</tr>
	<?php 
		}	
		if($question->qtype != ""){
	?>
	<tr>
		<td style='text-align:right;'><strong>Tipo da questão</strong></td>
		<td style='padding-left:10px;'><?php echo returnTipoQuestao($question->qtype); ?></td>
	</tr>
	<?php 
		}	
		if($question->penalty != ""){
	?>
	<tr>
		<td style='text-align:right;'><strong>Penalidade</strong></td>
		<td style='padding-left:10px;'><?php echo calcularNota($question->penalty); ?></td>
	</tr>
	<?php 
		}	
		if($question->generalfeedback != ""){
	?>
	<tr>
		<td style='text-align:right;'><strong>FeedBack geral</strong></td>
		<td style='padding-left:10px;'><?php echo strip_tags($question->generalfeedback); ?></td>
	</tr>
	<?php } ?>
</table>
</div>

<?php
	if($question->qtype != 'essay' && $question->qtype != 'description'){
?>

<div class="container-exibir-info" style="padding:15px; background-color:#f0f6f6; margin-bottom:20px;">
<h3 style="border-bottom:2px solid #ffffff; margin-top: 0px;">Respostas</h3>
<table>

<?php
	if(isset($question->options) and isset($question->options->single)){
		if($question->options->single == 0){
			echo "<tr>";
				echo "<td style='text-align:right;'><strong>Uma ou múltiplas respostas ?</strong></td>";
				echo "<td style='padding-left:10px;'>Múltiplas respostas permitidas</td>";
 			echo "</tr>";
		}
		if($question->options->single == 1){
			echo "<tr>";
				echo "<td style='text-align:right;'><strong>Uma ou múltiplas respostas ?</strong></td>";
				echo "<td style='padding-left:10px;'>Apenas uma resposta</td>";
 			echo "</tr>";
		}
	}

	// Exibir 
	if(isset($question->options->answers)){
		foreach($question->options->answers as $ans => $value){
			echo "<tr>";
				echo "<td style='text-align:right;'><strong>Escolha</strong></td>";
				echo "<td style='padding-left:10px;'>".strip_tags($value->answer)."</td>";
 			echo "</tr>";
 			echo "<tr>";
				echo "<td style='text-align:right;'><strong>Nota</strong></td>";
				echo "<td style='padding-left:10px;'>".calcularNota($value->fraction)."</td>";
 			echo "</tr>";
 			if($value->feedback != ""){
 				echo "<tr>";
					echo "<td style='text-align:right;'><strong>Feedback</strong></td>";
					echo "<td style='padding-left:10px;'>".strip_tags($value->feedback)."</td>";
	 			echo "</tr>";
 			}
 			echo "<tr><td><br></td><tr>";
		}
	}

	// Tipo associativa - mostrar as questoes
	if(isset($question->options->subquestions)){
		foreach($question->options->subquestions as $sub => $value){
			echo "<tr>";
				echo "<td style='text-align:right;'><strong>Questão</strong></td>";
				echo "<td style='padding-left:10px;'>".strip_tags($value->questiontext)."</td>";
 			echo "</tr>";
 			echo "<tr>";
				echo "<td style='text-align:right;'><strong>Resposta</strong></td>";
				echo "<td style='padding-left:10px;'>".strip_tags($value->answertext)."</td>";
 			echo "</tr>";
 			echo "<tr><td><br></td><tr>";
		}
	}

?>
</table>
</div>

<?php 	
	}	

    $edit_info = array( 'courseid' => $courseid, 'id' => $id, 'cat' => $categoria, 'qpage' => $qpage );
 
class simplehtml_form extends moodleform {

    function definition() {

        global $CFG, $edit_info, $question;
        
        $mform = $this->_form;

        $form_action = $_SERVER['PHP_SELF'];
        $form_action .= '?courseid=' . $edit_info['courseid'];
        $form_action .= '&id=' . $edit_info['id'];
        $form_action .= $this->verificar_link('cat', $edit_info['cat']);
        $form_action .= $this->verificar_link('qpage', $edit_info['qpage']);

        $mform->_attributes['action'] = $form_action;

        $urlEdicaoCompleta = $CFG->wwwroot."/question/question.php?returnurl=%2Fquestion%2Fedit.php%3Fcourseid%3D".$edit_info['courseid']."&courseid=".$edit_info['courseid']."&id=".$edit_info['id'];

        // Adiciona o Select
        $select = $mform->createElement('select', 'nivel', 'Selecionar nível');
        $mform->setDefault('nivel', $question->nivel);
        $select->addOption('Selecione', '', array('disabled' => 'disabled', 'selected' => 'selected'));
        $select->addOption('Fácil', 1);
        $select->addOption('Médio', 2);
        $select->addOption('Difícil', 3);
        $selecionarNivel = $mform->addElement($select);
        $mform->setType('nivel', PARAM_INT);
        $mform->addRule('nivel', "Você deve adicionar o nivel da questão", 'required', 'numeric', 'client');

        // Adiciona o Radiobutton 
        $radioarray = array();
        $mform->setDefault('aprovada', $question->validada);
        $radioarray[] = $mform->createElement('radio', 'aprovada', '', 'Sim', 1);
        $radioarray[] = $mform->createElement('radio', 'aprovada', '', 'Não',  2);
        $mform->addGroup($radioarray, 'radioar', 'Aprovar questão', array(' ', ' '), false);

        // Adicionar o Textarea
        $mform->setDefault('observacao', $question->observacao);
        $mform->addElement('textarea', 'observacao', 'Adicionar Observação', array('wrap' => 'virtual', 'cols' => 55));
        $mform->setType('name', PARAM_TEXT);

        // button
		$mform->addElement('submit', 'submitbutton', 'Salvar mudanças');
		
        $mform->addElement('static', 'name', html_writer::tag('a', 'Ir para edição completa', array('href'=>$urlEdicaoCompleta)));

    }

    function verificar_link($chave, $valor){
        if($valor != null){
            return "&".$chave."=".$valor;
        }
    }

}   

echo html_writer::tag('h2', 'Editar validações da questão');
echo html_writer::empty_tag('br');

$mform = new simplehtml_form();	
$mform->display();

?>

<!-- 
	Metódo para selecionar o nivel da questão pelo click do teclado 
	F - Fácil
	M - Médio
	D - Difícil
-->
<script type="text/javascript">
	document.onkeydown = checkKey;
	$("textarea").click(function(){
    	document.onkeydown = null;
    });
    function checkKey(e) {
        var nivel = document.getElementsByName("nivel");
        e = e || window.event;
        if (e.keyCode == '70') {
            nivel.item(0).options[1].selected = true;
        }
        else if (e.keyCode == '77') {
            nivel.item(0).options[2].selected = true;
        }
        else if (e.keyCode == '68') {
            nivel.item(0).options[3].selected = true;
        }  
    }
</script>

<?php
/*
 * Retorna o tipo da questão em portuques 	
 */ 
function returnTipoQuestao($questionTipo){
	switch ($questionTipo) {		
		case 'match':
			return 'Associação';
			break;

		case 'calculated':
			return 'Calculado';
			break;

		case 'calculatedsimple':
			return 'Cálculo simples';
			break;

		case 'randomsamatch':
			return 'Correspondência de resposta curta randomica';
			break;

		case 'essay':
			return 'Ensaio';
			break;

		case 'multichoice':
			return 'Múltipla escolha';
			break;

		case 'calculatedmulti':
			return 'Múltipla escolha calculada';
			break;

		case 'numerical':
			return 'Numérico';
			break;

		case 'shortanswer':
			return 'Resposta curta';
			break;

		case 'multianswer':
			return 'Respostas embutidas (cloze)';
			break;

		case 'truefalse':
			return 'Verdadeiro/Falso';
			break;

		case 'description':
			return 'Descrição';
			break;

		default:
			return $questionTipo;
			break;
	}
}

/*
 * Retorna o percentual da nota  
 */
function calcularNota($nota){
	return $nota * 100 . "%";
}

/*
 * Retorna a URL da pagina 
 */
function completar_link($tipo, $valor){
    if($valor != null){
        return "&".$tipo."=".$valor;
    }
}

echo $OUTPUT->footer();