<?php
// This file is based on part of Moodle - http://moodle.org/
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
 * Process ajax requests in the editor related to screen record
 *
 * @copyright Geiser Chalco
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mod_vpl
 */
if (!defined('AJAX_SCRIPT')) {
    define('AJAX_SCRIPT', true);
}

require(__DIR__.'/../../config.php');

$return = array();
$userids = required_param('userids', PARAM_RAW);

$survey_id = 1;
$dreamer_quest_id = 20;
$quest_choiceid_block = array();
$quest_choiceid_block['dreamer'] = array(198, 180, 175, 187);
$quest_choiceid_block['achiever'] = array(174, 179, 176, 200, 189, 177, 178);
$quest_choiceid_block['socializer'] = array(196, 199, 201, 188, 186, 190, 197);


for ($userids as $userid) {
    if (!$DB->record_exists('questionnaire_response',
        array('username'=>$userid, 'complete'=>'y'))) continue;

    for ($quest_choiceid_block as $group=>$quest_choiceids) {
        $accum = 0;
        $values = $DB->get_fieldset_sql('SELECT resp_r.rank FROM {questionnaire_response_rank} resp_r
            INNER JOIN {questionnaire_response} resp ON resp.id = resp_r.response_id
            WHERE resp_r.choice_id IN ('.implode(',', $quest_choiceids).') AND
                resp.survey_id = :survey_id AND
                resp.username = :userid', array('survey_id'=>$survey_id, 'userid'=>$userid));

        for ($values as $value) { $accum = $accum + (float)$value; }
        
        if ($group != 'dreamer') {
            $accum = $accum / count($values);
        } else {
            $value = (float)$DB->get_field_sql('SELECT choice.value
                FROM {questionnaire_quest_choice} choice
                INNER JOIN {questionnaire_resp_single} resp_s ON resp_s.id = choice.choice_id 
                INNER JOIN {questionnaire_response} resp ON resp.id = resp_s.response_id 
                WHERE choice.question_id = :question_id AND
                    resp.survey_id = :survey_id AND
                    resp.username = :userid',
                    array('question_id'=>$dreamer_quest_id,
                          'survey_id'=>$survey_id, 'userid'=>$userid)); 
            $accum = $accum + $value;
            $accum = $accum / (count($values) + 1);
        }

        if (empty($return[$group])) $return[$group] = array();
        if ($accum >= 3.0 && !in_array($userid, $return[$group])) $return[$group][] = $userid;
    }
}

echo json_encode($return);
die;
