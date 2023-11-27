<?php
    $root = $_SERVER["DOCUMENT_ROOT"]."/solution/";
    require $root."lib/rtf_parser/vendor/autoload.php";
    require $root."lib/pdf_parser/vendor/autoload.php";
    require_once $root."lib/doc_parser/Doc.php";
    require_once $root."lib/docx_parser/Docx.php";
    
    function set_attributes(array $array, array $keys)
    {
        if (count($keys) < 2 || count($array) != count($keys)) return $array;
        if ($keys[0] != "Тип")
        {
            $index = array_search("Тип", $keys);

            $tmpVal = $array[0];
            $array[0] = $array[$index];
            $array[$index] = $tmpVal;

            $tmpKey = $keys[0];
            $keys[0] = $keys[$index];
            $keys[$index] = $tmpKey;
        }

        $array[$keys[0]] = $array[0];
        unset($array[0]);

        $subarray = array();
        for ($i = 1; $i < count($keys); $i++)
        {
            $subarray[$keys[$i]] = $array[$i];
            unset($array[$i]);
        }

        $array["Атрибуты"] = $subarray;

        return $array;
    }

    function to_gost_assoc(string $line) : array
    {
        $element = array();
        preg_match_all("/(ГОСТ)|(\d+\.\d+(?=-))|((?<=-)\d{2,4})/", $line, $element);
        $element = set_attributes($element[0], array("Тип", "Номер", "Год"));
        return $element;
    }

    function to_tu_assoc(string $line) : array
    {
        $element = array();
        preg_match_all("/(ТУ)|(\d+\.\d+\.\d+(?=-))|((?<=-)\d+(?=-))|((?<=-)\d+(?=-))|((?<=-)\d{2,4})/", $line, $element);
        $element = set_attributes($element[0], array("Тип", "ОКПД2", "Номер", "ОКПО", "Год"));
        return $element;
    }

    function to_fz_assoc(string $line) : array
    {
        $element = array();
        preg_match_all("/(\d+)|(ФЗ)/", $line, $element);
        $element = set_attributes($element[0], array("Номер", "Тип"));
        return $element;
    }

    function to_snip_assoc(string $line) : array
    {
        $element = array();
        preg_match_all("/(СНиП)|(\d+\.\d+\.\d+(?=-))|((?<=-)\d{2,4})/", $line, $element);
        $element = set_attributes($element[0], array("Тип", "Номер", "Год"));
        return $element;
    }

    /*
    Возвращает ассоциативный массив из текста по шаблону и заданной функции
    конвертации в ассоциативный массив
    */ 
    function assoc_array(string $text, string $pattern, $assoc_callback) : array {
        $matches = array();
        preg_match_all($pattern, $text, $matches);
        $matches = array_unique($matches[0]);
        $assoc = array();
        foreach ($matches as $match) {
            array_push($assoc, $assoc_callback($match));
        }
        return $assoc;
    }

    function parse_files_to_json(array $files) : string {
        $list = array();
        $json = "";

        foreach ($files as $file) 
        {
            $content = "";

            $extension = explode(".",$file["name"])[count(explode(".",$file["name"])) - 1];

            if ($file["type"] == "text/plain" || $file["type"] == "text/html") $content = mb_convert_encoding(file_get_contents($file["tmp_name"]), "UTF-8");
            else if ( $file["type"] == "application/msword" && $extension == "rtf" || $file["type"] == "application/rtf")
            {
                $content = file_get_contents($file["tmp_name"]);
                $scanner = new RtfParser\Scanner($content);
                $parser = new RtfParser\Parser($scanner);
                $text = "";
                $doc = $parser->parse();
                foreach ($doc->childNodes() as $node) {
                    $text .= $node->text();
                }
                $input_encoding = $doc->getEncoding();
                $content = mb_convert_encoding($text,"UTF-8", $input_encoding);
            }
            else if ($file["type"] == "application/pdf")
            {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($file["tmp_name"]);
                $content = mb_convert_encoding($pdf->getText(), "UTF-8");
            }
            else if ($file["type"] == "application/msword")
            {
                $content = mb_convert_encoding(read_doc_file($file["tmp_name"]), "UTF-8");           
            }
            else if ($file["type"] == "application/vnd.openxmlformats-officedocument.wordprocessingml.document")
            {
                $content = mb_convert_encoding(read_docx_file($file["tmp_name"]), "UTF-8");
            }

            
            $gosts = assoc_array($content, "/ГОСТ \d+\.\d+-\d{2,4}/", 'to_gost_assoc');
            $tus = assoc_array($content, "/ТУ \d+\.\d+\.\d+-\d+-\d+-\d{2,4}/", 'to_tu_assoc');
            $fzs = assoc_array($content, "/\d+-ФЗ/", 'to_fz_assoc');
            $snips = assoc_array($content, "/СНиП \d+\.\d+\.\d+-\d{2,4}/", 'to_snip_assoc');

            $all = array_merge($gosts, $tus, $fzs, $snips);               

            $all_C = count($all);
            for ($i = 0; $i < $all_C; ++$i)
            {
                $list_C = count($list);
                if ($list_C == 0 && $all_C != 0)
                {
                    $all[$i]["Файлы"] = array($file["name"]);
                    array_push($list, $all[$i]);
                    continue;
                }                       

                for ($j = 0; $j < $list_C; ++$j)
                {
                    if ($list[$j]["Тип"] === $all[$i]["Тип"] &&
                    $list[$j]["Атрибуты"] === $all[$i]["Атрибуты"])
                    {
                        array_push($list[$j]["Файлы"], $file["name"]);
                        break;
                    }
                    else if ($j == $list_C - 1)
                    {
                        $all[$i]["Файлы"] = array($file["name"]);
                        array_push($list, $all[$i]);
                    }
                }
            }

            sort($list);
            $json = json_encode($list, JSON_UNESCAPED_UNICODE);
                      
        }

        return $json;
    }

    
?>