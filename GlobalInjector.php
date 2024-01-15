<?PHP
class GlobalInjector {
    private $data;

    public function __construct() {
     
    }

    public static function setGlobals(array $array) {
        foreach ($array as $key => $value) {
            $GLOBALS[$key] = $value;
        }
    }

    public static function setGlobalsTofunction(array $array) {
    	$text = ""; 
        foreach ($array as $key => $value) {
            $text .= 'global $'.$key.';';
        }
        return $text;
    }


}
?>