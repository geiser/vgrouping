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
 * @package block_vgrouping
 */
if (!defined('AJAX_SCRIPT')) {
    define('AJAX_SCRIPT', true);
}

require(__DIR__.'/../../config.php');

$return = array();
$action = required_param('action', PARAM_ALPHA);
$userids = required_param('userids', PARAM_RAW);

function is_right_part_valid_exp($str) {
    return (bool)preg_match('/^\s*(<|>|<=|>=|==)\s*[0-9]+\s*$/',$str);
}

if ($action == 'groupingbyqpj') {

    $limit = required_param('limit', PARAM_FLOAT) + 2.0;

    $survey_id = 1;
    $dreamer_quest_id = 20;
    $quest_choiceid_block = array();
    $quest_choiceid_block['dreamer'] = array(198, 180, 175, 187);
    $quest_choiceid_block['achiever'] = array(174, 179, 176, 200, 189, 177, 178);
    $quest_choiceid_block['socializer'] = array(196, 199, 201, 188, 186, 190, 197);

    foreach ($userids as $userid) {
        if (!$DB->record_exists('questionnaire_response',
            array('username'=>$userid, 'complete'=>'y'))) continue;

        foreach ($quest_choiceid_block as $group=>$quest_choiceids) {
            $accum = 0;
            // obtain values from the questionnaire-choice ids by each possible group 
            $values = $DB->get_fieldset_sql('SELECT resp_r.rank
                FROM {questionnaire_response_rank} resp_r
                INNER JOIN {questionnaire_response} resp ON resp.id = resp_r.response_id
                WHERE resp_r.choice_id IN ('.implode(',', $quest_choiceids).') AND
                    resp.survey_id = :survey_id AND
                    resp.username = :userid', array('survey_id'=>$survey_id, 'userid'=>$userid));
        
            // obtain accumulation
            foreach ($values as $value) {
                $accum = $accum + (float)$value;
            }

            // if the group is dreamer, add value from the single response
            if ($group != 'dreamer') {
                $accum = $accum / count($values);
            } else {
                $value = (float)$DB->get_field_sql('SELECT choice.value
                    FROM {questionnaire_quest_choice} choice
                    INNER JOIN {questionnaire_resp_single} resp_s ON resp_s.choice_id = choice.id 
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
            if (($accum >= $limit)  && !in_array($userid, $return[$group])) {
                $return[$group][] = $userid;
            }
        }
    }
} else if ($action == 'groupingbygrade') {



    foreach ($rules as $rule) {
        $potential_users = array();
        if (empty($userids) || empty($rule->itemids)) continue;
        
        foreach ($userids as $userid) {
            $is_potential_user = false;
            $grades = $DB->get_records('SELECT * FROM {grade_grades}
                WHERE userid = :userid AND itemid IN ('.implode(',', $rule->itemids).')', array('userid'=>$userid));
            
            if ($rule->operation == 'average') {
                // the average of final-grade for all item ids must evaluate
                // with the right part of expression that is passed as argument
                $sum = 0; 
                foreach ($grades as $grade) $sum += (float)$grade->finalgrade;
                
                $right_part_exp = array_values($rule->expressions)[0];
                if (!is_right_part_valid_exp($right_part_exp)) {
                    throw new Exception('Invalid expression! value '.$right_part_exp.' is invalid.');
                }

                eval('$is_potential_users = ('.$sum/count($grades).$right_part_exp.');');
            } else if ($rule->operation == 'or') {
                // potential users are who satisfy one expression
                $iditems
                foreach ($rule->expressions as $right_exp) {
                    if (!is_right_part_valid_exp($right_part_exp)) {
                        throw new Exception('Invalid expression! value '.$right_part_exp.' is invalid.');
                    }
                    $grades[$idgrade]
                }
                while (!$is_potential_users && list($idgrade, $grade) = each($grades)) {

                }
            } else if ($rule->operation == 'and') { 
                // potential users are who satisfy all expression
                while (!$is_potential_users && ) {
                }
            }

            if ($is_potential_user) $potential_users[] = $userid;
        }

        $return[$rule->groupname] = $potential_users;
    }

    /*
    $rules = required_param('rules', PARAM_RAW);
    foreach ($rules as $rule) {
        $groupname = $rule->groupname;
        $rule->operation 

        if (is_valid_expr) {
        } 
        eval( );
    }
     */
}

function get_cond_expression($left_part, $right_part, $format = 'php') {
    $result = '';
    return $result;
}

echo json_encode($return);
die;
