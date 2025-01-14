<?
function loadPackage($dir)
{
    $autoloadJson = file_get_contents("$dir/autoload.json");
    $composer = json_decode($autoloadJson, 1);
    $namespaces = $composer['autoload']['psr-4'];

    // Foreach namespace specified in the composer, load the given classes
    foreach ($namespaces as $namespace => $classpaths) {
        if (!is_array($classpaths)) {
            $classpaths = array($classpaths);
        }
        spl_autoload_register(function ($classname) use ($namespace, $classpaths) {
            // Check if the namespace matches the class we are looking for
            if (preg_match("#^".preg_quote($namespace)."#", $classname)) {
                // Remove the namespace from the file path since it's psr4
                $classname = str_replace($namespace, "", $classname);
                $filename = preg_replace("#\\\\#", "/", $classname).".php";
                foreach ($classpaths as $classpath) {
                    $fullpath = $_SERVER['DOCUMENT_ROOT']."/".$classpath."/$filename";
                    if (file_exists($fullpath)) {
                        include_once $fullpath;
                    }
                }
            }
        });
    }
}
loadPackage(TOOLS_FOLDER);
