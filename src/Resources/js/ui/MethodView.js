define([
    'deepmerge',
    'eve',
    './MethodView/InteractiveGridOverlay',
    '../lib/webfont',
    '../helpers/URL',
    '../helpers/LocalStorage',
    '../helpers/GridOptionsBuilder',
    '../helpers/Grid',
    '../helpers/PlaceNotation',
    '../helpers/Text',
    '../helpers/Music'
], function(
    deepmerge,
    eve,
    InteractiveGridOverlay,
    webfont,
    URL,
    LocalStorage,
    GridOptionsBuilder,
    MethodGrid,
    PlaceNotation,
    Text,
    Music
) {

    var newMethodView;

    // Module scope variables
    var options, method, methodTexts,
        lineContainerEl, gridContainerEl,
        active = false,
        lastShowToolTips, lastHighlightMusic, lastStyle, lastFollow, lastScale, lastNumberOfColumns,
        music,
        options_plainCourse, line_plainCourse, line_calls;

    // Main function to initialize or update the view
    newMethodView = function(o) {
        options = o;
        active = true;

        lastShowToolTips = lastHighlightMusic = lastStyle = lastFollow = lastScale = lastNumberOfColumns = music = null;

        lineContainerEl = document.querySelector(options.lineContainer);
        gridContainerEl = document.querySelector(options.gridContainer);

        if (!lineContainerEl) {
            console.error('MethodView: lineContainer element not found with selector "' + options.lineContainer + '"');
            active = false;
            return;
        }
        if (!gridContainerEl) {
            console.error('MethodView: gridContainer element not found with selector "' + options.gridContainer + '"');
            active = false;
            return;
        }

        redrawMethodView();
    };

    // Core function to update the display based on settings and options
    var redrawMethodView = function() {
        if (!active || !lineContainerEl || !gridContainerEl) {
            return;
        }

        try {
            var newShowTooltips = LocalStorage.getSetting('method_tooltips', true);
            var newHighlightMusic = LocalStorage.getSetting('method_music', false);
            var newScale = (typeof window.devicePixelRatio === 'number') ? window.devicePixelRatio : 1;
            var newFollow = LocalStorage.getSetting('method_follow', 'heaviest');
            var newStyle = (URL.parameter('style') !== null) ? URL.parameter('style') : LocalStorage.getSetting('method_style', 'numbers');
            var widths, maxWidth;

            // Update Method Object if 'follow' changed
            if (newFollow !== lastFollow) {
                options.workingBell = newFollow;
                method = new GridOptionsBuilder(deepmerge({}, options));
            }

            if (!method) {
                 console.error("MethodView: 'method' object not initialized.");
                 active = false;
                 return;
            }

            // Re-create text tooltips if settings changed
            if (newShowTooltips !== lastShowToolTips || newFollow !== lastFollow) {
                if (newShowTooltips) {
                    methodTexts = method.workGroups.map(function(e) {
                        var toFollow = (newFollow == 'lightest') ? Math.min.apply(null, e) : Math.max.apply(null, e);
                        var notationString = method.notation.text + '.';
                        var fullNotation = '';
                        for (var i = 0; i < method.numberOfLeads; i++) {
                           fullNotation += notationString;
                        }
                        return {
                            bell: toFollow,
                            hunt: (e.length === 1),
                            text: Text.fromNotation(fullNotation, method.stage, toFollow, true)
                        };
                    });
                } else {
                    methodTexts = [];
                }
            }

            // Analyze music if needed
            if (newHighlightMusic && music === null) {
                var parsedNotation = [];
                var parsedNotationArr = PlaceNotation.parse(options.notation, options.stage);
                for (var lead = 0; lead < method.numberOfLeads; lead++) {
                   parsedNotation.push(parsedNotationArr);
                }
                var allRows = [].concat.apply([], parsedNotation); // Flatten array
                allRows = allRows.concat(PlaceNotation.rounds(options.stage)); // Add rounds

                music = Music(allRows).map(function(e) {
                    return e.score.map(function(s) {
                        var s2 = 0.35 * Math.min(Math.pow(s / (100 - (method.stage * 3)), 1 / 1.4), 1);
                        return 'rgba(0,255,0,' + (s2 < 0.1 ? 0 : s2) + ')';
                    });
                });
            }

            // Re-create line views if style or music highlighting changed
            if (newHighlightMusic !== lastHighlightMusic || newFollow !== lastFollow || newStyle !== lastStyle) {
                options_plainCourse = method.gridOptions.plainCourse[newStyle]();
                options_plainCourse.highlighting = newHighlightMusic ? { show: true, colors: music } : false;
                line_plainCourse = new MethodGrid(options_plainCourse);

                if (method.gridOptions.calls && typeof method.gridOptions.calls[newStyle] === 'function') {
                    line_calls = method.gridOptions.calls[newStyle]().map(function(callOptions) {
                        return new MethodGrid(callOptions);
                    });
                } else {
                    line_calls = [];
                }
            }

             if (!line_plainCourse || !line_calls) {
                 console.error("MethodView: Line views (plain course or calls) not initialized.");
                 active = false;
                 return;
             }

            // Calculate optimal number of columns for the line view
            var newNumberOfColumns = (function() {
                var numberOfLeads = method.numberOfLeads;
                var callWidth = (line_calls && line_calls[0] && typeof line_calls[0].measure === 'function') ? line_calls[0].measure().canvas.width : 0;
                var contentEl = document.getElementById('content');
                var availableWidth = contentEl ? contentEl.offsetWidth - 24 : window.innerWidth - 24;

                var leadsPerColumn = 1;
                var numberOfColumns = numberOfLeads;

                if (numberOfLeads <= 0) return 1;

                // Assume setOptions modifies the instance directly
                line_plainCourse.setOptions({ layout: { numberOfColumns: numberOfColumns } });

                var iterations = 0;
                var maxIterations = numberOfLeads + 1;

                while (leadsPerColumn <= numberOfLeads && iterations < maxIterations) {
                    var measuredWidth = line_plainCourse.measure().canvas.width;
                    if (measuredWidth + callWidth + 48 <= availableWidth) {
                         break;
                    }
                    leadsPerColumn++;
                    numberOfColumns = Math.ceil(numberOfLeads / leadsPerColumn);
                    line_plainCourse.setOptions({ layout: { numberOfColumns: numberOfColumns } });
                    iterations++;
                }

                 if (iterations >= maxIterations) {
                     console.warn("MethodView: Column calculation loop reached max iterations.");
                }

                return numberOfColumns;
            })();


            // Redraw grid views if scale changed
            if (newScale !== lastScale) {
                gridContainerEl.innerHTML = '';

                var grid_plainCourse = new MethodGrid(method.gridOptions.plainCourse.grid());
                var grid_calls = method.gridOptions.calls.grid().map(function(callOptions) {
                    return new MethodGrid(callOptions);
                });

                gridContainerEl.appendChild(grid_plainCourse.draw());
                for (var i = 0; i < grid_calls.length; i++) {
                    gridContainerEl.appendChild(grid_calls[i].draw());
                }

                // Align grid canvases
                var gridCanvases = gridContainerEl.querySelectorAll('canvas');
                if (gridCanvases.length > 0) {
                    widths = [];
                    for(var j=0; j<gridCanvases.length; j++) {
                        widths.push(gridCanvases[j].offsetWidth);
                    }
                    maxWidth = Math.max.apply(null, [0].concat(widths));
                    for(var k=0; k<gridCanvases.length; k++) {
                        var canvas = gridCanvases[k];
                        var marginLeft = Math.max(0, 12 + maxWidth - widths[k]);
                        canvas.style.marginLeft = marginLeft + 'px';
                    }
                }
            }

            // Redraw call lines if scale or style changed
            if (newScale !== lastScale || newStyle !== lastStyle) {
                lineContainerEl.innerHTML = '';

                for (var i = 0; i < line_calls.length; i++) {
                    lineContainerEl.appendChild(line_calls[i].draw());
                }

                // Align call canvases
                var callCanvases = lineContainerEl.querySelectorAll('canvas');
                if (callCanvases.length > 0) {
                    widths = [];
                    for(var j=0; j<callCanvases.length; j++) {
                        widths.push(callCanvases[j].offsetWidth);
                    }
                    maxWidth = Math.max.apply(null, [0].concat(widths));
                    for(var k=0; k<callCanvases.length; k++) {
                         var canvas = callCanvases[k];
                         var marginLeft = Math.max(0, 12 + maxWidth - widths[k]);
                        canvas.style.marginLeft = marginLeft + 'px';
                    }
                }
            }

            // Redraw plain course line if needed (and update overlay)
            var needsPlainCourseRedraw = lastHighlightMusic !== newHighlightMusic ||
                                         lastShowToolTips !== newShowTooltips ||
                                         newScale !== lastScale ||
                                         newFollow !== lastFollow ||
                                         newStyle !== lastStyle ||
                                         newNumberOfColumns !== lastNumberOfColumns;

            if (needsPlainCourseRedraw) {
                if (!(newScale !== lastScale || newStyle !== lastStyle)) {
                     var existingPlainCourse = lineContainerEl.firstChild;
                     if (existingPlainCourse) {
                         lineContainerEl.removeChild(existingPlainCourse);
                     }
                 }

                 var newPlainCourseEl = line_plainCourse.draw();
                 lineContainerEl.insertBefore(newPlainCourseEl, lineContainerEl.firstChild);

                if (newShowTooltips) {
                     InteractiveGridOverlay(line_plainCourse, method, methodTexts);
                }
            }


            // Update last known states
            lastShowToolTips = newShowTooltips;
            lastHighlightMusic = newHighlightMusic;
            lastStyle = newStyle;
            lastScale = newScale;
            lastNumberOfColumns = newNumberOfColumns;
            lastFollow = newFollow;

        } catch (error) {
             console.error("Error during MethodView redraw:", error);
             active = false; // Deactivate on error
        }
    };

    // --- Initialization and Event Listeners ---

    var checkForNewSettings = function() {
        active = false; // Deactivate any existing view

        webfont(function() {
            var methodViewElements = document.querySelectorAll('.MethodView[data-set]:not([data-methodview-initialized])');

            for (var i = 0; i < methodViewElements.length; i++) {
                var element = methodViewElements[i];
                var settingsData = element.getAttribute('data-set');
                if (settingsData) {
                    try {
                        var settings = JSON.parse(settingsData);
                        if (!settings.landmark && element.id) {
                            settings.landmark = '#' + element.id;
                        }
                        // Provide default selectors if missing
                        if (!settings.lineContainer) settings.lineContainer = '#' + element.id + '-line';
                        if (!settings.gridContainer) settings.gridContainer = '#' + element.id + '-grid';

                        newMethodView(settings);
                        element.setAttribute('data-methodview-initialized', 'true');
                    } catch (e) {
                        console.error('Failed to parse MethodView settings JSON:', settingsData, e);
                        element.setAttribute('data-methodview-initialized', 'error');
                    }
                } else {
                     console.warn('MethodView element found without data-set attribute:', element);
                     element.setAttribute('data-methodview-initialized', 'nodata');
                }
            }
        });
    };

    eve.on('page.loaded', checkForNewSettings);
    checkForNewSettings(); // Initial run

    // Add resize listener
    var resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(redrawMethodView, 50); // Debounce
    });

    eve.on('setting.changed.*', redrawMethodView);

    return newMethodView;
});
