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
 * Outcome Mapper access helper.
 *
 * @package   local_outcomemapper
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_outcomemapper;

use cm_info;

/**
 * Access checks for course objects inspected by Outcome Mapper.
 *
 * @package   local_outcomemapper
 */
class access_helper {
    /**
     * Check whether the current user may inspect a course module definition.
     *
     * cm_info::uservisible is the primary Moodle availability/visibility gate. For
     * modules with a discoverable read/view capability, this method also requires
     * that capability so a crafted POST cannot bypass module-specific access.
     *
     * @param cm_info $cm Course module info.
     * @return bool
     */
    public static function can_access_cm(cm_info $cm): bool {
        if (!$cm->uservisible) {
            return false;
        }

        $candidates = match ($cm->modname) {
            'book' => ['mod/book:read'],
            'forum' => ['mod/forum:viewdiscussion'],
            'quiz' => ['mod/quiz:view', 'mod/quiz:manage'],
            default => ['mod/' . $cm->modname . ':view'],
        };

        $existing = [];
        foreach ($candidates as $capability) {
            if (get_capability_info($capability)) {
                $existing[] = $capability;
            }
        }

        // Third-party modules do not have to follow a :view naming convention.
        // In that case, Moodle's own cm_info::uservisible remains authoritative.
        if (!$existing) {
            return true;
        }

        return has_any_capability($existing, $cm->context);
    }
}
