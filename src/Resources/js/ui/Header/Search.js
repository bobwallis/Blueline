// This module manages the search box UI, updating it on page changes.
// It also receives user input into the search box, and issues appropriate
// data requests

define(['eve', '../../helpers/URL', '../../data/Page'], function(eve, URL, Page) {

    var contentEl = document.getElementById('content');
    var searchEl = document.getElementById('search');
    var qEl = document.getElementById('q');

    if (!contentEl || !searchEl || !qEl) {
        console.error("Search UI elements (#content, #search, #q) not found.");
        return {
            visible: false,
            hide: function() {},
            show: function() {}
        };
    }

    var Search = {
        visible: false,
        hide: function() {
            if (Search.visible === true) {
                qEl.blur();
                searchEl.style.display = 'none';
                Search.visible = false;
                contentEl.classList.remove('searchable');
            }
        },
        show: function(section) {
            if (typeof section !== 'string' || section === '') {
                searchEl.setAttribute('action', '');
                qEl.setAttribute('placeholder', 'Search');
            } else {
                searchEl.setAttribute('action', URL.baseURL + section + '/search');
                qEl.setAttribute('placeholder', 'Search ' + section);
            }

            var isFocused = document.activeElement === qEl;
            if (!isFocused || (window.history.state !== null && window.history.state.type !== 'keyup' && window.history.state.type !== 'clipboard')) {
                qEl.value = URL.parameter('q') || '';
            }

            if (Search.visible === false) {
                searchEl.style.display = '';
                Search.visible = true;
                contentEl.classList.add('searchable');
            }
        }
    };

    Search.visible = window.getComputedStyle(searchEl).display !== 'none';

    eve.on('page.request', function(request) {
        if (request.showSearchBar === true) {
            Search.show(request.section);
        } else {
            Search.hide();
        }
    });

    if ('serviceWorker' in navigator) {
        document.addEventListener('keyup', function(e) {
            if (!e.target.matches || !e.target.matches('#q')) {
                return;
            }

            var inputEl = e.target;
            var formEl = null;

            if (inputEl.closest) {
                 formEl = inputEl.closest('form');
            } else {
                var parent = inputEl.parentNode;
                while (parent && parent.tagName !== 'FORM') {
                    parent = parent.parentNode;
                }
                formEl = parent;
            }

            var href;
            var ignoredKeys = [13, 16, 17, 27, 33, 34, 35, 36, 37, 38, 39, 40, 45, 91];

            if (ignoredKeys.indexOf(e.which) !== -1 || (e.which === 191 && inputEl.value.indexOf('/') === -1)) {
                return;
            }

            if (inputEl.value === '') {
                if (formEl && formEl.hasAttribute('action')) {
                     href = formEl.getAttribute('action').replace(/search\/?$/, '');
                } else {
                    console.warn("Search form or action missing when trying to clear search.");
                    return;
                }
            }
            else if (formEl && formEl.hasAttribute('action')) {
                if (typeof FormData !== 'undefined' && typeof URLSearchParams !== 'undefined') {
                    var formData = new FormData(formEl);
                    var params = new URLSearchParams(formData).toString();
                    href = formEl.getAttribute('action') + '?' + params;
                } else {
                    console.warn("FormData or URLSearchParams not supported. Search may not work correctly.");
                    href = formEl.getAttribute('action') + '?q=' + encodeURIComponent(inputEl.value);
                }
            } else {
                 console.warn("Search form or action missing when trying to submit search.");
                 return;
            }

            setTimeout(function() {
                Page.request(href, e.type);
            }, 1);
        });

        document.body.addEventListener('submit', function(e) {
            if (!e.target.matches || !e.target.matches('#search, #custom_method')) {
                return;
            }

            var formEl = e.target;
            e.preventDefault();

             if (!formEl.hasAttribute('action')) {
                console.warn("Submitted form is missing an 'action' attribute.");
                return;
            }

            var href;

            if (typeof FormData !== 'undefined' && typeof URLSearchParams !== 'undefined') {
                var formData = new FormData(formEl);
                var params = new URLSearchParams(formData).toString();
                href = formEl.getAttribute('action') + '?' + params;
            } else {
                 console.warn("FormData or URLSearchParams not supported. Search may not work correctly.");
                 href = formEl.getAttribute('action') + '?q=' + encodeURIComponent(formEl.querySelector('[name="q"]').value);
            }

            Page.request(href, 'submit');
        });
    }

    return Search;
});
