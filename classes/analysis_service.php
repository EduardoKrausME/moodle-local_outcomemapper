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
 * Outcome Mapper analysis_service.php.
 *
 * @package   local_outcomemapper
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_outcomemapper;

use context_course;
use moodle_exception;
use Throwable;

/**
 * Orchestrate deterministic collection, AI mapping and persistence.
 *
 * @package   local_outcomemapper
 */
class analysis_service {
    /** @var mapping_repository Repository. */
    private mapping_repository $repository;

    /** @var ai_mapper Mapper. */
    private ai_mapper $mapper;

    /**
     * Constructor.
     *
     * @param mapping_repository|null $repository Repository.
     * @param ai_mapper|null $mapper Mapper.
     */
    public function __construct(?mapping_repository $repository = null, ?ai_mapper $mapper = null) {
        $this->repository = $repository ?? new mapping_repository();
        $this->mapper = $mapper ?? new ai_mapper();
    }

    /**
     * Run an analysis for current user.
     *
     * @param int $courseid Course id.
     * @param array $options Selection options.
     * @return int Analysis id.
     */
    public function run(int $courseid, array $options): int {
        global $USER;

        $context = context_course::instance($courseid);
        require_capability('local/outcomemapper:analyse', $context);

        $collector = new course_collector($courseid);
        $catalog = $collector->collect(
            $options['sectionids'] ?? [],
            $options['cmids'] ?? [],
            !empty($options['includecompetencies']),
            !empty($options['includeoutcomes']),
            !empty($options['includequestions']),
            (string)($options['additionalobjectives'] ?? '')
        );

        if (!$catalog['objectives']) {
            throw new moodle_exception('noobjectives', 'local_outcomemapper');
        }
        if (!$catalog['targets']) {
            throw new moodle_exception('notargets', 'local_outcomemapper');
        }

        $analysisid = $this->repository->create_analysis($courseid, (int)$USER->id, $catalog);

        try {
            $mapped = $this->mapper->map($catalog['objectives'], $catalog['targets']);
            $this->repository->complete_analysis(
                $analysisid,
                $courseid,
                $mapped['rows'],
                (int)$mapped['batches']
            );
        } catch (Throwable $e) {
            $this->repository->fail_analysis($analysisid);
            throw $e;
        }

        return $analysisid;
    }
}
