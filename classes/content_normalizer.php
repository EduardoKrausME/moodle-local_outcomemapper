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
 * Outcome Mapper content_normalizer.php.
 *
 * @package   local_outcomemapper
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_outcomemapper;

use core_text;

/**
 * Normalize course-owned text before semantic analysis.
 *
 * @package   local_outcomemapper
 * @copyright 2026 Eduardo Kraus
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content_normalizer {
    /**
     * Convert HTML or editor content to compact plain text.
     *
     * @param string|null $value Raw value.
     * @param int|null $limit Character limit.
     * @return string
     */
    public static function plain_text(?string $value, ?int $limit = null): string {
        if ($value === null || $value === '') {
            return '';
        }

        $value = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', ' ', $value) ?? $value;
        $value = str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>', '</li>'], "\n", $value);
        $value = strip_tags($value);
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/[\x{00A0}\t ]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\R{3,}/u', "\n\n", $value) ?? $value;
        $value = trim($value);

        if ($limit !== null && $limit > 0 && core_text::strlen($value) > $limit) {
            $value = rtrim(core_text::substr($value, 0, $limit - 1)) . '…';
        }

        return $value;
    }

    /**
     * Normalize a list of text fragments without repeating empty values.
     *
     * @param array $parts Text fragments.
     * @param int $limit Character limit.
     * @return string
     */
    public static function join(array $parts, int $limit): string {
        $normalized = [];
        foreach ($parts as $part) {
            $text = self::plain_text((string)$part);
            if ($text !== '' && !in_array($text, $normalized, true)) {
                $normalized[] = $text;
            }
        }

        return self::plain_text(implode("\n\n", $normalized), $limit);
    }
}
