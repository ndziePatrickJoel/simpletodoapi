<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;


/**
 *
 */
class CreateTodoListTest extends WebTestCase
{
    public $testUsername = "admin";
    public $testPassword = "pass_1234";

    private $em;
    private $wordsList;
    private $projectDir;
    private $client;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->client = static::createClient([], ['HTTP_php-auth-pw' => $this->testPassword, 'HTTP_php-auth-user' => $this->testUsername]);
        $this->projectDir = $this->client->getContainer()->getParameter('kernel.project_dir');
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @testdox test a todoList is created
     */
    public function testSuccessfullTodoListCreation()
    {
        $todoList = $this->createTodoList();

        $this->client->request('PUT',
            '/api/todolist',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode($todoList)
        );

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(),'Test if the todoList is successfully created');

    }

    private function createTodoList()
    {
        $this->wordsList = $this->readWords();
        $totalNumberOfWords = count($this->wordsList);

        $todoList = [
            'name' => $this->generateRandomString(rand(3, 10), $totalNumberOfWords),
            'description' => $this->generateRandomString(rand(10, 20), $totalNumberOfWords)
        ];
        $items = [];

        $numberOfItems = rand(1, 4);

        $j = 0;

        while ($j < $numberOfItems) {
            $items[] = [
                'name' => $this->generateRandomString(rand(3, 10), $totalNumberOfWords),
                'description' => $this->generateRandomString(rand(10, 20), $totalNumberOfWords)
            ];
           
            $j++;
        }

        $todoList['items'] = $items;

        return $todoList;

    }

    private function readWords()
    {
        $fileLines = file($this->projectDir . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'words.txt');
        $words = [];
        foreach ($fileLines as $line) {
            $words[] = trim($line);
        }

        return $words;
    }

    /**
     * This function is used to generate random string for the name and the description
     * of both TodoList and todoList Items by reading the words.txt file
     */
    private function generateRandomString($numberOfWords, $totalNumberOfWords)
    {
        $list = [];

        for ($i = 0; $i < $numberOfWords; $i++) {
            $list[] = $this->wordsList[rand(0, $totalNumberOfWords - 1)];
        }
        return trim(implode(" ", $list));
    }
}
