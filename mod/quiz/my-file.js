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
 * JavaScript library for the quiz module.
 *
 * @package    
 * @subpackage 
 * @copyright  
 * @license    
 */

var activities = document.getElementById('id_activitySelector');
console.log(activities);
activities.addEventListener('change', function() { 
    var cmid = document.getElementById('cmid').value;
    var idCategoria = this.value; 
    window.location.href = 'addrandom.php?returnurl={$url}/mod/quiz/edit.php?cmid='+cmid+'&amp;data-addonpage=0&cmid='+cmid+'&appendqnumstring=addarandomquestion&idCategory='+idCategoria;
});

var urlPagina = document.URL;
var idPagina = urlPagina.lastIndexOf('='); 
document.getElementById('id_category').value = urlPagina.substring(idPagina+1);