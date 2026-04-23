// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Web service wrapper for tiny_unsplash. Provider-agnostic — calls the Moodle
 * external functions which proxy the actual outbound API request server-side.
 *
 * @module      tiny_unsplash/api
 * @copyright   2026 eLeDia GmbH <support@eledia.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/ajax'], function(Ajax) {

    /**
     * Search images via the Pexels or Pixabay proxy.
     *
     * @param {string} provider 'pexels' | 'pixabay'
     * @param {string} query
     * @param {number} page
     * @param {number} perpage
     * @param {string} orientation
     * @param {number} contextid
     * @returns {Promise<object>}
     */
    var searchImages = function(provider, query, page, perpage, orientation, contextid) {
        return Ajax.call([{
            methodname: 'tiny_unsplash_search_images',
            args: {
                provider: provider,
                query: query,
                page: page,
                perpage: perpage,
                orientation: orientation,
                contextid: contextid,
            },
        }])[0];
    };

    /**
     * Server-side download of a stock image into the user's draft area.
     * Required for Pixabay (no hotlinking allowed); optional for Pexels.
     *
     * @param {string} provider
     * @param {string} id
     * @param {number} contextid
     * @param {number} draftitemid Optional existing draftitemid (0 to mint a new one).
     * @returns {Promise<object>}
     */
    var saveImage = function(provider, id, contextid, draftitemid) {
        return Ajax.call([{
            methodname: 'tiny_unsplash_save_image',
            args: {
                provider: provider,
                id: id,
                contextid: contextid,
                draftitemid: draftitemid || 0,
            },
        }])[0];
    };

    return {
        searchImages: searchImages,
        saveImage: saveImage,
    };
});
