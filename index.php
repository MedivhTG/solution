<!doctype html>
<html>
    <head>
    </head>
    <body>
        <form action="api/download_json.php" method="post" enctype="multipart/form-data">
            <input type="file" name="files[]" multiple="multiple" accept="application/msword, 
            application/vnd.openxmlformats-officedocument.wordprocessingml.document, 
            application/pdf, 
            text/html, 
            text/plain, 
            application/rtf">
            </br></br>
            <input type="submit" value="Загрузить">
        </form>
    </body>
</html>