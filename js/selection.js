// Probe dropdown
document.getElementById('select-all-abbrev').addEventListener('change', function(e) {
    document.querySelectorAll('.filter-checkbox-abbrev').forEach(checkbox => checkbox.checked = e.target.checked);
});


// Sort dropdown
document.getElementById('select-all-sort').addEventListener('change', function(e) {
    document.querySelectorAll('.filter-checkbox-sort').forEach(checkbox => checkbox.checked = e.target.checked);
});