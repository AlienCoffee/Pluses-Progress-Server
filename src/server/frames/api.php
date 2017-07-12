<!DOCTYPE HTML>
<html lang='en'>

    <head>
        <meta charset='UTF-8' />
        <title>Pluses Progress API</title>
    </head>

    <body>
    
        <h1>Pluses Progress API conventions</h1>

        <?php
        
            function toString ($array) {
                $out = "";
                foreach ($array as $key => $value) {
                    if (strlen ($out) != 0) {
                        $out .= ", ";
                    }
                    $out .= $value;
                }

                return $out;
            }

            function show ($array, $prefix) {
                if (is_array ($array) && $prefix == "#file") {
                    // This is information about some file
                    if (array_key_exists ("type", $array) && $array ['type'] == "page") {
                        echo ("[PAGE] File: <b>".$array ['src']."</b>".br);
                        echo ("       Rights: <b>".$array ['rights']."</b> - "
                                ."Enabled: <b>".($array ['enabled'] ? "true" : "false")
                                ."</b>".br.br);
                    }

                    return;
                }

                if (is_array ($array) && array_key_exists ("function", $array)) {
                    // This is information about some method
                    $arguments = toString ($array ['arguments']);
                    echo ("[METHOD] <b>".$prefix." (<i>".$arguments."</i>)"."</b>".br);
                    echo ("         Rights: <b>".$array ['src']
                            ."</b>".br.br);
                    return;
                }

                if (strlen ($prefix) != 0) {
                    $prefix .= ".";
                }

                foreach ($array as $key => $value) {
                    if (is_array ($value)) {
                        if ($key == "#file") {
                            show ($value, $key);
                        } else {
                            show ($value, $prefix.$key);
                        }
                    }
                }
            }

            show ($_sources, "");
        
        ?>
    
    </body>

</html>