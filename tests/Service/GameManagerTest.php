<?php

namespace App\Tests\Service;

use App\Entity\Game;
use App\Entity\Level;
use App\Service\GameManager;
use PHPUnit\Framework\TestCase;

class GameManagerTest extends TestCase
{
    public function testValidGame()
    {
        $game = new Game();
        $game->setName('Football');
        $game->setType('Sport');
        $game->setDescription('Jeu très amusant');
        $game->setIdLevel(new Level());

        $manager = new GameManager();

        $this->assertTrue($manager->validate($game));
    }

    public function testGameWithoutName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom du jeu est obligatoire');

        $game = new Game();
        $game->setType('Sport');
        $game->setDescription('Jeu amusant');
        $game->setIdLevel(new Level());

        $manager = new GameManager();
        $manager->validate($game);
    }

    public function testGameWithInvalidName()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom doit contenir uniquement des lettres');

        $game = new Game();
        $game->setName('Game123');
        $game->setType('Sport');
        $game->setDescription('Jeu amusant');
        $game->setIdLevel(new Level());

        $manager = new GameManager();
        $manager->validate($game);
    }

    public function testGameWithoutType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le type du jeu est obligatoire');

        $game = new Game();
        $game->setName('Football');
        $game->setDescription('Jeu amusant');
        $game->setIdLevel(new Level());

        $manager = new GameManager();
        $manager->validate($game);
    }

    public function testGameWithInvalidType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le type doit contenir uniquement des lettres');

        $game = new Game();
        $game->setName('Football');
        $game->setType('Sport123');
        $game->setDescription('Jeu amusant');
        $game->setIdLevel(new Level());

        $manager = new GameManager();
        $manager->validate($game);
    }

    public function testGameWithoutDescription()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description est obligatoire');

        $game = new Game();
        $game->setName('Football');
        $game->setType('Sport');
        $game->setIdLevel(new Level());

        $manager = new GameManager();
        $manager->validate($game);
    }

    public function testGameWithInvalidDescription()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La description doit contenir uniquement des lettres et des espaces');

        $game = new Game();
        $game->setName('Football');
        $game->setType('Sport');
        $game->setDescription('Jeu123');
        $game->setIdLevel(new Level());

        $manager = new GameManager();
        $manager->validate($game);
    }

    public function testGameWithoutLevel()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le niveau est obligatoire');

        $game = new Game();
        $game->setName('Football');
        $game->setType('Sport');
        $game->setDescription('Jeu amusant');

        $manager = new GameManager();
        $manager->validate($game);
    }
}