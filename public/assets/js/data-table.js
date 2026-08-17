/**
 * Автоматски ги претвора сите <table class="data-table"> во DataTables со
 * сортирање по колона, странично прикажување (pagination) и филтер по
 * секоја колона (текст полиња во втор ред на заглавието). Работи над
 * серверски-рендерирани табели без никаква измена по view — само
 * class="data-table" на <table> и data-no-filter на th колони каде филтер
 * и сортирање немаат смисла (на пр. „Акции“).
 */
(function ($) {
    'use strict';

    if (typeof $ === 'undefined' || !$.fn || !$.fn.DataTable) {
        return;
    }

    var mkLanguage = {
        search: 'Пребарувај:',
        lengthMenu: 'Прикажи _MENU_ записи',
        info: 'Прикажани _START_–_END_ од вкупно _TOTAL_ записи',
        infoEmpty: 'Нема записи',
        infoFiltered: '(филтрирано од вкупно _MAX_)',
        zeroRecords: 'Нема пронајдени записи',
        emptyTable: 'Нема податоци во табелата',
        paginate: {
            first: 'Прва',
            last: 'Последна',
            next: 'Следна',
            previous: 'Претходна',
        },
    };

    function initTable(table) {
        var $table = $(table);

        if ($.fn.DataTable.isDataTable($table)) {
            return;
        }

        // Серверски-рендерираните "Нема записи" редови (еден <td colspan="N">)
        // не одговараат на бројот на колони — DataTables има своја празна
        // порака (language.emptyTable), па тој ред се отстранува пред init.
        var $rows = $table.find('tbody tr');
        if ($rows.length === 1 && $rows.find('td').length === 1 && $rows.find('td').first().attr('colspan')) {
            $rows.remove();
        }

        var $headerRow = $table.find('thead tr').first();
        var $headerCells = $headerRow.find('th');
        var noFilterIndexes = [];

        $headerCells.each(function (index) {
            if (this.hasAttribute('data-no-filter')) {
                noFilterIndexes.push(index);
            }
        });

        var columnDefs = noFilterIndexes.map(function (index) {
            return { targets: index, orderable: false, searchable: false };
        });

        var $filterRow = $('<tr class="filters"></tr>');
        $headerCells.each(function (index) {
            var $th = $('<th></th>');
            if (noFilterIndexes.indexOf(index) === -1) {
                $th.html('<input type="text" class="form-control form-control-sm" placeholder="Филтрирај...">');
            }
            $filterRow.append($th);
        });
        $table.find('thead').append($filterRow);

        var dataTable = $table.DataTable({
            columnDefs: columnDefs,
            order: [],
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            language: mkLanguage,
        });

        $filterRow.find('th').each(function (index) {
            var $input = $(this).find('input');

            if (!$input.length) {
                return;
            }

            $input.on('click keydown', function (e) {
                e.stopPropagation();
            });

            $input.on('keyup change clear', function () {
                var value = this.value;
                if (dataTable.column(index).search() !== value) {
                    dataTable.column(index).search(value).draw();
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('table.data-table').forEach(initTable);
    });
})(window.jQuery);
