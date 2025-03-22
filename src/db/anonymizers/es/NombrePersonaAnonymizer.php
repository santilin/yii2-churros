<?php

namespace santilin\churros\db\anonymizers\es;

/*
 * Example usage
 * $Anonymizer = new NameAnonymizer('your-secret-key-here');
 *
 * $originalName1 = 'Juan Carlos García López';
 * $originalName2 = 'María de los Ángeles Fernández';
 *
 * echo "Original: $originalName1\n";
 * echo "anonymized: " . $Anonymizer->anonymize($originalName1) . "\n";
 *
 * echo "Original: $originalName2\n";
 * echo "anonymized: " . $Anonymizer->anonymize($originalName2) . "\n";
 */

class NombrePersonaAnonymizer
{
    private $secretKey;
    private $vowels = ['a', 'e', 'i', 'o', 'u'];
    private $consonants = ['b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'm', 'n', 'p', 'q', 'r', 's', 't', 'v', 'w', 'x', 'y', 'z'];

    public function __construct(string $secretKey) {
        $this->secretKey = $secretKey;
    }

    public function anonymize(string $name): string {
        $words = explode(' ', $name);
        $anonymizedWords = array_map([$this, 'anonymizeWord'], $words);
        return implode(' ', $anonymizedWords);
    }

    private function anonymizeWord(string $word): string {
        $letters = mb_str_split($word);
        $anonymizedLetters = array_map([$this, 'anonymizeLetter'], $letters);

        // Ensure at least one vowel deterministically
        if (!$this->containsVowel($anonymizedLetters)) {
            $position = $this->getDeterministicPosition($word);
            $anonymizedLetters[$position] = $this->getDeterministicVowel($word, $position);
        }

        return implode('', $anonymizedLetters);
    }

    private function anonymizeLetter(string $letter): string {
        if (!ctype_alpha($letter)) {
            return $letter;
        }

        $hash = crc32($this->secretKey . $letter);
        $isVowel = in_array(strtolower($letter), $this->vowels);
        $alphabet = $isVowel ? $this->vowels : $this->consonants;

        if (ctype_upper($letter)) {
            return strtoupper($alphabet[$hash % count($alphabet)]);
        }

        return $alphabet[$hash % count($alphabet)];
    }

    private function containsVowel(array $letters): bool {
        foreach ($letters as $letter) {
            if (in_array(strtolower($letter), $this->vowels)) {
                return true;
            }
        }
        return false;
    }

    private function getDeterministicPosition(string $word): int {
        return crc32($this->secretKey . $word) % mb_strlen($word);
    }

    private function getDeterministicVowel(string $word, int $position): string {
        $hash = crc32($this->secretKey . $word . $position);
        $vowel = $this->vowels[$hash % count($this->vowels)];
        return ctype_upper($word[$position]) ? strtoupper($vowel) : $vowel;
    }
}
