// Manage one of the tab bars
define(['eve'], function(eve) {

    // Handles clicks on tab list items
    var tabClick = function(e) {
        var target = e.target.closest('li'); // Use closest() to find the parent <li>

        // If the click wasn't on an <li> or inside one, ignore it
        if (!target || !target.matches || !target.matches('li')) {
            return;
        }

        // Ensure the click happened within the specific ul this listener is attached to
        if (target.parentNode !== e.currentTarget) {
             return;
        }

        // Activate the clicked tab and show its content
        target.classList.add('active');
        var contentIdToShow = target.id.replace(/^tab_/, '');
        var contentElToShow = document.getElementById(contentIdToShow);
        if (contentElToShow) {
            contentElToShow.style.display = 'block';
        } else {
            console.warn('Content element not found for tab: #' + contentIdToShow);
        }


        // Deactivate sibling tabs and hide their content
        var siblings = target.parentNode.children;
        for (var i = 0; i < siblings.length; i++) {
            var tab = siblings[i];
            // Skip the currently clicked tab and ensure we are processing LIs
            if (tab === target || !tab.matches || !tab.matches('li')) {
                continue;
            }

            tab.classList.remove('active');
            var contentIdToHide = tab.id.replace(/^tab_/, '');
            var contentElToHide = document.getElementById(contentIdToHide);
            if (contentElToHide) {
                contentElToHide.style.display = 'none'; // Hide element
            }
        }

        if (typeof Event === 'function') { // Basic check for Event constructor support
            window.dispatchEvent(new Event('scroll'));
        }
    };

    // Constructor function for creating/managing a TabBar instance
    var TabBar = function(options) {
        var containerId = options.landmark + '_';
        var containerEl = document.getElementById(containerId);

        // If the container doesn't exist, create it
        if (!containerEl) {
            var placeholderEl = document.getElementById(options.landmark);
            if (!placeholderEl) {
                console.error('TabBar placeholder element not found: #' + options.landmark);
                return;
            }

            containerEl = document.createElement('ul');
            containerEl.id = containerId;
            containerEl.className = 'tabBar'; // Use className for initial setup

            var htmlContent = '';
            var escapeAttr = function(str) { return str ? str.replace(/"/g, '&quot;') : ''; };

            for (var i = 0; i < options.tabs.length; i++) {
                var t = options.tabs[i];
                 if (typeof t.external === 'string') {
                    htmlContent += '<li id="tab_' + escapeAttr(t.content) + '">' +
                           '<a href="' + escapeAttr(t.external) + '" class="external"' +
                           (t.onclick ? ' onclick="' + escapeAttr(t.onclick) + '"' : '') + '>' +
                           t.title + '</a></li>'; // Assuming t.title is safe HTML or plain text
                } else if (typeof t.content === 'string') {
                     htmlContent += '<li id="tab_' + escapeAttr(t.content) + '"' +
                           (t.className ? ' class="' + escapeAttr(t.className) + '"' : '') + '>' +
                           t.title + '</li>'; // Assuming t.title is safe HTML or plain text
                }
            }
            containerEl.innerHTML = htmlContent;

             // Add 'active' class to the specified tab (or the first one)
            var activeIndex = (typeof options.active === 'number') ? options.active : 0;
            var activeTab = containerEl.children[activeIndex];
             if (activeTab) {
                activeTab.classList.add('active');
            } else if (containerEl.children.length > 0) {
                 // Fallback to the first child if index is out of bounds but children exist
                 containerEl.children[0].classList.add('active');
                 console.warn('TabBar active index ' + activeIndex + ' out of bounds for #' + options.landmark + '. Defaulting to 0.');
            }

            // Replace the placeholder element with the new tab bar container
            if (placeholderEl.parentNode) {
                placeholderEl.parentNode.replaceChild(containerEl, placeholderEl);
            } else {
                 console.error('Placeholder element #' + options.landmark + ' has no parent.');
                 return;
            }

            // Initially hide all content panels except the active one
            var tabs = containerEl.children;
            for (var j = 0; j < tabs.length; j++) {
                 var tab = tabs[j];
                 if (!tab.matches || !tab.matches('li')) continue; // Skip non-LI elements if any
                 var contentId = tab.id.replace(/^tab_/, '');
                 var contentEl = document.getElementById(contentId);
                 if (contentEl) {
                     var isActiveDefault = (activeIndex < 0 && j === 0 && tabs.length > 0);
                     if (j !== activeIndex && !isActiveDefault) { // Check if not the active one
                         contentEl.style.display = 'none';
                     } else {
                         contentEl.style.display = 'block';
                     }
                 }
            }
        }

        // Add *one* delegated event listener to the container (ul)
        containerEl.addEventListener('click', tabClick);
    };

    // Check and listen for new tab bar requests from elements with class 'TabBar'
    var checkForNewSettings = function() {
        var tabBarPlaceholders = document.querySelectorAll('.TabBar[data-set]');

        for (var i = 0; i < tabBarPlaceholders.length; i++) {
            var el = tabBarPlaceholders[i];

            // Avoid re-initializing
            // Using dataset might require a polyfill for older IE, or use getAttribute
            if (el.getAttribute('data-tab-bar-initialized')) {
                continue;
            }

            // Using dataset might require a polyfill for older IE, or use getAttribute
            var settingsData = el.getAttribute('data-set');
            if (!settingsData) {
                console.warn('TabBar element found without data-set attribute:', el);
                continue;
            }

            var settings;
            try {
                settings = JSON.parse(settingsData);
                // The element itself might *be* the placeholder, so use its ID as the landmark
                if (!settings.landmark && el.id) {
                     settings.landmark = el.id;
                } else if (!settings.landmark && !el.id) {
                     console.error('TabBar element needs an ID or a "landmark" property in its data-set:', el);
                     continue;
                }

            } catch (e) {
                console.error('Failed to parse TabBar settings JSON from data-set:', settingsData, e);
                continue;
            }

            if (settings && settings.landmark) {
                TabBar(settings);
                // Mark as initialized
                 el.setAttribute('data-tab-bar-initialized', 'true');
            } else {
                 console.warn('Invalid settings or missing landmark for TabBar:', settings, el);
            }
        }
    };

    checkForNewSettings();
    eve.on('page.loaded', checkForNewSettings);

    return TabBar;
});
