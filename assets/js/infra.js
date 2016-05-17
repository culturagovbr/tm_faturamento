/**
 * Created by 71225919134 on 05/11/2015.
 */

function exportTableToCSV($table, filename, type) {
    var $rows = $table.find('tr:has(td)'),

    // Temporary delimiter characters unlikely to be typed by keyboard
    // This is to avoid accidentally splitting the actual contents
        tmpColDelim = String.fromCharCode(11), // vertical tab character
        tmpRowDelim = String.fromCharCode(0), // null character

    // actual delimiter characters for CSV format
        colDelim = '","',
        rowDelim = '"\r\n"',

    // Grab text from table into CSV formatted string
        csv = '"' + $rows.map(function (i, row) {
                var $row = $(row),
                    $cols = $row.find('td');

                return $cols.map(function (j, col) {
                    var $col = $(col),
                        text = $col.text();

                    return text.replace(/"/g, '""'); // escape double quotes

                }).get().join(tmpColDelim);

            }).get().join(tmpRowDelim)
                .split(tmpRowDelim).join(rowDelim)
                .split(tmpColDelim).join(colDelim) + '"',

    // Data URI
        csvData = null;
    if (type == 'csv') {
        csvData = 'data:application/csv;charset=utf-8,' + encodeURIComponent(csv);
    } else if (type == 'excel') {
        csvData = 'data:application/vnd.ms-excel,' + encodeURIComponent(csv);
    } else if (type == 'open') {
        csvData = 'data:application/vnd.oasis.opendocument.spreadsheet,' + encodeURIComponent(csv);
    } else {
        csvData = 'data:application/csv;charset=utf-8,' + encodeURIComponent(csv);
    }

    $(this)
        .attr({
            'download': filename,
            'href': csvData,
            'target': '_blank'
        });
}


/**
 *  Retorna a Data Atual Formatada para ser utilizada na composição do Nome do Arquivo XLS
 * @returns {string}
 */
function getDataAtualParaNomearArquivo() {
    var currentTime = new Date();

    var month = currentTime.getMonth() + 1;
    var day = currentTime.getDate();
    var year = currentTime.getFullYear();
    var hora = currentTime.getHours();
    var minuto = currentTime.getMinutes();
    var segundo = currentTime.getSeconds();

    var date_formatada = day + "_" + month + "_" + year + "_" + hora + "_" + minuto + "_" + segundo;

    return date_formatada;

}