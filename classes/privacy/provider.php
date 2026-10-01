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
 * Outcome Mapper provider.php.
 *
 * @package   local_outcomemapper
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_outcomemapper\privacy;

use context;
use context_course;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\plugin\provider as plugin_provider;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for local_outcomemapper.
 *
 * The plugin stores only teacher/admin user ids related to running or reviewing
 * analyses. It does not store student performance or student names.
 *
 * @package   local_outcomemapper
 */
class provider implements
    \core_privacy\local\metadata\provider,
    plugin_provider,
    core_userlist_provider {
    /**
     * Describe stored personal data.
     *
     * @param collection $collection Collection.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'local_outcomemapper_analysis',
            [
                'userid' => 'privacy:metadata:analysis:userid',
            ],
            'privacy:metadata:analysis'
        );

        $collection->add_database_table(
            'local_outcomemapper_suggest',
            [
                'reviewedby' => 'privacy:metadata:suggest:reviewedby',
            ],
            'privacy:metadata:suggest'
        );

        $collection->add_database_table(
            'local_outcomemapper_map',
            [
                'confirmedby' => 'privacy:metadata:map:confirmedby',
            ],
            'privacy:metadata:map'
        );

        return $collection;
    }

    /**
     * Find course contexts containing data for a user.
     *
     * @param int $userid User id.
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT ctx.id
                  FROM {context} ctx
                  JOIN {local_outcomemapper_analysis} a ON a.courseid = ctx.instanceid
                 WHERE ctx.contextlevel = :contextlevel1
                   AND a.userid = :userid1
                UNION
                SELECT ctx.id
                  FROM {context} ctx
                  JOIN {local_outcomemapper_suggest} s ON s.courseid = ctx.instanceid
                 WHERE ctx.contextlevel = :contextlevel2
                   AND s.reviewedby = :userid2
                UNION
                SELECT ctx.id
                  FROM {context} ctx
                  JOIN {local_outcomemapper_map} m ON m.courseid = ctx.instanceid
                 WHERE ctx.contextlevel = :contextlevel3
                   AND m.confirmedby = :userid3";

        $contextlist->add_from_sql($sql, [
            'contextlevel1' => CONTEXT_COURSE,
            'userid1' => $userid,
            'contextlevel2' => CONTEXT_COURSE,
            'userid2' => $userid,
            'contextlevel3' => CONTEXT_COURSE,
            'userid3' => $userid,
        ]);

        return $contextlist;
    }

    /**
     * Export user-related analysis metadata and review actions.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        $userid = (int)$contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_course) {
                continue;
            }

            $analyses = array_values($DB->get_records('local_outcomemapper_analysis', [
                'courseid' => $context->instanceid,
                'userid' => $userid,
            ], 'timecreated ASC', 'id,status,objectivecount,targetcount,batches,timecreated,timemodified'));

            foreach ($analyses as $analysis) {
                $analysis->timecreated = transform::datetime($analysis->timecreated);
                $analysis->timemodified = transform::datetime($analysis->timemodified);
            }

            $reviews = array_values($DB->get_records('local_outcomemapper_suggest', [
                'courseid' => $context->instanceid,
                'reviewedby' => $userid,
            ], 'timereviewed ASC',
                'id,objectivetitle,targettitle,relation,confidence,status,timereviewed'));

            foreach ($reviews as $review) {
                $review->timereviewed = transform::datetime($review->timereviewed);
            }

            $confirmed = array_values($DB->get_records('local_outcomemapper_map', [
                'courseid' => $context->instanceid,
                'confirmedby' => $userid,
            ], 'timecreated ASC',
                'id,objectivetitle,targettitle,relation,confidence,timecreated,timemodified'));

            foreach ($confirmed as $mapping) {
                $mapping->timecreated = transform::datetime($mapping->timecreated);
                $mapping->timemodified = transform::datetime($mapping->timemodified);
            }

            $writer = writer::with_context($context);
            if ($analyses) {
                $writer->export_data(
                    [get_string('privacy:path:analysis', 'local_outcomemapper')],
                    (object)['records' => $analyses]
                );
            }
            if ($reviews) {
                $writer->export_data(
                    [get_string('privacy:path:reviews', 'local_outcomemapper')],
                    (object)['records' => $reviews]
                );
            }
            if ($confirmed) {
                $writer->export_data(
                    [get_string('privacy:path:confirmed', 'local_outcomemapper')],
                    (object)['records' => $confirmed]
                );
            }
        }
    }

    /**
     * Anonymise all user references in a course context while preserving shared mappings.
     *
     * @param context $context Context.
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        global $DB;

        if (!$context instanceof context_course) {
            return;
        }

        $DB->set_field('local_outcomemapper_analysis', 'userid', 0, ['courseid' => $context->instanceid]);
        $DB->set_field('local_outcomemapper_suggest', 'reviewedby', 0, ['courseid' => $context->instanceid]);
        $DB->set_field('local_outcomemapper_map', 'confirmedby', 0, ['courseid' => $context->instanceid]);
    }

    /**
     * Anonymise one user's references in approved course contexts.
     *
     * @param approved_contextlist $contextlist Approved contexts.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        $userid = (int)$contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof context_course) {
                continue;
            }

            self::anonymise_user_in_course((int)$context->instanceid, $userid);
        }
    }

    /**
     * Add users who have personal references in this course context.
     *
     * @param userlist $userlist User list.
     */
    public static function get_users_in_context(userlist $userlist): void {
        $context = $userlist->get_context();
        if (!$context instanceof context_course) {
            return;
        }

        $courseid = (int)$context->instanceid;

        $userlist->add_from_sql(
            'userid',
            "SELECT userid
               FROM {local_outcomemapper_analysis}
              WHERE courseid = :courseid AND userid <> 0",
            ['courseid' => $courseid]
        );

        $userlist->add_from_sql(
            'reviewedby',
            "SELECT reviewedby
               FROM {local_outcomemapper_suggest}
              WHERE courseid = :courseid AND reviewedby <> 0",
            ['courseid' => $courseid]
        );

        $userlist->add_from_sql(
            'confirmedby',
            "SELECT confirmedby
               FROM {local_outcomemapper_map}
              WHERE courseid = :courseid AND confirmedby <> 0",
            ['courseid' => $courseid]
        );
    }

    /**
     * Anonymise approved users in a course context.
     *
     * @param approved_userlist $userlist Approved users.
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof context_course) {
            return;
        }

        $userids = $userlist->get_userids();
        if (!$userids) {
            return;
        }

        [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'uid');
        $params = ['courseid' => (int)$context->instanceid] + $inparams;

        $DB->set_field_select(
            'local_outcomemapper_analysis',
            'userid',
            0,
            "courseid = :courseid AND userid {$insql}",
            $params
        );
        $DB->set_field_select(
            'local_outcomemapper_suggest',
            'reviewedby',
            0,
            "courseid = :courseid AND reviewedby {$insql}",
            $params
        );
        $DB->set_field_select(
            'local_outcomemapper_map',
            'confirmedby',
            0,
            "courseid = :courseid AND confirmedby {$insql}",
            $params
        );
    }

    /**
     * Anonymise one user in one course.
     *
     * @param int $courseid Course id.
     * @param int $userid User id.
     */
    private static function anonymise_user_in_course(int $courseid, int $userid): void {
        global $DB;

        $DB->set_field('local_outcomemapper_analysis', 'userid', 0, [
            'courseid' => $courseid,
            'userid' => $userid,
        ]);
        $DB->set_field('local_outcomemapper_suggest', 'reviewedby', 0, [
            'courseid' => $courseid,
            'reviewedby' => $userid,
        ]);
        $DB->set_field('local_outcomemapper_map', 'confirmedby', 0, [
            'courseid' => $courseid,
            'confirmedby' => $userid,
        ]);
    }
}
