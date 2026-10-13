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
 * Remapping persistent objective and target identifiers after course restore.
 *
 * @package   local_outcomemapper
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_outcomemapper;

/**
 * Never reuse source-site IDs when an object has no valid restore mapping.
 */
final class restore_key_mapper {
    /**
     * Resolve persisted keys through the restore ID map.
     *
     * @param string $objectivekey Old objective identifier.
     * @param string $targetkey Old target identifier.
     * @param callable $lookup Takes (itemname, oldid), returns new ID or zero.
     * @return array|null Pair of remapped keys, or null when any reference is unresolved.
     */
    public static function remap(string $objectivekey, string $targetkey, callable $lookup): ?array {
        $objective = self::objective($objectivekey, $lookup);
        $target = self::target($targetkey, $lookup);
        if ($objective === null || $target === null) {
            return null;
        }
        return [$objective, $target];
    }

    /**
     * @param string $key Objective key.
     * @param callable $lookup Restore id lookup.
     * @return string|null
     */
    private static function objective(string $key, callable $lookup): ?string {
        if (preg_match('/^custom:[a-zA-Z0-9]+$/D', $key)) {
            return $key;
        }

        if (preg_match('/^(outcome|competency):([1-9][0-9]*)$/D', $key, $matches)) {
            $newid = (int)$lookup($matches[1], (int)$matches[2]);
            return $newid > 0 ? $matches[1] . ':' . $newid : null;
        }
        return null;
    }

    /**
     * @param string $key Target key.
     * @param callable $lookup Restore id lookup.
     * @return string|null
     */
    private static function target(string $key, callable $lookup): ?string {
        if (preg_match('/^cm:([1-9][0-9]*)$/D', $key, $matches)) {
            $newid = (int)$lookup('course_module', (int)$matches[1]);
            return $newid > 0 ? 'cm:' . $newid : null;
        }
        if (preg_match('/^section:([1-9][0-9]*)$/D', $key, $matches)) {
            $newid = (int)$lookup('course_section', (int)$matches[1]);
            return $newid > 0 ? 'section:' . $newid : null;
        }
        if (preg_match('/^question:([1-9][0-9]*):quiz:([1-9][0-9]*)$/D', $key, $matches)) {
            $newquestionid = (int)$lookup('question', (int)$matches[1]);
            $newquizid = (int)$lookup('quiz', (int)$matches[2]);
            if ($newquestionid <= 0 || $newquizid <= 0) {
                return null;
            }
            return 'question:' . $newquestionid . ':quiz:' . $newquizid;
        }
        return null;
    }
}
