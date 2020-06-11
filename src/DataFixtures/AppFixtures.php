<?php
namespace App\DataFixtures;

use App\Entity\TodoList;
use App\Entity\Item;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;



class AppFixtures extends Fixture
{
    //parameters bag
    private $params;
    private $wordsList;
    private $encoder;

    public function __construct(ParameterBagInterface $params, UserPasswordEncoderInterface $encoder)
    {
        $this->params = $params;
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager)
    {
        $this->wordsList = $this->readWords();
        $totalNumberOfWords = count($this->wordsList);

        $user = $this->createUser($manager);

        $i = 0;
        /**
         * Create random list of 
         * - 50 TodoList
         * - Each TodoList has a random number of Item between [1, 11[
         */
        while($i < 50) {
            $todoList = new TodoList();
            $todoList->setName($this->generateRandomString(rand(3, 10), $totalNumberOfWords));
            $todoList->setDescription($this->generateRandomString(rand(10, 20), $totalNumberOfWords));
            $todoList->setUser($user);
            $manager->persist($todoList);

            $numberOfItems = rand(1, 11);

            $j = 0;

            while($j < $numberOfItems)
            {
                $item = new Item();
                $item->setName($this->generateRandomString(rand(3, 10), $totalNumberOfWords));
                $item->setDescription($this->generateRandomString(rand(10, 20), $totalNumberOfWords));
                $item->setTodoList($todoList);
                $manager->persist($item);
                $j++;
            }
            $i++;
        }

        $manager->flush();
    }

    private function createUser(ObjectManager $manager)
    {
        $user = new User();
        $user->setUsername('admin');
        $user->setFirstname("Admin");
        $user->setLastname('Admin');
        $user->setRoles(['ROLE_USER', 'ROLE_ADMIN']);

        $password = $this->encoder->encodePassword($user, 'pass_1234');
        $user->setPassword($password);

        $manager->persist($user);
        $manager->flush();

        return $user;
    }


    private function readWords()
    {
        $fileLines = file($this->params->get('kernel.project_dir').DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR.'words.txt');
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
        $list  = [];

        for($i = 0; $i < $numberOfWords; $i++)
        {
            $list[] = $this->wordsList[rand(0, $totalNumberOfWords -1)];
        }
        return trim(implode(" ", $list));
    }
}

?>
