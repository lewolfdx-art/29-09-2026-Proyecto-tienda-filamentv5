<?php

namespace Tests\Unit;

use App\Support\Search;
use PHPUnit\Framework\TestCase;

class SearchPatternTest extends TestCase
{
    public function test_it_wraps_the_term_with_wildcards(): void
    {
        $this->assertSame('%algodon%', Search::pattern('algodon'));
    }

    public function test_it_escapes_the_percent_sign(): void
    {
        $this->assertSame('%100!%%', Search::pattern('100%'));
    }

    public function test_it_escapes_the_underscore(): void
    {
        $this->assertSame('%a!_b%', Search::pattern('a_b'));
    }

    public function test_it_escapes_the_escape_character_itself(): void
    {
        $this->assertSame('%!!%', Search::pattern('!'));
    }

    public function test_it_keeps_regular_characters_untouched(): void
    {
        $this->assertSame('%Camiseta Algodon%', Search::pattern('Camiseta Algodon'));
    }
}
