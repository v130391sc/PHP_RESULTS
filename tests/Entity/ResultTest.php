<?php

/**
 * PHP version 7.3
 *
 * @category TestEntities
 * @package  App\Tests\Entity
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://www.etsisi.upm.es/ E.T.S. de Ingeniería de Sistemas Informáticos
 */

namespace App\Tests\Entity;

use App\Entity\Result;
use App\Entity\User;
use Exception;
use Faker\Factory as FakerFactoryAlias;
use Faker\Generator as FakerGeneratorAlias;
use PHPUnit\Framework\TestCase;

/**
 * Class ResultadoTest
 *
 * @package App\Tests\Entity
 *
 * @coversDefaultClass \App\Entity\Result
 */
class ResultTest extends TestCase
{
    /**
     * @var Result
     */
    protected static $resultado;

    /** @var FakerGeneratorAlias $faker */
    private static $faker;

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     */
    public static function setUpBeforeClass()
    {
        self::$resultado = new Result(0, new User('', ''));
        self::$faker = FakerFactoryAlias::create('es_ES');
    }

    /**
     * Implement testConstructor().
     *
     * @covers ::__construct()
     * @return void
     */
    public function testConstructor(): void
    {
        $result = self::$faker->randomDigit;
        self::$resultado = new Result();
        self::$resultado->setResult($result);
        self::assertEquals(0, self::$resultado->getId());
        self::assertEquals($result, self::$resultado->getResult());
    }

    /**
     * Implement testGetId().
     *
     * @covers ::getId
     * @return void
     */
    public function testGetId(): void
    {
        self::assertEmpty(self::$resultado->getId());
    }

    /**
     * Implements testGetSetResult().
     *
     * @covers ::getResult()
     * @covers ::setResult()
     * @throws Exception
     * @return void
     */
    public function testGetSetResult(): void
    {
        $result = self::$faker->randomDigit;
        self::$resultado->setResult($result);
        static::assertEquals(
            $result,
            self::$resultado->getResult()
        );
    }

    /**
     * Implements testGetSetUser().
     *
     * @covers ::getUser()
     * @covers ::setUser()
     * @return void
     * @throws Exception
     */
    public function testGetSetUser(): void
    {
        $email = self::$faker->email;
        $user = new User($email);
        self::$resultado->setUser($user);
        static::assertEquals(
            $email,
            self::$resultado->getUser()->getEmail()
        );
    }

    /**
     * Implements testGetSetTime().
     *
     * @covers ::getTime()
     * @covers ::setTime()
     * @throws Exception
     * @return void
     */
    public function testGetSetTime(): void
    {
        $time = self::$faker->dateTime;
        self::$resultado->setTime($time);
        static::assertEquals(
            $time->format('Y-m-d H:i:s'),
            self::$resultado->getTime()
        );
    }
}