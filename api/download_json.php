<?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        //Преобразуем в человеческий вид наш массив файлов
        $files = array();
        foreach ($_FILES["files"] as $param => $array)
        {
            foreach ($array as $index => $value) {
                $files[$index][$param] = $value;
            }
        }

        $root = $_SERVER["DOCUMENT_ROOT"]."/solution/";
        require_once $root."src/Parser.php";
        error_reporting(E_ERROR | E_PARSE);
        
        $json = parse_files_to_json($files);

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="data.json"');

        echo $json;
    }
?>