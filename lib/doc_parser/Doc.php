<?php
   function read_doc_file($filename) {
    if (file_exists($filename)) {
        if (($fh = fopen($filename, 'r')) !== false) {
            $headers = fread($fh, 0xA00);

            // 1 = (ord(n)*1) ; Document has from 0 to 255 characters
            $n1 = ( ord($headers[0x21C]) - 1 );

            // 1 = ((ord(n)-8)*256) ; Document has from 256 to 63743 characters
            $n2 = ( ( ord($headers[0x21D]) - 8 ) * 256 );

            // 1 = ((ord(n)*256)*256) ; Document has from 63744 to 16775423 characters
            $n3 = ( ( ord($headers[0x21E]) * 256 ) * 256 );

            // 1 = (((ord(n)*256)*256)*256) ; Document has from 16775424 to 4294965504 characters
            $n4 = ( ( ( ord($headers[0x21F]) * 256 ) * 256 ) * 256 );

            // Total length of text in the document
            $textLength = ($n1 + $n2 + $n3 + $n4);

            $extracted_plaintext = fread($fh, $textLength);
            $extracted_plaintext = mb_convert_encoding( $extracted_plaintext, 'UTF-8', 'UTF-16LE' );
            return nl2br($extracted_plaintext);

        } else {
            return FALSE;
        }
    } else {
        return FALSE;
    }
} 
?>