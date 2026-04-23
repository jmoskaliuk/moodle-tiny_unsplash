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
 * Commands for tiny_unsplash — toolbar button + dialog. Supports three providers:
 * Unsplash (direct, hotlink), Pexels (server-proxied, hotlink) and Pixabay
 * (server-proxied, mandatory download to draft area).
 *
 * @module      tiny_unsplash/commands
 * @copyright   2026 eLeDia GmbH <support@eledia.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'core/str',
    'core/notification',
    'tiny_unsplash/common',
    'tiny_unsplash/options',
    'tiny_unsplash/api'
], function(Str, Notification, Common, Options, Api) {

    var UNSPLASH_API = 'https://api.unsplash.com';

    // -------------------------------------------------------------------------
    // Unsplash (direct).
    // -------------------------------------------------------------------------
    var unsplashSearch = function(apiKey, query, page, perPage, orientation) {
        var url = UNSPLASH_API + '/search/photos?query=' + encodeURIComponent(query)
            + '&page=' + page + '&per_page=' + perPage;
        if (orientation && orientation !== 'all') {
            url += '&orientation=' + orientation;
        }
        return fetch(url, {
            headers: {
                'Authorization': 'Client-ID ' + apiKey,
                'Accept-Version': 'v1',
            },
        }).then(function(r) {
            if (!r.ok) {
                throw new Error('HTTP ' + r.status);
            }
            return r.json();
        }).then(function(data) {
            // Normalise to the shape the gallery code expects.
            var results = (data.results || []).map(function(photo) {
                return {
                    provider: 'unsplash',
                    id: String(photo.id),
                    thumbnailurl: photo.urls.small,
                    previewurl: photo.urls.small,
                    fullurl: photo.urls.regular,
                    originalurl: photo.urls.full,
                    authorname: (photo.user && photo.user.name) || '',
                    authorurl: (photo.user && photo.user.links && photo.user.links.html) || '',
                    sourceurl: (photo.links && photo.links.html) || '',
                    alttext: photo.alt_description || photo.description || '',
                    width: photo.width,
                    height: photo.height,
                    license: 'unsplash-license',
                    // Carry through for the post-select download tracking call.
                    _downloadLocation: photo.links && photo.links.download_location,
                };
            });
            return {results: results, total: data.total || 0, page: page, perpage: perPage};
        });
    };

    var unsplashTrackDownload = function(apiKey, downloadLocation) {
        if (!downloadLocation) {
            return;
        }
        fetch(downloadLocation, {
            headers: {'Authorization': 'Client-ID ' + apiKey, 'Accept-Version': 'v1'},
        }).catch(function() {
            // Tracking is best-effort.
        });
    };

    // -------------------------------------------------------------------------
    // Gallery rendering (provider-agnostic).
    // -------------------------------------------------------------------------
    var buildGalleryHtml = function(results, strings) {
        if (!results || results.length === 0) {
            return '<p style="text-align:center;padding:20px;color:#666;">' + strings.noResults + '</p>';
        }
        var html = '<div class="tiny-unsplash-gallery" style="display:grid;'
            + 'grid-template-columns:repeat(3,1fr);gap:8px;max-height:400px;overflow-y:auto;padding:4px;">';
        results.forEach(function(photo, index) {
            var alt = (photo.alttext || '').replace(/"/g, '&quot;');
            var author = photo.authorname || 'Unknown';
            html += '<div class="tiny-unsplash-item" data-index="' + index + '" '
                + 'style="cursor:pointer;border:2px solid transparent;border-radius:6px;'
                + 'overflow:hidden;position:relative;transition:border-color 0.2s;">'
                + '<img src="' + photo.thumbnailurl + '" alt="' + alt + '" '
                + 'style="width:100%;height:140px;object-fit:cover;display:block;" loading="lazy" />'
                + '<div style="font-size:11px;padding:4px 6px;background:rgba(0,0,0,0.6);'
                + 'color:#fff;position:absolute;bottom:0;left:0;right:0;white-space:nowrap;'
                + 'overflow:hidden;text-overflow:ellipsis;">'
                + author + ' / ' + capitalise(photo.provider)
                + '</div></div>';
        });
        html += '</div>';
        return html;
    };

    var capitalise = function(s) {
        return (s || '').charAt(0).toUpperCase() + (s || '').slice(1);
    };

    var buildAttributionHtml = function(photo, strings) {
        var onText;
        if (photo.provider === 'pexels') {
            onText = strings.onPexels;
        } else if (photo.provider === 'pixabay') {
            onText = strings.onPixabay;
        } else {
            onText = strings.onUnsplash;
        }
        return '<figcaption style="font-size:12px;color:#666;">'
            + strings.photoBy + ' '
            + '<a href="' + photo.authorurl + '" target="_blank" rel="noopener">'
            + (photo.authorname || 'Unknown') + '</a> '
            + onText + ' '
            + '<a href="' + photo.sourceurl + '" target="_blank" rel="noopener">'
            + capitalise(photo.provider) + '</a>'
            + '</figcaption>';
    };

    // -------------------------------------------------------------------------
    // Dialog.
    // -------------------------------------------------------------------------
    var openDialog = function(editor) {
        var unsplashKey = Options.getApiKey(editor);
        var perPage = Options.getPerPage(editor);
        var contextId = Options.getContextId(editor);
        var providers = Options.getProviders(editor);
        var showAttribution = Options.getShowAttribution(editor);

        var availableProviders = [];
        if (providers.unsplash) {
            availableProviders.push('unsplash');
        }
        if (providers.pexels) {
            availableProviders.push('pexels');
        }
        if (providers.pixabay) {
            availableProviders.push('pixabay');
        }
        if (availableProviders.length === 0) {
            return;
        }

        var state = {
            provider: availableProviders[0],
            query: '',
            page: 1,
            orientation: 'all',
            results: [],
            selectedIndex: -1,
        };
        var dialogApi = null;

        Str.get_strings([
            {key: 'dialog_title', component: Common.component},
            {key: 'search_placeholder', component: Common.component},
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
            {key: 'error_download', component: Common.component},
            {key: 'photo_by', component: Common.component},
            {key: 'on_unsplash', component: Common.component},
            {key: 'on_pexels', component: Common.component},
            {key: 'on_pixabay', component: Common.component},
            {key: 'alt_text', component: Common.component},
            {key: 'provider', component: Common.component},
            {key: 'provider_unsplash', component: Common.component},
            {key: 'provider_pexels', component: Common.component},
            {key: 'provider_pixabay', component: Common.component},
        ]).then(function(s) {
            var strings = {
                title: s[0],
                placeholder: s[1],
                noResults: s[2],
                loading: s[3],
                insertBtn: s[4],
                orientAll: s[5],
                orientLandscape: s[6],
                orientPortrait: s[7],
                orientSquarish: s[8],
                prevPage: s[9],
                nextPage: s[10],
                errorNoKey: s[11],
                errorDownload: s[12],
                photoBy: s[13],
                onUnsplash: s[14],
                onPexels: s[15],
                onPixabay: s[16],
                altText: s[17],
                provider: s[18],
                providerUnsplash: s[19],
                providerPexels: s[20],
                providerPixabay: s[21],
            };

            var doSearch = function() {
                state.selectedIndex = -1;
                dialogApi.setData({
                    galleryhtml: '<p style="text-align:center;padding:40px;color:#666;">'
                        + strings.loading + '</p>',
                });

                var searchPromise;
                if (state.provider === 'unsplash') {
                    if (!unsplashKey) {
                        Notification.alert(strings.title, strings.errorNoKey);
                        return;
                    }
                    if (!state.query.trim()) {
                        return;
                    }
                    searchPromise = unsplashSearch(unsplashKey, state.query, state.page, perPage, state.orientation);
                } else {
                    searchPromise = Api.searchImages(
                        state.provider, state.query, state.page, perPage, state.orientation, contextId
                    ).then(function(resp) {
                        if (resp.error) {
                            throw new Error(resp.error);
                        }
                        return resp;
                    });
                }

                searchPromise.then(function(data) {
                    state.results = data.results || [];
                    var total = data.total || 0;
                    var totalPages = total > 0 ? Math.max(1, Math.ceil(total / perPage)) : null;

                    var html = buildGalleryHtml(state.results, strings);
                    var paginationHtml = '<div style="display:flex;justify-content:center;'
                        + 'align-items:center;gap:12px;padding:10px 0;">';
                    if (state.page > 1) {
                        paginationHtml += '<button type="button" class="btn btn-sm btn-secondary '
                            + 'tiny-unsplash-prev">' + strings.prevPage + '</button>';
                    }
                    paginationHtml += '<span style="color:#666;">' + state.page
                        + (totalPages ? (' / ' + totalPages) : '') + '</span>';
                    if (!totalPages || state.page < totalPages) {
                        paginationHtml += '<button type="button" class="btn btn-sm btn-secondary '
                            + 'tiny-unsplash-next">' + strings.nextPage + '</button>';
                    }
                    paginationHtml += '</div>';

                    dialogApi.setData({galleryhtml: html + paginationHtml});
                    setTimeout(attachHandlers, 100);
                }).catch(function(err) {
                    dialogApi.setData({
                        galleryhtml: '<p style="text-align:center;padding:20px;color:red;">'
                            + 'Error: ' + (err.message || 'Unknown') + '</p>',
                    });
                });
            };

            var attachHandlers = function() {
                var dialogEl = document.querySelector('.tox-dialog');
                if (!dialogEl) {
                    return;
                }
                var items = dialogEl.querySelectorAll('.tiny-unsplash-item');
                items.forEach(function(item) {
                    item.addEventListener('click', function() {
                        items.forEach(function(el) {
                            el.style.borderColor = 'transparent';
                        });
                        item.style.borderColor = '#0073aa';
                        state.selectedIndex = parseInt(item.getAttribute('data-index'), 10);
                    });
                });
                var prev = dialogEl.querySelector('.tiny-unsplash-prev');
                var next = dialogEl.querySelector('.tiny-unsplash-next');
                if (prev) {
                    prev.addEventListener('click', function() {
                        if (state.page > 1) {
                            state.page--;
                            doSearch();
                        }
                    });
                }
                if (next) {
                    next.addEventListener('click', function() {
                        state.page++;
                        doSearch();
                    });
                }
            };

            var insertSelected = function(api) {
                if (state.selectedIndex < 0 || !state.results[state.selectedIndex]) {
                    return;
                }
                var photo = state.results[state.selectedIndex];
                var formData = api.getData();
                var alt = (formData.alttext || photo.alttext || '').replace(/"/g, '&quot;');

                // Pixabay → must download server-side. Pexels / Unsplash → hotlink.
                if (photo.provider === 'pixabay') {
                    Api.saveImage(photo.provider, photo.id, contextId, 0).then(function(resp) {
                        if (resp.error || !resp.url) {
                            Notification.alert(strings.title, strings.errorDownload);
                            return;
                        }
                        var attribution = showAttribution ? buildAttributionHtml({
                            provider: resp.provider,
                            authorname: resp.authorname,
                            authorurl: resp.authorurl,
                            sourceurl: resp.sourceurl,
                        }, strings) : '';
                        var html = '<figure class="tiny-unsplash-figure">'
                            + '<img src="' + resp.url + '" alt="' + alt + '" '
                            + 'style="max-width:100%;height:auto;" />'
                            + attribution + '</figure>';
                        editor.insertContent(html);
                        api.close();
                        return resp;
                    }).catch(function() {
                        Notification.alert(strings.title, strings.errorDownload);
                    });
                    return;
                }

                if (photo.provider === 'unsplash') {
                    unsplashTrackDownload(unsplashKey, photo._downloadLocation);
                }
                var attributionHtml = showAttribution ? buildAttributionHtml(photo, strings) : '';
                var html = '<figure class="tiny-unsplash-figure">'
                    + '<img src="' + photo.fullurl + '" alt="' + alt + '" '
                    + 'style="max-width:100%;height:auto;" />'
                    + attributionHtml + '</figure>';
                editor.insertContent(html);
                api.close();
            };

            var providerItems = [];
            availableProviders.forEach(function(p) {
                providerItems.push({
                    text: p === 'unsplash' ? strings.providerUnsplash
                        : (p === 'pexels' ? strings.providerPexels : strings.providerPixabay),
                    value: p,
                });
            });

            dialogApi = editor.windowManager.open({
                title: strings.title,
                size: 'large',
                body: {
                    type: 'panel',
                    items: [
                        {
                            type: 'bar',
                            items: [
                                {
                                    type: 'selectbox',
                                    name: 'provider',
                                    label: strings.provider,
                                    items: providerItems,
                                },
                                {
                                    type: 'input',
                                    name: 'searchquery',
                                    placeholder: strings.placeholder,
                                    maximized: true,
                                },
                                {
                                    type: 'selectbox',
                                    name: 'orientation',
                                    items: [
                                        {text: strings.orientAll, value: 'all'},
                                        {text: strings.orientLandscape, value: 'landscape'},
                                        {text: strings.orientPortrait, value: 'portrait'},
                                        {text: strings.orientSquarish, value: 'squarish'},
                                    ],
                                },
                            ],
                        },
                        {
                            type: 'htmlpanel',
                            name: 'galleryhtml',
                            html: '<p style="text-align:center;padding:40px;color:#999;">'
                                + strings.placeholder + '</p>',
                        },
                        {
                            type: 'input',
                            name: 'alttext',
                            label: strings.altText,
                            placeholder: strings.altText,
                        },
                    ],
                },
                buttons: [
                    {type: 'cancel', name: 'cancel', text: 'Cancel'},
                    {type: 'submit', name: 'insert', text: strings.insertBtn, primary: true},
                ],
                initialData: {
                    provider: state.provider,
                    searchquery: '',
                    orientation: 'all',
                    galleryhtml: '',
                    alttext: '',
                },
                onChange: function(api, details) {
                    var data = api.getData();
                    if (details.name === 'provider') {
                        state.provider = data.provider;
                        state.page = 1;
                        if (state.query.trim()) {
                            doSearch();
                        }
                    } else if (details.name === 'orientation') {
                        state.orientation = data.orientation;
                        state.page = 1;
                        if (state.query.trim()) {
                            doSearch();
                        }
                    } else if (details.name === 'searchquery') {
                        state.query = data.searchquery;
                    }
                },
                onSubmit: insertSelected,
            });

            // Wire up Enter-to-search.
            setTimeout(function() {
                var dialogEl = document.querySelector('.tox-dialog');
                if (!dialogEl) {
                    return;
                }
                var searchInput = dialogEl.querySelector('input[placeholder="' + strings.placeholder + '"]');
                if (searchInput) {
                    searchInput.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            state.query = dialogApi.getData().searchquery;
                            state.page = 1;
                            doSearch();
                        }
                    });
                    searchInput.focus();
                }
            }, 200);

            return strings;
        }).catch(Notification.exception);
    };

    var getSetup = function() {
        return Str.get_string('button_unsplash', Common.component).then(function(label) {
            return function(editor) {
                editor.ui.registry.addButton(Common.pluginButtonName, {
                    icon: 'image',
                    tooltip: label,
                    onAction: function() {
                        openDialog(editor);
                    },
                });
                editor.ui.registry.addMenuItem(Common.pluginMenuItem, {
                    icon: 'image',
                    text: label,
                    onAction: function() {
                        openDialog(editor);
                    },
                });
            };
        });
    };

    return {
        getSetup: getSetup,
    };
});
