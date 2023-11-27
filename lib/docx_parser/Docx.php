<?php
    function read_docx_file($filename) {
        return getTextFromZippedXML($filename, "word/document.xml");
    }
    function getTextFromZippedXML($archiveFile, $contentFile) {
        // Создаёт "реинкарнацию" zip-архива...
        $zip = new ZipArchive;
        // И пытаемся открыть переданный zip-файл
        if ($zip->open($archiveFile)) {
            // В случае успеха ищем в архиве файл с данными
            if (($index = $zip->locateName($contentFile)) !== false) {
                // Если находим, то читаем его в строку
                $content = $zip->getFromIndex($index);
                // Закрываем zip-архив, он нам больше не нужен
                $zip->close();

                return $content;
            }
            $zip->close();
        }
        // Если что-то пошло не так, возвращаем пустую строку
        return "";
    } 
?>