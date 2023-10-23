<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Каталоги 5-ая передача</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
</head>
<body>

<div class="container-lg">
    <div class="px-4 pt-5 my-5 text-center border-bottom">
        <h1 class="display-4 fw-bold text-body-emphasis">Каталоги 5-ая передача</h1>
        <div class="col-lg-6 mx-auto">
            <p class="lead mb-4">вводи название категории и поехали</p>
            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center mb-5">
                <form action="FiveGear.php" method="POST" id="myForm">
                    <input type="text" name="directory_name" placeholder="Название каталога" required>
                    <input type="button" id="submitBtn" value="Найти каталог">
                </form>
            </div>

        </div>
        <div id="jsonDisplay"></div>


    </div>
</div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<script>
    $(document).ready(function () {
        $("#submitBtn").click(function () {
            var brandName = $("input[name=directory_name]").val();

            $.ajax({
                type: "POST",
                url: "FiveGear.php",
                data: {directory_name: brandName},
                success: function (response) {
                    console.log(response);
                    // вставляем JSON в HTML-элемент
                    $("#jsonDisplay").html(JSON.stringify(response, null, 2));
                },
                error: function (request, status, error) {
                    console.log("Error: " + error);
                }
            });
        });
    });
</script>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL"
        crossorigin="anonymous"></script>
</body>
</html>