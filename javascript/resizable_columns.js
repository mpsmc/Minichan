/*
 * Makes table headings resizable.
 */
function resizableColumns(table) {

    /* ==============================================================================
     * Constants
     * ==============================================================================
     */
    var HANDLE_WIDTH = 20;
    var MIN_PC_WIDTH = 5;
    var DEBUG = true;


    /* ==============================================================================
     * Variables
     * ==============================================================================
     */
    var cols = table.getElementsByTagName("col");
    var headings = table.querySelectorAll('th');
    var headingRow = headings.item(0).parentNode;

    var tableOffset = ancestors(table.parentNode).reduce(
        function(x, y) {
            return x + (typeof y.offsetLeft !== 'undefined'? y.offsetLeft : 0);
        }, 0);

    var storageKey = Array.prototype.reduce.call(
        headings,
        function(x, y) {
            return x + y.innerHTML.substring(0, 5) + "|";
        }, "rsz:");


    /* ==============================================================================
     * Functions
     * ==============================================================================
     */
    function persist() {
        /* Allow current proportions to be retrieved on next load. */
        
        window.localStorage.setItem(storageKey, JSON.stringify(proportions()));
    }

    function loadProportions() {
        /* Load the last persisted propotions. */
        
        return JSON.parse(window.localStorage.getItem(storageKey));
    }
    
    function ancestors(el) {
        /* The parent nodes of an element. */
        
        var ancestors = [el];
        while(el.offsetParentNode) ancestors.push(el = el.offsetParentNode);
        return ancestors;
    }

    function pc(px) {
        /* Conversion from pixels to the %-age of table width, and rounded to the nearest 0.05. */
        
        var proportion = px / table.clientWidth;
        var roundingError = (1000 * proportion) % 5 / 10;
        return 100 * proportion - roundingError;
    }
    
    function proportions() {
        /* The current percentages of the columns. */
        
        var colSizes = [], totalWidth = table.clientWidth, th;

        for (var i=0; i<headings.length; i++) {
            th = headings.item(i);
            colSizes[i] = pc(th.clientWidth);
        }
        return colSizes;
    }

    function isMouseDepressed(mouseEvent) {
        /* Gauge whether a mouse button is being depressed. */
        
        return (typeof mouseEvent.buttons !== 'undefined')?
            mouseEvent.buttons & 1 : mouseEvent.which === 1;
    }

    function resize(th) {
        /* Apply resizing mode. */
        
        var index = Array.prototype.indexOf.call(th.parentNode.children, th);
        var col = cols[index];
        var donorColumn = cols[index + 1];
        var previousCursor = document.body.style.cursor;
        
        function resizeEvent(mouseEvent) {
            /* Request a resize, stealing the table's width from its neighbour. */
            
            if (!isMouseDepressed(mouseEvent)) {
                cancelResize(mouseEvent);
                return true;
            } 

            var pcOriginalWidth = pc(th.clientWidth);
            var pcNewWidth = pc(mouseEvent.clientX - th.offsetLeft - tableOffset);

            var pcOriginalDonorWidth = Math.max(pc(th.nextElementSibling.clientWidth), MIN_PC_WIDTH);
            var pcNewDonorWidth = pcOriginalDonorWidth - pcNewWidth + pcOriginalWidth;

            // Protect against undersizing.
            if (pcNewWidth >= MIN_PC_WIDTH && pcNewDonorWidth >= MIN_PC_WIDTH) {
                col.style.width = pcNewWidth + '%';
                donorColumn.style.width = pcNewDonorWidth + '%';
            } else {
                if (pcOriginalWidth < MIN_PC_WIDTH) {
                    col.style.width = MIN_PC_WIDTH + '%';
                }
                if (pcOriginalDonorWidth < MIN_PC_WIDTH) {
                    donorColumn.style.width = MIN_PC_WIDTH + '%';
                }
            }

            return true;
        }

        function cancelResize() {
            /* Remove styling and events before storing the proportions. */
            
            window.removeEventListener('mousemove', resizeEvent, false);
            window.removeEventListener('mouseup', cancelResize, false);
            document.body.classList.remove('resizing');
            persist();
        }

        // Add styling and events
        document.body.classList.add('resizing');
        window.addEventListener('mouseup', cancelResize, false);
        window.addEventListener('mousemove', resizeEvent, false);
    }


    /* ==============================================================================
     * Initialise
     * ==============================================================================
     */
    table.style.tableLayout = 'fixed';

    Array.prototype.forEach.call(
        table.querySelectorAll('th'),
        function(el) {
            var idx = Array.prototype.indexOf.call(el.parentNode.children, el);
            var col = cols[idx];
            var heading = headings[idx];

            col.style.width = pc(el.clientWidth) + '%';
            heading.style.overflow = 'hidden';
            heading.style.wordWrap = 'normal';

            function eventApplyCursor(mouseEvent) {
                var lhs = el.offsetLeft + tableOffset;
                var rhs = lhs + el.clientWidth;
                var leftDistance = mouseEvent.clientX - lhs;
                var rightDistance = rhs - mouseEvent.clientX;

                if (leftDistance < HANDLE_WIDTH && idx !== 0) {
                    el.style.cursor = 'col-resize';
                } else if (rightDistance < HANDLE_WIDTH && idx !== el.parentNode.children.length - 1) {
                    el.style.cursor = 'col-resize';
                } else {
                    el.style.cursor = 'default';
                }                
            }

            function eventPrepareResize(mouseEvent) {
                var lhs = el.offsetLeft + tableOffset;
                var rhs = lhs + el.clientWidth;
                var leftDistance = mouseEvent.clientX - lhs;
                var rightDistance = rhs - mouseEvent.clientX;

                if (leftDistance < HANDLE_WIDTH && idx !== 0) {
                    resize(mouseEvent.target.previousElementSibling);
                } else if (rightDistance < HANDLE_WIDTH && idx !== el.parentNode.children.length - 1) {
                    resize(mouseEvent.target);
                }
            }

            el.addEventListener('mousemove', eventApplyCursor, false);
            el.addEventListener('mousedown', eventPrepareResize, false);
        });

    // Load the existing proportions.
    var savedColumns = loadProportions();
    var colSize;

    if (savedColumns) {
        for (var i=0; i<Math.max(savedColumns.length, cols.length); i++) {
            colSize = Number(savedColumns[i]);
            if (colSize) {
                cols[i].style.width = colSize + '%';
            } else {
                if (DEBUG) console.error("colSize: ", colSize);
            }
        }
    }
}

// Initialise any tables with the resizable class.
document.addEventListener("DOMContentLoaded", function(event) {
    Array.prototype.forEach.call(
        document.querySelectorAll('body.resizable_columns table.resizable'),
        function(table) { resizableColumns(table); }
    );
}, false);
