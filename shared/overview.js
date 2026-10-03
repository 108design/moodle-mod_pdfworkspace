/*
 * Responsive Overview table enhancement.
 * @package   mod_pdfworkspace
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/* Progressive enhancement for the Overview's responsive Moodle tables. */
function startOverview() {
    document.querySelectorAll('.pdfworkspace-overview-table').forEach(function(table) {
        const headers = Array.from(table.querySelectorAll('thead th')).map(function(th) {
            return th.textContent.trim();
        });
        table.querySelectorAll('tbody tr').forEach(function(row) {
            Array.from(row.querySelectorAll('td')).forEach(function(cell, index) {
                if (headers[index]) {
                    cell.setAttribute('data-label', headers[index]);
                }
            });
        });
    });
}
