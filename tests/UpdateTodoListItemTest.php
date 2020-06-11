<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\Item;
use App\Entity\TodoList;
/**
 *
 */
class UpdateTodoListItemTest extends WebTestCase
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
     * @testdox test if a todolist is successfully created
     */
    public function testSuccessfullTodoListCreation()
    {
        //Mock object to get the entity manager from

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

        $response = $this->client->getResponse()->getContent();

        return json_decode($response, true);
    }


    /**
     * @depends testSuccessfullTodoListCreation
     * @testdox This test takes as parameter the resulting todoList from the testSuccessfullTodoListCreation 
     * @param array $todoList
     * This test case
     */
    public function testTodoListItemUpdateFromCreatedToPending($todoList)
    {
        $item = $todoList['items'][0];
       
        //
        $this->assertEquals(Item::CREATED_STATE, $item['state'], 'Test if item state is CREATED');

        $item['state'] = Item::PENDING_STATE;

        //Update the item from CREATED to PENDING

        $this->client->request('PUT',
            '/api/items',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode($item)
        );

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(),'Test if the todoList item is successfully updated');
        

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $todoList = $response['todoList'];
        $this->assertEquals(Item::PENDING_STATE, $response['state'], 'Test if the Item is now in PENDING state');
        $this->assertEquals(TodoList::PENDING_STATE, $todoList['state'], 'Test if the todoList is now in PENDING state');

        return $response;

    }

    /**
     * @depends testTodoListItemUpdateFromCreatedToPending
     * @testdox This test takes as parameter the resulting todoList from the testTodoListItemUpdateFromCreatedToPending 
     * @param array $item
     * This test case
     */
    public function testTodoListItemUpdateFromPendingToCompleted($item)
    {       
        $item = [
            'id' => $item['id'],
            'state' => Item::COMPLETED_STATE,
            'name' => $item['name'],
            'description' => $item['description']
        ];

        //Update the item from CREATED to PENDING

        $this->client->request('PUT',
            '/api/items',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            json_encode($item)
        );

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode(),'Test if the todoList item is successfully updated');
        

        $response = json_decode($this->client->getResponse()->getContent(), true);
        
        $this->assertEquals(Item::COMPLETED_STATE, $response['state'], 'Test if the Item is now in COMPLETED state');


        $todoList = $response['todoList'];
        $todoListNumberOfItems = count($todoList['items']);

        $this->assertEquals(round($todoList['completionRate'], 2), round(1/$todoListNumberOfItems, 2), 'Test if the todoList completion rate is equal to 1 divided by the number of items in the todoList');

        return $response;

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
