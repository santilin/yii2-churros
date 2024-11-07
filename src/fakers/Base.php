<?php namespace santilin\churros\fakers;

class Base extends \Faker\Provider\Base
{
    public function shortString($max_nchars)
    {
		$nchars = $this->generator->numberBetween(2, $max_nchars);
		return substr($this->generator->text(20), 0, $nchars);
    }

    public function char($nchars = 1)
	{
		return $this->string($nchars);
	}

    public function string($nchars)
    {
		$nchars = $this->generator->numberBetween($nchars/3, $nchars);
		if ($nchars<5) {
			return substr($this->generator->word, 0, $nchars);
		} else {
			return $this->generator->text($nchars);
		}
    }

    public function integer($max_digits = 10)
    {
		if( $max_digits == 1 ) {
			return $this->randomDigitNotNull();
		} else {
			return $this->decimal($max_digits);
		}
    }

    public function integerUnsigned($max_digits = 10): int
    {
		if( $max_digits == 1 ) {
			return $this->generator->randomDigit();
		} else {
			return $this->decimalUnsigned($max_digits);
		}
    }

    public function integerUnsignedOrNull($max_digits = 10): ?int
    {
		$n = $this->integerUnsigned($max_digits);
		if( $n == 0 ) {
			return null;
		} else {
			return $n;
		}
	}

	public function integerUnsignedNonZero($max_digits = 10): int
	{
		$n = $this->integerUnsigned($max_digits);
		if( $n == 0 ) {
			return 1;
		} else {
			return $n;
		}
	}

	public function smallInteger(): int
    {
		return $this->decimal(4);
    }

	public function smallIntegerUnsigned(): int
    {
		return $this->decimalUnsigned(4);
    }

	public function decimal($max_digits = 10, $decimals = 0)
    {
		assert($decimals < $max_digits);
		$ret = $this->generator->randomElement(["-",""]);
		if ($ret=="-") {
			$max_digits--;
			if( $max_digits == 1 ) {
				return $this->generator->randomDigitNotNull();
			}
		}
		$max_digits = $this->generator->numberBetween(2, $max_digits);
		for( $i=1; $i<$max_digits; ++$i) {
			if( $i == $max_digits - $decimals && $decimals > 0 ) {
				$ret .= ".";
			}
			$ret .= $this->generator->randomDigit();
		}
		return $ret;
    }

	public function decimalUnsigned($max_digits = 10, $decimals = 0)
    {
		assert($decimals < $max_digits);
		$max_digits = $this->generator->numberBetween(3, $max_digits);
		$ret = $this->randomDigitNotNull();
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
	public function integer_string($exact_digits = 16, $decimals = 0): string
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

    public function bool(): bool
    {
		return $this->generator->boolean();
	}

	public function str_random($max_digits): string
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

	public function hours(): string
	{
		return str_pad($this->generator->numberBetween(0,23), 2, '0', STR_PAD_LEFT)
		. ':' . str_pad($this->generator->numberBetween(0,59), 2, '0', STR_PAD_LEFT);
	}

	public function autoIncrement(): int
	{
		static $last_value = 0;
		return ++$last_value;
	}

	public function dateStart(): string
	{
		return $this->generator->dateTimeBetween("-2 years", "now")->format('Y-m-d');
	}

	public function dateEnd(): string
	{
		return $this->generator->dateTimeBetween("now", "+2 years")->format('Y-m-d');;
	}

	public function dateTimeStart(): string
	{
		return $this->generator->dateTimeBetween("-2 years", "now")->format('Y-m-d H:i:s');
	}

	public function dateTimeEnd(): string
	{
		return $this->generator->dateTimeBetween("now", "+2 years")->format('Y-m-d H:i:s');;
	}
}
