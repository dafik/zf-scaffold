<?php
class ZFscaffold_Inflector{

  private static $rulesPlural = array(
    '/$/' =>  's',
    '/s$/i '=>  's',
    '/(ax|test)is$/i '=> '\1es',
    '/(octop|vir)us$/i '=> '\1i',
    '/(alias|status)$/i '=> '\1es',
    '/(bu)s$/i '=> '\1ses',
    '/(buffal|tomat)o$/i '=> '\1oes',
    '/([ti])um$/i '=> '\1a',
    '/sis$/i '=> 'ses',
    '/(?:([^f])fe|([lr])f)$/i '=> '\1\2ves',
    '/(hive)$/i '=> '\1s',
    '/([^aeiouy]|qu)y$/i '=> '\1ies',
    '/(x|ch|ss|sh)$/i '=> '\1es',
    '/(matr|vert|ind)(?:ix|ex)$/i '=> '\1ices',
    '/([m|l])ouse$/i '=> '\1ice',
    '/^(ox)$/i '=> '\1en',
    '/(quiz)$/i '=> '\1zes'
    );
    private static $rulesSingular = array(
     '/s$/i' => '',
    '/(n)ews$/i' =>  '\1ews',
    '/([ti])a$/i' => '\1um',
    '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\1\2sis',
    '/(^analy)ses$/i' => '\1sis',
    '/([^f])ves$/i' => '\1fe',
    '/(hive)s$/i' => '\1',
    '/(tive)s$/i' => '\1',
    '/([lr])ves$/i' => '\1f',
    '/([^aeiouy]|qu)ies$/i' => '\1y',
    '/(s)eries$/i' => '\1eries',
    '/(m)ovies$/i' => '\1ovie',
    '/(x|ch|ss|sh)es$/i' => '\1',
    '/([m|l])ice$/i' => '\1ouse',
    '/(bus)es$/i' => '\1',
    '/(o)es$/i' => '\1',
    '/(shoe)s$/i' => '\1',
    '/(cris|ax|test)es$/i' => '\1is',
    '/(octop|vir)i$/i' => '\1us',
    '/(alias|status)es$/i' => '\1',
    '/^(ox)en/i' => '\1',
    '/(vert|ind)ices$/i' => '\1ex',
    '/(matr)ices$/i' => '\1ix',
    '/(quiz)zes$/i' => '\1',

    );

    private static $rulesIrregular = array (
     'person' =>  'people',
     'man' =>  'men',
     'child' =>  'children',
     'sex' =>  'sexes',
     'move' => 'moves',
     'cow' =>  'kine',
     'data_base' => 'data_bases',
     'database' => 'databases',
     'DataBase' => 'DataBases',
     'ResultType2dataBase' => 'ResultType2dataBases',
     'StatusType2dataBase' => 'StatusType2dataBases',
     'Userstatus' => 'Userstatus',
     'Process' => 'Process'
     
     );

     public static  function pluralize($name){
       if(substr($name, -3) == 'ies') {
         $name = substr($name, 0, -3) . 'y';
       } elseif(substr($name, -1) == 's') {
         $name = substr($name, 0, -1);
       }
       return $name;
     }
     public static function singularize($word) {
	   if(array_search($word,self::$rulesIrregular)){
		return array_search($word,self::$rulesIrregular);
	   }else{
       foreach (array_reverse(self::$rulesSingular) as $rule => $replacement) {
         $count=0;
         $result = preg_replace($rule,$replacement,$word,10,$count);
         if ($count) {
           return $result;
         }
       }
       return $word;
       }
     }
      
      
     public static function camelize ($str)
     {
       return str_replace(' ','',ucwords(str_replace('_',' ',$str)));
     }

     public static function decamelize($str)
     {
       return ltrim(preg_replace('/([A-Z])/e',"'_'.strtolower('$1')",$str),'_');
     }

     /**
      * Make a string's first character lowercase
      *
      * @param string $str
      * @return string the resulting string.
      */
     public static function lcfirst( $str ) {
       $str[0] = strtolower($str[0]);
       return (string)$str;
     }
}
?>