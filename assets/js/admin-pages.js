(function () {
    'use strict';

    const tableBody = document.querySelector('.eas-pages-table tbody');

    if (!tableBody) {
        return;
    }

    let draggedRow = null;

    tableBody.addEventListener('dragstart', function (event) {
        const row = event.target.closest('.eas-page-row');

        if (!row) {
            return;
        }

        draggedRow = row;
        row.classList.add('dragging');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', row.dataset.pageId);
    });

    tableBody.addEventListener('dragend', function () {
        if (draggedRow) {
            draggedRow.classList.remove('dragging');
        }

        document.querySelectorAll('.eas-page-row.drag-over').forEach(function (row) {
            row.classList.remove('drag-over');
        });

        draggedRow = null;
    });

    tableBody.addEventListener('dragover', function (event) {
        event.preventDefault();

        const row = event.target.closest('.eas-page-row');

        if (!row || row === draggedRow || !draggedRow) {
            return;
        }

        document.querySelectorAll('.eas-page-row.drag-over').forEach(function (item) {
            item.classList.remove('drag-over');
        });

        row.classList.add('drag-over');

        const rect = row.getBoundingClientRect();
        const middle = rect.top + (rect.height / 2);

        if (event.clientY < middle) {
            tableBody.insertBefore(draggedRow, row);
        } else {
            tableBody.insertBefore(draggedRow, row.nextSibling);
        }

        row.classList.remove('drag-over');
    });

    tableBody.addEventListener('drop', function (event) {
        event.preventDefault();

        if (!draggedRow) {
            return;
        }

        const pageIds = Array.from(tableBody.querySelectorAll('.eas-page-row')).map(function (row) {
            return row.dataset.pageId;
        });

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = window.easAdminPages.uploadUrl;

        function addHiddenField(name, value) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = value;
            form.appendChild(input);
        }

        addHiddenField('action', 'eas_reorder_pages');
        addHiddenField('quiz_id', window.easAdminPages.quizId);
        addHiddenField('_wpnonce', window.easAdminPages.nonce);

        pageIds.forEach(function (pageId) {
            addHiddenField('page_ids[]', pageId);
        });

        document.body.appendChild(form);
        form.submit();
    });
})();
