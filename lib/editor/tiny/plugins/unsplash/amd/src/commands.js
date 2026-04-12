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
 * Commands for tiny_unsplash — registers toolbar button, opens the search dialog,
 * calls the Unsplash API, and inserts the selected image.
 *
 * @module      tiny_unsplash/commands
 * @copyright   2026 eLeDia GmbH <support@eledia.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'core/str',
    'core/templates',
    'core/notification',
    'tiny_unsplash/common',
    'tiny_unsplash/options'
], function(Str, Templates, Notification, Common, Options) {

    /** @type {string} Unsplash API base URL */
    var API_BASE = 'https://api.unsplash.com';

    /**
     * Perform a search against the Unsplash API.
     *
     * @param {string} apiKey The access key
     * @param {string} query Search term
     * @param {number} page Page number
     * @param {number} perPage Results per page
     * @param {string} orientation One of: '', 'landscape', 'portrait', 'squarish'
     * @returns {Promise<object>} The JSON response
     */
    var searchPhotos = function(apiKey, query, page, perPage, orientation) {
        var url = API_BASE + '/search/photos?query=' + encodeURIComponent(query)
            + '&page=' + page
            + '&per_page=' + perPage;

        if (orientation && orientation !== 'all') {
            url += '&orientation=' + orientation;
        }

        return fetch(url, {
            headers: {
                'Authorization': 'Client-ID ' + apiKey,
                'Accept-Version': 'v1',
            }
        }).then(function(response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        });
    };

    /**
     * Trigger the Unsplash download endpoint for attribution tracking.
     * Per Unsplash API guidelines this MUST be called when a user "downloads" / selects an image.
     *
     * @param {string} apiKey The access key
     * @param {string} downloadLocation The download_location URL from the photo object
     */
    var triggerDownload = function(apiKey, downloadLocation) {
        fetch(downloadLocation, {
            headers: {
                'Authorization': 'Client-ID ' + apiKey,
                'Accept-Version': 'v1',
            }
        }).catch(function(err) {
            window.console.warn('[tiny_unsplash] Download tracking failed:', err);
        });
    };

    /**
     * Build the HTML content for the image gallery inside the TinyMCE dialog.
     *
     * @param {object} data API response data
     * @param {string} appName Application name for UTM
     * @param {object} strings Localised strings
     * @returns {string} HTML string
     */
    var buildGalleryHtml = function(data, appName, strings) {
        if (!data.results || data.results.length === 0) {
            return '<p style="text-align:center;padding:20px;color:#666;">' + strings.noResults + '</p>';
        }

        var html = '<div class="tiny-unsplash-gallery" style="display:grid;grid-template-columns:repeat(3,1fr);'
            + 'gap:8px;max-height:400px;overflow-y:auto;padding:4px;">';

        data.results.forEach(function(photo, index) {
            var utmSuffix = '?utm_source=' + encodeURIComponent(appName) + '&utm_medium=referral';
            var userUrl = photo.user.links.html + utmSuffix;
            var photoUrl = photo.links.html + utmSuffix;
            var altText = photo.alt_description || photo.description || 'Unsplash photo';

            html += '<div class="tiny-unsplash-item" data-index="' + index + '" '
                + 'style="cursor:pointer;border:2px solid transparent;border-radius:6px;overflow:hidden;'
                + 'position:relative;transition:border-color 0.2s;">'
                + '<img src="' + photo.urls.small + '" alt="' + altText.replace(/"/g, '&quot;') + '" '
                + 'style="width:100%;height:140px;object-fit:cover;display:block;" loading="lazy" />'
                + '<div style="font-size:11px;padding:4px 6px;background:rgba(0,0,0,0.6);color:#fff;'
                + 'position:absolute;bottom:0;left:0;right:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">'
                + '<a href="' + userUrl + '" target="_blank" rel="noopener" '
                + 'style="color:#ddd;text-decoration:underline;" onclick="event.stopPropagation();">'
                + (photo.user.name || 'Unknown') + '</a>'
                + ' / <a href="' + photoUrl + '" target="_blank" rel="noopener" '
                + 'style="color:#ddd;text-decoration:underline;" onclick="event.stopPropagation();">Unsplash</a>'
                + '</div></div>';
        });

        html += '</div>';

        // Pagination controls.
        var totalPages = Math.ceil(data.total / data.results.length) || 1;
        // We don't know the current page from the response, so the caller will handle it.
        html += '<div class="tiny-unsplash-pagination" style="display:flex;justify-content:center;'
            + 'align-items:center;gap:12px;padding:10px 0;"></div>';

        return html;
    };

    /**
     * Open the Unsplash image search dialog.
     *
     * @param {TinyMCE.Editor} editor
     */
    var openUnsplashDialog = function(editor) {
        var apiKey = Options.getApiKey(editor);
        var appName = Options.getAppName(editor);
        var perPage = Options.getPerPage(editor);
        var currentPage = 1;
        var currentQuery = '';
        var currentOrientation = 'all';
        var currentResults = null;
        var selectedIndex = -1;
        var dialogApi = null;

        // Collect all needed strings up-front.
        Str.get_strings([
            {key: 'dialog_title', component: Common.component},
            {key: 'search_placeholder', component: Common.component},
            {key: 'search_button', component: Common.component},
            {key: 'no_results', component: Common.component},
            {key: 'loading', component: Common.component},
            {key: 'insert', component: Common.component},
            {key: 'orientation_all', component: Common.component},
            {key: 'orientation_landscape', component: Common.component},
            {key: 'orientation_portrait', component: Common.component},
            {key: 'orientation_squarish', component: Common.component},
            {key: 'prev_page', component: Common.component},
            {key: 'next_page', component: Common.component},
            {key: 'error_no_apikey', component: Common.component},
            {key: 'photo_by', component: Common.component},
            {key: 'on_unsplash', component: Common.component},
            {key: 'alt_text', component: Common.component},
        ]).then(function(strings) {
            var s = {
                title: strings[0],
                placeholder: strings[1],
                searchBtn: strings[2],
                noResults: strings[3],
                loading: strings[4],
                insertBtn: strings[5],
                orientAll: strings[6],
                orientLandscape: strings[7],
                orientPortrait: strings[8],
                orientSquarish: strings[9],
                prevPage: strings[10],
                nextPage: strings[11],
                errorNoKey: strings[12],
                photoBy: strings[13],
                onUnsplash: strings[14],
                altText: strings[15],
            };

            if (!apiKey) {
                Notification.alert(s.title, s.errorNoKey);
                return;
            }

            /**
             * Perform a search and update the dialog HTML panel.
             */
            var doSearch = function() {
                if (!currentQuery.trim()) {
                    return;
                }
                selectedIndex = -1;

                // Show loading state.
                dialogApi.setData({
                    galleryhtml: '<p style="text-align:center;padding:40px;color:#666;">'
                        + '<span class="spinner-border spinner-border-sm" role="status"></span> '
                        + s.loading + '</p>',
                });

                searchPhotos(apiKey, currentQuery, currentPage, perPage, currentOrientation)
                    .then(function(data) {
                        currentResults = data.results || [];
                        var totalPages = Math.ceil((data.total || 0) / perPage) || 1;

                        var galleryHtml = buildGalleryHtml(data, appName, s);

                        // Add pagination buttons into the HTML.
                        var paginationHtml = '<div style="display:flex;justify-content:center;'
                            + 'align-items:center;gap:12px;padding:10px 0;">';
                        if (currentPage > 1) {
                            paginationHtml += '<button type="button" class="btn btn-sm btn-secondary '
                                + 'tiny-unsplash-prev">' + s.prevPage + '</button>';
                        }
                        paginationHtml += '<span style="color:#666;">' + currentPage + ' / ' + totalPages + '</span>';
                        if (currentPage < totalPages) {
                            paginationHtml += '<button type="button" class="btn btn-sm btn-secondary '
                                + 'tiny-unsplash-next">' + s.nextPage + '</button>';
                        }
                        paginationHtml += '</div>';

                        dialogApi.setData({
                            galleryhtml: galleryHtml + paginationHtml,
                        });

                        // After DOM update, attach click handlers.
                        setTimeout(function() {
                            attachGalleryHandlers();
                        }, 100);
                    })
                    .catch(function(err) {
                        dialogApi.setData({
                            galleryhtml: '<p style="text-align:center;padding:20px;color:red;">'
                                + 'Error: ' + err.message + '</p>',
                        });
                    });
            };

            /**
             * Attach click handlers to gallery items and pagination buttons.
             */
            var attachGalleryHandlers = function() {
                // Find the dialog DOM element.
                var dialogEl = document.querySelector('.tox-dialog');
                if (!dialogEl) {
                    return;
                }

                // Image selection.
                var items = dialogEl.querySelectorAll('.tiny-unsplash-item');
                items.forEach(function(item) {
                    item.addEventListener('click', function() {
                        // Remove previous selection.
                        items.forEach(function(el) {
                            el.style.borderColor = 'transparent';
                        });
                        item.style.borderColor = '#0073aa';
                        selectedIndex = parseInt(item.getAttribute('data-index'), 10);
                    });
                });

                // Pagination.
                var prevBtn = dialogEl.querySelector('.tiny-unsplash-prev');
                var nextBtn = dialogEl.querySelector('.tiny-unsplash-next');
                if (prevBtn) {
                    prevBtn.addEventListener('click', function() {
                        if (currentPage > 1) {
                            currentPage--;
                            doSearch();
                        }
                    });
                }
                if (nextBtn) {
                    nextBtn.addEventListener('click', function() {
                        currentPage++;
                        doSearch();
                    });
                }
            };

            // Open the TinyMCE dialog.
            dialogApi = editor.windowManager.open({
                title: s.title,
                size: 'large',
                body: {
                    type: 'panel',
                    items: [
                        {
                            type: 'bar',
                            items: [
                                {
                                    type: 'input',
                                    name: 'searchquery',
                                    placeholder: s.placeholder,
                                    maximized: true,
                                },
                                {
                                    type: 'selectbox',
                                    name: 'orientation',
                                    items: [
                                        {text: s.orientAll, value: 'all'},
                                        {text: s.orientLandscape, value: 'landscape'},
                                        {text: s.orientPortrait, value: 'portrait'},
                                        {text: s.orientSquarish, value: 'squarish'},
                                    ],
                                },
                            ],
                        },
                        {
                            type: 'htmlpanel',
                            name: 'galleryhtml',
                            html: '<p style="text-align:center;padding:40px;color:#999;">'
                                + s.placeholder + '</p>',
                        },
                        {
                            type: 'input',
                            name: 'alttext',
                            label: s.altText,
                            placeholder: s.altText,
                        },
                    ],
                },
                buttons: [
                    {
                        type: 'cancel',
                        name: 'cancel',
                        text: 'Cancel',
                    },
                    {
                        type: 'submit',
                        name: 'insert',
                        text: s.insertBtn,
                        primary: true,
                    },
                ],
                initialData: {
                    searchquery: '',
                    orientation: 'all',
                    galleryhtml: '',
                    alttext: '',
                },
                onChange: function(api, details) {
                    if (details.name === 'orientation') {
                        currentOrientation = api.getData().orientation;
                        if (currentQuery.trim()) {
                            currentPage = 1;
                            doSearch();
                        }
                    }
                    if (details.name === 'searchquery') {
                        currentQuery = api.getData().searchquery;
                    }
                },
                onSubmit: function(api) {
                    if (selectedIndex < 0 || !currentResults || !currentResults[selectedIndex]) {
                        return;
                    }

                    var photo = currentResults[selectedIndex];
                    var data = api.getData();
                    var altText = data.alttext || photo.alt_description || photo.description || '';
                    var utmSuffix = '?utm_source=' + encodeURIComponent(appName) + '&utm_medium=referral';

                    // Trigger download tracking as required by Unsplash API guidelines.
                    triggerDownload(apiKey, photo.links.download_location);

                    // Build the <img> with proper attribution as a <figure>.
                    var userUrl = photo.user.links.html + utmSuffix;
                    var photoUrl = photo.links.html + utmSuffix;
                    var imgUrl = photo.urls.regular;

                    var html = '<figure class="tiny-unsplash-figure">'
                        + '<img src="' + imgUrl + '" alt="' + altText.replace(/"/g, '&quot;') + '" '
                        + 'style="max-width:100%;height:auto;" />'
                        + '<figcaption style="font-size:12px;color:#666;">'
                        + s.photoBy + ' <a href="' + userUrl + '" target="_blank" rel="noopener">'
                        + (photo.user.name || 'Unknown') + '</a> '
                        + s.onUnsplash.toLowerCase().replace('on', s.onUnsplash.charAt(0) === 'o' ? 'on' : s.onUnsplash)
                        + ' <a href="' + photoUrl + '" target="_blank" rel="noopener">Unsplash</a>'
                        + '</figcaption></figure>';

                    editor.insertContent(html);
                    api.close();
                },
            });

            // Handle Enter key in search field to trigger search.
            // We need a small workaround since TinyMCE dialog inputs don't have a direct "onEnter" event.
            setTimeout(function() {
                var dialogEl = document.querySelector('.tox-dialog');
                if (!dialogEl) {
                    return;
                }
                var searchInput = dialogEl.querySelector('input[placeholder="' + s.placeholder + '"]');
                if (searchInput) {
                    searchInput.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            currentQuery = dialogApi.getData().searchquery;
                            currentPage = 1;
                            doSearch();
                        }
                    });
                    // Focus the search input.
                    searchInput.focus();
                }
            }, 200);

            return s;
        }).catch(Notification.exception);
    };

    /**
     * Get the setup function that registers the toolbar button and menu item.
     *
     * @returns {Promise<Function>}
     */
    var getSetup = function() {
        return Str.get_string('button_unsplash', Common.component)
            .then(function(buttonLabel) {
                return function(editor) {
                    // Register toolbar button.
                    editor.ui.registry.addButton(Common.pluginButtonName, {
                        icon: 'image',
                        tooltip: buttonLabel,
                        onAction: function() {
                            openUnsplashDialog(editor);
                        },
                    });

                    // Register menu item.
                    editor.ui.registry.addMenuItem(Common.pluginMenuItem, {
                        icon: 'image',
                        text: buttonLabel,
                        onAction: function() {
                            openUnsplashDialog(editor);
                        },
                    });
                };
            });
    };

    return {
        getSetup: getSetup,
    };
});
