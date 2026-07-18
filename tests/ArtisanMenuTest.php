<?php

namespace JordJD\ArtisanMenu\Tests;

use JordJD\ArtisanMenu\Console\Commands\ArtisanMenu;
use PHPUnit\Framework\TestCase;

class ArtisanMenuTest extends TestCase
{
    public function testGlobalOptionsAreExcludedFromCommandPrompts()
    {
        $command = json_decode(json_encode([
            'definition' => [
                'options' => [
                    ['name' => 'help'],
                    ['name' => 'no-interaction'],
                    ['name' => 'force'],
                    ['name' => 'connection'],
                ],
            ],
        ]));

        $menu = new TestableArtisanMenu();
        $options = $menu->commandSpecificOptions($command);

        $this->assertSame(['force', 'connection'], array_map(function ($option) {
            return $option->name;
        }, $options));
    }

    public function testRepeatableValuesAreParsedFromCommaSeparatedInput()
    {
        $definition = (object) ['is_multiple' => true];
        $menu = new TestableArtisanMenu();

        $this->assertSame(
            ['alpha', '0', 'beta'],
            $menu->normalise($definition, 'alpha, 0, beta')
        );
    }

    public function testAffirmativeInputIsExplicit()
    {
        $menu = new TestableArtisanMenu();

        $this->assertTrue($menu->affirmative('YES'));
        $this->assertFalse($menu->affirmative('no'));
        $this->assertFalse($menu->affirmative('anything'));
    }
}

class TestableArtisanMenu extends ArtisanMenu
{
    public function commandSpecificOptions(\stdClass $command)
    {
        return $this->getCommandSpecificOptions($command);
    }

    public function normalise(\stdClass $definition, $value)
    {
        return $this->normaliseDefinitionValue($definition, $value);
    }

    public function affirmative($value)
    {
        return $this->isAffirmative($value);
    }
}
