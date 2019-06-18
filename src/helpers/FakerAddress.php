<?php namespace santilin\churros\helpers;

class FakerAddress extends \Faker\Provider\es_ES\Address
{
    protected static $state = [
        'La Coruña', 'Álava', 'Albacete', 'Alicante', 'Almería', 'Asturias', 'Ávila', 'Badajoz', 'Barcelona', 'Burgos', 'Cáceres', 'Cádiz', 'Cantabria', 'Castellón', 'Ceuta', 'Ciudad Real', 'Cuenca', 'Córdoba', 'Gerona', 'Granada', 'Guadalajara', 'Guipúzcoa', 'Huelva', 'Huesca', 'Islas Baleares', 'Jaén', 'La Rioja', 'Las Palmas', 'León', 'Lérida', 'Lugo', 'Málaga', 'Madrid', 'Melilla', 'Murcia', 'Navarra', 'Orense', 'Palencia', 'Pontevedra', 'Salamanca', 'Segovia', 'Sevilla', 'Soria', 'Santa Cruz de Tenerife', 'Tarragona', 'Teruel', 'Toledo', 'Valencia', 'Valladolid', 'Vizcaya', 'Zamora', 'Zaragoza'];

	public function es_nombrepropio()
	{
		return $this->generator->name();
	}

    public function es_codigo_postal()
    {
        return $this->generator->postcode();
    }

    public function es_direccion()
    {
        return $this->generator->streetAddress();
    }

    public function es_poblacion()
    {
        return $this->generator->city();
    }

	public function es_provincia()
    {
        return $this->generator->state();
    }

    public function es_cif_nif()
    {
        return $this->generator->dni();
    }

	public function iban()
    {
        return $this->generator->bankAccountNumber();
    }

    public function shortString($max_nchars)
    {
		$nchars = $this->generator->numberBetween(2, $max_nchars);
		return substr($this->generator->text(20), 0, $nchars);
    }

    public function string($nchars)
    {
		$nchars = $this->generator->numberBetween(10, $nchars);
		return $this->generator->text($nchars);
    }

    public function integer($max_digits = 16)
    {
		if( $max_digits == 1 ) {
			return $this->randomDigitNotNull();
		} else {
			return $this->decimal($max_digits);
		}
    }

    public function integer_unsigned($max_digits = 16)
    {
		if( $max_digits == 1 ) {
			return $this->randomDigitNotNull();
		} else {
			return $this->decimal_unsigned($max_digits);
		}
    }

	public function smallInteger()
    {
		return $this->decimal(4);
    }

	public function smallIntegerUnsigned()
    {
		return $this->decimal_unsigned(4);
    }

	public function decimal($max_digits = 16, $decimals = 0)
    {
		assert($decimals < $max_digits);
		$max_ditigs = $this->generator->numberBetween(2, $max_digits);
		$ret = $this->generator->randomElement(["-",""]);
		if ($ret=="-") {
			$max_digits -= 1;
		}
		for( $i=1; $i<$max_digits; ++$i) {
			if( $i == $max_digits - $decimals && $decimals > 0 ) {
				$ret .= ".";
			}
			$ret .= $this->generator->randomDigit();
		}
		return $ret;
    }

	public function decimal_unsigned($max_digits = 16, $decimals = 0)
    {
		assert($decimals < $max_digits);
		$max_ditigs = $this->generator->numberBetween(2, $max_digits);
		$ret = "";
		for( $i=1; $i<$max_digits; ++$i) {
			if( $i == $max_digits - $decimals && $decimals > 0 ) {
				$ret .= ".";
			}
			$ret .= $this->generator->randomDigit();
		}
		return $ret;
    }

	/**
	 * string of numbers with exactly $exact_digits chars
	 */
	public function integer_string($exact_digits = 16, $decimals = 0)
    {
		assert($decimals < $exact_digits);
		$ret = "";
		if ($exact_digits>0) {
			$ret .= $this->generator->randomDigitNotNull();
			for( $i=1; $i<$exact_digits-$decimals-2; ++$i) {
				$ret .= $this->generator->randomDigit();
			}
		}
		if( $decimals > 0 ) {
			$ret .= ".";
			for( $i=0; $i<$decimals; ++$i) {
				$ret .= $this->generator->randomDigit();
			}
		}
		return $ret;
    }

    public function bool()
    {
		return $this->generator->boolean();
	}

	public function str_random($max_digits)
	{
        return \str_random($max_digits);
    }

    public function null()
    {
		return null;
	}

	public function image_binary()
	{
		$image = imagecreatetruecolor(200, 50);
		$background_color = imagecolorallocate($image, 255, 255, 255);
		$text_color = imagecolorallocate($image, 0, 0, 0);
		imagefilledrectangle($image,0,0,200,50,$background_color);
		$letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
		$len = strlen($letters);
		$word = '';
		for ($i = 0; $i< 6;$i++) {
			$letter = $letters[rand(0, $len-1)];
			imagestring($image, 5,  5+($i*30), 20, $letter, $text_color);
			$word.=$letter;
		}
		// start buffering
		ob_start();
		imagepng($image);
		$contents =  ob_get_contents();
		ob_end_clean();
        imagedestroy($image);
        return $contents;
	}
}

