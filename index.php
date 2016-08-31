<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Парсер</title>
        <script type="text/javascript" src="/lib/jquery.min.js"></script>

    </head>
    <body>

        <table border="1" id="crawlerResults" data-limit="10" data-offset="10">
            <tr>
                <td>Id</td>
                <td>Posted</td>
                <td>Domain</td>
                <td>Page</td>
                <td>Description</td>
            </tr>
        </table>
    </body>

    <script type="text/javascript">
        $(document).ready(function () {

            setInterval(function ()
            {
                var table = $('#crawlerResults');
                var offset = table.data('offset');
                var limit = table.data('limit');
                $.ajax({
                    type: "POST",
                    url: "ajax.php",
                    data: {offset: offset, limit: limit},
                    success: function (data) {
                        var array = jQuery.parseJSON(data);

                        for (var i = 0; i < array.length; i++) {

                            var tr = $('<tr>');

                            $('tr#cr'+array[i].id).remove();
                            tr.append($('<td>').text(array[i].id));
                            tr.append($('<td>').text(array[i].posted));
                            tr.append($('<td>').text(array[i].domain));
                            tr.append($('<td>').text(array[i].url));
                            tr.append($('<td>').text(array[i].description));
                            table.append(tr);
                            table.data('offset',offset+limit);
                        }

                    },
                    error: function () {
                        alert('error')
                    }
                });
            }, 10000);
        });
    </script>
</html>