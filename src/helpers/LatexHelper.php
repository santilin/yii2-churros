<?php

namespace santilin\churros\helpers;

class LatexHelper
{
    static public function escape($string)
    {
		// el orden es importante, poner los \\ al inicio
		static $r1 = [ '\\', '{', '}', '\\{\\textbackslash\\}', '$', '_', '&', '#', '%', 'ﬁ', "●", "\u{FFFD}", "\u{CC88}", "\r\n" ];
        static $r2 = [ '{\\textbackslash}', '\\{', '\\}', '{\\textbackslash}', '\\$', '\\_', '\\&', '\\#', '\\%', 'fi', '-', '', '"', ' \\newline '];
        static $reg1 = [ '/(^|[^.])\.\.\.([^.])/', '/(^|\\s)"/', '/"(\\W|$)/', "/(^|\\s)'/" ];
        static $reg2 = [ '\\1{\\ldots}\\2', '\\1``', "''\\1", '\1`' ];

        $string = str_replace( $r1, $r2, $string);
        $string = preg_replace( $reg1, $reg2, $string );
        return $string;
	}

} // class
